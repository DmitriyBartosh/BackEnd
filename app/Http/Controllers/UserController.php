<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;

class UserController extends Controller
{
    protected $telegram;

    /**
     * Create a new controller instance.
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }

    public function getUser()
    {
        // Информация о пользователе
        $user = Auth::user();
        $telegramId = $user->telegram_chat;

        if ($telegramId === null) {
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'telegram' => null
            ], 200);
        } else {
            $telegramUser = $this->telegram->getChat(['chat_id' => $telegramId]);


            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'telegram' => [
                    'first_name' => $telegramUser->first_name,
                    'last_name' => $telegramUser->last_name,
                    'username' => $telegramUser->username,
                    'bio' => $telegramUser->bio
                ]
            ], 200);
        }
    }

    public function addTelegramId(Request $request)
    {
        // Информация о пользователе
        $user = Auth::user();

        $user->telegram_chat = $request->id;
        $telegramUser = $this->telegram->getChat(['chat_id' => $request->id]);

        $user->save();

        return response()->json([
            'user' => [
                'first_name' => $telegramUser->first_name,
                'last_name' => $telegramUser->last_name,
                'username' => $telegramUser->username,
                'bio' => $telegramUser->bio
            ]
        ], 200);
    }
}
