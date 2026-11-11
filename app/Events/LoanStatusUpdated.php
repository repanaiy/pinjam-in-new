<?php

namespace App\Events;

use App\Models\Loan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Loan $loan,
        public string $oldStatus,
        public string $newStatus,
        public ?string $message = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('loans.'.$this->loan->id),
            new PrivateChannel('user.'.$this->loan->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'loan' => [
                'id' => $this->loan->id,
                'request_code' => $this->loan->request_code,
                'status' => $this->loan->status,
                'status_label' => $this->loan->status_label,
            ],
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $this->message,
        ];
    }

    public function broadcastAs(): string
    {
        return 'loan.status.updated';
    }
}
