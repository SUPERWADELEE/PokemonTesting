<?php

use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\LoginController;
// use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('index', function () {
    return view('index');
});


Route::post('/payment', function (Request $request) {
    // Log所有回傳的資料
    // Log::info($request->all());

    return view('pokemons', ['paymentData' => $request->all()]);

});

Route::get('/', function () {
    return view('pokemons');
});
// Route::get('/', function () {
//         dd('fuck');
//     });


Route::get('/pokemon/{id}', function ($id) {
    return view('pokemon', ['id' => $id]);
});



Route::get('/addRace', function () {
    return view('addRace');
});


Route::get('/addProfile', function () {
    return view('addProfile');
});

// 第三方登入
Route::get('login/google', [GoogleLoginController::class, 'redirectToProvider']);
Route::get('login/google/callback', [GoogleLoginController::class,'handleProviderCallback']);


