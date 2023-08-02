<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRolesController extends Controller
{
    public function checkAdmin()
    {
        if (Auth::check() && Auth::user()->hasRole('Super Admin')) {
            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false]);
    }

    public function checkExpert()
    {
        $user = Auth::user();

        $isDesign = $user->hasRole('Design Expert');
        $isFrontend = $user->hasRole('Frontend Expert');

        return response()->json([
            'design' => $isDesign,
            'frontend' => $isFrontend
        ]);
    }
}
