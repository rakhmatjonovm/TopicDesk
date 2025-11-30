<?php

namespace App\Jobs;

use App\Services\TicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $updateData;

    public function __construct(array $updateData)
    {
        $this->updateData = $updateData;
    }

    public function handle(TicketService $ticketService): void
    {
        try {
            if (isset($this->updateData['message'])) {
                $ticketService->handleMessage($this->updateData['message']);
            }
        } catch (\Exception $e) {
            Log::error("Job Failed: " . $e->getMessage());
        }
    }
}