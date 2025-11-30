<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelegramWebhookController;

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);
