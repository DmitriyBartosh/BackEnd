<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class YandexAuthController extends Controller
{
    public function redirectToAuth(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('yandex')
                ->stateless()
                ->scopes([])
                ->with([
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
            $socialiteUser = Socialite::driver('yandex')->stateless()->user();
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
                'yandex_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]
        );

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'direction' => $user->direction
            ],
            'access_token' => $user->createToken('yandex-token')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
