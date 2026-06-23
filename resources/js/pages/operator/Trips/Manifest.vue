<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { operatorApi } from '@/api/operator.api';

interface Passenger {
    stt: number;
    booking_code: string;
    contact_name: string;
    contact_phone: string;
    seat_code: string;
    pickup_stop: string;
    pickup_address: string;
    dropoff_stop: string;
    checked_in: boolean;
    no_show: boolean;
}

interface TripInfo {
    route: string;
    depart_at: string;
    driver_name: string;
    vehicle_plate: string;
}

const route = useRoute();
const tripId = route.params.id as string;

const isLoading = ref(true);
const errorMsg = ref('');
const tripInfo = ref<TripInfo | null>(null);
const passengers = ref<Passenger[]>([]);

const summary = computed(() => ({
    total: passengers.value.length,
    checkedIn: passengers.value.filter((p) => p.checked_in).length,
    noShow: passengers.value.filter((p) => p.no_show).length,
    waiting: passengers.value.filter((p) => !p.checked_in && !p.no_show).length,
}));

const load = async () => {
    isLoading.value = true;
    errorMsg.value = '';

    const { data, error } = await operatorApi.getTripManifest(tripId);
    isLoading.value = false;

    if (error) {
        errorMsg.value = 'Không thể tải manifest. ' + error;
        return;
    }

    tripInfo.value = data?.trip_info ?? null;
    passengers.value = (data?.passengers ?? []).map((p: any, i: number) => ({
        ...p,
        stt: i + 1,
    }));
};

const printManifest = () => window.print();

const exportExcel = async () => {
    const { data, error } = await operatorApi.exportManifestExcel(tripId);
    if (error) {
        alert('Xuất Excel thất bại: ' + error);
        return;
    }
    const url = URL.createObjectURL(new Blob([data]));
    const a = document.createElement('a');
    a.href = url;
    a.download = `manifest-${tripId}.xlsx`;
    a.click();
    URL.revokeObjectURL(url);
};

onMounted(() => load());
</script>

<template>
    <div class="space-y-5 p-6 print:p-0">
        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div
                class="h-28 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
            <div
                class="h-64 animate-pulse rounded-xl border border-slate-200 bg-white"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-4 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            <svg
                class="h-6 w-6 flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            {{ errorMsg }}
            <button class="ml-auto text-sm underline" @click="load">
                Thử lại
            </button>
        </div>

        <template v-else>
            <!-- Trip header card -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <h1 class="text-xl font-semibold text-slate-800">
                            Chuyến {{ tripInfo?.route ?? '—' }}
                        </h1>
                        <div
                            class="flex flex-wrap gap-4 text-sm text-slate-600"
                        >
                            <span class="flex items-center gap-1.5">
                                <svg
                                    class="h-4 w-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                {{
                                    tripInfo
                                        ? new Date(
                                              tripInfo.depart_at,
                                          ).toLocaleString('vi-VN')
                                        : '—'
                                }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg
                                    class="h-4 w-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                {{ tripInfo?.driver_name ?? '—' }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg
                                    class="h-4 w-4 text-slate-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 17l-1.5-5H4a2 2 0 01-2-2V7a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2.5L16 17H8z"
                                    />
                                </svg>
                                {{ tripInfo?.vehicle_plate ?? '—' }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions — hidden in print -->
                    <div class="flex gap-2 print:hidden">
                        <button
                            class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                            @click="printManifest"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            In manifest
                        </button>
                        <button
                            class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                            @click="exportExcel"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Xuất Excel
                        </button>
                    </div>
                </div>

                <!-- Summary bar -->
                <div
                    class="mt-4 flex gap-6 border-t border-slate-100 pt-4 text-sm"
                >
                    <span class="text-slate-600"
                        >Tổng:
                        <strong class="text-slate-800"
                            >{{ summary.total }} khách</strong
                        ></span
                    >
                    <span class="text-green-600"
                        >Đã check-in:
                        <strong>{{ summary.checkedIn }}</strong></span
                    >
                    <span class="text-red-500"
                        >Vắng mặt: <strong>{{ summary.noShow }}</strong></span
                    >
                    <span class="text-slate-500"
                        >Chờ: <strong>{{ summary.waiting }}</strong></span
                    >
                </div>
            </div>

            <!-- Empty passengers -->
            <div
                v-if="passengers.length === 0"
                class="flex flex-col items-center rounded-xl border border-slate-200 bg-white py-16 text-slate-400"
            >
                <svg
                    class="mb-3 h-12 w-12 text-slate-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                </svg>
                <p class="font-medium">Chưa có hành khách nào</p>
            </div>

            <!-- Passengers table -->
            <div
                v-else
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="w-10 px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    STT
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Mã vé
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Tên khách
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Số điện thoại
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Số ghế
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Điểm đón
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Địa chỉ đón
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Điểm trả
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                                >
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="p in passengers"
                                :key="p.booking_code"
                                :class="
                                    p.no_show
                                        ? 'bg-red-50'
                                        : p.checked_in
                                          ? 'bg-green-50/40'
                                          : ''
                                "
                            >
                                <td class="px-4 py-3 text-slate-500">
                                    {{ p.stt }}
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-xs text-slate-700"
                                >
                                    {{ p.booking_code }}
                                </td>
                                <td
                                    class="px-4 py-3 font-medium text-slate-800"
                                >
                                    {{ p.contact_name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ p.contact_phone }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800"
                                        >{{ p.seat_code }}</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ p.pickup_stop }}
                                </td>
                                <td
                                    class="max-w-[160px] truncate px-4 py-3 text-xs text-slate-500"
                                >
                                    {{ p.pickup_address }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ p.dropoff_stop }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="p.checked_in"
                                        class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700"
                                    >
                                        Đã check-in
                                    </span>
                                    <span
                                        v-else-if="p.no_show"
                                        class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700"
                                    >
                                        Vắng mặt
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"
                                    >
                                        Chờ
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<style>
@media print {
    aside,
    header,
    .print\:hidden {
        display: none !important;
    }
    body {
        background: white;
    }
}
</style>
