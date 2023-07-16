<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VkAuthController;
use App\Http\Controllers\Auth\YandexAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChangeDirectionController;
use App\Http\Controllers\DesignController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [LogoutController::class, 'logout']);
    Route::get('user', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email
        ]);
    });
    // Скрывать или показывать направления
    Route::put('direction/change/design', [ChangeDirectionController::class, 'ChangeDesign']);
    Route::put('direction/change/frontend', [ChangeDirectionController::class, 'ChangeFrontend']);
    Route::put('direction/change/photo', [ChangeDirectionController::class, 'ChangePhoto']);

    Route::get('direction/status', [ChangeDirectionController::class, 'getDirections']);

    Route::put('design/addlink', [DesignController::class, 'addLink']);
    Route::patch('design/editlink', [DesignController::class, 'editLink']);
    Route::patch('design/deletelink', [DesignController::class, 'deleteLink']);
    Route::get('design/links', [DesignController::class, 'getLinks']);
});

Route::get('auth/vk', [VkAuthController::class, 'redirectToAuth']);
Route::get('auth/vk/callback', [VkAuthController::class, 'handleAuthCallback']);

Route::get('auth/yandex', [YandexAuthController::class, 'redirectToAuth']);
Route::get('auth/yandex/callback', [YandexAuthController::class, 'handleAuthCallback']);

Route::get('auth/google', [GoogleAuthController::class, 'redirectToAuth']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleAuthCallback']);
