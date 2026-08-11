export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
export type TicketCategory =
    | 'general'
    | 'payment'
    | 'refund'
    | 'complaint'
    | 'technical'
    | 'other';
export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent';
export type TicketSenderType = 'customer' | 'admin';

export interface SupportUser {
    id: string;
    full_name: string;
    phone: string;
    email?: string | null;
}

export interface SupportMessage {
    id: string;
    sender_type: TicketSenderType;
    sender_name: string;
    body: string;
    is_internal: boolean;
    created_at: string;
}

export interface SupportTicket {
    id: string;
    ticket_code: string;
    subject: string;
    category: TicketCategory;
    status: TicketStatus;
    priority: TicketPriority;
    booking_code?: string | null;
    assigned_to?: string | null;
    assignee?: Pick<SupportUser, 'id' | 'full_name'> | null;
    user?: SupportUser;
    messages?: SupportMessage[];
    message_count?: number;
    last_reply_at?: string | null;
    resolved_at?: string | null;
    closed_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface SupportStats {
    open: number;
    in_progress: number;
    resolved: number;
    closed: number;
}

export interface WebSocketEnvelope<TType extends string, TPayload> {
    v: 1;
    type: TType;
    payload: TPayload;
}

export type SupportMessageCreatedEvent = WebSocketEnvelope<
    'support_message.created',
    { ticket_id: string; message: SupportMessage }
>;

export type SupportTicketUpdatedEvent = WebSocketEnvelope<
    'support_ticket.updated',
    {
        ticket_id: string;
        status: TicketStatus;
        priority: TicketPriority;
        assigned_to: string | null;
        changed: Array<'status' | 'priority' | 'assigned_to'>;
        updated_at: string;
    }
>;

export type SupportTicketCreatedEvent = WebSocketEnvelope<
    'support_ticket.created',
    { ticket: SupportTicket }
>;

export const supportCategories: {
    value: TicketCategory;
    label: string;
    icon: string;
    desc: string;
}[] = [
    {
        value: 'payment',
        label: 'Vấn đề thanh toán',
        icon: '💳',
        desc: 'Thanh toán lỗi, chưa nhận vé',
    },
    {
        value: 'refund',
        label: 'Yêu cầu hoàn tiền',
        icon: '💰',
        desc: 'Hoàn tiền vé đã hủy',
    },
    {
        value: 'complaint',
        label: 'Khiếu nại dịch vụ',
        icon: '📢',
        desc: 'Chất lượng xe, tài xế',
    },
    {
        value: 'technical',
        label: 'Lỗi kỹ thuật',
        icon: '🔧',
        desc: 'Ứng dụng không hoạt động',
    },
    {
        value: 'general',
        label: 'Câu hỏi chung',
        icon: '💬',
        desc: 'Thắc mắc về dịch vụ',
    },
    { value: 'other', label: 'Khác', icon: '📋', desc: 'Vấn đề khác' },
];
