<?php

namespace App\Http\Controllers;

use App\Models\Expert;
use App\Models\Reviews;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpertController extends Controller
{
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

        return response()->json(['message' => "Работы успешно прошла проверку!"], 200);
    }
    // Новое
    public function workReview(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);
        $work = Works::find($reviewWork->work_id);

        $reviewWork->status = 'complete';
        $reviewWork->link = $work->link;
        $reviewWork->message_review = $message;

        $reviewWork->save();

        return response()->json(['message' => "Ревью успешно добавлено!"], 200);
    }

    public function workRevision(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        $reviewWork->status = 'revision';
        $reviewWork->message_revision = $message;

        $reviewWork->save();

        return response()->json(['message' => "Работы отправлена на доработку!"], 200);
    }

    public function workNotCounted(Request $request)
    {
        $reviewId = $request->id;
        $message = $request->message;

        $reviewWork = Reviews::find($reviewId);

        $reviewWork->status = 'complete';
        $reviewWork->message_failure = $message;

        $reviewWork->save();

        return response()->json(['message' => "Работы успешно прошла проверку!"], 200);
    }
}
