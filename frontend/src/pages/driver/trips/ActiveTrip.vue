<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import PassengerCheckinList from '@/components/driver/PassengerCheckinList.vue';
import { formatRouteLabel } from '@/lib/route-label';
import { useDriverStore } from '@/stores/driver.store';
import type { Passenger } from '@/stores/driver.store';

const router = useRouter();
const store = useDriverStore();

const trip = ref<any>(null);
const passengers = ref<Passenger[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const successMsg = ref('');
const completing = ref(false);
const showComplete = ref(false);

const checkedIn = computed(
    () => passengers.value.filter((p) => p.checked_in).length,
);
const checkinPct = computed(() =>
    passengers.value.length > 0
        ? Math.round((checkedIn.value / passengers.value.length) * 100)
        : 0,
);

function fmtTime(iso?: string | null) {
    return iso
        ? new Date(iso).toLocaleTimeString('vi-VN', {
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';
}

async function load() {
    isLoading.value = true;
    errorMsg.value = '';

    // Chuyến "đang chạy" = trip đã bấm bắt đầu (in_progress). Tài xế chỉ chạy một
    // chuyến tại một thời điểm nên lấy chuyến đầu tiên là đủ.
    const { data, error } = await driverApi.getTrips({ status: 'in_progress' });
    if (error) {
        isLoading.value = false;
        errorMsg.value =
            typeof error === 'string'
                ? error
                : 'Không tải được chuyến đang chạy';
        return;
    }

    const active = (data ?? [])[0] ?? null;
    trip.value = active;

    if (!active) {
        passengers.value = [];
        isLoading.value = false;
        return;
    }

    const passRes = await driverApi.getPassengers(active.id);
    isLoading.value = false;
    passengers.value = passRes.data ?? [];
    store.activeTrip = active;
    store.passengers = passengers.value;
}

async function completeTrip() {
    completing.value = true;
    const { error } = await driverApi.completeTrip(trip.value.id);
    completing.value = false;
    showComplete.value = false;
    if (error) {
        errorMsg.value = typeof error === 'string' ? error : 'Có lỗi xảy ra';
        return;
    }
    successMsg.value = 'Đã kết thúc chuyến!';
    setTimeout(() => router.push('/driver/dashboard'), 1200);
}

onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <h1 class="mb-1 text-xl font-bold text-gray-900">
            Chuyến đi đang chạy
        </h1>
        <p class="mb-5 text-sm text-gray-500">
            Đánh dấu khách đã lên xe và kết thúc chuyến tại đây.
        </p>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div class="h-32 animate-pulse rounded-xl bg-gray-200" />
            <div
                v-for="i in 3"
                :key="i"
                class="h-20 animate-pulse rounded-xl bg-gray-100"
            />
        </div>

        <!-- Không có chuyến đang chạy -->
        <div
            v-else-if="!trip"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm"
        >
            <div class="mb-3 text-5xl">🚌</div>
            <p class="font-semibold text-gray-700">Chưa có chuyến đang chạy</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">
                Khi bạn bấm “Bắt đầu chuyến” ở một chuyến trong lịch chạy,
                chuyến đó sẽ hiện tại đây kèm danh sách khách để đánh dấu lên
                xe.
            </p>
            <router-link
                to="/driver/schedule"
                class="mt-5 inline-block rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-700"
            >
                Xem lịch chạy
            </router-link>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-[1fr_300px]">
            <!-- ─── LEFT: thông tin chuyến + danh sách khách ─────────── -->
            <div class="space-y-4">
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="bg-green-600 px-5 py-4 text-white">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-bold">
                                {{ formatRouteLabel(trip.route) }}
                            </h2>
                            <span
                                class="shrink-0 rounded-full bg-white/20 px-3 py-1 text-sm font-medium"
                            >
                                Đang chạy
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-white/80">
                            {{ fmtTime(trip.depart_at) }} →
                            {{ fmtTime(trip.arrive_at) }} ·
                            {{ trip.vehicle?.plate_number }}
                        </p>
                    </div>

                    <div class="px-5 py-3">
                        <div
                            class="mb-2 flex items-center justify-between text-sm"
                        >
                            <span class="font-medium text-gray-600"
                                >Khách đã lên xe</span
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
                    </div>
                </div>

                <div
                    v-if="successMsg"
                    class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700"
                >
                    {{ successMsg }}
                </div>
                <div
                    v-if="errorMsg"
                    class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                >
                    {{ errorMsg }}
                </div>

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
                        :trip-id="trip.id"
                        :passengers="passengers"
                        :can-checkin="true"
                        @error="errorMsg = $event"
                        @success="successMsg = $event"
                    />
                </div>
            </div>

            <!-- ─── RIGHT: thao tác ──────────────────────────────────── -->
            <div class="sticky top-6 space-y-4 self-start">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">Thao tác</h3>
                    <router-link
                        :to="`/driver/trips/${trip.id}/navigate`"
                        class="mb-3 block w-full rounded-xl border border-gray-200 py-3 text-center text-sm font-medium text-gray-600 transition-colors hover:border-green-400 hover:bg-green-50 hover:text-green-700"
                    >
                        🗺️ Bật điều hướng GPS
                    </router-link>
                    <router-link
                        :to="`/driver/trips/${trip.id}`"
                        class="mb-3 block w-full rounded-xl border border-gray-200 py-3 text-center text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                    >
                        Chi tiết chuyến
                    </router-link>
                    <button
                        @click="showComplete = true"
                        class="w-full rounded-xl bg-gray-100 py-3.5 font-bold text-gray-700 transition-colors hover:bg-red-50 hover:text-red-600"
                    >
                        🏁 Kết thúc chuyến
                    </button>
                </div>
            </div>
        </div>

        <!-- Xác nhận kết thúc chuyến -->
        <Teleport to="body">
            <div
                v-if="showComplete"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="showComplete = false"
            >
                <div
                    class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                >
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
                            {{ checkedIn }}/{{ passengers.length }} khách đã lên
                            xe. Bạn có chắc muốn kết thúc?
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="showComplete = false"
                            class="flex-1 rounded-xl border border-gray-200 py-3 font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Hủy
                        </button>
                        <button
                            @click="completeTrip"
                            :disabled="completing"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 py-3 font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                        >
                            {{ completing ? '...' : 'Kết thúc' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
