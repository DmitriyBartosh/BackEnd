<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use YooKassa\Client;

class UserController extends Controller
{
    protected $telegram;

    /**
     * Create a new controller instance.
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    private function getClient(): Client
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.client_id'), config('services.yookassa.client_key'));

        return $client;
    }

    public function logout()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json([
            "message" => 'Токен был удален'
        ]);
    }

    public function setName(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->name = $request->name;

        $user->save();

        return response()->json([
            "message" => 'Имя было обновлено',
            "name" => $request->name
        ]);
    }

    public function getUser()
    {
        // Информация о пользователе
        $user = Auth::user();
        $telegramId = $user->telegram_chat;

        if ($telegramId === null) {
            // Если ID телеграма еще не вводилось
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'telegram' => null
            ], 200);
        } else {
            try {
                $telegramUser = $this->telegram->getChat(['chat_id' => $telegramId]);
                // Получаем чат пользователя с телеграм ботом
            } catch (TelegramResponseException $e) {

                // Если ID телеграма введен не верно и чат не найден, выводим ошибку для фронтенда
                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'telegram' => [
                        'error' => true
                    ]
                ], 200);
            }

            // Если никаких ошибок с поиском чата телеграма не выявлено, то мы можем его найти и вывести
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'telegram' => [
                    'error' => false,
                    'first_name' => $telegramUser->first_name,
                    'last_name' => $telegramUser->last_name,
                    'username' => $telegramUser->username,
                ]
            ], 200);
        }
    }

    public function addTelegramId(Request $request)
    {
        // Информация о пользователе
        $user = Auth::user();
        $user->telegram_chat = $request->id;

        $user->save();

        try {
            $telegramUser = $this->telegram->getChat(['chat_id' => $request->id]);
            // Получаем чат пользователя с телеграм ботом
        } catch (TelegramResponseException $e) {

            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'telegram' => [
                    'error' => true
                ]
            ], 200);
        }



        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'telegram' => [
                'error' => false,
                'first_name' => $telegramUser->first_name,
                'last_name' => $telegramUser->last_name,
                'username' => $telegramUser->username,
            ]
        ], 200);
    }

    public function allTransactions()
    {
        $user = Auth::user();

        $transactions = [];

        $subscribesTransaction = $user->allsubscribes->pluck('transaction_id')->toArray();
        $reviewsTransaction = $user->work_on_review->pluck('transaction_id')->toArray();

        // Объединить transaction_id из обоих таблиц
        $transactions = array_merge($subscribesTransaction, $reviewsTransaction);

        $client = $this->getClient();
        $payments = [];

        foreach ($transactions as $transaction) {
            if ($transaction) {
                $payment = $client->getPaymentInfo($transaction);
                $payments[] = $payment;
            }
        }

        usort($payments, function ($a, $b) {
            return $b->created_at <=> $a->created_at;
        });

        return response()->json([
            'transactions' => $payments,
        ]);
    }
}
