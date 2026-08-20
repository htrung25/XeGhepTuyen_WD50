<script setup lang="ts">
import { ref } from 'vue';
import { driverApi } from '@/api/driver.api';
import type { Passenger } from '@/stores/driver.store';

const props = defineProps<{
    tripId: string;
    passengers: Passenger[];
    /** Chỉ cho đánh dấu lên xe khi chuyến đang chạy */
    canCheckin: boolean;
}>();

const emit = defineEmits<{ error: [string]; success: [string] }>();

const expanded = ref<string | null>(null);
const checkinLoading = ref<string | null>(null);
const absentLoading = ref<string | null>(null);
const cashConfirmFor = ref<Passenger | null>(null);

const fmtVnd = (v: number) => new Intl.NumberFormat('vi-VN').format(v);

// Đánh dấu khách đã lên xe (check-in thủ công). Vé tiền mặt chưa thu thì BE trả
// requires_cash → dừng lại hỏi tài xế đã cầm tiền chưa, chỉ check-in sau khi xác nhận.
async function checkinPassenger(p: Passenger, cashCollected = false) {
    checkinLoading.value = p.id;
    const { data, error } = await driverApi.checkin({
        trip_id: props.tripId,
        booking_id: p.booking_id,
        cash_collected: cashCollected,
    });
    checkinLoading.value = null;

    if (error) {
        emit('error', typeof error === 'string' ? error : 'Có lỗi xảy ra');
        return;
    }
    if (data?.requires_cash) {
        cashConfirmFor.value = p;
        return;
    }

    p.checked_in = true;
    p.booking_status = 'checked_in';
    cashConfirmFor.value = null;
    emit('success', `Đã đánh dấu lên xe: ${p.passenger_name}`);
}

async function markAbsent(p: Passenger) {
    absentLoading.value = p.id;
    const { error } = await driverApi.markAbsent({
        trip_id: props.tripId,
        booking_id: p.booking_id,
    });
    absentLoading.value = null;
    if (error) {
        emit('error', typeof error === 'string' ? error : 'Có lỗi xảy ra');
        return;
    }
    p.booking_status = 'no_show';
    expanded.value = null;
    emit('success', `Đã đánh vắng: ${p.passenger_name}`);
}
</script>

<template>
    <div v-if="passengers.length === 0" class="p-8 text-center text-gray-400">
        <p class="mb-2 text-2xl">👥</p>
        <p>Chưa có hành khách đặt chỗ</p>
    </div>

    <div v-else class="divide-y divide-gray-100">
        <div v-for="(p, idx) in passengers" :key="p.id">
            <div
                @click="expanded = expanded === p.id ? null : p.id"
                class="flex w-full cursor-pointer items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-gray-50"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-sm font-bold text-gray-600"
                >
                    {{ idx + 1 }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="mb-0.5 flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-gray-900">{{
                            p.passenger_name
                        }}</span>
                        <span
                            v-for="code in p.seat_codes"
                            :key="code"
                            class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600"
                            >{{ code }}</span
                        >
                        <span
                            v-if="p.booking_status === 'no_show'"
                            class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600"
                            >Vắng</span
                        >
                        <span
                            v-else-if="p.checked_in"
                            class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                            >Đã lên xe</span
                        >
                        <span
                            v-if="p.amount_due"
                            class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700"
                        >
                            💵 Thu {{ fmtVnd(p.amount_due) }}đ
                        </span>
                        <span
                            v-else-if="p.payment_status === 'paid'"
                            class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                            >✓ Đã thanh toán</span
                        >
                    </div>
                    <p class="truncate text-sm text-green-600">
                        Đón: {{ p.pickup_stop?.stop_name }}
                    </p>
                    <p class="truncate text-xs text-gray-400">
                        Trả: {{ p.dropoff_stop?.stop_name }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="`tel:${p.passenger_phone}`"
                        @click.stop
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 text-green-700 transition-colors hover:bg-green-200"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            />
                        </svg>
                    </a>

                    <button
                        v-if="
                            canCheckin &&
                            !p.checked_in &&
                            p.booking_status !== 'no_show'
                        "
                        @click.stop="checkinPassenger(p)"
                        :disabled="checkinLoading === p.id"
                        title="Đánh dấu khách đã lên xe"
                        class="flex h-9 items-center gap-1.5 rounded-lg border border-green-300 bg-green-50 px-3 text-xs font-semibold text-green-700 transition-colors hover:bg-green-100 disabled:opacity-60"
                    >
                        <div
                            v-if="checkinLoading === p.id"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-green-600 border-t-transparent"
                        />
                        <svg
                            v-else
                            class="h-3.5 w-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <span>Lên xe</span>
                    </button>
                    <div
                        v-else
                        :class="[
                            'flex h-9 w-9 items-center justify-center rounded-lg',
                            p.checked_in
                                ? 'bg-green-500'
                                : 'border border-gray-300 bg-gray-100',
                        ]"
                    >
                        <svg
                            v-if="p.checked_in"
                            class="h-4 w-4 text-white"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                v-if="expanded === p.id"
                class="flex items-center justify-between gap-4 border-t border-gray-100 bg-gray-50 px-5 py-3"
            >
                <p class="flex-1 text-sm text-gray-500">
                    📍 {{ p.pickup_stop?.address }}
                </p>
                <button
                    v-if="!p.checked_in && p.booking_status !== 'no_show'"
                    @click="markAbsent(p)"
                    :disabled="absentLoading === p.id"
                    class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-500 transition-colors hover:bg-red-50 disabled:opacity-60"
                >
                    {{ absentLoading === p.id ? '...' : 'Đánh dấu vắng' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Xác nhận thu tiền mặt trước khi cho lên xe -->
    <Teleport to="body">
        <div
            v-if="cashConfirmFor"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="cashConfirmFor = null"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 text-center">
                    <div
                        class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl"
                    >
                        💵
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">
                        Thu tiền mặt
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ cashConfirmFor.passenger_name }} thanh toán bằng tiền
                        mặt — xác nhận đã thu trước khi cho lên xe
                    </p>
                </div>
                <div
                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-center"
                >
                    <p class="mb-0.5 text-xs text-amber-600">Số tiền cần thu</p>
                    <p class="text-2xl font-bold text-amber-700">
                        {{ fmtVnd(cashConfirmFor.amount_due ?? 0) }}đ
                    </p>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="cashConfirmFor = null"
                        class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="checkinPassenger(cashConfirmFor, true)"
                        :disabled="checkinLoading === cashConfirmFor.id"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-amber-600 py-3 font-bold text-white transition-colors hover:bg-amber-700 disabled:opacity-60"
                    >
                        <div
                            v-if="checkinLoading === cashConfirmFor.id"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        <span>Đã thu &amp; Lên xe</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
