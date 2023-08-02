<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\DesignExpert;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        return UserCollection::collection(
            User::query()->orderBy('id', 'asc')->paginate(30)
        );
    }

    public function addDesign(Request $request)
    {
        $userId = $request->id;

        $user = User::find($userId)->first();
        $user->assignRole(['name' => 'Design Expert']);

        $designExpert = new DesignExpert();
        $designExpert->user_id = $user->id;
        $designExpert->save();


        return response()->json([
            'success' => true
        ]);
    }

    public function removeDesign(Request $request)
    {
        $userId = $request->id;

        $user = User::find($userId)->first();

        $user->removeRole('Design Expert');

        $designExpert = DesignExpert::where('user_id', $user->id)->first();
        if ($designExpert) {
            $designExpert->delete();
        }


        return response()->json([
            'success' => true
        ]);
    }
}
