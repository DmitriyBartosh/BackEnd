<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\Expert;
use App\Models\User;
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

        $expert = $user->expert;

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
            $avatarPath = "/images/" . $avatarName;

            // Назначаем нужную роль
            switch ($direction) {
                case 'design':
                    $user->assignRole(['name' => 'Design Expert']);
                    break;

                case 'frontend':
                    $user->assignRole(['name' => 'Frontend Expert']);
                    break;

                case 'photo':
                    $user->assignRole(['name' => 'Photo Expert']);
                    break;
            }

            $designExpert = new Expert();
            $designExpert->user_id = $id;
            $designExpert->avatar = $avatarPath;
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

            return response()->json(['message' => "Эксперт добавлен"], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Мы не смогли добавить Ваш сервис',  'Значение: ' . $request->active . $th], 500);
        }
    }

    public function editExpert(Request $request)
    {
        try {
            $expert = $request->expert;
            $price = $request->price;

            $id = $expert["id"];
            $user = User::find($id);

            $editableExpert = Expert::where('user_id', $id)->first();

            $direction = $expert["direction"];
            $name = $expert["name"];
            $about = $expert["about"];
            $slug = $expert["slug"];

            $avatar = $expert["avatar"];

            if ($avatar !== $editableExpert->avatar) {
                $avatarName = $slug . "_avatar" .  "." . $avatar->getClientOriginalExtension();
                $avatarPath = "/images/" . $avatarName;

                // Сжать и загрузить аватар в /public/images
                $avatarImage = Image::make($avatar);
                $optimizeAvatar = $avatarImage->fit(1500, 1500)->encode('webp');
                Storage::disk('public_images')->put($avatarName, $optimizeAvatar);

                $editableExpert->avatar = $avatarPath;
            }

            if ($direction !== $editableExpert->direction) {
                // Удаляем старую роль
                switch ($editableExpert->direction) {
                    case 'design':
                        $user->removeRole('Design Expert');
                        break;

                    case 'frontend':
                        $user->removeRole('Frontend Expert');
                        break;

                    case 'photo':
                        $user->removeRole('Photo Expert');
                        break;
                }

                // Назначаем нужную роль
                switch ($direction) {
                    case 'design':
                        $user->assignRole(['name' => 'Design Expert']);
                        break;

                    case 'frontend':
                        $user->assignRole(['name' => 'Frontend Expert']);
                        break;

                    case 'photo':
                        $user->assignRole(['name' => 'Photo Expert']);
                        break;
                }

                $editableExpert->direction = $direction;
            }

            $editableExpert->name = $name;
            $editableExpert->about = $about;
            $editableExpert->slug = $slug;
            $editableExpert->price = $price;

            $editableExpert->save();


            return response()->json(['message' => 'Эксперт успешно обновлен!'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Мы не смогли добавить Ваш сервис',  'Значение: ' . $request->active . $th], 500);
        }
    }

    public function deleteDesign(Request $request)
    {
        $userId = $request->id;

        $user = User::find($userId)->first();

        $designExpert = Expert::where('user_id', $user->id)->first();
        $direction = $designExpert->direction;

        switch ($direction) {
            case 'design':
                $user->removeRole('Design Expert');
                break;

            case 'frontend':
                $user->removeRole('Frontend Expert');
                break;

            case 'photo':
                $user->removeRole('Photo Expert');
                break;
        }

        if ($designExpert) {
            $designExpert->delete();
        }

        return response()->json(['message' => 'Эксперт успешно удален!'], 200);
    }
}
