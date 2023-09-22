<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscribeRequest;
use App\Http\Requests\UpdateSubscribeRequest;
use App\Models\Plan;
use App\Models\Subscribe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use YooKassa\Client;

class SubscribeController extends Controller
{
    private function getClient(): Client
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.client_id'), config('services.yookassa.client_key'));

        return $client;
    }

    public function addSubscribe(Request $request)
    {
        // Информация о пользователе
        $user = Auth::user();

        // Выбранное направление
        $plan = Plan::where('name', $request->direction)->first();

        // Получаем текущую дату
        $now = Carbon::now();
        $end = Carbon::now()->addDays($plan->periodicity);

        // Создаем платеж
        $client = $this->getClient();
        $payment = $client->createPayment(
            array(
                'amount' => array(
                    'value' => (int) $request->cost,
                    'currency' => 'RUB',
                ),
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => config('app.frontend_url') . "/portfolio",
                ),
                'metadata' => array(
                    'user' => $user->email,
                    'direciton' => $request->name,
                    'start' => $now,
                    'end' => $end
                ),
                'payment_method_data' => $request->method,
                'capture' => true,
                'description' => 'Доступ к направлению «' . $request->name . "» на 30 дней.",
            ),
            uniqid('', true)
        );

        $subscribe = new Subscribe();
        $subscribe->user_id = $user->id;
        $subscribe->plan_id = $plan->id;
        $subscribe->transaction_id = $payment->id;


        $subscribe->transaction_status = 'pending';
        $subscribe->started_at = $now;
        $subscribe->expired_at = $end;

        $subscribe->save();

        return response()->json([
            'url' => $payment->confirmation->confirmation_url
        ]);
    }

    public function checkPayment($id)
    {
        $subscribe = Subscribe::find($id);

        if ($subscribe->transaction_id) {
            $client = $this->getClient();
            $payment = $client->getPaymentInfo($subscribe->transaction_id);

            if ($payment->paid) {
                $subscribe->transaction_status = 'succeeded';
                $subscribe->save();

                return response()->json([
                    'message' => 'Подписка оплачена'
                ]);
            }

            if ($payment->status === 'canceled') {
                $subscribe->transaction_status = 'canceled';
                $subscribe->save();

                return response()->json([
                    'message' => 'Платеж не действителен'
                ]);
            }

            if ($payment->status === 'pending') {
                $subscribe->transaction_status = 'pending';
                $subscribe->save();

                return response()->json([
                    'message' => 'Платеж уже создан',
                    'url' => $payment->confirmation->confirmation_url
                ]);
            }
        }

        return response()->json([
            'message' => 'Платежей не создавалось'
        ]);
    }



    public function allSubscribes()
    {
        // Информация о пользователе
        $user = Auth::user();
        $data = $user->allsubscribes;

        // Получаем текущую дату
        $now = Carbon::now();

        foreach ($data as $item) {
            // Получаем дату окончания абонемента из базы данных
            $endDate = Carbon::parse($item['expired_at']);

            // Проверяем активен ли абонемент
            if ($now <= $endDate && $item['transaction_status'] === 'succeeded') {
                // Определяем количество дней, оставшихся до конца подписки
                $daysLeft = $now->diffInDays($item['expired_at']);
                $isActive = true;
            } else {
                $daysLeft = 0;
                $isActive = false;
            }

            $subscribes[] = [
                'plan' => $item['plan']['name'],
                'start_subscribe' => $item['started_at'],
                'end_subscribe' => $item['expired_at'],
                'active' => $isActive,
                'days_left' => $daysLeft,
                'transaction_id' => $item['transaction_id'],
                'transaction_status' => $item['transaction_status'],
            ];
        }

        return response()->json([
            'subscribes' => $subscribes
        ]);
    }
}
