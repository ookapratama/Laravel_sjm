<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TreeApiController;
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
use App\Http\Controllers\MidtransCallbackController;
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// routes/api.php
Route::get('tree/root', [TreeApiController::class, 'getRoot']);
Route::get('tree/children/{id}', [TreeApiController::class, 'getChildren']);
