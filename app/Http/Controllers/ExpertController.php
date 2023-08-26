<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpertRequest;
use App\Http\Requests\UpdateExpertRequest;
use App\Models\Expert;
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
}
