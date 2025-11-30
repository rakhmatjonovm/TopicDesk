<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Handle incoming webhook update message.
     */
    public function handleMessage(array $message): void
    {
        $chatType = $message['chat']['type'] ?? 'private';
        $text = $message['text'] ?? ($message['caption'] ?? '');

        if ($chatType === 'private') {
            $this->processUserMessage($message, $text);
            return;
        }

        if ($chatType === 'supergroup' && isset($message['message_thread_id'])) {
            $this->processSupportReply($message, $text);
            return;
        }
    }

    protected function processUserMessage(array $message, string $text): void
    {
        $userData = $message['from'];
        $telegramId = $userData['id'];

        if ($text === '/start') {
            $welcomeText = "👋 <b>Привет, {$userData['first_name']}!</b>\n\n" .
                           "Это бот поддержки. Отправьте сообщение (текст или медиа), " .
                           "и операторы ответят вам в ближайшее время.";
            $this->telegramService->sendMessageToUser($telegramId, $welcomeText);
            return;
        }

        $user = TelegramUser::updateOrCreate(
            ['telegram_id' => $telegramId],
            [
                'first_name' => $userData['first_name'] ?? null,
                'last_name' => $userData['last_name'] ?? null,
                'username' => $userData['username'] ?? null,
                'language_code' => $userData['language_code'] ?? 'en',
            ]
        );

        $ticket = $user->tickets()->where('status', 'open')->first();

        if (!$ticket) {
            $subject = $text ? Str::limit($text, 30) : 'Media Attachment';
            
            $ticket = Ticket::create([
                'telegram_user_id' => $user->id,
                'status' => 'open',
                'subject' => $subject,
            ]);

            $topicName = "🎫 " . trim("{$user->first_name} {$user->last_name}");
            if ($user->username) $topicName .= " (@{$user->username})";

            $topicId = $this->telegramService->createTopic($topicName);

            if ($topicId) {
                $ticket->update(['topic_id' => $topicId]);

                $userLink = "<a href='tg://user?id={$user->telegram_id}'>" . htmlspecialchars($user->first_name) . "</a>";
                $systemMsg = "<b>🆕 Новый тикет #{$ticket->short_id}</b>\n\n" .
                             "👤 <b>Клиент:</b> {$userLink}\n" .
                             "🆔 <b>ID:</b> <code>{$user->telegram_id}</code>\n" .
                             "➖➖➖➖➖➖➖➖";
                $this->telegramService->sendMessageToTopic($topicId, $systemMsg);
            } else {
                Log::error("Failed to create topic for ticket {$ticket->id}");
                return;
            }
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'direction' => 'incoming',
            'user_message_id' => $message['message_id'],
            'content' => $text,
            'payload' => json_encode($message),
        ]);

        if ($ticket->topic_id) {
            if (isset($message['text'])) {
                $msgToSend = "📩 <b>Клиент:</b>\n" . htmlspecialchars($text);
                $this->telegramService->sendMessageToTopic($ticket->topic_id, $msgToSend);
            } else {
                $this->telegramService->sendMessageToTopic($ticket->topic_id, "📎 <b>Вложение от клиента:</b>");
                $this->telegramService->copyMessage(
                    toChatId: env('TELEGRAM_SUPPORT_GROUP_ID'),
                    fromChatId: $telegramId,
                    messageId: $message['message_id'],
                    topicId: $ticket->topic_id
                );
            }
        }
    }

    protected function processSupportReply(array $message, string $text): void
    {
        if (isset($message['from']['is_bot']) && $message['from']['is_bot']) return;

        $topicId = $message['message_thread_id'];
        $ticket = Ticket::with('user')->where('topic_id', $topicId)->first();

        if (!$ticket || !$ticket->user) return;

        if (trim($text) === '/close') {
            $ticket->update(['status' => 'closed']);
            $this->telegramService->closeTopic($topicId);
            $this->telegramService->sendMessageToTopic($topicId, "✅ Тикет закрыт. Архив сохранен.");
            $this->telegramService->sendMessageToUser($ticket->user->telegram_id, "✅ Ваш запрос закрыт. Спасибо за обращение!");
            return;
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outgoing',
            'support_message_id' => $message['message_id'],
            'content' => $text,
            'payload' => json_encode($message),
        ]);

        $userTelegramId = $ticket->user->telegram_id;

        if (isset($message['text'])) {
            $this->telegramService->sendMessageToUser($userTelegramId, $text);
        } else {
            $this->telegramService->copyMessage(
                toChatId: $userTelegramId,
                fromChatId: env('TELEGRAM_SUPPORT_GROUP_ID'),
                messageId: $message['message_id']
            );
        }
    }
}