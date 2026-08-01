<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { adminApi } from '@/api/admin.api';
import type {
    SupportMessage,
    SupportTicket,
    SupportUser,
    TicketPriority,
    TicketStatus,
} from '@/types/support';

type AdminSupportTicket = SupportTicket & {
    user: SupportUser;
    messages: SupportMessage[];
};

interface AdminStaffOption {
    id: string;
    full_name: string;
}

const route = useRoute();
const router = useRouter();
const ticket = ref<AdminSupportTicket | null>(null);
const loading = ref(true);
const replyText = ref('');
const replyLoading = ref(false);
const isInternal = ref(false);
const statusLoading = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const messagesEnd = ref<HTMLElement | null>(null);
const newStatus = ref<TicketStatus>('open');
const newPriority = ref<TicketPriority>('normal');
const showStatusPanel = ref(false);
const staff = ref<AdminStaffOption[]>([]);
const assignedTo = ref('');
const assignLoading = ref(false);
const canPublicReply = computed(
    () =>
        ticket.value?.status === 'open' ||
        ticket.value?.status === 'in_progress',
);

// ─── Computed ────────────────────────────────────────────────────────────────
const statusLabel = computed(
    () =>
        ({
            open: 'Chờ xử lý',
            in_progress: 'Đang xử lý',
            resolved: 'Đã giải quyết',
            closed: 'Đã đóng',
        })[ticket.value?.status ?? 'open'],
);
const statusBadgeClass = computed(
    () =>
        ({
            open: 'bg-yellow-100 text-yellow-700',
            in_progress: 'bg-blue-100 text-blue-700',
            resolved: 'bg-green-100 text-green-700',
            closed: 'bg-slate-100 text-slate-600',
        })[ticket.value?.status ?? 'open'],
);
const priorityLabel = computed(
    () =>
        ({
            low: 'Thấp',
            normal: 'Bình thường',
            high: 'Cao',
            urgent: 'Khẩn cấp',
        })[ticket.value?.priority ?? 'normal'],
);

const categoryMap: Record<string, { label: string; icon: string }> = {
    general: { label: 'Câu hỏi chung', icon: '💬' },
    payment: { label: 'Vấn đề thanh toán', icon: '💳' },
    refund: { label: 'Yêu cầu hoàn tiền', icon: '💰' },
    complaint: { label: 'Khiếu nại dịch vụ', icon: '📢' },
    technical: { label: 'Lỗi kỹ thuật', icon: '🔧' },
    other: { label: 'Khác', icon: '📋' },
};
const categoryInfo = computed(
    () => categoryMap[ticket.value?.category ?? 'other'] ?? categoryMap.other,
);

// ─── Methods ─────────────────────────────────────────────────────────────────
function formatTime(dateStr: string) {
    return new Date(dateStr).toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
function timeAgo(dateStr: string) {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Vừa xong';
    if (mins < 60) return `${mins} phút trước`;
    const h = Math.floor(mins / 60);
    if (h < 24) return `${h} giờ trước`;
    return formatTime(dateStr);
}
function scrollToBottom() {
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
}

async function loadTicket() {
    loading.value = true;
    errorMsg.value = '';
    const { data, error } = await adminApi.getSupportTicket(
        String(route.params.id),
    );
    ticket.value = (data as AdminSupportTicket | null) ?? null;
    if (ticket.value) {
        newStatus.value = ticket.value.status;
        newPriority.value = ticket.value.priority;
        assignedTo.value = ticket.value.assigned_to ?? '';
        if (!canPublicReply.value) isInternal.value = true;
    }
    if (error) errorMsg.value = error;
    loading.value = false;
    await nextTick();
    scrollToBottom();
}
async function loadStaff() {
    const { data } = await adminApi.getAdminStaff({ status: 'active' });
    staff.value = ((data as any[]) ?? []).map((item) => ({
        id: item.id,
        full_name: item.full_name,
    }));
}
async function sendReply() {
    if (!replyText.value.trim() || replyLoading.value) return;
    if (!isInternal.value && !canPublicReply.value) {
        errorMsg.value =
            'Ticket đã giải quyết hoặc đóng chỉ cho phép ghi chú nội bộ.';
        return;
    }
    replyLoading.value = true;
    errorMsg.value = '';
    const body = replyText.value.trim();
    try {
        const { data, error } = await adminApi.replySupportTicket(
            ticket.value!.id,
            { body, is_internal: isInternal.value },
        );
        if (error || !data) {
            errorMsg.value = error ?? 'Gửi phản hồi thất bại.';
            return;
        }
        ticket.value!.messages.push(data as SupportMessage);
        if (!isInternal.value && ticket.value!.status === 'open') {
            ticket.value!.status = 'in_progress';
            newStatus.value = 'in_progress';
        }
        replyText.value = '';
        await nextTick();
        scrollToBottom();
        successMsg.value = isInternal.value
            ? 'Ghi chú nội bộ đã được lưu.'
            : 'Phản hồi đã được gửi đến khách hàng.';
        setTimeout(() => (successMsg.value = ''), 4000);
    } finally {
        replyLoading.value = false;
    }
}
async function updateStatusAndPriority() {
    if (!ticket.value) return;
    statusLoading.value = true;
    errorMsg.value = '';
    try {
        const { data, error } = await adminApi.updateSupportTicket(
            ticket.value.id,
            { status: newStatus.value, priority: newPriority.value },
        );
        if (error || !data) {
            errorMsg.value = error ?? 'Cập nhật thất bại.';
            return;
        }
        ticket.value = {
            ...ticket.value,
            ...(data as SupportTicket),
        };
        showStatusPanel.value = false;
        successMsg.value = 'Đã cập nhật trạng thái và mức độ ưu tiên.';
        setTimeout(() => (successMsg.value = ''), 3000);
    } finally {
        statusLoading.value = false;
    }
}

async function assignTicket() {
    if (!ticket.value || !assignedTo.value) return;
    assignLoading.value = true;
    errorMsg.value = '';
    const { error } = await adminApi.assignSupportTicket(
        ticket.value.id,
        assignedTo.value,
    );
    assignLoading.value = false;
    if (error) {
        errorMsg.value = error;
        return;
    }
    ticket.value.assigned_to = assignedTo.value;
    ticket.value.assignee =
        staff.value.find((item) => item.id === assignedTo.value) ?? null;
    successMsg.value = 'Đã phân công nhân viên xử lý.';
}

onMounted(() => {
    void Promise.all([loadTicket(), loadStaff()]);
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 pb-16">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 px-6 py-4 text-sm">
            <button
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-violet-600 transition hover:bg-violet-50"
                @click="router.push('/admin/support')"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>
                Quản lý Hỗ trợ
            </button>
            <span class="text-slate-300">/</span>
            <span v-if="ticket" class="font-mono text-slate-500">{{
                ticket.ticket_code
            }}</span>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="flex min-h-[60vh] flex-col items-center justify-center gap-3 text-slate-500"
        >
            <svg
                class="h-10 w-10 animate-spin text-violet-500"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                ></path>
            </svg>
            <p class="text-sm">Đang tải ticket...</p>
        </div>

        <div
            v-else-if="ticket"
            class="mx-auto grid max-w-6xl gap-4 px-6 lg:grid-cols-[1fr_280px]"
        >
            <!-- ─── Left: Chat Column ───────────────────────────────────── -->
            <main class="space-y-4">
                <!-- Chat Header -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
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
                                        statusBadgeClass,
                                    ]"
                                    >{{ statusLabel }}</span
                                >
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                                    >{{ priorityLabel }}</span
                                >
                            </div>
                            <h1 class="text-base font-bold text-slate-900">
                                {{ ticket.subject }}
                            </h1>
                        </div>
                        <button
                            class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-600"
                            @click="showStatusPanel = !showStatusPanel"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                            Cập nhật
                        </button>
                    </div>

                    <!-- Status panel -->
                    <transition
                        enter-active-class="transition duration-200"
                        enter-from-class="opacity-0 -translate-y-1"
                        leave-active-class="transition duration-150"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="showStatusPanel"
                            class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50 p-4"
                        >
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                        >Trạng thái</label
                                    >
                                    <select
                                        v-model="newStatus"
                                        :disabled="ticket.status === 'closed'"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200"
                                    >
                                        <option
                                            value="open"
                                            :disabled="ticket.status !== 'open'"
                                        >
                                            🟡 Chờ xử lý
                                        </option>
                                        <option value="in_progress">
                                            🔵 Đang xử lý
                                        </option>
                                        <option value="resolved">
                                            🟢 Đã giải quyết
                                        </option>
                                        <option value="closed">
                                            ⚫ Đã đóng
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                        >Mức độ ưu tiên</label
                                    >
                                    <select
                                        v-model="newPriority"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-200"
                                    >
                                        <option value="low">🔵 Thấp</option>
                                        <option value="normal">
                                            🟢 Bình thường
                                        </option>
                                        <option value="high">🟡 Cao</option>
                                        <option value="urgent">
                                            🔴 Khẩn cấp
                                        </option>
                                    </select>
                                </div>
                                <div class="flex items-end gap-2">
                                    <button
                                        class="flex-1 rounded-lg border border-slate-300 bg-white py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                                        @click="showStatusPanel = false"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        :disabled="statusLoading"
                                        :class="[
                                            'flex flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-sm font-semibold text-white shadow-sm transition',
                                            statusLoading
                                                ? 'cursor-not-allowed bg-violet-300'
                                                : 'bg-violet-600 hover:bg-violet-700',
                                        ]"
                                        @click="updateStatusAndPriority"
                                    >
                                        <svg
                                            v-if="statusLoading"
                                            class="h-4 w-4 animate-spin"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                            ></path>
                                        </svg>
                                        {{
                                            statusLoading
                                                ? 'Đang lưu...'
                                                : 'Lưu thay đổi'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Alerts -->
                <transition
                    enter-active-class="transition duration-200"
                    enter-from-class="opacity-0 -translate-y-1"
                    leave-active-class="transition duration-150"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="successMsg"
                        class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                    >
                        <svg
                            class="h-4 w-4 shrink-0 text-green-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ successMsg }}
                    </div>
                </transition>
                <div
                    v-if="errorMsg"
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ errorMsg }}
                </div>

                <!-- Messages Thread -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="space-y-6">
                        <div
                            v-for="msg in ticket.messages"
                            :key="msg.id"
                            :class="[
                                'flex gap-3',
                                msg.sender_type === 'admin'
                                    ? 'flex-row-reverse'
                                    : '',
                            ]"
                        >
                            <!-- Avatar -->
                            <div
                                :class="[
                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white',
                                    msg.sender_type === 'admin'
                                        ? 'bg-gradient-to-br from-violet-600 to-purple-600'
                                        : 'bg-gradient-to-br from-sky-500 to-cyan-500',
                                ]"
                            >
                                {{ msg.sender_name.charAt(0) }}
                            </div>
                            <!-- Bubble -->
                            <div
                                :class="[
                                    'flex max-w-[80%] flex-col',
                                    msg.sender_type === 'admin'
                                        ? 'items-end'
                                        : '',
                                ]"
                            >
                                <div
                                    :class="[
                                        'mb-1 flex flex-wrap items-center gap-2',
                                        msg.sender_type === 'admin'
                                            ? 'justify-end'
                                            : '',
                                    ]"
                                >
                                    <span
                                        class="text-xs font-semibold text-slate-700"
                                        >{{ msg.sender_name }}</span
                                    >
                                    <span
                                        v-if="msg.sender_type === 'admin'"
                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-600"
                                        >Admin</span
                                    >
                                    <span
                                        v-if="msg.is_internal"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700"
                                        >Nội bộ</span
                                    >
                                    <span class="text-xs text-slate-400">{{
                                        timeAgo(msg.created_at)
                                    }}</span>
                                </div>
                                <div
                                    :class="[
                                        'rounded-2xl px-4 py-3 text-sm leading-relaxed break-words whitespace-pre-wrap',
                                        msg.sender_type === 'admin'
                                            ? msg.is_internal
                                                ? 'rounded-tr-sm border-2 border-dashed border-amber-300 bg-amber-50 text-amber-900'
                                                : 'rounded-tr-sm bg-gradient-to-br from-violet-600 to-indigo-600 text-white'
                                            : 'rounded-tl-sm bg-slate-100 text-slate-800',
                                    ]"
                                >
                                    {{ msg.body }}
                                </div>
                            </div>
                        </div>
                        <div ref="messagesEnd"></div>
                    </div>
                </div>

                <!-- Reply Box -->
                <div
                    class="rounded-xl border border-indigo-200 bg-white p-5 shadow-sm"
                >
                    <!-- Toggle -->
                    <div class="mb-4 flex rounded-xl bg-slate-100 p-1">
                        <button
                            :disabled="!canPublicReply"
                            :class="[
                                'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all',
                                !isInternal
                                    ? 'bg-violet-600 text-white shadow'
                                    : 'text-slate-500 hover:text-slate-800',
                            ]"
                            @click="isInternal = false"
                        >
                            <svg
                                class="h-4 w-4"
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
                            Phản hồi khách hàng
                        </button>
                        <button
                            :class="[
                                'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all',
                                isInternal
                                    ? 'bg-amber-500 text-white shadow'
                                    : 'text-slate-500 hover:text-slate-800',
                            ]"
                            @click="isInternal = true"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                            Ghi chú nội bộ
                        </button>
                    </div>

                    <textarea
                        v-model="replyText"
                        rows="4"
                        :placeholder="
                            isInternal
                                ? 'Ghi chú nội bộ — khách hàng sẽ không thấy nội dung này...'
                                : 'Nhập phản hồi gửi đến khách hàng... (Ctrl+Enter để gửi)'
                        "
                        :class="[
                            'w-full resize-y rounded-lg border px-4 py-3 text-sm text-slate-900 transition outline-none',
                            isInternal
                                ? 'border-amber-300 bg-amber-50 focus:border-amber-400 focus:ring-2 focus:ring-amber-200'
                                : 'border-slate-300 bg-slate-50 focus:border-violet-400 focus:bg-white focus:ring-2 focus:ring-violet-200',
                        ]"
                        @keydown.ctrl.enter="sendReply"
                    ></textarea>

                    <div
                        class="mt-3 flex flex-wrap items-center justify-between gap-3"
                    >
                        <span
                            v-if="isInternal"
                            class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700"
                            >🔒 Chỉ admin thấy ghi chú này</span
                        >
                        <span
                            v-else
                            class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700"
                            >📤 Sẽ gửi đến khách hàng</span
                        >
                        <button
                            :disabled="!replyText.trim() || replyLoading"
                            :class="[
                                'inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition',
                                !replyText.trim() || replyLoading
                                    ? 'cursor-not-allowed opacity-50'
                                    : isInternal
                                      ? 'bg-amber-500 hover:bg-amber-600'
                                      : 'bg-violet-600 hover:bg-violet-700',
                            ]"
                            @click="sendReply"
                        >
                            <svg
                                v-if="replyLoading"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                ></path>
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                                />
                            </svg>
                            {{
                                replyLoading
                                    ? 'Đang gửi...'
                                    : isInternal
                                      ? 'Lưu ghi chú'
                                      : 'Gửi phản hồi'
                            }}
                        </button>
                    </div>
                </div>
            </main>

            <!-- ─── Right: Info Panel ──────────────────────────────────── -->
            <aside class="space-y-4">
                <!-- User info -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h3
                        class="mb-4 border-b border-slate-100 pb-3 text-sm font-bold text-gray-900"
                    >
                        Thông tin khách hàng
                    </h3>
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-cyan-500 text-base font-bold text-white"
                        >
                            {{ ticket.user.full_name.charAt(0) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">
                                {{ ticket.user.full_name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ ticket.user.phone }}
                            </p>
                            <p
                                v-if="ticket.user.email"
                                class="text-xs text-slate-400"
                            >
                                {{ ticket.user.email }}
                            </p>
                        </div>
                    </div>
                    <a
                        :href="`/admin/users?id=${ticket.user.id}`"
                        class="block rounded-lg border border-slate-200 py-2 text-center text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50"
                    >
                        Xem hồ sơ khách hàng →
                    </a>
                </div>

                <!-- Ticket info -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h3
                        class="mb-4 border-b border-slate-100 pb-3 text-sm font-bold text-gray-900"
                    >
                        Chi tiết ticket
                    </h3>
                    <div class="mb-4">
                        <label
                            for="support-assignee"
                            class="mb-1 block text-xs font-medium tracking-wide text-slate-400 uppercase"
                        >
                            Nhân viên xử lý
                        </label>
                        <div class="flex gap-2">
                            <select
                                id="support-assignee"
                                v-model="assignedTo"
                                :disabled="ticket.status === 'closed'"
                                class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs text-slate-700 disabled:bg-slate-100"
                            >
                                <option value="">Chưa phân công</option>
                                <option
                                    v-for="member in staff"
                                    :key="member.id"
                                    :value="member.id"
                                >
                                    {{ member.full_name }}
                                </option>
                            </select>
                            <button
                                :disabled="
                                    !assignedTo ||
                                    assignLoading ||
                                    ticket.status === 'closed'
                                "
                                class="rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                                @click="assignTicket"
                            >
                                {{ assignLoading ? 'Lưu...' : 'Giao' }}
                            </button>
                        </div>
                    </div>
                    <dl class="space-y-3">
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Mã ticket
                            </dt>
                            <dd
                                class="inline-block rounded bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-600"
                            >
                                {{ ticket.ticket_code }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Trạng thái
                            </dt>
                            <dd>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        statusBadgeClass,
                                    ]"
                                    >{{ statusLabel }}</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Mức độ
                            </dt>
                            <dd class="text-sm font-medium text-slate-700">
                                {{ priorityLabel }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Danh mục
                            </dt>
                            <dd class="text-sm text-slate-700">
                                {{ categoryInfo.icon }} {{ categoryInfo.label }}
                            </dd>
                        </div>
                        <div v-if="ticket.booking_code">
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Mã đặt vé
                            </dt>
                            <dd class="text-sm font-medium text-cyan-600">
                                {{ ticket.booking_code }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Ngày tạo
                            </dt>
                            <dd class="text-xs text-slate-600">
                                {{ formatTime(ticket.created_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="mb-1 text-xs font-medium tracking-wide text-slate-400 uppercase"
                            >
                                Số tin nhắn
                            </dt>
                            <dd class="text-sm font-medium text-slate-700">
                                {{ ticket.messages.length }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Actions -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-3 text-sm font-bold text-gray-900">
                        Thao tác nhanh
                    </h3>
                    <div class="space-y-2">
                        <button
                            v-if="ticket.status === 'open'"
                            class="w-full rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-left text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                            @click="
                                newStatus = 'in_progress';
                                newPriority = ticket.priority;
                                updateStatusAndPriority();
                            "
                        >
                            ▶ Bắt đầu xử lý
                        </button>
                        <button
                            v-if="
                                ticket.status !== 'resolved' &&
                                ticket.status !== 'closed'
                            "
                            class="w-full rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-left text-sm font-semibold text-green-700 transition hover:bg-green-100"
                            @click="
                                newStatus = 'resolved';
                                newPriority = ticket.priority;
                                updateStatusAndPriority();
                            "
                        >
                            ✓ Đánh dấu đã giải quyết
                        </button>
                        <button
                            v-if="ticket.status !== 'closed'"
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                            @click="
                                newStatus = 'closed';
                                newPriority = ticket.priority;
                                updateStatusAndPriority();
                            "
                        >
                            ✕ Đóng ticket
                        </button>
                        <button
                            v-if="ticket.status === 'resolved'"
                            class="w-full rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-left text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                            @click="
                                newStatus = 'in_progress';
                                newPriority = ticket.priority;
                                updateStatusAndPriority();
                            "
                        >
                            ↺ Tiếp tục xử lý
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
