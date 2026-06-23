<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { customerApi } from '@/api/customer.api';

type BookingTab = 'upcoming' | 'past' | 'cancelled';
interface Booking {
    id: string;
    booking_code: string;
    booking_status: string;
    payment_status: string;
    payment_method: string;
    trip: {
        depart_at: string;
        route?: { origin_city: string; dest_city: string };
    };
    passengers: any[];
    final_amount: number;
    contact_name: string;
    qr_code?: string;
}

const activeTab = ref<BookingTab>('upcoming');
const bookings = ref<Booking[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const totalCount = ref(0);
const cancelTarget = ref<string | null>(null);
const cancelLoading = ref(false);

const tabs: { key: BookingTab; label: string; color: string }[] = [
    {
        key: 'upcoming',
        label: 'Sắp đi',
        color: 'text-blue-600 border-blue-600',
    },
    { key: 'past', label: 'Đã đi', color: 'text-gray-600 border-gray-600' },
    { key: 'cancelled', label: 'Đã hủy', color: 'text-red-500 border-red-400' },
];

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

function fmtDateTime(iso: string) {
    const d = new Date(iso);
    const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    return `${days[d.getDay()]}, ${d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })} · ${d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hour12: false })}`;
}

function statusLabel(s: string) {
    const map: Record<string, { label: string; cls: string }> = {
        pending: {
            label: 'Chờ xác nhận',
            cls: 'bg-yellow-100 text-yellow-700',
        },
        confirmed: { label: 'Đã xác nhận', cls: 'bg-blue-100 text-blue-700' },
        checked_in: {
            label: 'Đang chạy',
            cls: 'bg-green-100 text-green-700 animate-pulse',
        },
        completed: { label: 'Hoàn thành', cls: 'bg-gray-100 text-gray-600' },
        cancelled: { label: 'Đã hủy', cls: 'bg-red-100 text-red-600' },
        no_show: { label: 'Vắng mặt', cls: 'bg-red-100 text-red-500' },
    };
    return map[s] ?? { label: s, cls: 'bg-gray-100 text-gray-600' };
}

function payMethodIcon(m: string) {
    if (m === 'momo') return '💜 MoMo';
    if (m === 'vnpay') return '🏦 VNPay';
    if (m === 'wallet') return '👛 Ví XeGhep';
    if (m === 'cash') return '💵 Tiền mặt';
    return m;
}

async function loadBookings(tab: BookingTab, page = 1) {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await customerApi.getBookings({
        status: tab,
        page,
    });
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải danh sách vé.';
        return;
    }
    bookings.value = data?.data ?? data ?? [];
    currentPage.value = data?.current_page ?? 1;
    totalPages.value = data?.last_page ?? 1;
    totalCount.value = data?.total ?? bookings.value.length;
}

async function cancelBooking() {
    if (!cancelTarget.value) return;
    cancelLoading.value = true;
    const { error } = await customerApi.cancelBooking(cancelTarget.value);
    cancelLoading.value = false;
    cancelTarget.value = null;
    if (!error) loadBookings(activeTab.value, currentPage.value);
}

watch(activeTab, (tab) => {
    currentPage.value = 1;
    loadBookings(tab);
});
onMounted(() => loadBookings('upcoming'));
</script>

<template>
    <div class="mx-auto max-w-5xl px-6 py-8">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Vé của tôi</h1>

        <!-- Tabs -->
        <div class="mb-6 flex gap-0 border-b border-gray-200">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    '-mb-px border-b-2 px-6 py-3 text-sm font-semibold transition-colors',
                    activeTab === tab.key
                        ? tab.color + ' bg-white'
                        : 'border-transparent text-gray-500 hover:text-gray-700',
                ]"
            >
                {{ tab.label }}
                <span
                    v-if="activeTab === tab.key && totalCount > 0"
                    class="bg-opacity-10 ml-2 rounded-full bg-current px-1.5 py-0.5 text-xs"
                >
                    {{ totalCount }}
                </span>
            </button>
        </div>

        <!-- Loading skeletons -->
        <div v-if="isLoading" class="space-y-4">
            <div
                v-for="i in 3"
                :key="i"
                class="animate-pulse rounded-xl border border-gray-200 bg-white p-5"
            >
                <div class="mb-3 flex justify-between">
                    <div class="h-5 w-32 rounded bg-gray-200" />
                    <div class="h-5 w-20 rounded bg-gray-200" />
                </div>
                <div class="mb-2 h-4 w-full rounded bg-gray-100" />
                <div class="h-4 w-2/3 rounded bg-gray-100" />
            </div>
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-sm text-red-700"
        >
            <p class="mb-2 font-medium">{{ errorMsg }}</p>
            <button
                @click="loadBookings(activeTab)"
                class="text-xs text-red-600 underline"
            >
                Thử lại
            </button>
        </div>

        <!-- Empty states -->
        <div
            v-else-if="bookings.length === 0"
            class="rounded-xl border border-gray-200 bg-white px-8 py-16 text-center"
        >
            <div class="mb-4 text-5xl">
                {{
                    activeTab === 'upcoming'
                        ? '🎫'
                        : activeTab === 'past'
                          ? '🕐'
                          : '❌'
                }}
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-800">
                {{
                    activeTab === 'upcoming'
                        ? 'Bạn chưa có chuyến nào sắp tới'
                        : activeTab === 'past'
                          ? 'Chưa có lịch sử chuyến đi'
                          : 'Không có vé đã hủy'
                }}
            </h3>
            <p
                v-if="activeTab === 'upcoming'"
                class="mb-6 text-sm text-gray-500"
            >
                Đặt vé ngay để có chuyến đi đầu tiên với XeGhep.vn
            </p>
            <router-link
                v-if="activeTab === 'upcoming'"
                to="/home"
                class="inline-flex rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
                Đặt vé ngay
            </router-link>
        </div>

        <!-- Booking list -->
        <div v-else class="space-y-4">
            <div
                v-for="b in bookings"
                :key="b.id"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md"
            >
                <div class="flex items-start gap-5">
                    <!-- Left: code + status -->
                    <div class="w-36 shrink-0 border-r border-gray-100 pr-5">
                        <p
                            class="mb-2 font-mono text-sm font-bold text-gray-900"
                        >
                            {{ b.booking_code }}
                        </p>
                        <span
                            :class="[
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                statusLabel(b.booking_status).cls,
                            ]"
                        >
                            {{ statusLabel(b.booking_status).label }}
                        </span>
                    </div>

                    <!-- Center: trip info -->
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex items-center gap-2">
                            <svg
                                class="h-4 w-4 shrink-0 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                            </svg>
                            <span class="font-bold text-gray-900">
                                {{ b.trip?.route?.origin_city ?? 'Hà Nội' }} →
                                {{ b.trip?.route?.dest_city ?? 'Hải Phòng' }}
                            </span>
                        </div>
                        <p class="mb-2 text-sm text-gray-500">
                            {{
                                b.trip?.depart_at
                                    ? fmtDateTime(b.trip.depart_at)
                                    : '—'
                            }}
                        </p>
                        <div
                            class="flex items-center gap-3 text-xs text-gray-500"
                        >
                            <span
                                >{{ b.passengers?.length ?? 1 }} hành
                                khách</span
                            >
                            <span>·</span>
                            <span>{{ b.contact_name }}</span>
                        </div>
                    </div>

                    <!-- Right: price + method -->
                    <div
                        class="shrink-0 border-l border-gray-100 pl-5 text-right"
                    >
                        <p class="mb-1 text-base font-bold text-gray-900">
                            {{ fmt(b.final_amount) }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ payMethodIcon(b.payment_method) }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex shrink-0 flex-col items-end gap-2 border-l border-gray-100 pl-4"
                    >
                        <!-- Upcoming actions -->
                        <template v-if="activeTab === 'upcoming'">
                            <router-link
                                :to="`/customer/bookings/${b.id}`"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold whitespace-nowrap text-white transition-colors hover:bg-blue-700"
                            >
                                Xem vé QR
                            </router-link>
                            <router-link
                                :to="`/customer/bookings/${b.id}/track`"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium whitespace-nowrap text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                📡 Theo dõi xe
                            </router-link>
                            <button
                                @click="cancelTarget = b.id"
                                class="text-xs font-medium text-red-500 transition-colors hover:text-red-700"
                            >
                                Hủy vé
                            </button>
                        </template>

                        <!-- Past actions -->
                        <template v-else-if="activeTab === 'past'">
                            <router-link
                                :to="`/customer/bookings/${b.id}/review`"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold whitespace-nowrap text-white transition-colors hover:bg-blue-700"
                            >
                                ⭐ Đánh giá
                            </router-link>
                        </template>

                        <!-- Cancelled info -->
                        <template v-else>
                            <span class="text-xs text-gray-400 italic"
                                >Đã hủy</span
                            >
                        </template>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="totalPages > 1"
                class="mt-6 flex items-center justify-center gap-2"
            >
                <button
                    @click="loadBookings(activeTab, currentPage - 1)"
                    :disabled="currentPage <= 1"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 transition-colors hover:bg-gray-50 disabled:opacity-40"
                >
                    ‹
                </button>
                <span class="px-2 text-sm text-gray-600"
                    >Trang {{ currentPage }} / {{ totalPages }}</span
                >
                <button
                    @click="loadBookings(activeTab, currentPage + 1)"
                    :disabled="currentPage >= totalPages"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 transition-colors hover:bg-gray-50 disabled:opacity-40"
                >
                    ›
                </button>
            </div>
        </div>

        <!-- Cancel confirmation modal -->
        <div
            v-if="cancelTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="mb-2 text-lg font-bold text-gray-900">
                    Xác nhận hủy vé
                </h3>
                <p class="mb-6 text-sm text-gray-600">
                    Bạn chắc chắn muốn hủy vé này? Tiền hoàn sẽ được xử lý theo
                    chính sách hoàn tiền của XeGhep.vn.
                </p>
                <div class="flex gap-3">
                    <button
                        @click="cancelTarget = null"
                        class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Không, giữ vé
                    </button>
                    <button
                        @click="cancelBooking"
                        :disabled="cancelLoading"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        <div
                            v-if="cancelLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        <span>{{
                            cancelLoading ? 'Đang hủy...' : 'Hủy vé'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
