<?php

namespace App\Services;

use App\DTOs\SupportTicketMutationResultDTO;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use App\Events\SupportTicketCreatedEvent;
use App\Events\SupportTicketMessageCreatedEvent;
use App\Events\SupportTicketUpdatedEvent;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly AdminNotificationService $adminNotificationService,
    ) {}

    /**
     * Tạo ticket hỗ trợ mới kèm theo tin nhắn đầu tiên
     */
    public function createTicket(User $user, array $data): SupportTicket
    {
        $ticket = DB::transaction(function () use ($user, $data) {
            $ticket = SupportTicket::create([
                'ticket_code' => $this->nextTicketCode(),
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'normal',
                'booking_code' => $data['booking_code'] ?? null,
                'status' => TicketStatusEnum::Open,
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'sender_type' => 'customer',
                'sender_name' => $user->full_name,
                'body' => $data['message'],
            ]);

            return $ticket->loadCount('messages');
        });

        $this->adminNotificationService->notify(
            'support_tickets.manage',
            'Yêu cầu hỗ trợ mới',
            "{$user->full_name} vừa tạo yêu cầu {$ticket->ticket_code}.",
            ['kind' => 'support_ticket_created', 'ticket_id' => $ticket->id, 'link' => "/admin/support/{$ticket->id}"],
        );
        SupportTicketCreatedEvent::dispatch($ticket);

        return $ticket;
    }

    /**
     * Khách hàng phản hồi ticket thuộc chính họ.
     */
    public function replyAsCustomer(User $customer, string $ticketId, string $body): SupportTicketMutationResultDTO
    {
        $result = DB::transaction(function () use ($customer, $ticketId, $body) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);

            if ($ticket->user_id !== $customer->id) {
                throw new \DomainException('Bạn không có quyền phản hồi yêu cầu hỗ trợ này.');
            }

            $this->ensureTicketAcceptsReplies($ticket);

            $message = $this->createMessage($ticket, $customer, $body, 'customer');
            $ticket->touch();

            $this->adminNotificationService->notify(
                'support_tickets.manage',
                'Khách hàng phản hồi ticket',
                "{$customer->full_name} vừa phản hồi yêu cầu {$ticket->ticket_code}.",
                ['kind' => 'support_ticket_replied', 'ticket_id' => $ticket->id, 'link' => "/admin/support/{$ticket->id}"],
            );

            return new SupportTicketMutationResultDTO(
                ticket: $ticket->refresh(),
                oldValues: [],
                newValues: ['message_id' => $message->id],
                message: $message,
            );
        });

        SupportTicketMessageCreatedEvent::dispatch($result->message);

        return $result;
    }

    /**
     * Admin phản hồi khách hàng hoặc ghi chú nội bộ.
     */
    public function replyAsAdmin(User $admin, string $ticketId, string $body, bool $isInternal = false): SupportTicketMutationResultDTO
    {
        $result = DB::transaction(function () use ($admin, $ticketId, $body, $isInternal) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);
            $oldStatus = $ticket->status;

            if (! $isInternal) {
                $this->ensureTicketAcceptsReplies($ticket);
            }

            $message = $this->createMessage($ticket, $admin, $body, 'admin', $isInternal);

            if (! $isInternal && $ticket->status === TicketStatusEnum::Open) {
                $ticket->status = TicketStatusEnum::InProgress;
            }

            $ticket->save();

            if (! $isInternal) {
                $this->notificationService->send(
                    $ticket->user()->firstOrFail(),
                    NotificationTypeEnum::System,
                    [
                        'title' => 'Yêu cầu hỗ trợ có phản hồi mới',
                        'body' => "Yêu cầu {$ticket->ticket_code} vừa được nhân viên hỗ trợ phản hồi.",
                        'kind' => 'support_ticket_replied',
                        'ticket_id' => $ticket->id,
                        'link' => "/support/{$ticket->id}",
                    ],
                    [NotificationChannelEnum::InApp],
                    "support-reply:{$message->id}:{$ticket->user_id}",
                );
            }

            return new SupportTicketMutationResultDTO(
                ticket: $ticket->refresh(),
                oldValues: ['status' => $oldStatus->value],
                newValues: [
                    'status' => $ticket->status->value,
                    'message_id' => $message->id,
                    'is_internal' => $isInternal,
                ],
                message: $message,
            );
        });

        SupportTicketMessageCreatedEvent::dispatch($result->message);
        if ($result->oldValues['status'] !== $result->newValues['status']) {
            SupportTicketUpdatedEvent::dispatch($result->ticket, ['status']);
        }

        return $result;
    }

    /**
     * Phân công ticket cho một nhân viên admin.
     */
    public function assignTicket(string $ticketId, string $adminId): SupportTicketMutationResultDTO
    {
        $result = DB::transaction(function () use ($ticketId, $adminId) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);

            if ($ticket->status === TicketStatusEnum::Closed) {
                throw new \InvalidArgumentException('Không thể phân công yêu cầu hỗ trợ đã đóng.');
            }

            $oldAssignee = $ticket->assigned_to;
            $ticket->assigned_to = $adminId;
            $ticket->save();

            return new SupportTicketMutationResultDTO(
                ticket: $ticket->refresh(),
                oldValues: ['assigned_to' => $oldAssignee],
                newValues: ['assigned_to' => $adminId],
            );
        });

        SupportTicketUpdatedEvent::dispatch($result->ticket, ['assigned_to']);

        return $result;
    }

    /**
     * Cập nhật trạng thái/mức ưu tiên qua một transition duy nhất.
     */
    public function updateTicket(
        string $ticketId,
        ?TicketStatusEnum $status = null,
        ?TicketPriorityEnum $priority = null,
    ): SupportTicketMutationResultDTO {
        $result = DB::transaction(function () use ($ticketId, $status, $priority) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);
            $oldValues = [
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
            ];

            if ($status !== null && $status !== $ticket->status) {
                $this->assertTransitionAllowed($ticket->status, $status);
                $ticket->status = $status;
                $ticket->resolved_at = $status === TicketStatusEnum::Resolved ? now() : null;
                $ticket->closed_at = $status === TicketStatusEnum::Closed ? now() : null;
            }

            if ($priority !== null) {
                $ticket->priority = $priority;
            }

            $ticket->save();

            return new SupportTicketMutationResultDTO(
                ticket: $ticket->refresh(),
                oldValues: $oldValues,
                newValues: [
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                ],
            );
        });

        $changed = array_keys(array_filter(
            $result->newValues,
            fn ($value, $key) => ($result->oldValues[$key] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));
        if ($changed !== []) {
            SupportTicketUpdatedEvent::dispatch($result->ticket, $changed);
        }

        return $result;
    }

    public function closeAsCustomer(User $customer, string $ticketId): SupportTicketMutationResultDTO
    {
        $result = DB::transaction(function () use ($customer, $ticketId) {
            $ticket = SupportTicket::lockForUpdate()->findOrFail($ticketId);

            if ($ticket->user_id !== $customer->id) {
                throw new \DomainException('Bạn không có quyền đóng yêu cầu hỗ trợ này.');
            }

            return $this->transitionLockedTicket($ticket, TicketStatusEnum::Closed);
        });

        SupportTicketUpdatedEvent::dispatch($result->ticket, ['status']);

        return $result;
    }

    public function resolveTicket(string $ticketId): SupportTicketMutationResultDTO
    {
        return $this->updateTicket($ticketId, TicketStatusEnum::Resolved);
    }

    public function closeTicket(string $ticketId): SupportTicketMutationResultDTO
    {
        return $this->updateTicket($ticketId, TicketStatusEnum::Closed);
    }

    private function transitionLockedTicket(SupportTicket $ticket, TicketStatusEnum $status): SupportTicketMutationResultDTO
    {
        $oldStatus = $ticket->status;
        $this->assertTransitionAllowed($oldStatus, $status);

        if ($oldStatus !== $status) {
            $ticket->status = $status;
            $ticket->resolved_at = $status === TicketStatusEnum::Resolved ? now() : null;
            $ticket->closed_at = $status === TicketStatusEnum::Closed ? now() : null;
            $ticket->save();
        }

        return new SupportTicketMutationResultDTO(
            ticket: $ticket->refresh(),
            oldValues: ['status' => $oldStatus->value],
            newValues: ['status' => $status->value],
        );
    }

    private function assertTransitionAllowed(TicketStatusEnum $from, TicketStatusEnum $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = match ($from) {
            TicketStatusEnum::Open => [TicketStatusEnum::InProgress, TicketStatusEnum::Resolved, TicketStatusEnum::Closed],
            TicketStatusEnum::InProgress => [TicketStatusEnum::Resolved, TicketStatusEnum::Closed],
            TicketStatusEnum::Resolved => [TicketStatusEnum::InProgress, TicketStatusEnum::Closed],
            TicketStatusEnum::Closed => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new \InvalidArgumentException("Không thể chuyển ticket từ {$from->value} sang {$to->value}.");
        }
    }

    private function ensureTicketAcceptsReplies(SupportTicket $ticket): void
    {
        if (! in_array($ticket->status, [TicketStatusEnum::Open, TicketStatusEnum::InProgress], true)) {
            throw new \InvalidArgumentException('Chỉ có thể phản hồi yêu cầu hỗ trợ đang mở hoặc đang xử lý.');
        }
    }

    private function createMessage(
        SupportTicket $ticket,
        User $sender,
        string $body,
        string $senderType,
        bool $isInternal = false,
    ): SupportMessage {
        return SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => $sender->id,
            'sender_type' => $senderType,
            'sender_name' => $sender->full_name,
            'body' => $body,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Sinh mã tuần tự an toàn khi nhiều request tạo ticket đồng thời.
     */
    private function nextTicketCode(): string
    {
        $sequence = DB::table('support_ticket_sequences')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            throw new \RuntimeException('Support ticket sequence chưa được khởi tạo.');
        }

        $latestCode = SupportTicket::max('ticket_code');
        $latestNumber = is_string($latestCode) ? (int) substr($latestCode, 3) : 0;
        $nextNumber = max((int) $sequence->ticket_number, $latestNumber) + 1;

        DB::table('support_ticket_sequences')
            ->where('id', 1)
            ->update(['ticket_number' => $nextNumber]);

        return 'TK-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
