<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    /**
     * Tạo ticket hỗ trợ mới kèm theo tin nhắn đầu tiên
     */
    public function createTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticketCode = $this->generateTicketCode();

            $ticket = SupportTicket::create([
                'ticket_code' => $ticketCode,
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'normal',
                'booking_code' => $data['booking_code'] ?? null,
                'status' => TicketStatus::Open,
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'sender_type' => 'customer',
                'sender_name' => $user->full_name,
                'body' => $data['message'],
            ]);

            return $ticket;
        });
    }

    /**
     * Gửi tin nhắn phản hồi cho ticket
     */
    public function replyToTicket(User $user, string $ticketId, string $body, string $senderType): SupportMessage
    {
        return DB::transaction(function () use ($user, $ticketId, $body, $senderType) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);

            if ($ticket->status === TicketStatus::Closed) {
                throw new \InvalidArgumentException('Không thể phản hồi yêu cầu hỗ trợ đã đóng.');
            }

            $message = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'sender_type' => $senderType,
                'sender_name' => $user->full_name,
                'body' => $body,
            ]);

            // Nếu admin trả lời và ticket đang ở trạng thái 'open', chuyển sang 'in_progress'
            if ($senderType === 'admin' && $ticket->status === TicketStatus::Open) {
                $ticket->status = TicketStatus::InProgress;
            }

            // Lưu ticket để kích hoạt updated_at của ticket
            $ticket->touch();
            $ticket->save();

            return $message;
        });
    }

    /**
     * Phân công ticket hỗ trợ cho admin xử lý
     */
    public function assignTicket(string $ticketId, string $adminId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update([
            'assigned_to' => $adminId,
        ]);
    }

    /**
     * Đánh dấu ticket đã được giải quyết
     */
    public function resolveTicket(string $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Đóng ticket hỗ trợ
     */
    public function closeTicket(string $ticketId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    /**
     * Sinh mã ticket độc nhất tăng dần: TK-000001
     */
    private function generateTicketCode(): string
    {
        $last = SupportTicket::latest('created_at')->value('ticket_code');

        if (! $last) {
            $seq = 1;
        } else {
            $seq = ((int) substr($last, 3)) + 1;
        }

        return 'TK-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
