<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Expert;
use App\Models\Reviews;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use YooKassa\Client;

class ExpertController extends Controller
{
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
        $reviewWork->status = 'revision';

        // Записываем дату до которой можно сдать работу
        $reviewWork->time_for_revision = $deadline;

        $reviewWork->save();

        return response()->json([
            'message' => 'Сроки доработки работы были продлены на 5 дней'
        ], 200);
    }
}
