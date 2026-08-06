<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '@/api/admin.api';
import { supportCategories } from '@/types/support';
import type {
    SupportStats,
    SupportTicket,
    SupportUser,
    TicketCategory,
    TicketPriority,
    TicketStatus,
} from '@/types/support';

const router = useRouter();

type AdminSupportTicket = SupportTicket & {
    user: SupportUser;
    message_count: number;
};

const tickets = ref<AdminSupportTicket[]>([]);
const loading = ref(true);
const errorMsg = ref('');
const search = ref('');
const statusFilter = ref<'all' | TicketStatus>('all');
const categoryFilter = ref<'all' | TicketCategory>('all');
const priorityFilter = ref<'all' | TicketPriority>('all');
const stats = ref<SupportStats>({
    open: 0,
    in_progress: 0,
    resolved: 0,
    closed: 0,
});
const currentPage = ref(1);
const lastPage = ref(1);


const hasActiveFilter = computed(
    () =>
        statusFilter.value !== 'all' ||
        categoryFilter.value !== 'all' ||
        priorityFilter.value !== 'all' ||
        !!search.value,
);

const categories = supportCategories;

// ─── Methods ────────────────────────────────────────────────────────────────
function statusLabel(s: TicketStatus) {
    return {
        open: 'Chờ xử lý',
        in_progress: 'Đang xử lý',
        resolved: 'Đã giải quyết',
        closed: 'Đã đóng',
    }[s];
}
function statusBadgeClass(s: TicketStatus) {
    return {
        open: 'bg-yellow-100 text-yellow-700',
        in_progress: 'bg-blue-100 text-blue-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-slate-100 text-slate-600',
    }[s];
}
function priorityBarClass(p: TicketPriority) {
    return {
        low: 'bg-slate-300',
        normal: 'bg-blue-500',
        high: 'bg-amber-400',
        urgent: 'bg-red-500',
    }[p];
}
function priorityLabel(p: TicketPriority) {
    return { low: 'Thấp', normal: 'BT', high: 'Cao', urgent: 'Khẩn' }[p];
}
function priorityBadgeClass(p: TicketPriority) {
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
    const h = Math.floor(mins / 60);
    if (h < 24) return `${h} giờ trước`;
    return `${Math.floor(h / 24)} ngày trước`;
}

async function fetchTickets(page = 1) {
    loading.value = true;
    errorMsg.value = '';
    const { data, meta, error } = await adminApi.getSupportTickets({
        page,
        ...(search.value.trim() ? { search: search.value.trim() } : {}),
        ...(statusFilter.value !== 'all' ? { status: statusFilter.value } : {}),
        ...(categoryFilter.value !== 'all'
            ? { category: categoryFilter.value }
            : {}),
        ...(priorityFilter.value !== 'all'
            ? { priority: priorityFilter.value }
            : {}),
    });
    tickets.value = (data as AdminSupportTicket[] | null) ?? [];
    if (meta?.stats) stats.value = meta.stats as SupportStats;
    currentPage.value = meta?.current_page ?? 1;
    lastPage.value = meta?.last_page ?? 1;
    if (error) errorMsg.value = error;
    loading.value = false;
}

watchDebounced(search, () => void fetchTickets(), { debounce: 400 });
watch(
    [statusFilter, categoryFilter, priorityFilter],
    () => void fetchTickets(),
);
onMounted(() => void fetchTickets());
</script>

<template>
    <div class="p-6">
        <!-- ─── Page Header ─────────────────────────────────────────────── -->
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-purple-600 text-2xl shadow-lg shadow-violet-500/30"
                >
                    🎧
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        Quản lý Hỗ trợ
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Theo dõi và xử lý yêu cầu hỗ trợ từ khách hàng
                    </p>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─────────────────────────────────────────────────────── -->
        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="s in [
                    {
                        key: 'open',
                        label: 'Chờ xử lý',
                        count: stats.open,
                        dot: 'bg-amber-400',
                        text: 'text-amber-600',
                        ring: 'ring-amber-200',
                    },
                    {
                        key: 'in_progress',
                        label: 'Đang xử lý',
                        count: stats.in_progress,
                        dot: 'bg-blue-400',
                        text: 'text-blue-600',
                        ring: 'ring-blue-200',
                    },
                    {
                        key: 'resolved',
                        label: 'Đã giải quyết',
                        count: stats.resolved,
                        dot: 'bg-green-400',
                        text: 'text-green-600',
                        ring: 'ring-green-200',
                    },
                    {
                        key: 'closed',
                        label: 'Đã đóng',
                        count: stats.closed,
                        dot: 'bg-slate-400',
                        text: 'text-slate-500',
                        ring: 'ring-slate-200',
                    },
                ]"
                :key="s.key"
                :class="[
                    'cursor-pointer rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md',
                    statusFilter === s.key ? `ring-2 ${s.ring}` : '',
                ]"
                @click="
                    statusFilter =
                        s.key === statusFilter ? 'all' : (s.key as any)
                "
            >
                <div class="mb-2 flex items-center gap-2">
                    <span :class="['h-2 w-2 rounded-full', s.dot]"></span>
                    <span
                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >{{ s.label }}</span
                    >
                </div>
                <p :class="['text-3xl font-bold', s.text]">{{ s.count }}</p>
            </div>
        </div>

        <!-- ─── Filters ─────────────────────────────────────────────────── -->
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div
                class="flex min-w-[200px] flex-1 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 transition focus-within:border-violet-400 focus-within:ring-2 focus-within:ring-violet-200"
            >
                <svg
                    class="h-4 w-4 shrink-0 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Tìm mã ticket, tiêu đề, khách hàng..."
                    class="flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400"
                />
                <button
                    v-if="search"
                    class="text-slate-400 transition hover:text-slate-700"
                    @click="search = ''"
                >
                    ✕
                </button>
            </div>
            <!-- Category -->
            <select
                v-model="categoryFilter"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200"
            >
                <option value="all">Tất cả danh mục</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">
                    {{ c.icon }} {{ c.label }}
                </option>
            </select>
            <!-- Priority -->
            <select
                v-model="priorityFilter"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200"
            >
                <option value="all">Tất cả mức độ</option>
                <option value="urgent">🔴 Khẩn cấp</option>
                <option value="high">🟡 Cao</option>
                <option value="normal">🟢 Bình thường</option>
                <option value="low">🔵 Thấp</option>
            </select>
            <!-- Clear -->
            <button
                v-if="hasActiveFilter"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                @click="
                    statusFilter = 'all';
                    categoryFilter = 'all';
                    priorityFilter = 'all';
                    search = '';
                "
            >
                Xóa lọc
            </button>
        </div>

        <div
            v-if="errorMsg"
            class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ errorMsg }}
        </div>

        <!-- ─── Table Card ─────────────────────────────────────────────────── -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div
                class="flex items-center gap-2 border-b border-slate-100 px-5 py-3"
            >
                <span class="text-sm text-slate-500"
                    >{{ tickets.length }} ticket</span
                >
                <span
                    v-if="statusFilter !== 'all'"
                    :class="[
                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                        statusBadgeClass(statusFilter as TicketStatus),
                    ]"
                    >{{ statusLabel(statusFilter as TicketStatus) }}</span
                >
            </div>

            <!-- Loading -->
            <div v-if="loading" class="divide-y divide-slate-100">
                <div v-for="i in 5" :key="i" class="animate-pulse p-5">
                    <div class="mb-2 h-3 w-3/5 rounded bg-slate-200"></div>
                    <div class="h-2.5 w-2/5 rounded bg-slate-100"></div>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else-if="tickets.length === 0"
                class="py-16 text-center"
            >
                <p class="text-4xl">🎫</p>
                <p class="mt-3 text-base font-bold text-slate-800">
                    Không tìm thấy ticket
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    Thử điều chỉnh bộ lọc hoặc từ khóa tìm kiếm
                </p>
            </div>

            <!-- Rows -->
            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="ticket in tickets"
                    :key="ticket.id"
                    class="group flex cursor-pointer items-stretch transition hover:bg-slate-50"
                    @click="router.push(`/admin/support/${ticket.id}`)"
                >
                    <!-- Priority bar -->
                    <div
                        :class="[
                            'w-1 shrink-0',
                            priorityBarClass(ticket.priority),
                        ]"
                    ></div>

                    <div
                        class="flex flex-1 flex-wrap items-center justify-between gap-4 px-5 py-4"
                    >
                        <!-- Left -->
                        <div class="min-w-0 flex-1">
                            <div
                                class="mb-1.5 flex flex-wrap items-center gap-2"
                            >
                                <span
                                    class="rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-600"
                                    >{{ ticket.ticket_code }}</span
                                >
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        statusBadgeClass(ticket.status),
                                    ]"
                                    >{{ statusLabel(ticket.status) }}</span
                                >
                                <span
                                    :class="[
                                        'rounded-full px-2 py-0.5 text-xs font-bold tracking-wide uppercase',
                                        priorityBadgeClass(ticket.priority),
                                    ]"
                                    >{{ priorityLabel(ticket.priority) }}</span
                                >
                            </div>
                            <h3
                                class="mb-1.5 truncate text-sm font-semibold text-slate-800"
                            >
                                {{ ticket.subject }}
                            </h3>
                            <div
                                class="flex flex-wrap items-center gap-3 text-xs text-slate-500"
                            >
                                <span
                                    >{{ categoryIcon(ticket.category) }}
                                    {{ categoryLabel(ticket.category) }}</span
                                >
                                <span
                                    v-if="ticket.booking_code"
                                    class="text-cyan-600"
                                    >🎫 {{ ticket.booking_code }}</span
                                >
                                <span class="flex items-center gap-1">
                                    <svg
                                        class="h-3 w-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                        />
                                    </svg>
                                    {{ ticket.message_count }}
                                </span>
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-cyan-500 text-xs font-bold text-white"
                                >
                                    {{ ticket.user.full_name.charAt(0) }}
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-xs font-semibold text-slate-700"
                                    >
                                        {{ ticket.user.full_name }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ ticket.user.phone }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-1 text-xs text-slate-400"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{
                                    timeAgo(
                                        ticket.last_reply_at ??
                                            ticket.updated_at,
                                    )
                                }}
                            </div>
                            <span
                                class="flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 transition group-hover:bg-indigo-100 group-hover:text-indigo-600"
                            >
                                Xem
                                <svg
                                    class="h-3 w-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="lastPage > 1"
                class="flex items-center justify-center gap-3 border-t border-slate-100 px-5 py-4"
            >
                <button
                    :disabled="currentPage <= 1"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="fetchTickets(currentPage - 1)"
                >
                    Trang trước
                </button>
                <span class="text-sm text-slate-500">
                    {{ currentPage }}/{{ lastPage }}
                </span>
                <button
                    :disabled="currentPage >= lastPage"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="fetchTickets(currentPage + 1)"
                >
                    Trang sau
                </button>
            </div>
        </div>
    </div>
</template>
