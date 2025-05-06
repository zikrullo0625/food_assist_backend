<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

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

Route::get('auth/{provider}', [AuthController::class, 'redirectToProvider'])->where('provider', 'google');
Route::get('auth/callback/{provider}', [AuthController::class, 'handleProviderCallback']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/webhook', [TelegramController::class, 'webhook']);
    Route::post('/analyze', [ProductController::class, 'analyze']);
    Route::post('/getScans', [UserController::class, 'getScans']);
    Route::get('/stats', [UserController::class, 'stats']);
    Route::get('/history', [UserController::class, 'history']);
});
