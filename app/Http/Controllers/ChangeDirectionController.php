<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChangeDirectionController extends Controller
{
    public function ChangeDirection(Request $request)
    {
        $directionJson = $request->object;

        $user = Auth::user();
        $user->direction = $directionJson;

        $user->save();

        return response()->json([
            'success' => $directionJson
        ]);
    }

    public function getDirections()
    {
        $user = Auth::user();

        return response()->json([
            'direction' => $user->direction
        ]);
    }
}
