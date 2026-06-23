<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { driverApi } from '@/api/driver.api';
import type { DriverTrip } from '@/stores/driver.store';

const trips = ref<DriverTrip[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const weekOffset = ref(0); // 0 = current week, -1 = last week, 1 = next week
const selectedTrip = ref<DriverTrip | null>(null);

// Build week dates based on offset
const weekDates = computed(() => {
    const now = new Date();
    const day = now.getDay(); // 0=Sun, 1=Mon...
    const diff = day === 0 ? -6 : 1 - day; // adjust to Monday
    const monday = new Date(now);
    monday.setDate(now.getDate() + diff + weekOffset.value * 7);
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        return d;
    });
});

const weekLabel = computed(() => {
    const s = weekDates.value[0];
    const e = weekDates.value[6];
    const fmt = (d: Date) =>
        d.toLocaleDateString('vi-VN', { day: 'numeric', month: 'numeric' });
    return `${fmt(s)} – ${fmt(e)}, ${s.getFullYear()}`;
});

const dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

// Map trips to their calendar day (index 0-6)
const tripsByDay = computed(() => {
    const map: Record<number, DriverTrip[]> = {};
    weekDates.value.forEach((_, i) => {
        map[i] = [];
    });
    trips.value.forEach((trip) => {
        const tripDate = new Date(trip.depart_at).toDateString();
        weekDates.value.forEach((d, i) => {
            if (d.toDateString() === tripDate) map[i].push(trip);
        });
    });
    return map;
});

const isToday = (d: Date) => d.toDateString() === new Date().toDateString();
const isPast = (d: Date) => d < new Date() && !isToday(d);

const statusColors = {
    scheduled: 'bg-blue-500',
    in_progress: 'bg-green-600',
    completed: 'bg-gray-400',
    cancelled: 'bg-red-400',
} as const;

const statusLabels = {
    scheduled: 'Sắp tới',
    in_progress: 'Đang chạy',
    completed: 'Hoàn thành',
    cancelled: 'Đã hủy',
} as const;

function fmtTime(iso: string) {
    return new Date(iso).toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
function fmtDate(d: Date) {
    return d.toLocaleDateString('vi-VN', {
        weekday: 'short',
        day: 'numeric',
        month: 'numeric',
    });
}

// Upcoming trips sorted
const upcomingTrips = computed(() =>
    trips.value
        .filter((t) => t.status === 'scheduled' || t.status === 'in_progress')
        .sort(
            (a, b) =>
                new Date(a.depart_at).getTime() -
                new Date(b.depart_at).getTime(),
        ),
);

async function load() {
    isLoading.value = true;
    errorMsg.value = '';
    const from = weekDates.value[0].toISOString().split('T')[0];
    const to = weekDates.value[6].toISOString().split('T')[0];
    const { data, error } = await driverApi.getTrips({
        date: undefined,
    } as any);
    // Fallback: getTrips doesn't support from/to, use full list
    isLoading.value = false;
    if (error) {
        errorMsg.value = error as string;
        return;
    }
    trips.value = data ?? [];
}

onMounted(load);
</script>

<template>
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Lịch chạy của tôi
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">{{ weekLabel }}</p>
            </div>

            <!-- Week navigation -->
            <div class="flex items-center gap-2">
                <button
                    @click="
                        weekOffset--;
                        load();
                    "
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                >
                    ‹
                </button>
                <button
                    @click="
                        weekOffset = 0;
                        load();
                    "
                    :class="[
                        'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        weekOffset === 0
                            ? 'bg-green-600 text-white'
                            : 'border border-gray-200 text-gray-600 hover:bg-gray-50',
                    ]"
                >
                    Tuần này
                </button>
                <button
                    @click="
                        weekOffset++;
                        load();
                    "
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                >
                    ›
                </button>
            </div>
        </div>

        <!-- Error -->
        <div
            v-if="errorMsg"
            class="mb-5 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600"
        >
            <span>{{ errorMsg }}</span>
            <button @click="load" class="text-sm font-semibold underline">
                Thử lại
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div class="grid grid-cols-7 gap-2">
                <div
                    v-for="i in 7"
                    :key="i"
                    class="h-60 animate-pulse rounded-xl bg-gray-200"
                />
            </div>
            <div class="h-48 animate-pulse rounded-xl bg-gray-200" />
        </div>

        <div v-else class="space-y-5">
            <!-- ─── Weekly calendar grid ──────────────────────────── -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Day headers -->
                <div class="grid grid-cols-7 border-b border-gray-100">
                    <div
                        v-for="(date, i) in weekDates"
                        :key="i"
                        :class="[
                            'border-r border-gray-100 px-3 py-3 text-center last:border-r-0',
                            isToday(date)
                                ? 'bg-green-50'
                                : isPast(date)
                                  ? 'bg-gray-50/50'
                                  : '',
                        ]"
                    >
                        <p
                            :class="[
                                'text-xs font-semibold tracking-wider uppercase',
                                isToday(date)
                                    ? 'text-green-600'
                                    : 'text-gray-400',
                            ]"
                        >
                            {{ dayNames[i] }}
                        </p>
                        <p
                            :class="[
                                'mt-0.5 text-lg font-black',
                                isToday(date)
                                    ? 'text-green-600'
                                    : isPast(date)
                                      ? 'text-gray-400'
                                      : 'text-gray-800',
                            ]"
                        >
                            {{ date.getDate() }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{
                                date.toLocaleDateString('vi-VN', {
                                    month: 'numeric',
                                })
                            }}/{{ date.getFullYear().toString().slice(-2) }}
                        </p>
                    </div>
                </div>

                <!-- Day columns with trip blocks -->
                <div class="grid min-h-[200px] grid-cols-7">
                    <div
                        v-for="(date, dayIdx) in weekDates"
                        :key="dayIdx"
                        :class="[
                            'space-y-1.5 border-r border-gray-100 px-2 py-3 last:border-r-0',
                            isToday(date)
                                ? 'bg-green-50/40'
                                : isPast(date)
                                  ? 'bg-gray-50/20'
                                  : '',
                        ]"
                    >
                        <!-- Trip blocks for this day -->
                        <div v-if="tripsByDay[dayIdx]?.length > 0">
                            <div
                                v-for="trip in tripsByDay[dayIdx]"
                                :key="trip.id"
                                @click="selectedTrip = trip"
                                :class="[
                                    'cursor-pointer rounded-lg px-2 py-2 transition-all hover:scale-[1.02] hover:shadow-sm',
                                    statusColors[trip.status] ?? 'bg-gray-400',
                                    'text-white',
                                ]"
                            >
                                <p class="text-xs leading-tight font-black">
                                    {{ fmtTime(trip.depart_at) }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs leading-tight font-medium opacity-90"
                                >
                                    HN→HP
                                </p>
                                <p class="mt-0.5 text-xs opacity-70">
                                    {{ trip.passengers_count }} khách
                                </p>
                            </div>
                        </div>

                        <!-- Empty day -->
                        <div
                            v-else
                            class="flex h-16 items-center justify-center"
                        >
                            <span class="text-xs text-gray-300">—</span>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div
                    class="flex items-center gap-4 border-t border-gray-100 px-4 py-3"
                >
                    <span class="text-xs font-medium text-gray-400"
                        >Trạng thái:</span
                    >
                    <div
                        v-for="(color, key) in statusColors"
                        :key="key"
                        class="flex items-center gap-1.5"
                    >
                        <div :class="['h-3 w-3 rounded-sm', color]" />
                        <span class="text-xs text-gray-500">{{
                            statusLabels[key as keyof typeof statusLabels]
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- ─── Upcoming trips table ───────────────────────────── -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                >
                    <h2 class="font-bold text-gray-900">Lịch sắp tới</h2>
                    <span class="text-sm text-gray-400"
                        >{{ upcomingTrips.length }} chuyến</span
                    >
                </div>

                <div
                    v-if="upcomingTrips.length === 0"
                    class="py-10 text-center text-gray-400"
                >
                    <div class="mb-2 text-3xl">📅</div>
                    <p>Không có chuyến sắp tới trong tuần này</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Ngày
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Giờ
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Tuyến
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Xe
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Số khách đặt
                                </th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Trạng thái
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="trip in upcomingTrips"
                                :key="trip.id"
                                @click="
                                    $router.push(`/driver/trips/${trip.id}`)
                                "
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                            >
                                <td class="px-5 py-4 text-gray-600">
                                    {{
                                        new Date(
                                            trip.depart_at,
                                        ).toLocaleDateString('vi-VN', {
                                            weekday: 'short',
                                            day: 'numeric',
                                            month: 'numeric',
                                        })
                                    }}
                                </td>
                                <td
                                    class="px-5 py-4 font-mono font-bold text-gray-900"
                                >
                                    {{ fmtTime(trip.depart_at) }}
                                </td>
                                <td
                                    class="px-5 py-4 font-semibold text-gray-900"
                                >
                                    {{ trip.route?.origin_city }} →
                                    {{ trip.route?.dest_city }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs text-gray-600"
                                    >
                                        {{ trip.vehicle?.plate_number }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100"
                                        >
                                            <div
                                                class="h-full rounded-full bg-green-500"
                                                :style="{
                                                    width:
                                                        (trip.passengers_count /
                                                            (trip.vehicle
                                                                ?.seat_count ||
                                                                1)) *
                                                            100 +
                                                        '%',
                                                }"
                                            />
                                        </div>
                                        <span class="font-medium text-gray-700"
                                            >{{ trip.passengers_count }}/{{
                                                trip.vehicle?.seat_count
                                            }}</span
                                        >
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                            trip.status === 'in_progress'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-blue-50 text-blue-600',
                                        ]"
                                    >
                                        {{
                                            statusLabels[trip.status] ??
                                            trip.status
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── Trip detail slide-over ─────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="selectedTrip"
                class="fixed inset-0 z-50 flex items-center justify-end bg-black/30"
                @click.self="selectedTrip = null"
            >
                <div
                    class="h-full w-full max-w-sm overflow-y-auto bg-white p-6 shadow-2xl"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            Chi tiết chuyến
                        </h3>
                        <button
                            @click="selectedTrip = null"
                            class="text-gray-400 transition-colors hover:text-gray-600"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="rounded-xl bg-green-50 p-4">
                            <p class="text-xl font-black text-gray-900">
                                {{ selectedTrip.route?.origin_city }} →
                                {{ selectedTrip.route?.dest_city }}
                            </p>
                            <p class="mt-1 font-semibold text-green-700">
                                {{ fmtTime(selectedTrip.depart_at) }} →
                                {{ fmtTime(selectedTrip.arrive_at) }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-gray-50 p-3">
                                <p class="text-xs text-gray-400">Biển số xe</p>
                                <p
                                    class="mt-0.5 font-mono font-bold text-gray-900"
                                >
                                    {{ selectedTrip.vehicle?.plate_number }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-3">
                                <p class="text-xs text-gray-400">
                                    Số hành khách
                                </p>
                                <p class="mt-0.5 font-bold text-gray-900">
                                    {{ selectedTrip.passengers_count }}/{{
                                        selectedTrip.vehicle?.seat_count
                                    }}
                                </p>
                            </div>
                        </div>
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold',
                                selectedTrip.status === 'in_progress'
                                    ? 'bg-green-100 text-green-700'
                                    : selectedTrip.status === 'completed'
                                      ? 'bg-gray-100 text-gray-600'
                                      : selectedTrip.status === 'cancelled'
                                        ? 'bg-red-100 text-red-600'
                                        : 'bg-blue-50 text-blue-600',
                            ]"
                        >
                            {{
                                statusLabels[selectedTrip.status] ??
                                selectedTrip.status
                            }}
                        </span>
                    </div>

                    <div class="mt-6 space-y-3">
                        <router-link
                            :to="`/driver/trips/${selectedTrip.id}`"
                            @click="selectedTrip = null"
                            class="block w-full rounded-xl bg-green-600 py-3 text-center font-bold text-white transition-colors hover:bg-green-700"
                        >
                            Xem chi tiết →
                        </router-link>
                        <button
                            @click="selectedTrip = null"
                            class="block w-full rounded-xl border border-gray-200 py-3 text-center font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
