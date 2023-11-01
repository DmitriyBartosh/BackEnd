<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Expert;
use App\Models\Reviews;
use App\Models\User;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;
use YooKassa\Client;

class ExpertController extends Controller
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

    public function sendTelegramNotification($telegram_chat, $notification)
    {
        // Если телеграм привязан, отправляем уведомление
        if (isset($telegram_chat)) {

            // Отправляем уведомление пользователю об ошибке
            $this->telegram->sendMessage([
                'chat_id' => $telegram_chat,
                'text' => $notification,
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

    public function getExpert()
    {
        $user = Auth::user();
        $expert = $user->expert;

        return response()->json([
            'expert' => $expert
        ]);
    }

    public function editExpert(Request $request)
    {
        $user = Auth::user();

        $admin = $user->expert;

        $admin->status = $request->status;

        if ($request->backtowork) {
            $admin->backtowork = $request->backtowork;
        } else {
            $admin->backtowork = '1 января';
        }

        $admin->save();

        return response()->json(['message' => "Статус эксперта изменен!"], 200);
    }

    public function getAllReviews()
    {
        $user = Auth::user();
        $userId = $user->id;

        $expertId = Expert::where('user_id', $userId)->value('id');

        $works = Reviews::where('expert_id', $expertId)
            ->with('user', 'work')
            ->get();

        // Проверяем каждую работу
        foreach ($works as $work) {
            // Если рецензия отправлена на доработку, проверим не истек ли дедлайн на доработку
            if ($work->status === 'revision') {
                $timeForRevision = Carbon::parse($work->time_for_revision);

                // Если дата в поле time_for_revision прошла, устанавливаем статус "test"
                if ($timeForRevision->isPast()) {
                    $work->status = "overdue";
                    $work->save();
                }
            }
        }


        return response()->json([
            'works' => $works
        ]);
    }

    public function getAllWorks($direction)
    {
        $works = Works::with('user')->where('direction', $direction)->get();

        return response()->json([
            'works' => $works
        ]);
    }

    public function workVerified(Request $request)
    {
        $reviewId = $request->id;

        $reviewWork = Reviews::find($reviewId);

        // Сохраняем ссылку
        $work = Works::find($reviewWork->work_id);
        $reviewWork->link = $work->link;

        $reviewWork->status = 'verified';


        // Находим телеграм чат пользователя и если он есть отправляем уведомление
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "Работа <b>" . $work->name . "</b> готова к рецензии."
            . PHP_EOL . "После оплаты на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a></b> эксперт начнет писать рецензию.";

        $this->sendTelegramNotification($telegram_chat, $notification);

        $reviewWork->save();

        return response()->json(['message' => "Работы успешно прошла проверку!"], 200);
    }

    public function workFail(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        $reviewWork->status = 'fail';
        $reviewWork->message_failure = $message;

        // Находим телеграм чат пользователя и если он есть отправляем уведомление
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "В работу <b>" . $work->name . "</b> нужно внести правки."
            . PHP_EOL . "Подробный текст правки на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a>.</b>";

        $this->sendTelegramNotification($telegram_chat, $notification);

        $reviewWork->save();

        return response()->json(['message' => "Не рабочая ссылка или работу нужно дополнить!"], 200);
    }

    // Отправить на доработку
    public function workRevision(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        // Получаем текущую дату и время
        $currentDate = Carbon::now();

        // Добавляем пять дней к текущей дате
        $deadline = $currentDate->addDays(5);

        // Подтверждаем платеж
        $client = $this->getClient();
        $payment = $client->getPaymentInfo($reviewWork->transaction_id);

        // Уведомления для пользователя
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "Рецензия на работу <b>" . $work->name . "</b> готова."
            . PHP_EOL . "Работу можно дополнить в течении 5 дней, до <b>" . date('d.m.Y', strtotime($deadline)) . ".</b>"
            . PHP_EOL . "Подробный текст рецензии на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a>.</b>";

        // Отправляем уведомление
        $this->sendTelegramNotification($telegram_chat, $notification);


        if ($payment->status === "waiting_for_capture") {
            // Получаем дату создания платежа
            $createdAt = Carbon::parse($payment->created_at);

            // Проверяем, был ли платеж создан не позже трех дней назад
            if ($createdAt->diffInDays($currentDate) <= 3) {
                // Списываем деньги
                $idempotenceKey = uniqid('', true);
                $client->capturePayment(
                    array(
                        'amount' => $payment->amount,
                    ),
                    $payment->id,
                    $idempotenceKey
                );

                // Записываем дату до которой можно сдать работу
                $reviewWork->time_for_revision = $deadline;
                $reviewWork->status = 'revision';
                $reviewWork->message_revision = $message;

                $reviewWork->save();

                return response()->json([
                    'message' => 'Платеж подтвержден и работа отправлена на доработку'
                ], 200);
            } else {
                // Возвращаем платеж
                $idempotenceKey = uniqid('', true);

                $client->cancelPayment(
                    $payment->id,
                    $idempotenceKey
                );

                // Записываем дату до которой можно сдать работу
                $reviewWork->time_for_revision = $deadline;
                $reviewWork->status = 'revision';
                $reviewWork->message_revision = $message;

                $reviewWork->save();

                return response()->json([
                    'message' => 'Платеж был возвращен и работа отправлена на доработку'
                ], 200);
            }
        }

        // Записываем дату до которой можно сдать работу
        $reviewWork->time_for_revision = $deadline;
        $reviewWork->status = 'revision';
        $reviewWork->message_revision = $message;

        $reviewWork->save();

        return response()->json(['message' => "Работы отправлена на доработку!"], 200);
    }

    // Работа зачтена
    public function workReview(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        // Уведомления для пользователя
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "Прекрасня работа, ждем публикацию <b>" . $work->name . "</b> на Графикси!"
            . PHP_EOL . "Подробный текст рецензии и инструкция для публикации на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a>.</b>";

        // Отправляем уведомление
        $this->sendTelegramNotification($telegram_chat, $notification);

        // Подтверждаем платеж
        $client = $this->getClient();
        $payment = $client->getPaymentInfo($reviewWork->transaction_id);

        if ($payment->status === "waiting_for_capture") {

            // Получаем текущую дату и время
            $currentDate = Carbon::now();

            // Получаем дату создания платежа
            $createdAt = Carbon::parse($payment->created_at);

            // Проверяем, был ли платеж создан не позже трех дней назад
            if ($createdAt->diffInDays($currentDate) <= 3) {
                // Списываем деньги
                $idempotenceKey = uniqid('', true);
                $client->capturePayment(
                    array(
                        'amount' => $payment->amount,
                    ),
                    $payment->id,
                    $idempotenceKey
                );

                $reviewWork->status = 'complete';
                $reviewWork->message_review = $message;

                $reviewWork->save();

                return response()->json([
                    'message' => 'Платеж подтвержден'
                ], 200);
            } else {
                // Возвращаем платеж
                $idempotenceKey = uniqid('', true);

                $client->cancelPayment(
                    $payment->id,
                    $idempotenceKey
                );

                $reviewWork->status = 'complete';
                $reviewWork->message_review = $message;

                $reviewWork->save();

                return response()->json([
                    'message' => 'Платеж был возвращен'
                ], 200);
            }
        }

        $reviewWork->status = 'complete';
        $reviewWork->message_review = $message;

        $reviewWork->save();

        return response()->json(['message' => "!"], 200);
    }

    // Работа не зачтена после второй итерации
    public function workNotCounted(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        // Уведомления для пользователя
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "Рецензия на работу <b>" . $work->name . "</b> готова."
            . PHP_EOL . "Подробный текст рецензии на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a>.</b>";

        // Отправляем уведомление
        $this->sendTelegramNotification($telegram_chat, $notification);


        $reviewWork->status = 'notcounted';
        $reviewWork->message_notcounted = $message;

        $reviewWork->save();

        return response()->json(['message' => "Работа прошла проверку, но не зачтена."], 200);
    }

    // Продлить дедлайн на 5 дней
    public function extendDeadline(Request $request)
    {
        $reviewId = $request->id;

        // Получаем текущую дату и время
        $currentDate = Carbon::now();

        // Добавляем пять дней к текущей дате
        $deadline = $currentDate->addDays(5);

        $reviewWork = Reviews::find($reviewId);

        // Уведомления для пользователя
        $work = Works::find($reviewWork->work_id);
        $telegram_chat = User::find($work->user_id)->telegram_chat;

        $notification = "Работу <b>" . $work->name . "</b> можно доработать до <b>"  . date('d.m.Y', strtotime($deadline)) . ".</b>"
            . PHP_EOL . "Вернуться к работе на " . "<b><a href='" . env('FRONTEND_URL') . "/portfolio'>Графикси | Портфолио</a>.</b>";

        // Отправляем уведомление
        $this->sendTelegramNotification($telegram_chat, $notification);


        $reviewWork->status = 'revision';

        // Записываем дату до которой можно сдать работу
        $reviewWork->time_for_revision = $deadline;

        $reviewWork->save();

        return response()->json([
            'message' => 'Сроки доработки работы были продлены на 5 дней'
        ], 200);
    }
}
