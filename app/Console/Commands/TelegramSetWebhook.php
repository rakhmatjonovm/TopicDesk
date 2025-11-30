<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramSetWebhook extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * Описание команды.
     */
    protected $description = 'Set the Telegram Webhook URL with Secret Token';

    /**
     * Выполнение команды.
     */
    public function handle()
    {
        $this->info('Setting up Telegram Webhook...');

        $webhookUrl = env('TELEGRAM_WEBHOOK_URL');
        $secretToken = env('TELEGRAM_SECRET_TOKEN');

        if (empty($webhookUrl)) {
            $this->error('Error: TELEGRAM_WEBHOOK_URL is not set in .env');
            return Command::FAILURE;
        }

        try {
            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

            // Отправляем запрос в Telegram API
            $response = $telegram->setWebhook([
                'url' => $webhookUrl,
                'secret_token' => $secretToken,
                // Опционально: можно указать allowed_updates, чтобы получать только нужные события
                // 'allowed_updates' => ['message', 'edited_message', 'callback_query'] 
            ]);

            if ($response) {
                $this->info("Success! Webhook set to: {$webhookUrl}");
                $this->info("Secret Token attached.");
                return Command::SUCCESS;
            } else {
                $this->error('Failed to set webhook (Unknown API response).');
                return Command::FAILURE;
            }

        } catch (TelegramSDKException $e) {
            $this->error("Telegram SDK Error: " . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("General Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}