<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VkAuthController;
use App\Http\Controllers\Auth\YandexAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChangeDirectionController;
use App\Http\Controllers\CheckRolesController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DesignExpertController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\WorksController;

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

// Для авторизованных пользователей
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
    Route::post('direction/change', [ChangeDirectionController::class, 'ChangeDirection']);
    Route::get('direction/status', [ChangeDirectionController::class, 'getDirections']);

    Route::get('design/allexpert', [DesignController::class, 'allExpert']);

    Route::get('works/all', [WorksController::class, 'getWorks']);
    Route::post('works/add', [WorksController::class, 'addWorks']);
    Route::post('works/edit', [WorksController::class, 'editWorks']);
    Route::post('works/delete', [WorksController::class, 'deleteWorks']);


    Route::put('review/add', [ReviewsController::class, 'addWorks']);
    Route::get('review/works', [ReviewsController::class, 'allWorksForUser']);

    Route::get('admin/check', [CheckRolesController::class, 'checkAdmin']);
    Route::get('admin/expert', [CheckRolesController::class, 'checkExpert']);
});

// Добавлять или удалять администраторов для супер админа
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    Route::get('admin/users', [AdminController::class, 'users']);
    Route::get('admin/expert/{id}', [AdminController::class, 'getExpert']);
    Route::post('admin/expert/add', [AdminController::class, 'addExpert']);
    Route::post('admin/expert/edit', [AdminController::class, 'editExpert']);
    Route::put('admin/design/remove', [AdminController::class, 'removeDesign']);
});

// Для Экспертов по дизайну
Route::middleware(['auth:sanctum', 'role:Design Expert'])->group(function () {
    Route::get('admin/design/settings', [DesignExpertController::class, 'profileInfo']);
    Route::get('admin/design/works', [DesignExpertController::class, 'works']);
    Route::put('admin/design/editsettings', [DesignExpertController::class, 'editProfileInfo']);
});


Route::get('auth/vk', [VkAuthController::class, 'redirectToAuth']);
Route::get('auth/vk/callback', [VkAuthController::class, 'handleAuthCallback']);

Route::get('auth/yandex', [YandexAuthController::class, 'redirectToAuth']);
Route::get('auth/yandex/callback', [YandexAuthController::class, 'handleAuthCallback']);

Route::get('auth/google', [GoogleAuthController::class, 'redirectToAuth']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleAuthCallback']);
