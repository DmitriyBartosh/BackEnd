<?php

namespace App\Http\Controllers;

use App\Models\EntryreviewAnswers;
use App\Models\EntryreviewQuestions;
use Illuminate\Http\Request;
use YooKassa\Client;
use Telegram\Bot\Api;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EntryreviewController extends Controller
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

    public function sendTelegramNotification($telegram_chat, $message)
    {
        // Если телеграм привязан, отправляем уведомление
        if (isset($telegram_chat)) {

            // Отправляем уведомление пользователю об ошибке
            $this->telegram->sendMessage([
                'chat_id' => $telegram_chat,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        }
    }

    private function getClient(): Client
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.client_id'), config('services.yookassa.client_key'));

        return $client;
    }

    // Получаем список вопросы для админки и для пользователя
    public function getAllQuestions($direction)
    {
        // Используем Eloquent ORM для поиска записи в таблице entrytest_questions
        $question = EntryreviewQuestions::where('slug', $direction)->first();

        // Если запись найдена, возвращаем её
        if ($question) {
            return $question;
        } else {
            return null;
        }

        return response()->json(['message' => "Вопросы добавлены"]);
    }

    // Добавляем вопросы из админки
    public function addQuestions(Request $request)
    {
        $questions = EntryreviewQuestions::where('slug', $request->slug)->first();

        if ($questions) {
            // Обновление данных
            $questions->name = $request->name;
            $questions->questions = $request->questions;
            $questions->price = (int) $request->price;

            $questions->save();

            return response()->json(['message' => "Вопросы обновлены"]);
        } else {
            // Создание новой записи
            $newQuestions = new EntryreviewQuestions();
            $newQuestions->slug = $request->slug;
            $newQuestions->name = $request->name;
            $newQuestions->questions = $request->questions;
            $newQuestions->price = (int) $request->price;

            $newQuestions->save();

            return response()->json(['message' => "Вопросы добавлены"]);
        }
    }

    // Отправить на проверку вопросы
    public function addEntryReview(Request $request)
    {
        // Информация о пользователе
        $user = Auth::user();

        $newAnswers = new EntryreviewAnswers();
        $newAnswers->user_id = $user->id;
        $newAnswers->slug = $request->slug;
        $newAnswers->answers = $request->answers;
        $newAnswers->questions = $request->questions;
        $newAnswers->status = 'onpayment';

        // Создаем платеж
        $client = $this->getClient();
        $payment = $client->createPayment(
            array(
                'amount' => array(
                    'value' => (int) $request->price,
                    'currency' => 'RUB',
                ),
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => config('app.frontend_url') . "/portfolio",
                ),
                'metadata' => array(
                    'user' => $user->email
                ),
                'payment_method_data' => $request->method,
                'capture' => false,
                'description' => $request->service,
            ),
            uniqid('', true)
        );

        $newAnswers->transaction_id = $payment->id;
        $newAnswers->save();



        return response()->json([
            'url' => $payment->confirmation->confirmation_url
        ]);
    }

    // Все входные тестирования для пользователя
    public function getEntryReview()
    {
        $user = Auth::user();
        $userId = $user->id;

        $groupNotification = env('TELEGRAM_GROUP_NOTIFICATION');


        $answer = EntryreviewAnswers::where('user_id', $userId)->first();

        // Если у пользователя нет записи о входном ревью
        if (!$answer) {
            return response()->json([
                'status' => 'notfound',
                'answer' => null
            ]);
        }

        if ($answer->status === 'complete'){
            return response()->json([
                'status' => 'complete',
                'answer' => $answer
            ]);
        }

        if ($answer->status === 'paid'){
            return response()->json([
                'status' => 'paid',
                'answer' => $answer
            ]);
        }

        // если статус на оплату, то проверяем прошел ли платеж или он был отменен
        if ($answer->status === 'onpayment' && $answer->transaction_id) {

            $client = $this->getClient();
            $payment = $client->getPaymentInfo($answer->transaction_id);

            // Если платеж оплачен, отправляем уведомление в админку
            if ($payment->paid) {
                // Если сообщение об оплате еще не приходило, отправить
                if ($answer->status !== 'paid') {
                    $telegramUser = $this->telegram->getChat(['chat_id' => $user->telegram_chat]);
                    $user_contact = "<a href='" . "https://t.me/" . $telegramUser->username . "'>" . "@" . $telegramUser->username . "</a>";

                    $notification = "<b>" . $user->name . " / " . $user_contact . "</b> оплатил DesignReview 360."
                        . PHP_EOL . "Проверить в течении трех дней, до <b>" . date('d.m.Y', strtotime('+3 days', strtotime($answer->updated_at))) . ".</b>"
                        . PHP_EOL . "Открыть <b><a href='" . env('FRONTEND_URL') . "/admin'>Графикси | Админ панель</a></b>.";

                    $this->sendTelegramNotification($groupNotification, $notification);
                }


                $answer->status = 'paid';
                $answer->save();

                return response()->json([
                    'status' => 'paid',
                    'answer' => $answer
                ]);
            }

            // Если платеж активен, выдаем старую ссылку на оплату
            if ($payment->status === 'pending') {
                return response()->json([
                    'status' => 'pending',
                    'answer' => $answer,
                    'url' => $payment->confirmation->confirmation_url
                ]);
            }

            // Если платеж отменен, удаляем из таблицы запись
            if ($payment->status === 'canceled') {
                // Удаляем запись
                $answer->delete();

                return response()->json([
                    'status' => 'notfound',
                    'answer' => null
                ]);
            }


        }
    }

    public function addFeetback(Request $request)
    {
        // Найти запись по ID
        $entryReview = EntryreviewAnswers::find($request->id);

        // Если запись найдена, обновить поля
        if ($entryReview) {
            $entryReview->annotation = $request->annotation;
            $entryReview->roadmap = $request->roadmap;

            if($entryReview->status === 'paid') {
                // Уведомления для пользователя
                $telegram_chat = User::find($entryReview->user_id)->telegram_chat;

                $notification = "Мы изучили ответы и подготовили для тебя карту знаний."
                . PHP_EOL . "Смотри на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Мое портфолио | Графикси</a>.</b>";

                // Отправляем уведомление
                $this->sendTelegramNotification($telegram_chat, $notification);

                $entryReview->status = 'complete';
            }

            // Сохранить изменения
            $entryReview->save();

            return response()->json(['message' => 'Запись сохранена']);
        }

        return response()->json(['message' => 'Запись не найдена'], 404);
    }


    public function getAllAnswers($direction)
    {
        $entryreviewCollection = EntryreviewAnswers::with('user')->where('slug', $direction)->get();

        $entryreview = [];

        if ($entryreviewCollection->isNotEmpty()) {
            foreach ($entryreviewCollection as $answer) {
                $telegramUser = $this->telegram->getChat(['chat_id' => $answer->user->telegram_chat]);

                $entryreview[] = [
                    'id' => $answer->id,
                    'user_name' => $answer->user->name,
                    'user_email' => $answer->user->email,
                    'telegram' => $telegramUser,
                    'questions' => $answer->questions,
                    'answers' => $answer->answers,
                    'status' => $answer->status,
                    'annotation' => $answer->annotation,
                    'roadmap' => $answer->roadmap,
                    'created_at' => $answer->created_at,
                ];
            }
        }

        return $entryreview;
    }

}
