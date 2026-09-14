<?php

use App\Http\Controllers\Api\IntegrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for PESU PELUH System
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('integration')->group(function () {
    Route::get('/categories', [IntegrationController::class, 'categories']);
    Route::post('/sipuas-ticket', [IntegrationController::class, 'createTicketFromSipuas']);
});
