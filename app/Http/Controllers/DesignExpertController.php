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

        $settings = $admin->settings;

        $settings->update(['logo' => $request->test]);


        return response()->json([
            'success' => true,
            'settings' => $settings
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
