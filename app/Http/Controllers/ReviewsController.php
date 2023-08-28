<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewsRequest;
use App\Http\Requests\UpdateReviewsRequest;
use App\Models\Expert;
use Spatie\Permission\Models\Role;
use App\Models\Reviews;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewsController extends Controller
{
    public function addWorks(Request $request)
    {
        $user = Auth::user();

        $user_id = $user->id;
        $expert_id = $request->expert_id;

        $works = $request->works;

        foreach ($works as $workData) {
            $work = new Reviews();

            $work->user_id = $user_id;
            $work->expert_id = $expert_id;
            $work->work_id = $workData['id'];

            $work->save();
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

    public function allExperts($direction)
    {
        $experts = Expert::where('direction', $direction)->get();

        return response()->json([
            'experts' => $experts
        ]);
    }
}
