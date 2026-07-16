<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const router = useRouter();
const auth = useCustomerAuthStore();

// ─── Types ─────────────────────────────────────────────────────────────────
type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
type SenderType = 'customer' | 'admin';

interface SupportMessage {
    id: string;
    sender_type: SenderType;
    sender_name: string;
    body: string;
    created_at: string;
}

interface SupportTicket {
    id: string;
    ticket_code: string;
    subject: string;
    category: string;
    status: TicketStatus;
    priority: string;
    created_at: string;
    updated_at: string;
    booking_code?: string;
    messages: SupportMessage[];
}

// ─── State ─────────────────────────────────────────────────────────────────
const ticket = ref<SupportTicket | null>(null);
const loading = ref(true);
const replyText = ref('');
const replyLoading = ref(false);
const closeLoading = ref(false);
const showCloseConfirm = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const messagesEnd = ref<HTMLElement | null>(null);

const mockTicket: SupportTicket = {
    id: 'tk-001',
    ticket_code: 'TK-000001',
    subject: 'Không nhận được vé sau khi thanh toán',
    category: 'payment',
    status: 'in_progress',
    priority: 'high',
    created_at: '2024-06-20T10:30:00Z',
    updated_at: '2024-06-20T14:00:00Z',
    booking_code: 'XG-20240620',
    messages: [
        {
            id: 'msg-001',
            sender_type: 'customer',
            sender_name: 'Nguyễn Văn An',
            body: 'Xin chào, tôi đã thanh toán thành công qua MoMo lúc 10:25 sáng nay nhưng chưa nhận được vé điện tử. Mã giao dịch MoMo là: 20240620102534. Xin hãy kiểm tra giúp tôi.',
            created_at: '2024-06-20T10:30:00Z',
        },
        {
            id: 'msg-002',
            sender_type: 'admin',
            sender_name: 'Nhân viên hỗ trợ',
            body: 'Xin chào anh/chị! Cảm ơn đã liên hệ với XeGhep.vn. Chúng tôi đang kiểm tra giao dịch với mã code XG-20240620. Vui lòng chờ 15-30 phút để xác minh.',
            created_at: '2024-06-20T11:00:00Z',
        },
        {
            id: 'msg-003',
            sender_type: 'customer',
            sender_name: 'Nguyễn Văn An',
            body: 'Cảm ơn bạn. Tôi sẽ chờ. Nhưng chuyến đi của tôi là 2 giờ chiều hôm nay, bạn có thể xử lý nhanh hơn không?',
            created_at: '2024-06-20T11:15:00Z',
        },
        {
            id: 'msg-004',
            sender_type: 'admin',
            sender_name: 'Nhân viên hỗ trợ',
            body: 'Chúng tôi hiểu sự cấp bách. Nhóm kỹ thuật đang ưu tiên xử lý ngay. Bạn có thể xuất trình màn hình xác nhận thanh toán MoMo tại quầy lúc lên xe!',
            created_at: '2024-06-20T11:20:00Z',
        },
    ],
};

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
            open: 'bg-yellow-100 text-yellow-700 border border-yellow-200',
            in_progress: 'bg-blue-100 text-blue-700 border border-blue-200',
            resolved: 'bg-green-100 text-green-700 border border-green-200',
            closed: 'bg-slate-100 text-slate-600 border border-slate-200',
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
const canReply = computed(
    () =>
        ticket.value &&
        ticket.value.status !== 'closed' &&
        ticket.value.status !== 'resolved',
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
    await new Promise((r) => setTimeout(r, 600));
    ticket.value = mockTicket;
    loading.value = false;
    await nextTick();
    scrollToBottom();
}
async function sendReply() {
    if (!replyText.value.trim() || replyLoading.value) return;
    replyLoading.value = true;
    errorMsg.value = '';
    try {
        await new Promise((r) => setTimeout(r, 700));
        ticket.value!.messages.push({
            id: `msg-${Date.now()}`,
            sender_type: 'customer',
            sender_name: auth.user?.full_name ?? 'Bạn',
            body: replyText.value,
            created_at: new Date().toISOString(),
        });
        replyText.value = '';
        await nextTick();
        scrollToBottom();
    } catch {
        errorMsg.value = 'Gửi tin nhắn thất bại. Vui lòng thử lại.';
    } finally {
        replyLoading.value = false;
    }
}
async function closeTicket() {
    closeLoading.value = true;
    try {
        await new Promise((r) => setTimeout(r, 700));
        ticket.value!.status = 'closed';
        showCloseConfirm.value = false;
        successMsg.value = 'Ticket đã được đóng. Cảm ơn bạn đã liên hệ!';
        setTimeout(() => (successMsg.value = ''), 4000);
    } catch {
        errorMsg.value = 'Có lỗi xảy ra. Vui lòng thử lại.';
    } finally {
        closeLoading.value = false;
    }
}

onMounted(() => loadTicket());
</script>

<template>
    <div class="min-h-dvh bg-slate-50 pb-16">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 px-4 py-4 text-sm">
            <button
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-blue-600 transition hover:bg-blue-50"
                @click="router.push('/support')"
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
                Trung tâm Hỗ trợ
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
                class="h-10 w-10 animate-spin text-blue-500"
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
            <p class="text-sm">Đang tải thông tin ticket...</p>
        </div>

        <div
            v-else-if="ticket"
            class="mx-auto grid max-w-5xl gap-4 px-4 md:grid-cols-[260px_1fr]"
        >
            <!-- ─── Sidebar ─────────────────────────────────────────────── -->
            <aside class="space-y-4">
                <!-- Info card -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h2
                        class="mb-4 border-b border-slate-100 pb-3 text-sm font-bold text-slate-900"
                    >
                        Thông tin ticket
                    </h2>
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
                                Đặt vé
                            </dt>
                            <dd class="text-sm font-medium text-cyan-600">
                                🎫 {{ ticket.booking_code }}
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
                    </dl>
                </div>

                <!-- Close ticket -->
                <div
                    v-if="canReply"
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <button
                        class="w-full rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-semibold text-green-700 transition hover:bg-green-100"
                        @click="showCloseConfirm = true"
                    >
                        ✓ Đánh dấu đã giải quyết
                    </button>
                    <p class="mt-2 text-center text-xs text-slate-400">
                        Bấm khi vấn đề đã được xử lý xong
                    </p>
                </div>

                <!-- Hotline -->
                <div
                    class="rounded-xl bg-gradient-to-br from-blue-700 to-blue-500 p-4 text-center text-white shadow-sm"
                >
                    <p class="text-xs opacity-80">Cần hỗ trợ khẩn cấp?</p>
                    <a
                        href="tel:1900xxxx"
                        class="mt-1 block text-lg font-extrabold"
                        >📞 1900 xxxx</a
                    >
                    <p class="text-xs opacity-70">Hotline hoạt động 24/7</p>
                </div>
            </aside>

            <!-- ─── Main Chat ───────────────────────────────────────────── -->
            <main class="space-y-4">
                <!-- Header -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-1 flex flex-wrap items-center gap-2">
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
                        <span class="ml-auto text-xs text-slate-400"
                            >{{ ticket.messages.length }} tin nhắn</span
                        >
                    </div>
                    <h1 class="text-base font-bold text-slate-900">
                        {{ ticket.subject }}
                    </h1>
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

                <!-- Resolved/closed banner -->
                <div
                    v-if="
                        ticket.status === 'resolved' ||
                        ticket.status === 'closed'
                    "
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
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
                    <span
                        >Ticket này đã được
                        {{
                            ticket.status === 'closed' ? 'đóng' : 'giải quyết'
                        }}.</span
                    >
                    <router-link
                        to="/support"
                        class="ml-auto font-semibold text-blue-600 hover:underline"
                        >Tạo ticket mới →</router-link
                    >
                </div>

                <!-- Messages -->
                <div
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="space-y-6">
                        <div
                            v-for="msg in ticket.messages"
                            :key="msg.id"
                            :class="[
                                'flex gap-3',
                                msg.sender_type === 'customer'
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
                                <span v-if="msg.sender_type === 'admin'"
                                    >🎧</span
                                >
                                <span v-else>{{
                                    msg.sender_name.charAt(0)
                                }}</span>
                            </div>
                            <!-- Bubble -->
                            <div
                                :class="[
                                    'flex max-w-[80%] flex-col',
                                    msg.sender_type === 'customer'
                                        ? 'items-end'
                                        : '',
                                ]"
                            >
                                <div
                                    class="mb-1 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="text-xs font-semibold text-slate-700"
                                        >{{
                                            msg.sender_type === 'admin'
                                                ? 'Nhân viên hỗ trợ'
                                                : msg.sender_name
                                        }}</span
                                    >
                                    <span
                                        v-if="msg.sender_type === 'admin'"
                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-600"
                                        >Admin</span
                                    >
                                    <span class="text-xs text-slate-400">{{
                                        timeAgo(msg.created_at)
                                    }}</span>
                                </div>
                                <div
                                    :class="[
                                        'rounded-2xl px-4 py-3 text-sm leading-relaxed',
                                        msg.sender_type === 'customer'
                                            ? 'rounded-tr-sm bg-blue-600 text-white'
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

                <!-- Reply box -->
                <div
                    v-if="canReply"
                    class="rounded-xl border border-indigo-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-cyan-500 text-sm font-bold text-white"
                        >
                            {{ (auth.user?.full_name ?? 'B').charAt(0) }}
                        </div>
                        <span class="text-sm font-semibold text-slate-700"
                            >Trả lời</span
                        >
                    </div>
                    <textarea
                        v-model="replyText"
                        rows="4"
                        placeholder="Nhập tin nhắn của bạn... (Ctrl+Enter để gửi)"
                        class="w-full resize-y rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 transition outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                        @keydown.ctrl.enter="sendReply"
                    ></textarea>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-400"
                            >Ctrl + Enter để gửi nhanh</span
                        >
                        <button
                            :disabled="!replyText.trim() || replyLoading"
                            :class="[
                                'inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition',
                                replyText.trim() && !replyLoading
                                    ? 'bg-blue-600 hover:bg-blue-700'
                                    : 'cursor-not-allowed bg-blue-300',
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
                            {{ replyLoading ? 'Đang gửi...' : 'Gửi tin nhắn' }}
                        </button>
                    </div>
                </div>
            </main>
        </div>

        <!-- ─── Close Confirm Modal ─────────────────────────────────────── -->
        <transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showCloseConfirm"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="showCloseConfirm = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl"
                >
                    <p class="text-5xl">✅</p>
                    <h3 class="mt-3 text-lg font-extrabold text-slate-900">
                        Đóng ticket?
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Bạn xác nhận rằng vấn đề của mình đã được giải quyết?
                        Sau khi đóng bạn không thể gửi thêm tin nhắn.
                    </p>
                    <div class="mt-6 flex gap-3">
                        <button
                            class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                            @click="showCloseConfirm = false"
                        >
                            Hủy
                        </button>
                        <button
                            :disabled="closeLoading"
                            :class="[
                                'flex flex-1 items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold text-white shadow-sm transition',
                                closeLoading
                                    ? 'cursor-not-allowed bg-green-300'
                                    : 'bg-green-600 hover:bg-green-700',
                            ]"
                            @click="closeTicket"
                        >
                            <svg
                                v-if="closeLoading"
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
                                closeLoading ? 'Đang xử lý...' : 'Xác nhận đóng'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
