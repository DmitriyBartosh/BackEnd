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

        $subscriptionExists = Subscribe::where('plan_id', $plan->id)
            ->where('active', true)
            ->exists();

        $subscribe = new Subscribe();
        $subscribe->user_id = $user->id;
        $subscribe->plan_id = $plan->id;

        // Если уже есть активная подписка, то продляем ее
        if ($subscriptionExists) {
            $subscribe_expired_at = Subscribe::where('plan_id', $plan->id)
                ->where('active', true)
                ->orderBy('expired_at', 'desc')
                ->first()
                ->expired_at;

            $now = Carbon::parse($subscribe_expired_at)->addDay(1);
            $end = Carbon::parse($subscribe_expired_at)->addDays($plan->periodicity);

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

            $subscribe->transaction_id = $payment->id;
            $subscribe->transaction_status = 'pending';
            $subscribe->started_at = $now;
            $subscribe->expired_at = $end;

            $subscribe->save();


            return response()->json([
                'url' => $payment->confirmation->confirmation_url,
            ]);
        } else {
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


            $subscribe->transaction_id = $payment->id;
            $subscribe->transaction_status = 'pending';
            $subscribe->started_at = $now;
            $subscribe->expired_at = $end;

            $subscribe->save();

            return response()->json([
                'url' => $payment->confirmation->confirmation_url,
            ]);
        }
    }

    public function checkPayment($subscribe)
    {
        // Если платеж уже получил статус Успешно, то не проверять его повторно
        if ($subscribe->transaction_status === 'succeeded') {
            return;
        };

        if ($subscribe->transaction_id) {
            $client = $this->getClient();
            $payment = $client->getPaymentInfo($subscribe->transaction_id);

            if ($payment->paid) {
                $subscribe->transaction_status = 'succeeded';
                $subscribe->active = true;
                $subscribe->save();

                return;
            }

            if ($payment->status === 'canceled') {
                $subscribe->transaction_status = 'canceled';
                $subscribe->save();

                return;
            }

            if ($payment->status === 'pending') {
                $subscribe->transaction_status = 'pending';
                $subscribe->save();

                return $payment->confirmation->confirmation_url;
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

        // Проверяем, есть ли данные в $data
        if ($data->isEmpty()) {
            return response()->json([
                'subscribes' => []
            ]);
        }

        // Получаем текущую дату
        $now = Carbon::now();

        foreach ($data as $item) {
            // Проверяем статус платежа
            $checkPayment = $this->checkPayment($item);

            // Получаем дату окончания абонемента из базы данных
            $endDate = Carbon::parse($item['expired_at']);

            // Проверяем активен ли абонемент
            if ($now <= $endDate && $item['active']) {
                // Определяем количество дней, оставшихся до конца подписки
                $daysLeft = $now->diffInDays($item['expired_at']) + 1;
                $isActive = true;
            } else {
                $daysLeft = 0;
                $isActive = false;

                $item->active = false;
                $item->save();
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

            // если функция checkPayment вернула url, то добавить поле redirect_url для добавление ссылки в кнопку завершения платежа
            if (isset($checkPayment)) {
                $subscribes[count($subscribes) - 1]['redirect_url'] = $checkPayment;
            }
        }

        // Все не активные подписки
        $allNotActiveSubscribes = array_filter($subscribes, function ($subscribe) {
            return !$subscribe['active'];
        });

        // Все активные подписки (они могут быть несколько активных по одному направлению, каждая друг друга продолжает)
        $allActiveSubscribes = array_filter($subscribes, function ($subscribe) {
            return $subscribe['active'];
        });

        // Только уникальная подписка, которая идет последней
        $UniqueActiveSubscribes = collect($allActiveSubscribes)->groupBy('plan')->map(function ($group) {
            return $group->max('days_left');
        })->map(function ($maxDaysLeft, $plan) use ($allActiveSubscribes) {
            return collect($allActiveSubscribes)->where('plan', $plan)->where('days_left', $maxDaysLeft)->first();
        })->values()->toArray();

        $result = array_merge($allNotActiveSubscribes, $UniqueActiveSubscribes);

        return response()->json([
            'subscribes' => $result,
        ]);
    }
}
