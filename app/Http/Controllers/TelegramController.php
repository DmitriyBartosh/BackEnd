<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Api;

class TelegramController extends Controller
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

    public function sendMessage()
    {
        $user = Auth::user();

        $response = $this->telegram->sendMessage([
            'chat_id' => $user->telegram_chat,
            'text' => 'Еще одно'
        ]);

        $messageId = $response->getMessageId();

        return response()->json([
            'name' => $response
        ], 200);
    }
}
