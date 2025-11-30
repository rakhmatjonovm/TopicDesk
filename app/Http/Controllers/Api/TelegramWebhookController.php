<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
// Мы пока не создали Job, поэтому просто закомментируем или оставим заглушку
// use App\Jobs\ProcessIncomingUpdateJob; 

class TelegramWebhookController extends Controller
{
    /**
     * Обработка входящего Webhook от Telegram.
     */
    public function handle(Request $request)
    {
        // 1. Проверка секретного токена (Security)
        // Telegram передает его в заголовке X-Telegram-Bot-Api-Secret-Token
        $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        
        if ($secretToken !== env('TELEGRAM_SECRET_TOKEN')) {
            Log::warning('Telegram Webhook: Access denied. Invalid Token.', [
                'ip' => $request->ip(),
                'token_received' => $secretToken
            ]);
            return response()->json(['message' => 'Access denied'], 403);
        }

        // 2. Получение данных
        $update = $request->all();

        // Логируем входящий запрос (для отладки, в продакшене лучше убрать)
        Log::info('Telegram Webhook received:', ['update_id' => $update['update_id'] ?? 'unknown']);

        // 3. Отправка в очередь (пока просто заглушка логики)
        // Позже мы раскомментируем вызов Job-а:
        // ProcessIncomingUpdateJob::dispatch($update);
        
        Log::info('Update dispatched to queue.');

        // 4. Telegram ждет 200 OK, иначе он будет слать повторы
        return response()->json(['status' => 'ok']);
    }
}