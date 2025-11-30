<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramUnsetWebhook extends Command
{
    protected $signature = 'telegram:unset-webhook';

    protected $description = 'Remove the Telegram Webhook (stop receiving updates via HTTP)';

    public function handle()
    {
        $this->info('Removing Telegram Webhook...');

        try {
            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

            // removeWebhook() — это обертка над deleteWebhook
            $response = $telegram->removeWebhook();

            if ($response) {
                $this->info('Success! Webhook removed.');
                return Command::SUCCESS;
            }

        } catch (TelegramSDKException $e) {
            $this->error("Telegram SDK Error: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::FAILURE;
    }
}