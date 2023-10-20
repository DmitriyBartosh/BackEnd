<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\View\Components\Alert;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;

class VkAuthController extends Controller
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


    public function redirectToAuth(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('vkontakte')
                ->stateless()
                ->scopes([])
                ->with([
                    'display' => 'popup',
                    'access_type' => 'offline',
                ])
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    public function handleAuthCallback(): JsonResponse
    {
        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver('vkontakte')->stateless()->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Что-то пошло не так.'], 422);
        }

        /** @var User $user */
        $user = User::query()->firstOrCreate(
            [
                'email' => $socialiteUser->getEmail(),
            ],
            [
                'email_verified_at' => now(),
                'name' => $socialiteUser->getName(),
                'vkontakte_id' => $socialiteUser->getId(),
            ]
        );

        // Выводим информацию пользователя вместе с телеграмом
        $telegramId = $user->telegram_chat;

        if ($telegramId === null) {
            // Если ID телеграма еще не вводилось
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'access_token' => $user->createToken('vkontakte-token')->plainTextToken,
                'telegram' => null
            ], 200);
        } else {
            try {
                $telegramUser = $this->telegram->getChat(['chat_id' => $telegramId]);
                // Получаем чат пользователя с телеграм ботом
            } catch (TelegramResponseException $e) {

                // Если ID телеграма введен не верно и чат не найден, выводим ошибку для фронтенда
                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_token' => $user->createToken('vkontakte-token')->plainTextToken,
                    'telegram' => [
                        'error' => true
                    ]
                ], 200);
            }

            // Если никаких ошибок с поиском чата телеграма не выявлено, то мы можем его найти и вывести
            return response()->json([
                'name' => $user->name,
                'email' => $user->email,
                'access_token' => $user->createToken('vkontakte-token')->plainTextToken,
                'telegram' => [
                    'error' => false,
                    'first_name' => $telegramUser->first_name,
                    'last_name' => $telegramUser->last_name,
                    'username' => $telegramUser->username,
                ]
            ], 200);
        }
    }
}
