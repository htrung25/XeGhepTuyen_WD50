<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import PassengerCheckinList from '@/components/driver/PassengerCheckinList.vue';
import { formatRouteLabel } from '@/lib/route-label';
import { formatVehicleType } from '@/lib/vehicle-type';
import { useDriverStore } from '@/stores/driver.store';
import type { Passenger } from '@/stores/driver.store';

const route = useRoute();
const router = useRouter();
const store = useDriverStore();
const tripId = route.params.id as string;

const trip = ref<any>(null);
const passengers = ref<Passenger[]>([]);
const isLoading = ref(true);
const actionLoading = ref(false);
const errorMsg = ref('');
const showConfirm = ref<'start' | 'complete' | 'unavailable' | null>(null);
const successMsg = ref('');
const unavailableReason = ref('');
const unavailableIssueType = ref<'driver' | 'vehicle'>('driver');
const unavailableIssueOptions: Array<{
    value: 'driver' | 'vehicle';
    label: string;
}> = [
    { value: 'driver', label: 'Tài xế' },
    { value: 'vehicle', label: 'Xe' },
];
const checkedIn = computed(
    () => passengers.value.filter((p) => p.checked_in).length,
);
const checkinPct = computed(() =>
    passengers.value.length > 0
        ? Math.round((checkedIn.value / passengers.value.length) * 100)
        : 0,
);

const statusConfig = {
    scheduled: {
        label: 'Sắp tới',
        cls: 'bg-blue-100 text-blue-700',
        headerCls: 'bg-blue-600',
    },
    boarding: {
        label: 'Đang đón khách',
        cls: 'bg-cyan-100 text-cyan-700',
        headerCls: 'bg-cyan-600',
    },
    in_progress: {
        label: 'Đang chạy',
        cls: 'bg-green-100 text-green-700',
        headerCls: 'bg-green-600',
    },
    completed: {
        label: 'Hoàn thành',
        cls: 'bg-gray-100 text-gray-500',
        headerCls: 'bg-gray-500',
    },
    cancelled: {
        label: 'Đã hủy',
        cls: 'bg-red-100 text-red-600',
        headerCls: 'bg-red-500',
    },
} as const;

// trip.status là any (ref<any>) — ép về key hợp lệ để TS không báo implicit-any khi index.
function statusInfo(status: string) {
    return statusConfig[status as keyof typeof statusConfig];
}

function fmtTime(iso: string) {
    return new Date(iso).toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
function fmtDateTime(iso: string) {
    return new Date(iso).toLocaleString('vi-VN', {
        weekday: 'short',
        month: 'numeric',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function startTrip() {
    actionLoading.value = true;
    const { error } = await driverApi.startTrip(tripId);
    actionLoading.value = false;
    showConfirm.value = null;
    if (error) {
        errorMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra';
        return;
    }
    trip.value.status = 'in_progress';
    successMsg.value = 'Chuyến đã bắt đầu!';
    setTimeout(() => {
        router.push(`/driver/trips/${tripId}/navigate`);
    }, 800);
}

async function completeTrip() {
    actionLoading.value = true;
    const { error } = await driverApi.completeTrip(tripId);
    actionLoading.value = false;
    showConfirm.value = null;
    if (error) {
        errorMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra';
        return;
    }
    setTimeout(() => router.push('/driver/dashboard'), 1500);
}

async function reportUnavailable() {
    const reason = unavailableReason.value.trim();
    if (!reason) {
        errorMsg.value = 'Vui lòng nhập lý do không thể chạy chuyến';
        return;
    }

    actionLoading.value = true;
    errorMsg.value = '';
    const { error, message } = await driverApi.reportUnavailable(
        tripId,
        reason,
        unavailableIssueType.value,
    );
    actionLoading.value = false;

    if (error) {
        errorMsg.value = error;
        return;
    }

    trip.value.is_awaiting_reassignment = true;
    unavailableReason.value = '';
    unavailableIssueType.value = 'driver';
    showConfirm.value = null;
    successMsg.value =
        message ?? 'Đã báo nhà xe sắp xếp tài xế thay thế cho chuyến.';
}

onMounted(async () => {
    isLoading.value = true;
    const [tripRes, passRes] = await Promise.all([
        driverApi.getTrip(tripId),
        driverApi.getPassengers(tripId),
    ]);
    isLoading.value = false;
    if (tripRes.error) {
        errorMsg.value = tripRes.error as string;
        return;
    }
    trip.value = tripRes.data;
    passengers.value = passRes.data ?? [];
    store.activeTrip = trip.value;
    store.passengers = passengers.value;
});
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <!-- Breadcrumb -->
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <router-link
                to="/driver/dashboard"
                class="transition-colors hover:text-green-600"
                >← Lịch chạy</router-link
            >
            <span>/</span>
            <span class="font-medium text-gray-700">Chi tiết chuyến</span>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div class="h-32 animate-pulse rounded-xl bg-gray-200" />
            <div
                v-for="i in 4"
                :key="i"
                class="h-20 animate-pulse rounded-xl bg-gray-100"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg && !trip"
            class="rounded-xl border border-red-200 bg-red-50 p-6 text-center text-red-700"
        >
            <p class="mb-3 font-medium">{{ errorMsg }}</p>
            <router-link
                to="/driver/dashboard"
                class="rounded-lg border border-red-300 px-5 py-2 text-sm text-red-600 hover:bg-red-50"
            >
                ← Quay lại
            </router-link>
        </div>

        <div v-else-if="trip" class="grid gap-6 lg:grid-cols-[1fr_300px]">
            <!-- ─── LEFT: Trip info + passenger list ─────────────── -->
            <div class="space-y-4">
                <!-- Trip header card -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        :class="[
                            'px-5 py-4 text-white',
                            statusInfo(trip.status)?.headerCls ?? 'bg-gray-500',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <h1 class="text-lg font-bold">
                                {{ formatRouteLabel(trip.route) }}
                            </h1>
                            <span
                                class="rounded-full bg-white/20 px-3 py-1 text-sm font-medium"
                            >
                                {{ statusInfo(trip.status)?.label }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-white/80">
                            {{ fmtDateTime(trip.depart_at) }} →
                            {{ fmtTime(trip.arrive_at) }}
                        </p>
                    </div>
                    <div
                        class="grid grid-cols-3 gap-4 border-b border-gray-100 px-5 py-3 text-sm"
                    >
                        <div>
                            <p class="text-xs text-gray-400">Biển số xe</p>
                            <p
                                class="mt-0.5 font-mono font-semibold text-gray-900"
                            >
                                {{ trip.vehicle?.plate_number }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Loại xe</p>
                            <p class="mt-0.5 font-semibold text-gray-900">
                                {{
                                    formatVehicleType(
                                        trip.vehicle?.vehicle_type,
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Số ghế</p>
                            <p class="mt-0.5 font-semibold text-gray-900">
                                {{ trip.vehicle?.seat_count }} chỗ
                            </p>
                        </div>
                    </div>

                    <!-- Check-in progress -->
                    <div class="px-5 py-3">
                        <div
                            class="mb-2 flex items-center justify-between text-sm"
                        >
                            <span class="font-medium text-gray-600"
                                >Check-in hành khách</span
                            >
                            <span class="font-bold text-green-600"
                                >{{ checkedIn }}/{{ passengers.length }}</span
                            >
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full bg-green-500 transition-all duration-500"
                                :style="{ width: checkinPct + '%' }"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">
                            {{ checkinPct }}% hành khách đã check-in
                        </p>
                    </div>
                </div>

                <!-- Success/Error messages -->
                <div
                    v-if="successMsg"
                    class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700"
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
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    {{ successMsg }}
                </div>
                <div
                    v-if="errorMsg && trip"
                    class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                >
                    {{ errorMsg }}
                </div>

                <!-- Passenger list -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-5 py-3"
                    >
                        <h2 class="font-semibold text-gray-900">
                            Danh sách hành khách
                        </h2>
                        <span class="text-sm text-gray-500"
                            >{{ passengers.length }} người</span
                        >
                    </div>

                    <PassengerCheckinList
                        :trip-id="tripId"
                        :passengers="passengers"
                        :can-checkin="trip.status === 'in_progress'"
                        @error="errorMsg = $event"
                        @success="successMsg = $event"
                    />
                </div>
            </div>

            <!-- ─── RIGHT: Action panel ───────────────────────────── -->
            <div class="sticky top-6 space-y-4 self-start">
                <!-- Action card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Thao tác chuyến đi
                    </h3>

                    <!-- Scheduled → Start -->
                    <button
                        v-if="
                            ['scheduled', 'boarding'].includes(trip.status) &&
                            !trip.is_awaiting_reassignment
                        "
                        @click="showConfirm = 'start'"
                        class="mb-3 w-full rounded-xl bg-green-600 py-3.5 font-bold text-white transition-colors hover:bg-green-700"
                    >
                        🚦 Bắt đầu chuyến
                    </button>

                    <button
                        v-if="
                            ['scheduled', 'boarding'].includes(trip.status) &&
                            !trip.is_awaiting_reassignment
                        "
                        @click="showConfirm = 'unavailable'"
                        class="w-full rounded-xl border border-red-200 bg-red-50 py-3 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                    >
                        Không thể chạy chuyến này
                    </button>

                    <div
                        v-if="trip.is_awaiting_reassignment"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"
                    >
                        Đã báo nghỉ chuyến. Nhà xe đang sắp xếp tài xế thay thế.
                    </div>

                    <!-- In progress → Check-in thủ công + Complete -->
                    <template v-else-if="trip.status === 'in_progress'">
                        <p
                            class="mb-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700"
                        >
                            ✓ Nhấn vào ô check-in cạnh mỗi hành khách trong danh
                            sách để xác nhận đã lên xe.
                        </p>
                        <button
                            @click="showConfirm = 'complete'"
                            class="w-full rounded-xl border border-gray-200 bg-gray-100 py-3.5 font-bold text-gray-700 transition-colors hover:border-red-300 hover:bg-red-50 hover:text-red-600"
                        >
                            🏁 Kết thúc chuyến
                        </button>
                    </template>

                    <!-- Completed -->
                    <div
                        v-else-if="trip.status === 'completed'"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3.5 text-center font-semibold text-gray-500"
                    >
                        ✅ Chuyến đã hoàn thành
                    </div>

                    <!-- Cancelled -->
                    <div
                        v-else-if="trip.status === 'cancelled'"
                        class="w-full rounded-xl border border-red-200 bg-red-50 py-3.5 text-center font-semibold text-red-500"
                    >
                        ❌ Chuyến đã bị hủy
                    </div>
                </div>

                <!-- Navigate link -->
                <router-link
                    v-if="trip.status === 'in_progress'"
                    :to="`/driver/trips/${tripId}/navigate`"
                    class="block w-full rounded-xl border border-gray-200 py-3 text-center text-sm font-medium text-gray-600 transition-colors hover:border-green-400 hover:bg-green-50 hover:text-green-700"
                >
                    🗺️ Bật điều hướng GPS
                </router-link>

                <!-- Trip summary -->
                <div
                    class="space-y-2 rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm"
                >
                    <div class="flex justify-between text-gray-600">
                        <span>Hành khách đặt</span>
                        <span class="font-semibold text-gray-900">{{
                            passengers.length
                        }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Đã check-in</span>
                        <span class="font-semibold text-green-600">{{
                            checkedIn
                        }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Vắng mặt</span>
                        <span class="font-semibold text-red-500">{{
                            passengers.filter(
                                (p) => p.booking_status === 'no_show',
                            ).length
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-100 pt-2"
                    >
                        <span class="font-semibold text-gray-700"
                            >Dự kiến thu</span
                        >
                        <span class="font-bold text-green-600"
                            >{{
                                new Intl.NumberFormat('vi-VN').format(
                                    trip.price * checkedIn,
                                )
                            }}đ</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Confirm modals ─────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showConfirm"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showConfirm = null"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                >
                    <!-- Start confirm -->
                    <template v-if="showConfirm === 'start'">
                        <div class="mb-5 text-center">
                            <div
                                class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl"
                            >
                                🚦
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Bắt đầu chuyến?
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Xác nhận bắt đầu chuyến
                                {{ formatRouteLabel(trip?.route) }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="showConfirm = null"
                                class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                Hủy
                            </button>
                            <button
                                @click="startTrip"
                                :disabled="actionLoading"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-green-600 py-3 font-bold text-white transition-colors hover:bg-green-700 disabled:opacity-60"
                            >
                                <div
                                    v-if="actionLoading"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                />
                                <span>{{
                                    actionLoading ? '...' : 'Bắt đầu'
                                }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- Complete confirm -->
                    <template v-else-if="showConfirm === 'complete'">
                        <div class="mb-5 text-center">
                            <div
                                class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-3xl"
                            >
                                🏁
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Kết thúc chuyến?
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Chỉ {{ checkedIn }}/{{ passengers.length }} hành
                                khách đã check-in. Bạn có chắc muốn kết thúc?
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="showConfirm = null"
                                class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                Hủy
                            </button>
                            <button
                                @click="completeTrip"
                                :disabled="actionLoading"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 py-3 font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                            >
                                <div
                                    v-if="actionLoading"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                />
                                <span>{{
                                    actionLoading ? '...' : 'Kết thúc'
                                }}</span>
                            </button>
                        </div>
                    </template>

                    <template v-else-if="showConfirm === 'unavailable'">
                        <div class="mb-5 text-center">
                            <div
                                class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-3xl"
                            >
                                ⚠️
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Không thể chạy chuyến?
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Nhà xe sẽ nhận thông báo để sắp xếp tài xế thay
                                thế. Chỉ có thể báo trước thời hạn quy định.
                            </p>
                        </div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Loại sự cố
                        </label>
                        <div class="mb-4 grid grid-cols-2 gap-2">
                            <button
                                v-for="option in unavailableIssueOptions"
                                :key="option.value"
                                type="button"
                                class="rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                                :class="
                                    unavailableIssueType === option.value
                                        ? 'border-red-500 bg-red-50 text-red-700'
                                        : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                                "
                                @click="unavailableIssueType = option.value"
                            >
                                {{ option.label }} gặp vấn đề
                            </button>
                        </div>
                        <label
                            for="unavailable-reason"
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Lý do
                        </label>
                        <textarea
                            id="unavailable-reason"
                            v-model="unavailableReason"
                            maxlength="255"
                            rows="3"
                            class="mb-4 w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            placeholder="Ví dụ: Sức khỏe không đảm bảo, xe gặp sự cố..."
                        />
                        <div class="flex gap-3">
                            <button
                                @click="showConfirm = null"
                                :disabled="actionLoading"
                                class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-60"
                            >
                                Quay lại
                            </button>
                            <button
                                @click="reportUnavailable"
                                :disabled="
                                    actionLoading ||
                                    unavailableReason.trim().length === 0
                                "
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 py-3 font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                            >
                                {{ actionLoading ? 'Đang gửi...' : 'Xác nhận' }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>
