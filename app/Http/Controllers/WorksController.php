<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorksRequest;
use App\Http\Requests\UpdateWorksRequest;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorksController extends Controller
{
    public function getWorks()
    {
        $user = Auth::user();
        $works = $user->allworks;

        return response()->json([
            'works' => $works
        ]);
    }

    public function addWorks(Request $request)
    {
        $user = Auth::user();

        $work = new Works();

        $work->user_id = $user->id;
        $work->direction = $request->direction;
        $work->theme = $request->theme;
        $work->name = $request->name;
        $work->link = $request->link;

        $work->save();

        return response()->json(['message' => 'Работа успешно добавлена!'], 200);
    }

    public function editWorks(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $workId = (int) $request->id;
        $work = Works::where('user_id', $userId)->findOrFail($workId);

        $newLink = $request->link;

        $work->link = $newLink;

        $work->save();

        return response()->json(['message' => 'Работа успешно изменена!'], 200);
    }
}
