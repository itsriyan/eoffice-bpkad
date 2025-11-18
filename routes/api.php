<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappWebhookController;

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

// Webhook routes (public, no auth/CSRF)
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify']);