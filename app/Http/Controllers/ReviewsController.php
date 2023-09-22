<?php

namespace App\Http\Controllers;

use App\Models\Expert;
use App\Models\Reviews;
use App\Models\Transaction;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use YooKassa\Client;

class ReviewsController extends Controller
{
    private function getClient(): Client
    {
        $client = new Client();
        $client->setAuth(config('services.yookassa.client_id'), config('services.yookassa.client_key'));

        return $client;
    }

    public function addWorks(Request $request)
    {
        $user = Auth::user();

        $user_id = $user->id;
        $expert_id = $request->expert_id;

        $works = $request->works;

        foreach ($works as $workData) {
            $workId = (int) $workData['id'];

            $review = new Reviews();

            $review->user_id = $user_id;
            $review->expert_id = (int) $expert_id;
            $review->work_id = $workId;

            $review->save();
        }

        return response()->json(['message' => 'Работы добавлены для рецензирования!'], 200);
    }

    public function allWorksOnReview()
    {
        $user = Auth::user();
        $userId = $user->id;

        $works = Reviews::where('user_id', $userId)
            ->with('expert', 'work')
            ->get();


        return response()->json([
            'works' => $works
        ]);
    }

    public function deleteReview($id)
    {
        $user = Auth::user();
        $userId = $user->id;

        $review = Reviews::find($id);

        if ($userId === $review->user_id) {
            $review->delete();

            return response()->json(['message' => "Заявка на рецензию удалена"]);
        }

        return response()->json([
            'message' => 'Такая работа не найдена'
        ]);
    }

    public function allExperts($direction)
    {
        $experts = Expert::where('direction', $direction)->get();

        return response()->json([
            'experts' => $experts
        ]);
    }

    public function fixWork(Request $request)
    {
        $link = $request->link;
        $idReview = $request->id;

        $review = Reviews::find($idReview);
        $workId = $review->work_id;

        $work = Works::find($workId);

        if ($link != $work->link) {
            $work->link = $link;

            $work->save();
        }

        $review->status = 'checking';
        $review->save();

        return response()->json(['message' => 'Работа исправлена!']);
    }

    // Отправить работу после исправления ошибок
    public function revisionWork(Request $request)
    {
        $idReview = $request->id;

        $review = Reviews::find($idReview);

        $review->user_comment = $request->comment;
        $review->status = 'secondchecked';
        $review->save();

        return response()->json(['message' => 'Работа дополнена!']);
    }

    public function getPayment(Request $request)
    {
        // Информация о пользователе
        $user = Auth::user();
        // Информация о работе по id из списка выбранных работ
        $work = Works::find($request->work);
        // Эксперт
        $expert = Expert::find($request->expert);
        // Ревью
        $review = Reviews::find($request->review);

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
                    'expert' => $expert->name,
                    'user' => $user->email,
                    'work' => $work->name,
                    'link' => $work->link
                ),
                'payment_method_data' => $request->method,
                'capture' => false,
                'description' => 'Рецензия на работу «' . $work->name . "»" . " от эксперта " . $expert->name,
            ),
            uniqid('', true)
        );

        $review->transaction_id = $payment->id;
        $review->save();

        return response()->json([
            'url' => $payment->confirmation->confirmation_url
        ]);
    }

    public function checkPayment($id)
    {
        $review = Reviews::find($id);

        if ($review->transaction_id) {
            $client = $this->getClient();
            $payment = $client->getPaymentInfo($review->transaction_id);

            if ($payment->paid) {
                $review->status = 'firstchecked';
                $review->save();

                return response()->json([
                    'message' => 'Рецензия оплачена'
                ]);
            }

            if ($payment->status === 'canceled') {
                $review->transaction_id = null;
                $review->save();

                return response()->json([
                    'message' => 'Платеж не действителен'
                ]);
            }

            if ($payment->status === 'pending') {
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
}
