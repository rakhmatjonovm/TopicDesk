<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use GuzzleHttp\Client;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

class TelegramService
{
    protected Api $telegram;
    protected string $supportGroupId;

    public function __construct()
    {
        $client = new Client(['verify' => false]); // Note: Enable verify in production environment
        $httpClient = new GuzzleHttpClient($client);

        $this->telegram = new Api(
            env('TELEGRAM_BOT_TOKEN'),
            false,
            $httpClient
        );

        $this->supportGroupId = (string) env('TELEGRAM_SUPPORT_GROUP_ID');
    }

    /**
     * Create a new forum topic in the support supergroup.
     */
    public function createTopic(string $name): ?int
    {
        try {
            $safeName = mb_substr($name, 0, 60);
            $response = $this->telegram->post('createForumTopic', [
                'chat_id' => $this->supportGroupId,
                'name' => $safeName,
            ]);

            $result = $response->getResult();
            if (is_object($result) && method_exists($result, 'toArray')) {
                $result = $result->toArray();
            } elseif (is_object($result)) {
                $result = (array) $result;
            }

            return $result['message_thread_id'] ?? null;
        } catch (\Exception $e) {
            Log::error("TelegramService: Failed to create topic. " . $e->getMessage());
            return null;
        }
    }

    /**
     * Close a forum topic (sets the icon to closed).
     */
    public function closeTopic(int $topicId): void
    {
        try {
            $this->telegram->post('closeForumTopic', [
                'chat_id' => $this->supportGroupId,
                'message_thread_id' => $topicId,
            ]);
        } catch (\Exception $e) {
            Log::warning("TelegramService: Failed to close topic $topicId: " . $e->getMessage());
        }
    }

    /**
     * Copy a message (text, media, etc.) from one chat to another.
     */
    public function copyMessage(string|int $toChatId, string|int $fromChatId, int $messageId, ?int $topicId = null): ?int
    {
        try {
            $params = [
                'chat_id' => $toChatId,
                'from_chat_id' => $fromChatId,
                'message_id' => $messageId,
            ];

            if ($topicId) {
                $params['message_thread_id'] = $topicId;
            }

            $response = $this->telegram->post('copyMessage', $params);
            
            $result = $response->getResult();
            if (is_object($result) && method_exists($result, 'toArray')) {
                $result = $result->toArray();
            } elseif (is_object($result)) {
                $result = (array) $result;
            }

            return $result['message_id'] ?? null;
        } catch (\Exception $e) {
            Log::error("TelegramService: Failed to copy message. " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a text message to a specific topic.
     */
    public function sendMessageToTopic(int $topicId, string $text): ?int
    {
        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $this->supportGroupId,
                'message_thread_id' => $topicId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
            return $response->message_id;
        } catch (\Exception $e) {
            Log::error("TelegramService: Failed to send to topic ($topicId): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a text message to a private user.
     */
    public function sendMessageToUser(int|string $chatId, string $text): ?int
    {
        try {
            $response = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);
            return $response->message_id;
        } catch (\Exception $e) {
            Log::error("TelegramService: Failed to send to user ($chatId): " . $e->getMessage());
            return null;
        }
    }
}