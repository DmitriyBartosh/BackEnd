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
            $existingWorks = Reviews::where('user_id', $user_id)
                ->where('theme', $workData['theme'])
                ->where('name', $workData['name'])
                ->first();

            if ($existingWorks) {
                // Если запись с id/тема/название тз уже есть, то перезаписываем ссылку
                $existingWorks->expert_id = $expert_id;
                $existingWorks->link = $workData['link'];

                $existingWorks->save();
            } else {
                // Если запись новая
                $work = new Reviews();

                $work->user_id = $user_id;
                $work->expert_id = $expert_id;
                $work->theme = $workData['theme'];
                $work->name = $workData['name'];
                $work->link = $workData['link'];

                $work->save();
            }
        }

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function allWorksForUser()
    {
        $user = Auth::user();

        $works = $user->work_under_review()
            ->with('expert')
            ->get();


        return response()->json([
            'works' => $works
        ]);
    }

    public function allExperts(Request $request)
    {
        $direction = $request->direction;

        $role = Expert::where('direction', $direction)->get();

        return response()->json([
            'experts' => $role,
        ]);
    }
}
