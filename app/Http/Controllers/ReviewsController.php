<?php

namespace App\Http\Controllers;

use App\Models\Expert;
use App\Models\Reviews;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Works;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;
use YooKassa\Client;

class ReviewsController extends Controller
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

    public function addWorks(Request $request)
    {
        $user = Auth::user();

        $user_id = $user->id;
        $user_name = $user->name;
        $expert_id = $request->expert_id;

        // Проверяем, если есть привязанные телеграм, то в контакты для обратной связи записываем телеграм, если нет, то почту
        if (isset($user->telegram_chat)) {
            $telegramUser = $this->telegram->getChat(['chat_id' => $user->telegram_chat]);
            $user_contact = "<a href='" . "https://t.me/" . $telegramUser->username . "'>" . "@" . $telegramUser->username . "</a>";
        } else {
            $user_contact = $user->email;
        }

        // Добавить уведомление для эксперта о новой работе
        $works = $request->works;
        $message = "<b>" . $user_name . " / " . $user_contact . "</b> отправил работы на рецензию."
            . PHP_EOL . "<b>Список работ:</b>";

        foreach ($works as $workData) {
            $work_id = (int) $workData['id'];

            $review = new Reviews();

            $review->user_id = $user_id;
            $review->expert_id = (int) $expert_id;
            $review->work_id = $work_id;

            $work_name = Works::find($work_id)->name;
            $message .= PHP_EOL . $work_name;

            $review->save();
        }

        $message .= PHP_EOL . "Открыть <b><a href='" . env('FRONTEND_URL') . "/admin'>Графикси | Админ панель</a></b>.";

        $expert_telegram = User::find($expert_id)->telegram_chat;

        $this->sendTelegramNotification($expert_telegram, $message);

        return response()->json(['message' => 'Работы добавлены для рецензирования!'], 200);
    }

    public function allWorksOnReview()
    {
        $user = Auth::user();
        $userId = $user->id;

        $works = Reviews::where('user_id', $userId)
            ->with('expert', 'work')
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

        $user = User::find($review->user_id);
        $work_name = Works::find($review->work_id)->name;

        $notification = "<b>" . $user->name . "</b> дополнил/исправил работу - <b>" . $work_name . "</b>."
            . PHP_EOL . "Открыть <b><a href='" . env('FRONTEND_URL') . "/admin'>Графикси | Админ панель</a></b>.";

        $this->sendTelegramNotification($user->telegram_chat, $notification);

        $review->status = 'checking';
        $review->save();

        return response()->json(['message' => 'Работа исправлена!']);
    }

    // Отправить работу после исправления ошибок
    public function revisionWork(Request $request)
    {
        $idReview = $request->id;

        $review = Reviews::find($idReview);

        $user = User::find($review->user_id);
        $work_name = Works::find($review->work_id)->name;

        $notification = "<b>" . $user->name . "</b> внес правки в работу - <b>" . $work_name . "</b>, после первой рецензии."
            . PHP_EOL . "Открыть <b><a href='" . env('FRONTEND_URL') . "/admin'>Графикси | Админ панель</a></b>.";

        $this->sendTelegramNotification($user->telegram_chat, $notification);

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
                // Если сообщение об оплате еще не приходило, отправить
                if ($review->status !== 'firstchecked') {
                    $user = User::find($review->user_id);
                    $work_name = Works::find($review->work_id)->name;

                    $notification = "<b>" . $user->name . "</b> оплатил рецензию для работы - <b>" . $work_name . "</b>."
                        . PHP_EOL . "Проверить работу в течении трех дней, до <b>" . date('d.m.Y', strtotime('+3 days', strtotime($review->updated_at))) . ".</b>"
                        . PHP_EOL . "Открыть <b><a href='" . env('FRONTEND_URL') . "/admin'>Графикси | Админ панель</a></b>.";

                    $this->sendTelegramNotification($user->telegram_chat, $notification);
                }


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
