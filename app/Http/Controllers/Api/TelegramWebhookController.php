<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingUpdateJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        
        if ($secretToken !== env('TELEGRAM_SECRET_TOKEN')) {
            Log::warning('Webhook: Access denied. Invalid Token.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Access denied'], 403);
        }

        $update = $request->all();
        
        ProcessIncomingUpdateJob::dispatch($update);

        return response()->json(['status' => 'ok']);
    }
}