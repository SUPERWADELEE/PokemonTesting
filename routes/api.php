<?php

use App\Http\Controllers\PokemonController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentsResponseController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::middleware('auth:api', 'checkStatus', 'throttle:100000,1')->group(function () {

    /**
     * pokemon管理
     * 
     */
    // pokemon列表

    Route::apiResource('pokemons', PokemonController::class)->only([
        'index', 'show', 'destroy'
    ]);

    Route::put('pokemons/{pokemon}/evolution', [PokemonController::class, 'evolution']);

    

    /**
     * user管理
     */
    // 使用者細節
    Route::get('user', [UserController::class, 'show']);
    Route::post('user', [UserController::class, 'update']);


    // 購物車詳情
    Route::get('cart_items', [CartItemController::class, 'index']);
    Route::post('cart_items', [CartItemController::class, 'store']);
    Route::put('cart_items', [CartItemController::class, 'update']);
    Route::delete('cart_items/{cart_item}', [CartItemController::class, 'destroy']);


    // 訂單
    Route::get('orders', [OrderController::class, 'index']);

    // 訂單詳情
    Route::get('orders/{order}/order_details', [OrderDetailController::class, 'index']);

    // 購買金流
    Route::post('payments', [PaymentController::class, 'checkout']);
});

/**
     * race管理
     */
    // race列表
    Route::get('races', [RaceController::class, 'index']);


    // 註冊
Route::post('/register', [RegisterController::class, 'register']);

// 登入
Route::post('/Auth/login', [AuthController::class, 'login']);
// 登出
Route::post('/Auth/logout', [AuthController::class, 'logout']);

// 藍星金流結帳回傳
Route::post('/payResult', [PaymentsResponseController::class, 'notifyResponse']);

// 第三方登入
Route::get('login/google', [GoogleLoginController::class, 'redirectToProvider']);
Route::get('login/google/callback', [GoogleLoginController::class, 'handleProviderCallback']);
