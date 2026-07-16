<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const router = useRouter();
const auth = useCustomerAuthStore();

// ─── Types ─────────────────────────────────────────────────────────────────
type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
type TicketCategory =
    | 'general'
    | 'payment'
    | 'refund'
    | 'complaint'
    | 'technical'
    | 'other';
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
    message_count?: number;
    booking_code?: string;
}

// ─── State ─────────────────────────────────────────────────────────────────
const activeTab = ref<'list' | 'create'>('list');
const tickets = ref<SupportTicket[]>([]);
const statusFilter = ref<'all' | TicketStatus>('all');
const loading = ref(false);
const createLoading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const myBookings = ref([
    { id: 'bk-001', booking_code: 'XG-20240101', route: 'Hà Nội → Hải Phòng', date: '01/01/2024' },
    { id: 'bk-002', booking_code: 'XG-20240215', route: 'Hải Phòng → Hà Nội', date: '15/02/2024' },
]);

const form = ref({
    category: '' as TicketCategory | '',
    subject: '',
    body: '',
    booking_id: '',
    priority: 'normal' as Priority,
});

const mockTickets: SupportTicket[] = [
    {
        id: 'tk-001',
        ticket_code: 'TK-000001',
        subject: 'Không nhận được vé sau khi thanh toán',
        category: 'payment',
        status: 'in_progress',
        priority: 'high',
        created_at: '2024-06-20T10:30:00Z',
        updated_at: '2024-06-20T14:00:00Z',
        last_reply_at: '2024-06-20T14:00:00Z',
        message_count: 3,
        booking_code: 'XG-20240620',
    },
    {
        id: 'tk-002',
        ticket_code: 'TK-000002',
        subject: 'Yêu cầu hoàn tiền vé đã hủy',
        category: 'refund',
        status: 'open',
        priority: 'normal',
        created_at: '2024-06-18T09:15:00Z',
        updated_at: '2024-06-18T09:15:00Z',
        message_count: 1,
        booking_code: 'XG-20240615',
    },
    {
        id: 'tk-003',
        ticket_code: 'TK-000003',
        subject: 'Tài xế đến muộn 30 phút',
        category: 'complaint',
        status: 'resolved',
        priority: 'normal',
        created_at: '2024-06-10T08:00:00Z',
        updated_at: '2024-06-12T16:30:00Z',
        last_reply_at: '2024-06-12T16:30:00Z',
        message_count: 5,
        booking_code: 'XG-20240610',
    },
];

// ─── Computed ───────────────────────────────────────────────────────────────
const filteredTickets = computed(() => {
    if (statusFilter.value === 'all') return tickets.value;
    return tickets.value.filter((t) => t.status === statusFilter.value);
});

const openCount = computed(() => tickets.value.filter((t) => t.status === 'open').length);
const inProgressCount = computed(() => tickets.value.filter((t) => t.status === 'in_progress').length);
const resolvedCount = computed(() => tickets.value.filter((t) => t.status === 'resolved' || t.status === 'closed').length);

const categories: { value: TicketCategory; label: string; icon: string; desc: string }[] = [
    { value: 'payment', label: 'Vấn đề thanh toán', icon: '💳', desc: 'Thanh toán lỗi, chưa nhận vé' },
    { value: 'refund', label: 'Yêu cầu hoàn tiền', icon: '💰', desc: 'Hoàn tiền vé đã hủy' },
    { value: 'complaint', label: 'Khiếu nại dịch vụ', icon: '📢', desc: 'Chất lượng xe, tài xế' },
    { value: 'technical', label: 'Lỗi kỹ thuật', icon: '🔧', desc: 'Ứng dụng không hoạt động' },
    { value: 'general', label: 'Câu hỏi chung', icon: '💬', desc: 'Thắc mắc về dịch vụ' },
    { value: 'other', label: 'Khác', icon: '📋', desc: 'Vấn đề khác' },
];

// ─── Methods ────────────────────────────────────────────────────────────────
function statusLabel(s: TicketStatus) {
    return { open: 'Chờ xử lý', in_progress: 'Đang xử lý', resolved: 'Đã giải quyết', closed: 'Đã đóng' }[s];
}

function statusBadgeClass(s: TicketStatus) {
    return {
        open: 'bg-yellow-100 text-yellow-700 border border-yellow-200',
        in_progress: 'bg-blue-100 text-blue-700 border border-blue-200',
        resolved: 'bg-green-100 text-green-700 border border-green-200',
        closed: 'bg-slate-100 text-slate-600 border border-slate-200',
    }[s];
}

function priorityLabel(p: Priority) {
    return { low: 'Thấp', normal: 'Bình thường', high: 'Cao', urgent: 'Khẩn cấp' }[p];
}

function priorityBadgeClass(p: Priority) {
    return {
        low: 'bg-slate-100 text-slate-600',
        normal: 'bg-blue-50 text-blue-600',
        high: 'bg-amber-50 text-amber-700',
        urgent: 'bg-red-50 text-red-600',
    }[p];
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
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours} giờ trước`;
    return `${Math.floor(hours / 24)} ngày trước`;
}

function isFormValid() {
    return form.value.category && form.value.subject.trim().length >= 5 && form.value.body.trim().length >= 20;
}

async function loadTickets() {
    loading.value = true;
    await new Promise((r) => setTimeout(r, 500));
    tickets.value = mockTickets;
    loading.value = false;
}

async function submitTicket() {
    if (!isFormValid()) return;
    createLoading.value = true;
    errorMsg.value = '';
    try {
        await new Promise((r) => setTimeout(r, 1000));
        const newTicket: SupportTicket = {
            id: `tk-${Date.now()}`,
            ticket_code: `TK-${String(tickets.value.length + 1).padStart(6, '0')}`,
            subject: form.value.subject,
            category: form.value.category as TicketCategory,
            status: 'open',
            priority: form.value.priority,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            message_count: 1,
        };
        tickets.value.unshift(newTicket);
        successMsg.value = `Ticket ${newTicket.ticket_code} đã được tạo thành công! Chúng tôi sẽ phản hồi trong vòng 24 giờ.`;
        form.value = { category: '', subject: '', body: '', booking_id: '', priority: 'normal' };
        activeTab.value = 'list';
        setTimeout(() => (successMsg.value = ''), 6000);
    } catch {
        errorMsg.value = 'Có lỗi xảy ra, vui lòng thử lại.';
    } finally {
        createLoading.value = false;
    }
}

onMounted(() => loadTickets());
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- ─── Hero Banner ─────────────────────────────────────────────── -->
        <div class="bg-gradient-to-r from-blue-800 via-blue-600 to-sky-500 px-6 py-10 md:px-12">
            <div class="mx-auto flex max-w-4xl flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight text-white">Trung tâm Hỗ trợ</h1>
                        <p class="mt-0.5 text-sm text-blue-100">Chúng tôi luôn sẵn sàng giải đáp mọi thắc mắc 24/7</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span> {{ openCount }} Chờ xử lý
                    </span>
                    <span class="flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-blue-300"></span> {{ inProgressCount }} Đang xử lý
                    </span>
                    <span class="flex items-center gap-1.5 rounded-full border border-white/25 bg-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span> {{ resolvedCount }} Đã giải quyết
                    </span>
                </div>
            </div>
        </div>

        <div class="mx-auto -mt-6 max-w-4xl px-4 pb-16">
            <!-- ─── Success Alert ──────────────────────────────────────── -->
            <transition enter-active-class="transition duration-300" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-200" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="successMsg" class="mb-4 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="flex-1">{{ successMsg }}</p>
                    <button @click="successMsg = ''" class="shrink-0 text-green-600 hover:text-green-800">✕</button>
                </div>
            </transition>

            <!-- ─── Tab Switcher ───────────────────────────────────────── -->
            <div class="mb-4 flex rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-slate-200">
                <button :class="['flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all', activeTab === 'list' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']" @click="activeTab = 'list'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Ticket của tôi
                    <span v-if="tickets.length" :class="['rounded-full px-2 py-0.5 text-xs font-bold', activeTab === 'list' ? 'bg-white/25 text-white' : 'bg-blue-100 text-blue-700']">{{ tickets.length }}</span>
                </button>
                <button :class="['flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all', activeTab === 'create' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']" @click="activeTab = 'create'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tạo yêu cầu mới
                </button>
            </div>

            <!-- ═══ TAB: TICKET LIST ══════════════════════════════════════ -->
            <div v-if="activeTab === 'list'">
                <!-- Filter -->
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        v-for="f in [{ value: 'all', label: 'Tất cả' }, { value: 'open', label: 'Chờ xử lý' }, { value: 'in_progress', label: 'Đang xử lý' }, { value: 'resolved', label: 'Đã giải quyết' }, { value: 'closed', label: 'Đã đóng' }]"
                        :key="f.value"
                        :class="['rounded-full px-4 py-1.5 text-xs font-medium transition-all border', statusFilter === f.value ? 'border-blue-500 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-blue-600']"
                        @click="statusFilter = f.value as any"
                    >
                        {{ f.label }}
                    </button>
                </div>

                <!-- Loading skeleton -->
                <div v-if="loading" class="space-y-3">
                    <div v-for="i in 3" :key="i" class="animate-pulse rounded-xl border border-slate-200 bg-white p-5">
                        <div class="mb-3 h-3 w-1/2 rounded bg-slate-200"></div>
                        <div class="h-2.5 w-2/5 rounded bg-slate-100"></div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else-if="filteredTickets.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                    <p class="text-4xl">🎫</p>
                    <h3 class="mt-3 text-base font-bold text-slate-800">Chưa có ticket nào</h3>
                    <p class="mt-1 text-sm text-slate-500">Tạo yêu cầu hỗ trợ để chúng tôi có thể giúp bạn nhanh nhất.</p>
                    <button class="mt-4 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700" @click="activeTab = 'create'">
                        Tạo yêu cầu đầu tiên
                    </button>
                </div>

                <!-- Ticket Cards -->
                <div v-else class="space-y-3">
                    <div
                        v-for="ticket in filteredTickets"
                        :key="ticket.id"
                        class="group cursor-pointer rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                        @click="router.push(`/support/${ticket.id}`)"
                    >
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-600">{{ ticket.ticket_code }}</span>
                                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadgeClass(ticket.status)]">{{ statusLabel(ticket.status) }}</span>
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-bold uppercase tracking-wide', priorityBadgeClass(ticket.priority)]">{{ priorityLabel(ticket.priority) }}</span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-slate-400">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ timeAgo(ticket.last_reply_at ?? ticket.updated_at) }}
                            </div>
                        </div>

                        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ ticket.subject }}</h3>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                            <span>{{ categoryIcon(ticket.category) }} {{ categoryLabel(ticket.category) }}</span>
                            <span v-if="ticket.message_count" class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                {{ ticket.message_count }} tin nhắn
                            </span>
                            <span v-if="ticket.booking_code">🎫 {{ ticket.booking_code }}</span>
                        </div>

                        <!-- in_progress indicator -->
                        <div v-if="ticket.status === 'in_progress'" class="mt-3 h-1 w-full overflow-hidden rounded-full bg-blue-100">
                            <div class="h-full w-3/5 animate-pulse rounded-full bg-blue-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB: CREATE TICKET ════════════════════════════════════ -->
            <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-base font-bold text-slate-900">Tạo yêu cầu hỗ trợ mới</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Mô tả chi tiết vấn đề để chúng tôi hỗ trợ bạn nhanh nhất</p>
                </div>

                <form @submit.prevent="submitTicket" class="space-y-6 p-6">
                    <!-- Error -->
                    <div v-if="errorMsg" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</div>

                    <!-- Category -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Loại yêu cầu <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <label
                                v-for="cat in categories"
                                :key="cat.value"
                                :class="['relative flex cursor-pointer flex-col gap-1 rounded-xl border-2 p-3.5 transition-all', form.category === cat.value ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-slate-50 hover:border-blue-200']"
                            >
                                <input type="radio" :value="cat.value" v-model="form.category" class="sr-only" />
                                <span class="text-2xl">{{ cat.icon }}</span>
                                <span class="text-xs font-semibold text-slate-800">{{ cat.label }}</span>
                                <span class="text-xs text-slate-500">{{ cat.desc }}</span>
                                <span v-if="form.category === cat.value" class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">✓</span>
                            </label>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label for="subject" class="mb-1.5 block text-sm font-semibold text-slate-700">Tiêu đề <span class="text-red-500">*</span></label>
                        <input
                            id="subject"
                            v-model="form.subject"
                            type="text"
                            maxlength="255"
                            placeholder="Mô tả ngắn gọn vấn đề của bạn..."
                            class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                        />
                        <p class="mt-1 text-right text-xs text-slate-400">{{ form.subject.length }}/255</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="body" class="mb-1.5 block text-sm font-semibold text-slate-700">Mô tả chi tiết <span class="text-red-500">*</span></label>
                        <textarea
                            id="body"
                            v-model="form.body"
                            rows="6"
                            placeholder="Hãy mô tả chi tiết vấn đề: thời gian xảy ra, những bước bạn đã thực hiện, kết quả mong muốn..."
                            class="w-full resize-y rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                        ></textarea>
                        <p class="mt-1 text-xs text-slate-400">Tối thiểu 20 ký tự ({{ form.body.length }})</p>
                    </div>

                    <!-- Booking link -->
                    <div>
                        <label for="booking" class="mb-1.5 block text-sm font-semibold text-slate-700">Đặt vé liên quan <span class="text-slate-400 font-normal">(tùy chọn)</span></label>
                        <select id="booking" v-model="form.booking_id" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            <option value="">— Không liên kết đặt vé —</option>
                            <option v-for="b in myBookings" :key="b.id" :value="b.id">{{ b.booking_code }} · {{ b.route }} · {{ b.date }}</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mức độ ưu tiên</label>
                        <div class="flex flex-wrap gap-2.5">
                            <label
                                v-for="p in [{ value: 'low', label: 'Thấp', icon: '🔵' }, { value: 'normal', label: 'Bình thường', icon: '🟢' }, { value: 'high', label: 'Cao', icon: '🟡' }, { value: 'urgent', label: 'Khẩn cấp', icon: '🔴' }]"
                                :key="p.value"
                                :class="['flex cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-2 text-sm font-medium transition-all', form.priority === p.value ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:border-blue-200']"
                            >
                                <input type="radio" :value="p.value" v-model="form.priority" class="sr-only" />
                                <span>{{ p.icon }}</span>
                                <span>{{ p.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50" @click="activeTab = 'list'">Hủy</button>
                        <button
                            type="submit"
                            :disabled="!isFormValid() || createLoading"
                            :class="['inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition', isFormValid() && !createLoading ? 'bg-blue-600 hover:bg-blue-700' : 'cursor-not-allowed bg-blue-300']"
                        >
                            <svg v-if="createLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ createLoading ? 'Đang gửi...' : 'Gửi yêu cầu hỗ trợ' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ─── Quick Help ─────────────────────────────────────────── -->
            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-bold text-slate-900">Câu hỏi thường gặp</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <a v-for="faq in [{ icon: '⏱️', q: 'Thời gian phản hồi?', a: 'Trong vòng 24 giờ làm việc' }, { icon: '💸', q: 'Chính sách hoàn tiền?', a: 'Hoàn 100% khi hủy trước 24h' }, { icon: '📞', q: 'Hotline khẩn cấp?', a: '1900 xxxx (24/7)' }]" :key="faq.q" href="#" class="flex items-start gap-3 rounded-xl border border-slate-100 p-3.5 transition hover:border-blue-200 hover:bg-blue-50">
                        <span class="text-xl">{{ faq.icon }}</span>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">{{ faq.q }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ faq.a }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
