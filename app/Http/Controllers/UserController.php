<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

    /**
     * Show the bot information.
     */
    public function telegramGetMe()
    {

        $updates = $this->telegram->getUpdates();

        $chat_bot = $this->telegram->getMe();

        return response()->json([
            'chat_bot' => $chat_bot,
            'updates' => $updates
        ], 200);
    }
}
