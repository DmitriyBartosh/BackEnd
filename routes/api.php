<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VkAuthController;
use App\Http\Controllers\Auth\YandexAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChangeDirectionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExpertController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromocodeController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\WorksController;
use App\Models\Promocode;

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

    Route::get('works/all', [WorksController::class, 'getWorks']);
    Route::post('works/add', [WorksController::class, 'addWorks']);
    Route::post('works/edit', [WorksController::class, 'editWorks']);

    Route::post('subscribe/add', [SubscribeController::class, 'addSubscribe']);
    Route::get('subscribe/all', [SubscribeController::class, 'allSubscribes']);

    Route::get('review/{direction}/allexperts', [ReviewsController::class, 'allExperts']);
    Route::post('review/add', [ReviewsController::class, 'addWorks']);
    Route::delete('review/delete/{id}', [ReviewsController::class, 'deleteReview']);
    Route::get('review/works', [ReviewsController::class, 'allWorksOnReview']);
    Route::post('review/fix', [ReviewsController::class, 'fixWork']);
    Route::post('review/revision', [ReviewsController::class, 'revisionWork']);

    Route::get('review/payment/{id}', [ReviewsController::class, 'checkPayment']);
    Route::post('review/payment/get', [ReviewsController::class, 'getPayment']);

    Route::post('promo/activate', [PromocodeController::class, 'activatePromo']);
});

// Добавлять или удалять администраторов для супер админа
Route::middleware(['auth:sanctum', 'role:Super Admin'])->group(function () {
    Route::get('admin/users', [AdminController::class, 'users']);
    Route::get('admin/expert/{id}', [AdminController::class, 'getExpert']);
    Route::post('admin/expert/add', [AdminController::class, 'addExpert']);
    Route::post('admin/expert/edit', [AdminController::class, 'editExpert']);
    Route::post('admin/expert/delete', [AdminController::class, 'deleteDesign']);

    Route::post('promo/add', [PromocodeController::class, 'generatePromoCodes']);
    Route::get('promo/all', [PromocodeController::class, 'getAllPromoCodes']);
});

// Для Экспертов по дизайну
Route::middleware(['auth:sanctum', 'role:Design Expert|Frontend Expert|Photo Expert'])->group(function () {
    Route::get('expert/allworks/{direction}', [ExpertController::class, 'getAllWorks']);

    Route::get('expert/get', [ExpertController::class, 'getExpert']);
    Route::get('expert/reviews', [ExpertController::class, 'getAllReviews']);
    Route::post('expert/edit', [ExpertController::class, 'editExpert']);

    Route::post('expert/work/verified', [ExpertController::class, 'workVerified']);
    Route::post('expert/work/fail', [ExpertController::class, 'workFail']);
    Route::post('expert/work/review', [ExpertController::class, 'workReview']);
    Route::post('expert/work/revision', [ExpertController::class, 'workRevision']);
    Route::post('expert/work/notcounted', [ExpertController::class, 'workNotCounted']);
    Route::post('expert/work/extend', [ExpertController::class, 'extendDeadline']);
});

Route::get('payment', [PaymentController::class, 'createPayment']);
Route::get('payment/get', [PaymentController::class, 'getPayments']);
Route::get('payment/all', [PaymentController::class, 'allPayments']);

Route::get('auth/vk', [VkAuthController::class, 'redirectToAuth']);
Route::get('auth/vk/callback', [VkAuthController::class, 'handleAuthCallback']);

Route::get('auth/yandex', [YandexAuthController::class, 'redirectToAuth']);
Route::get('auth/yandex/callback', [YandexAuthController::class, 'handleAuthCallback']);

Route::get('auth/google', [GoogleAuthController::class, 'redirectToAuth']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleAuthCallback']);
