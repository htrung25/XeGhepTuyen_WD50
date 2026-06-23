<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerStore } from '@/stores/customer.store';
import type { TripResult } from '@/stores/customer.store';

const router = useRouter();
const store = useCustomerStore();

const trips = ref<TripResult[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');

const filterTime = ref<string[]>([]);
const filterType = ref<string[]>([]);
const sortBy = ref<'depart_asc' | 'price_asc' | 'seats_desc'>('depart_asc');

const timeOptions = [
    { key: 'morning', label: 'Sáng (05–12h)' },
    { key: 'afternoon', label: 'Chiều (12–18h)' },
    { key: 'evening', label: 'Tối (18–22h)' },
];
const typeOptions = [
    { key: 'mpv_7', label: '7 chỗ' },
    { key: 'van_9', label: '9 chỗ' },
    { key: 'minibus_16', label: '16 chỗ' },
];

const filtered = computed(() => {
    let list = [...trips.value];

    if (filterTime.value.length) {
        list = list.filter((t) => {
            const h = new Date(t.depart_at).getHours();
            return filterTime.value.some((f) => {
                if (f === 'morning') return h >= 5 && h < 12;
                if (f === 'afternoon') return h >= 12 && h < 18;
                if (f === 'evening') return h >= 18 && h < 22;
                return true;
            });
        });
    }

    if (filterType.value.length) {
        list = list.filter((t) =>
            filterType.value.some((f) =>
                t.vehicle?.vehicle_type
                    ?.toLowerCase()
                    .includes(f.split('_')[0]),
            ),
        );
    }

    if (sortBy.value === 'price_asc')
        list = [...list].sort((a, b) => a.price - b.price);
    if (sortBy.value === 'seats_desc')
        list = [...list].sort((a, b) => b.available_seats - a.available_seats);

    return list;
});

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

function fmtTime(iso: string) {
    return new Date(iso).toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}

function fmtDuration(min: number) {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return m ? `${h}h${m}p` : `${h}h`;
}

function vehicleLabel(type: string) {
    if (!type) return '7 chỗ';
    if (type.includes('16')) return '16 chỗ';
    if (type.includes('9')) return '9 chỗ';
    return '7 chỗ';
}

function selectTrip(trip: TripResult) {
    store.selectedTrip = trip;
    store.bookingDraft.trip_id = trip.id;
    router.push(`/trips/${trip.id}/seats`);
}

function toggleFilter(arr: string[], key: string) {
    const i = arr.indexOf(key);
    if (i >= 0) arr.splice(i, 1);
    else arr.push(key);
}

function resetFilters() {
    filterTime.value = [];
    filterType.value = [];
    sortBy.value = 'depart_asc';
}

onMounted(async () => {
    const p = store.searchParams;
    if (!p.from_city || !p.to_city) {
        router.replace('/home');
        return;
    }
    isLoading.value = true;
    const { data, error } = await customerApi.searchTrips({
        from_city: p.from_city,
        to_city: p.to_city,
        date: p.date,
        passengers: p.passengers,
    });
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải danh sách chuyến. Vui lòng thử lại.';
        return;
    }
    trips.value = data ?? [];
});
</script>

<template>
    <div class="mx-auto max-w-7xl px-6 py-8">
        <!-- Page title row -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    {{ store.searchParams.from_city }} →
                    {{ store.searchParams.to_city }}
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{
                        new Date(store.searchParams.date).toLocaleDateString(
                            'vi-VN',
                            {
                                weekday: 'long',
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                            },
                        )
                    }}
                    · {{ store.searchParams.passengers }} hành khách
                </p>
            </div>
            <router-link
                to="/home"
                class="rounded-lg border border-blue-200 px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50 hover:underline"
            >
                ← Thay đổi tìm kiếm
            </router-link>
        </div>

        <div class="flex gap-6">
            <!-- ─── LEFT SIDEBAR: Filters ─────────────────── -->
            <aside class="w-64 shrink-0">
                <div
                    class="sticky top-20 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Bộ lọc
                        </h3>
                        <button
                            @click="resetFilters"
                            class="text-xs font-medium text-blue-600 hover:underline"
                        >
                            Đặt lại
                        </button>
                    </div>

                    <!-- Time filter -->
                    <div class="mb-5">
                        <h4
                            class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            Giờ xuất phát
                        </h4>
                        <label
                            v-for="opt in timeOptions"
                            :key="opt.key"
                            class="group flex cursor-pointer items-center gap-2.5 py-1.5"
                        >
                            <input
                                type="checkbox"
                                :value="opt.key"
                                v-model="filterTime"
                                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span
                                class="text-sm text-gray-700 group-hover:text-gray-900"
                                >{{ opt.label }}</span
                            >
                        </label>
                    </div>

                    <!-- Vehicle type filter -->
                    <div class="mb-5">
                        <h4
                            class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            Loại xe
                        </h4>
                        <label
                            v-for="opt in typeOptions"
                            :key="opt.key"
                            class="group flex cursor-pointer items-center gap-2.5 py-1.5"
                        >
                            <input
                                type="checkbox"
                                :value="opt.key"
                                v-model="filterType"
                                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span
                                class="text-sm text-gray-700 group-hover:text-gray-900"
                                >{{ opt.label }}</span
                            >
                        </label>
                    </div>

                    <!-- Sort -->
                    <div>
                        <h4
                            class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            Sắp xếp
                        </h4>
                        <label
                            v-for="opt in [
                                { key: 'depart_asc', label: 'Giờ đi sớm nhất' },
                                { key: 'price_asc', label: 'Giá thấp nhất' },
                                { key: 'seats_desc', label: 'Nhiều chỗ nhất' },
                            ]"
                            :key="opt.key"
                            class="group flex cursor-pointer items-center gap-2.5 py-1.5"
                        >
                            <input
                                type="radio"
                                name="sort"
                                :value="opt.key"
                                v-model="sortBy"
                                class="h-4 w-4 cursor-pointer border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span
                                class="text-sm text-gray-700 group-hover:text-gray-900"
                                >{{ opt.label }}</span
                            >
                        </label>
                    </div>
                </div>
            </aside>

            <!-- ─── RIGHT MAIN: Trip List ─────────────────── -->
            <div class="min-w-0 flex-1">
                <!-- Result count -->
                <p class="mb-4 text-sm font-medium text-gray-500">
                    <template v-if="!isLoading">
                        Tìm thấy
                        <span class="font-semibold text-gray-900">{{
                            filtered.length
                        }}</span>
                        chuyến
                    </template>
                </p>

                <!-- Loading skeletons -->
                <div v-if="isLoading" class="space-y-4">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="animate-pulse rounded-xl border border-gray-200 bg-white p-5"
                    >
                        <div class="mb-4 flex justify-between">
                            <div class="h-6 w-24 rounded bg-gray-200" />
                            <div class="h-6 w-20 rounded bg-gray-200" />
                        </div>
                        <div class="mb-2 h-4 w-full rounded bg-gray-100" />
                        <div class="h-4 w-2/3 rounded bg-gray-100" />
                    </div>
                </div>

                <!-- Error state -->
                <div
                    v-else-if="errorMsg"
                    class="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700"
                >
                    <p class="mb-2 font-medium">{{ errorMsg }}</p>
                    <router-link
                        to="/home"
                        class="text-xs font-medium text-red-600 underline"
                    >
                        ← Quay lại tìm kiếm
                    </router-link>
                </div>

                <!-- Empty state -->
                <div
                    v-else-if="filtered.length === 0"
                    class="rounded-xl border border-gray-200 bg-white px-8 py-20 text-center"
                >
                    <div class="mb-4 text-5xl">🚌</div>
                    <h3 class="mb-2 text-lg font-bold text-gray-800">
                        Không tìm thấy chuyến phù hợp
                    </h3>
                    <p class="mb-6 text-sm text-gray-500">
                        Thử thay đổi ngày đi hoặc xóa bộ lọc hiện tại
                    </p>
                    <div class="flex justify-center gap-3">
                        <button
                            @click="resetFilters"
                            class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Xóa bộ lọc
                        </button>
                        <router-link
                            to="/home"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Tìm ngày khác
                        </router-link>
                    </div>
                </div>

                <!-- Trip cards -->
                <div v-else class="space-y-4">
                    <div
                        v-for="trip in filtered"
                        :key="trip.id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-blue-200 hover:shadow-md"
                    >
                        <div class="p-5">
                            <div class="flex items-start gap-5">
                                <!-- Operator & time -->
                                <div
                                    class="w-36 shrink-0 border-r border-gray-100 pr-5"
                                >
                                    <p
                                        class="mb-1 truncate text-sm font-semibold text-gray-800"
                                    >
                                        {{
                                            trip.operator?.company_name ??
                                            'Nhà xe'
                                        }}
                                    </p>
                                    <div class="mb-3 flex items-center gap-1">
                                        <svg
                                            class="h-3.5 w-3.5 fill-yellow-400 text-yellow-400"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                            />
                                        </svg>
                                        <span
                                            class="text-xs font-medium text-gray-600"
                                            >{{
                                                trip.driver?.rating_avg?.toFixed(
                                                    1,
                                                ) ?? '4.8'
                                            }}</span
                                        >
                                    </div>
                                    <span
                                        class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        {{
                                            vehicleLabel(
                                                trip.vehicle?.vehicle_type,
                                            )
                                        }}
                                    </span>
                                </div>

                                <!-- Time + route -->
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex items-center gap-4">
                                        <div
                                            class="text-2xl font-bold text-gray-900 tabular-nums"
                                        >
                                            {{ fmtTime(trip.depart_at) }}
                                        </div>
                                        <div
                                            class="flex flex-1 items-center gap-2"
                                        >
                                            <div
                                                class="h-2 w-2 shrink-0 rounded-full bg-blue-600"
                                            />
                                            <div
                                                class="flex-1 border-t border-dashed border-gray-300"
                                            />
                                            <span
                                                class="shrink-0 text-xs text-gray-400"
                                            >
                                                {{
                                                    fmtDuration(
                                                        trip.route
                                                            ?.est_duration_min ??
                                                            150,
                                                    )
                                                }}
                                            </span>
                                            <div
                                                class="flex-1 border-t border-dashed border-gray-300"
                                            />
                                            <div
                                                class="h-2 w-2 shrink-0 rounded-full bg-gray-400"
                                            />
                                        </div>
                                        <div
                                            class="text-xl font-semibold text-gray-600 tabular-nums"
                                        >
                                            {{ fmtTime(trip.arrive_at) }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center justify-between text-xs text-gray-500"
                                    >
                                        <span>{{
                                            trip.route?.origin_city ??
                                            store.searchParams.from_city
                                        }}</span>
                                        <span>{{
                                            trip.route?.dest_city ??
                                            store.searchParams.to_city
                                        }}</span>
                                    </div>

                                    <!-- Amenities -->
                                    <div class="mt-3 flex items-center gap-2">
                                        <span
                                            v-if="
                                                trip.vehicle?.amenities?.includes(
                                                    'wifi',
                                                )
                                            "
                                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs text-blue-600"
                                        >
                                            📶 WiFi
                                        </span>
                                        <span
                                            v-if="
                                                trip.vehicle?.amenities?.includes(
                                                    'ac',
                                                ) ||
                                                trip.vehicle?.amenities?.includes(
                                                    'điều_hoà',
                                                )
                                            "
                                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs text-blue-600"
                                        >
                                            ❄️ Điều hòa
                                        </span>
                                        <span
                                            v-if="
                                                trip.vehicle?.amenities?.includes(
                                                    'usb',
                                                )
                                            "
                                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs text-blue-600"
                                        >
                                            🔌 Sạc USB
                                        </span>
                                    </div>
                                </div>

                                <!-- Price + seats + button -->
                                <div
                                    class="flex shrink-0 flex-col items-end gap-3 border-l border-gray-100 pl-5"
                                >
                                    <div class="text-right">
                                        <div
                                            class="text-xl font-bold text-blue-600 tabular-nums"
                                        >
                                            {{ fmt(trip.price) }}
                                        </div>
                                        <div
                                            class="mt-0.5 text-xs text-gray-400"
                                        >
                                            /người
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                            trip.available_seats > 3
                                                ? 'bg-green-100 text-green-700'
                                                : trip.available_seats > 0
                                                  ? 'bg-orange-100 text-orange-700'
                                                  : 'bg-red-100 text-red-600',
                                        ]"
                                    >
                                        {{
                                            trip.available_seats > 0
                                                ? `Còn ${trip.available_seats} chỗ`
                                                : 'Hết chỗ'
                                        }}
                                    </span>
                                    <button
                                        @click="selectTrip(trip)"
                                        :disabled="trip.available_seats === 0"
                                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold whitespace-nowrap text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400"
                                    >
                                        {{
                                            trip.available_seats === 0
                                                ? 'Hết chỗ'
                                                : 'Chọn chuyến'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
