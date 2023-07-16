<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChangeDirectionController extends Controller
{
    public function ChangeDesign(Request $request)
    {
        $user = Auth::user();

        $userId = auth()->id();

        // Добавляем запись если она еще не существует с прогрессом по курсу дизайн
        Design::firstOrCreate([
            'user_id' => $userId
        ], [
            'logo' => [],
            'poster' => [],
            'socialmedia' => [],
            'polygraphy' => []
        ]);

        $user->design = $request->input('design');

        $user->save();

        return response()->json([
            'design' => $user->design,
            'frontend' => $user->frontend,
            'photo' => $user->photo
        ]);
    }

    public function ChangeFrontend(Request $request)
    {
        $user = Auth::user();
        $user->frontend = $request->input('frontend');

        $user->save();

        return response()->json([
            'design' => $user->design,
            'frontend' => $user->frontend,
            'photo' => $user->photo
        ]);
    }

    public function ChangePhoto(Request $request)
    {
        $user = Auth::user();
        $user->photo = $request->input('photo');

        $user->save();

        return response()->json([
            'design' => $user->design,
            'frontend' => $user->frontend,
            'photo' => $user->photo
        ]);
    }

    public function getDirections()
    {
        $user = Auth::user();

        return response()->json([
            'design' => $user->design,
            'frontend' => $user->frontend,
            'photo' => $user->photo
        ]);
    }
}
