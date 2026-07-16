<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { watchDebounced } from '@vueuse/core';

const router = useRouter();

// ─── Types ─────────────────────────────────────────────────────────────────
type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
type TicketCategory = 'general' | 'payment' | 'refund' | 'complaint' | 'technical' | 'other';
type Priority = 'low' | 'normal' | 'high' | 'urgent';

interface SupportTicket {
    id: string;
    ticket_code: string;
    subject: string;
    category: TicketCategory;
    status: TicketStatus;
    priority: Priority;
    created_at: string;
    updated_at: string;
    last_reply_at?: string;
    message_count: number;
    booking_code?: string;
    user: { id: string; full_name: string; phone: string; email?: string };
}

// ─── State ─────────────────────────────────────────────────────────────────
const tickets = ref<SupportTicket[]>([]);
const loading = ref(true);
const search = ref('');
const statusFilter = ref<'all' | TicketStatus>('all');
const categoryFilter = ref<'all' | TicketCategory>('all');
const priorityFilter = ref<'all' | Priority>('all');
const stats = ref({ open: 0, in_progress: 0, resolved: 0, closed: 0 });

const mockTickets: SupportTicket[] = [
    { id: 'tk-001', ticket_code: 'TK-000001', subject: 'Không nhận được vé sau khi thanh toán MoMo', category: 'payment', status: 'in_progress', priority: 'high', created_at: '2024-06-20T10:30:00Z', updated_at: '2024-06-20T14:00:00Z', last_reply_at: '2024-06-20T14:00:00Z', message_count: 4, booking_code: 'XG-20240620', user: { id: 'u1', full_name: 'Nguyễn Văn An', phone: '0901234567', email: 'an@gmail.com' } },
    { id: 'tk-002', ticket_code: 'TK-000002', subject: 'Yêu cầu hoàn tiền vé đã hủy ngày 15/6', category: 'refund', status: 'open', priority: 'normal', created_at: '2024-06-18T09:15:00Z', updated_at: '2024-06-18T09:15:00Z', message_count: 1, booking_code: 'XG-20240615', user: { id: 'u2', full_name: 'Trần Thị Bình', phone: '0912345678' } },
    { id: 'tk-003', ticket_code: 'TK-000003', subject: 'Tài xế đến muộn 30 phút, không xin lỗi', category: 'complaint', status: 'resolved', priority: 'normal', created_at: '2024-06-10T08:00:00Z', updated_at: '2024-06-12T16:30:00Z', last_reply_at: '2024-06-12T16:30:00Z', message_count: 5, booking_code: 'XG-20240610', user: { id: 'u3', full_name: 'Lê Văn Cường', phone: '0923456789' } },
    { id: 'tk-004', ticket_code: 'TK-000004', subject: 'Ứng dụng bị lỗi khi đặt vé trên iPhone', category: 'technical', status: 'open', priority: 'urgent', created_at: '2024-06-22T15:00:00Z', updated_at: '2024-06-22T15:00:00Z', message_count: 1, user: { id: 'u4', full_name: 'Phạm Thị Dung', phone: '0934567890' } },
    { id: 'tk-005', ticket_code: 'TK-000005', subject: 'Câu hỏi về chương trình khách hàng thân thiết', category: 'general', status: 'closed', priority: 'low', created_at: '2024-06-05T11:00:00Z', updated_at: '2024-06-07T10:00:00Z', last_reply_at: '2024-06-07T10:00:00Z', message_count: 3, user: { id: 'u5', full_name: 'Hoàng Văn Em', phone: '0945678901' } },
];

// ─── Computed ────────────────────────────────────────────────────────────────
const filteredTickets = computed(() => {
    let list = tickets.value;
    if (statusFilter.value !== 'all') list = list.filter((t) => t.status === statusFilter.value);
    if (categoryFilter.value !== 'all') list = list.filter((t) => t.category === categoryFilter.value);
    if (priorityFilter.value !== 'all') list = list.filter((t) => t.priority === priorityFilter.value);
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter((t) => t.ticket_code.toLowerCase().includes(q) || t.subject.toLowerCase().includes(q) || t.user.full_name.toLowerCase().includes(q) || t.user.phone.includes(q));
    }
    return list;
});

const hasActiveFilter = computed(() => statusFilter.value !== 'all' || categoryFilter.value !== 'all' || priorityFilter.value !== 'all' || !!search.value);

const categories: { value: TicketCategory; label: string; icon: string }[] = [
    { value: 'general', label: 'Câu hỏi chung', icon: '💬' },
    { value: 'payment', label: 'Thanh toán', icon: '💳' },
    { value: 'refund', label: 'Hoàn tiền', icon: '💰' },
    { value: 'complaint', label: 'Khiếu nại', icon: '📢' },
    { value: 'technical', label: 'Kỹ thuật', icon: '🔧' },
    { value: 'other', label: 'Khác', icon: '📋' },
];

// ─── Methods ────────────────────────────────────────────────────────────────
function statusLabel(s: TicketStatus) {
    return { open: 'Chờ xử lý', in_progress: 'Đang xử lý', resolved: 'Đã giải quyết', closed: 'Đã đóng' }[s];
}
function statusBadgeClass(s: TicketStatus) {
    return { open: 'bg-yellow-100 text-yellow-700', in_progress: 'bg-blue-100 text-blue-700', resolved: 'bg-green-100 text-green-700', closed: 'bg-slate-100 text-slate-600' }[s];
}
function priorityBarClass(p: Priority) {
    return { low: 'bg-slate-300', normal: 'bg-blue-500', high: 'bg-amber-400', urgent: 'bg-red-500' }[p];
}
function priorityLabel(p: Priority) {
    return { low: 'Thấp', normal: 'BT', high: 'Cao', urgent: 'Khẩn' }[p];
}
function priorityBadgeClass(p: Priority) {
    return { low: 'bg-slate-100 text-slate-600', normal: 'bg-blue-50 text-blue-600', high: 'bg-amber-50 text-amber-700', urgent: 'bg-red-50 text-red-600' }[p];
}
function categoryIcon(c: TicketCategory) {
    return categories.find((x) => x.value === c)?.icon ?? '📋';
}
function categoryLabel(c: TicketCategory) {
    return categories.find((x) => x.value === c)?.label ?? c;
}
function timeAgo(dateStr: string) {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins} phút trước`;
    const h = Math.floor(mins / 60);
    if (h < 24) return `${h} giờ trước`;
    return `${Math.floor(h / 24)} ngày trước`;
}

async function fetchTickets() {
    loading.value = true;
    await new Promise((r) => setTimeout(r, 600));
    tickets.value = mockTickets;
    stats.value = {
        open: mockTickets.filter((t) => t.status === 'open').length,
        in_progress: mockTickets.filter((t) => t.status === 'in_progress').length,
        resolved: mockTickets.filter((t) => t.status === 'resolved').length,
        closed: mockTickets.filter((t) => t.status === 'closed').length,
    };
    loading.value = false;
}

watchDebounced(search, () => fetchTickets(), { debounce: 400 });
onMounted(() => fetchTickets());
</script>

<template>
    <div class="p-6">
        <!-- ─── Page Header ─────────────────────────────────────────────── -->
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-purple-600 text-2xl shadow-lg shadow-violet-500/30">🎧</div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Quản lý Hỗ trợ</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Theo dõi và xử lý yêu cầu hỗ trợ từ khách hàng</p>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─────────────────────────────────────────────────────── -->
        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="s in [
                    { key: 'open', label: 'Chờ xử lý', count: stats.open, dot: 'bg-amber-400', text: 'text-amber-600', ring: 'ring-amber-200' },
                    { key: 'in_progress', label: 'Đang xử lý', count: stats.in_progress, dot: 'bg-blue-400', text: 'text-blue-600', ring: 'ring-blue-200' },
                    { key: 'resolved', label: 'Đã giải quyết', count: stats.resolved, dot: 'bg-green-400', text: 'text-green-600', ring: 'ring-green-200' },
                    { key: 'closed', label: 'Đã đóng', count: stats.closed, dot: 'bg-slate-400', text: 'text-slate-500', ring: 'ring-slate-200' },
                ]"
                :key="s.key"
                :class="['cursor-pointer rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md', statusFilter === s.key ? `ring-2 ${s.ring}` : '']"
                @click="statusFilter = s.key === statusFilter ? 'all' : (s.key as any)"
            >
                <div class="mb-2 flex items-center gap-2">
                    <span :class="['h-2 w-2 rounded-full', s.dot]"></span>
                    <span class="text-xs font-medium tracking-wide text-gray-500 uppercase">{{ s.label }}</span>
                </div>
                <p :class="['text-3xl font-bold', s.text]">{{ s.count }}</p>
            </div>
        </div>

        <!-- ─── Filters ─────────────────────────────────────────────────── -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="flex flex-1 min-w-[200px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 focus-within:border-violet-400 focus-within:ring-2 focus-within:ring-violet-200 transition">
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" placeholder="Tìm mã ticket, tiêu đề, khách hàng..." class="flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400" />
                <button v-if="search" class="text-slate-400 transition hover:text-slate-700" @click="search = ''">✕</button>
            </div>
            <!-- Category -->
            <select v-model="categoryFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-200">
                <option value="all">Tất cả danh mục</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.icon }} {{ c.label }}</option>
            </select>
            <!-- Priority -->
            <select v-model="priorityFilter" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-200">
                <option value="all">Tất cả mức độ</option>
                <option value="urgent">🔴 Khẩn cấp</option>
                <option value="high">🟡 Cao</option>
                <option value="normal">🟢 Bình thường</option>
                <option value="low">🔵 Thấp</option>
            </select>
            <!-- Clear -->
            <button v-if="hasActiveFilter" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100" @click="statusFilter = 'all'; categoryFilter = 'all'; priorityFilter = 'all'; search = ''">
                Xóa lọc
            </button>
        </div>

        <!-- ─── Table Card ─────────────────────────────────────────────────── -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
                <span class="text-sm text-slate-500">{{ filteredTickets.length }} ticket</span>
                <span v-if="statusFilter !== 'all'" :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass(statusFilter as TicketStatus)]">{{ statusLabel(statusFilter as TicketStatus) }}</span>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="divide-y divide-slate-100">
                <div v-for="i in 5" :key="i" class="animate-pulse p-5">
                    <div class="mb-2 h-3 w-3/5 rounded bg-slate-200"></div>
                    <div class="h-2.5 w-2/5 rounded bg-slate-100"></div>
                </div>
            </div>

            <!-- Empty -->
            <div v-else-if="filteredTickets.length === 0" class="py-16 text-center">
                <p class="text-4xl">🎫</p>
                <p class="mt-3 text-base font-bold text-slate-800">Không tìm thấy ticket</p>
                <p class="mt-1 text-sm text-slate-400">Thử điều chỉnh bộ lọc hoặc từ khóa tìm kiếm</p>
            </div>

            <!-- Rows -->
            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="ticket in filteredTickets"
                    :key="ticket.id"
                    class="group flex cursor-pointer items-stretch transition hover:bg-slate-50"
                    @click="router.push(`/admin/support/${ticket.id}`)"
                >
                    <!-- Priority bar -->
                    <div :class="['w-1 shrink-0', priorityBarClass(ticket.priority)]"></div>

                    <div class="flex flex-1 flex-wrap items-center justify-between gap-4 px-5 py-4">
                        <!-- Left -->
                        <div class="flex-1 min-w-0">
                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                <span class="rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-600">{{ ticket.ticket_code }}</span>
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass(ticket.status)]">{{ statusLabel(ticket.status) }}</span>
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-bold uppercase tracking-wide', priorityBadgeClass(ticket.priority)]">{{ priorityLabel(ticket.priority) }}</span>
                            </div>
                            <h3 class="mb-1.5 truncate text-sm font-semibold text-slate-800">{{ ticket.subject }}</h3>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span>{{ categoryIcon(ticket.category) }} {{ categoryLabel(ticket.category) }}</span>
                                <span v-if="ticket.booking_code" class="text-cyan-600">🎫 {{ ticket.booking_code }}</span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    {{ ticket.message_count }}
                                </span>
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-cyan-500 text-xs font-bold text-white">{{ ticket.user.full_name.charAt(0) }}</div>
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-slate-700">{{ ticket.user.full_name }}</p>
                                    <p class="text-xs text-slate-400">{{ ticket.user.phone }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-slate-400">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ timeAgo(ticket.last_reply_at ?? ticket.updated_at) }}
                            </div>
                            <span class="flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 transition group-hover:bg-indigo-100 group-hover:text-indigo-600">
                                Xem
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
