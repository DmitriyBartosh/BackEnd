<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;

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

    public function logout()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json([
            "message" => 'Токен был удален'
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
}
