<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserDesignCollection;
use App\Models\Design;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DesignExpertController extends Controller
{
    public function profileInfo()
    {
        $admin = Auth::user();
        $adminName = $admin->name;

        $settings = $admin->settings;

        return response()->json([
            'name' => $adminName,
            'settings' => $settings
        ]);
    }

    public function editProfileInfo(Request $request)
    {
        $admin = Auth::user();

        $admin->name = $request->name;

        $admin->save();

        $settings = $admin->settings;

        $settings->status = $request->status;
        $settings->backtowork = $request->backtowork;

        $settings->logo = $request->logo;
        $settings->polygraphy = $request->polygraphy;
        $settings->socialmedia = $request->socialmedia;
        $settings->poster = $request->poster;

        $settings->save();


        return response()->json([
            'success' => true
        ]);
    }

    public function works()
    {
        $works = Design::select('user_id', 'polygraphy', 'socialmedia', 'poster', 'logo')
            ->with('user:id,name')
            ->get();


        return response()->json([
            'links' => $works
        ]);
    }
}
