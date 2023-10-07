<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Support\Str;
use App\Models\Promocode;
use App\Models\Subscribe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromocodeController extends Controller
{
    private function generateUniqueCode()
    {
        $promoCode = Str::random(8); // Генерация случайной строки длиной 8 символов
        $promoCode = strtoupper($promoCode); // Преобразование строки в заглавные буквы

        return $promoCode;
    }

    public function generatePromoCodes(Request $request)
    {
        $name = $request->promo['name'];
        $direction = $request->promo['direction'];
        $periodicity = (int) $request->promo['periodicity'];
        $expired_at = $request->promo['expired_at'];
        $count = (int) $request->promo['count'];


        for ($i = 0; $i < $count; $i++) {
            $promoCode = new Promocode();
            $promoCode->name = $name;
            $promoCode->direction = $direction;
            $promoCode->periodicity = $periodicity;
            $promoCode->expired_at = $expired_at;
            $promoCode->code = $this->generateUniqueCode(); // Генерация уникального кода промокода

            $promoCode->save();
        }

        return response()->json(['message' => 'Промокоды добавлены']);
    }

    public function getAllPromoCodes()
    {

        $promocodes = Promocode::with('user')->get();

        return response()->json([
            'promocodes' => $promocodes
        ]);
    }

    public function activatePromo(Request $request)
    {
        $code = $request->code;

        $promocode = Promocode::where('code', $code)->first();

        // Проверяем есть ли введеный промокод в базе
        if ($promocode) {
            $user = Auth::user();

            // Проверка есть ли активированный ранее промокод с таким же именем
            $activePromoCodeWithName = Promocode::where('name', $promocode->name)
                ->where('user_id', $user->id)
                ->first();

            if ($activePromoCodeWithName) {
                // Промокод уже активирован
                return response()->json([
                    'activate' => false,
                    'message' => "Вы уже использовали похожий промокод.",
                ]);
            } else {
                // Проверяем не активирован ли промокод другим пользователем
                if ($promocode->status === "active") {
                    $promocode->user_id = $user->id;
                    $promocode->status = 'used';


                    // Ищем id направления в таблице Plan
                    $directionPlan = Plan::where('name', $promocode->direction)->first();

                    // Проверяем, есть ли активная подписка
                    $subscription = Subscribe::where('active', true)
                        ->where('plan_id', $directionPlan->id)
                        ->first();

                    if ($subscription) {
                        // Если есть активная подписка, добавляем количество дней к ее сроку, которое указано в промокоде
                        $subscription->expired_at = $subscription->expired_at->addDays($promocode->periodicity);
                        $subscription->save();

                        $promocode->save();

                        // Промокод активирован
                        return response()->json([
                            'activate' => true,
                            'message' => "К активной подписке " . $directionPlan->title . " добавлено " . $promocode->periodicity . " дней.",
                        ]);
                    } else {
                        // Получаем текущую дату
                        $now = Carbon::now();

                        // Если подписки нет, создаем новую подписку
                        $newSubscription = new Subscribe();
                        $newSubscription->user_id = $user->id;
                        $newSubscription->plan_id = $directionPlan->id;
                        $newSubscription->active = true;
                        $newSubscription->started_at = $now;
                        $newSubscription->expired_at = $now->addDays($promocode->periodicity);
                        $newSubscription->save();

                        $promocode->save();

                        // Промокод активирован
                        return response()->json([
                            'activate' => true,
                            'message' => "Подписка на " . $promocode->periodicity . " дней для направления " . $directionPlan->title . ".",
                        ]);
                    }
                } {
                    // Промокод уже активирован
                    return response()->json([
                        'activate' => false,
                        'message' => "Промокод уже активирован другим пользователем.",
                    ]);
                }
            }
        } else {
            // Запись не существует
            return response()->json(['activate' => false, 'message' => 'Промокод не найден.']);
        }
    }
}
