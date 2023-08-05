<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignController extends Controller
{
    public function addLink(Request $request)
    {
        $user = Auth::user();
        $design = $user->progress_design;

        $theme = $request->theme;

        $link = [
            'id' => $request->id,
            'name' => $request->name,
            'link' => $request->link
        ];


        $element = $design->$theme;
        $element[] = $link;
        $design->$theme = $element;


        $design->save();

        return response()->json([
            'logo' => $design->logo,
            'polygraphy' => $design->polygraphy,
            'socialmedia' => $design->socialmedia,
            'poster' => $design->poster
        ]);
    }

    public function editLink(Request $request)
    {
        $user = Auth::user();
        $design = $user->progress_design;

        $theme = $request->theme;
        $index = $request->index;

        $links_array = $design->$theme;

        $new_link = [
            'id' => $request->id,
            'name' => $request->name,
            'link' => $request->link
        ];

        if (isset($links_array)) {
            $replace_link = array($index => $new_link);
            $new_design = array_replace($links_array, $replace_link);

            $design->$theme = $new_design;

            $design->save();
        }

        return response()->json([
            'logo' => $design->logo,
            'polygraphy' => $design->polygraphy,
            'socialmedia' => $design->socialmedia,
            'poster' => $design->poster
        ]);
    }

    public function deleteLink(Request $request)
    {
        $user = Auth::user();
        $design = $user->progress_design;

        $theme = $request->theme;
        $index = $request->index;

        // Находим нужное поле в зависимости от того, что было отправлено в request theme, logo, poster, polygraphy и тд
        $links_array = $design->$theme;

        // Проверяем существует ли массив
        if (isset($links_array)) {
            array_splice($links_array, $index, 1);

            $design->$theme = $links_array;

            $design->save();
        }

        return response()->json([
            'index' => $index,
            'theme' => $theme,
            'logo' => $design->logo,
            'polygraphy' => $design->polygraphy,
            'socialmedia' => $design->socialmedia,
            'poster' => $design->poster
        ]);
    }

    public function getLinks()
    {
        $user = Auth::user();
        $design = $user->progress_design;

        return response()->json([
            'logo' => $design->logo,
            'polygraphy' => $design->polygraphy,
            'socialmedia' => $design->socialmedia,
            'poster' => $design->poster
        ]);
    }
}
