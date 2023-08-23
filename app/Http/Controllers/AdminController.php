<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\DesignExpert;
use App\Models\Expert;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function users()
    {
        return UserCollection::collection(
            User::query()->orderBy('id', 'asc')->paginate(30)
        );
    }

    public function getExpert($id)
    {
        $user = User::find($id);

        $expert = $user->settings;

        return response()->json([
            'user' => $expert
        ]);
    }

    public function addExpert(Request $request)
    {
        try {
            $expert = $request->expert;
            $price = $request->price;

            $id = $expert["id"];
            $user = User::find($id);

            $direction = $expert["direction"];
            $name = $expert["name"];
            $about = $expert["about"];
            $slug = $expert["slug"];

            $avatarFile = $expert["avatar"];
            $avatarName = $slug . "_avatar" .  "." . $avatarFile->getClientOriginalExtension();
            $aratarSlug = "/images/" . $avatarName;

            if ($direction === "design") {
                $user->assignRole(['name' => 'Design Expert']);

                $designExpert = new Expert();
                $designExpert->user_id = $id;
                $designExpert->avatar = $aratarSlug;
                $designExpert->name = $name;
                $designExpert->about = $about;
                $designExpert->slug = $slug;
                $designExpert->direction = $direction;
                $designExpert->price = $price;

                // Сжать и загрузить аватар в /public/images
                $avatar = Image::make($avatarFile);
                $optimizeAvatar = $avatar->fit(1500, 1500)->encode('webp');
                Storage::disk('public_images')->put($avatarName, $optimizeAvatar);

                // Сохранить изменения в таблицу
                $designExpert->save();


                return response()->json(['message' => 'Эксперт по направлению графический дизайнер добавлен!'], 200);
            };
            return response()->json(['message' => "Направление еще не готово"], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Мы не смогли добавить Ваш сервис',  'Значение: ' . $request->active . $th], 500);
        }
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
