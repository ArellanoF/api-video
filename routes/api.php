<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VideoTaskController;

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

Route::post('/tasks', [VideoTaskController::class, 'store']);
Route::get('/tasks/{id}', [VideoTaskController::class, 'show']);
Route::get('/tasks/{id}/final', [VideoTaskController::class, 'final']);