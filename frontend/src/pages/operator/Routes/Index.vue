<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface Stop {
    id?: string;
    stop_name: string;
    stop_address: string;
    stop_order: number;
    lat: string;
    lng: string;
    is_pickup: boolean;
    is_dropoff: boolean;
}

interface RouteRow {
    id: string;
    route_code: string;
    origin_city: string;
    dest_city: string;
    distance_km: number;
    duration_hours: number;
    stops_count?: number;
    is_active: boolean;
}

const routes = ref<RouteRow[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');

// Modal state
const showModal = ref(false);
const saving = ref(false);
const saveError = ref('');

const form = ref({
    route_code: '',
    origin_city: 'Hà Nội',
    dest_city: 'Hải Phòng',
    distance_km: 105,
    duration_hours: 2.5,
    description: '',
    stops: [] as Stop[],
});

const addStop = () => {
    form.value.stops.push({
        stop_name: '',
        stop_address: '',
        stop_order: form.value.stops.length + 1,
        lat: '',
        lng: '',
        is_pickup: true,
        is_dropoff: false,
    });
};

const removeStop = (idx: number) => {
    form.value.stops.splice(idx, 1);
    form.value.stops.forEach((s, i) => (s.stop_order = i + 1));
};

const loadRoutes = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await operatorApi.getRoutes();
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải danh sách tuyến đường';
        return;
    }
    routes.value = data ?? [];
};

const openCreate = () => {
    form.value = {
        route_code: '',
        origin_city: 'Hà Nội',
        dest_city: 'Hải Phòng',
        distance_km: 105,
        duration_hours: 2.5,
        description: '',
        stops: [],
    };
    addStop();
    showModal.value = true;
    saveError.value = '';
};

const saveRoute = async () => {
    if (!form.value.route_code || form.value.stops.length < 2) {
        saveError.value = 'Vui lòng nhập mã tuyến và ít nhất 2 điểm dừng';
        return;
    }
    saving.value = true;
    saveError.value = '';
    const { error } = await operatorApi.createRoute(form.value);
    saving.value = false;
    if (error) {
        saveError.value = error;
        return;
    }
    showModal.value = false;
    await loadRoutes();
};

const deleteRoute = async (id: string) => {
    if (!confirm('Bạn có chắc muốn xóa tuyến đường này?')) return;
    const { error } = await operatorApi.deleteRoute(id);
    if (error) {
        alert(error);
        return;
    }
    await loadRoutes();
};

onMounted(() => loadRoutes());
</script>

<template>
    <div class="space-y-5 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">
                    Quản lý tuyến đường
                </h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    Thiết lập tuyến và điểm dừng
                </p>
            </div>
            <button
                class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                @click="openCreate"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Thêm tuyến mới
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-3">
            <div
                v-for="i in 4"
                :key="i"
                class="h-16 animate-pulse rounded-xl border border-slate-200 bg-white"
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
            <button class="ml-auto text-sm underline" @click="loadRoutes">
                Thử lại
            </button>
        </div>

        <template v-else>
            <!-- Empty -->
            <div
                v-if="routes.length === 0"
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
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                    />
                </svg>
                <p class="font-medium">Chưa có tuyến đường nào</p>
                <button
                    class="mt-3 rounded-lg bg-amber-500 px-4 py-2 text-sm text-white transition-colors hover:bg-amber-600"
                    @click="openCreate"
                >
                    Tạo tuyến đầu tiên
                </button>
            </div>

            <!-- Table -->
            <div
                v-else
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Tên tuyến
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Điểm đi
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Điểm đến
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Số điểm dừng
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Khoảng cách
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Hành động
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="route in routes"
                            :key="route.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ route.origin_city }} → {{ route.dest_city }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ route.origin_city }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ route.dest_city }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ route.stops_count ?? '—' }} điểm
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ route.distance_km }} km
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="
                                        route.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-slate-100 text-slate-500'
                                    "
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >
                                    {{
                                        route.is_active
                                            ? 'Đang hoạt động'
                                            : 'Tạm ngừng'
                                    }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        class="text-sm font-medium text-amber-600 transition-colors hover:text-amber-700"
                                    >
                                        Chỉnh sửa
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        class="text-sm font-medium text-red-500 transition-colors hover:text-red-600"
                                        @click="deleteRoute(route.id)"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <!-- Create Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl"
                >
                    <!-- Modal header -->
                    <div
                        class="flex flex-shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4"
                    >
                        <h2 class="text-lg font-semibold text-slate-800">
                            Thêm tuyến đường mới
                        </h2>
                        <button
                            class="text-slate-400 transition-colors hover:text-slate-600"
                            @click="showModal = false"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                        <!-- Error -->
                        <div
                            v-if="saveError"
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        >
                            {{ saveError }}
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Mã tuyến *</label
                                >
                                <input
                                    v-model="form.route_code"
                                    placeholder="VD: HNHP"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Khoảng cách (km)</label
                                >
                                <input
                                    v-model.number="form.distance_km"
                                    type="number"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Điểm đi</label
                                >
                                <input
                                    v-model="form.origin_city"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Điểm đến</label
                                >
                                <input
                                    v-model="form.dest_city"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Stops timeline -->
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <label
                                    class="text-sm font-semibold text-slate-700"
                                    >Điểm dừng</label
                                >
                                <button
                                    class="flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-700"
                                    @click="addStop"
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
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    Thêm điểm dừng
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(stop, idx) in form.stops"
                                    :key="idx"
                                    class="flex items-start gap-3"
                                >
                                    <!-- Timeline dot -->
                                    <div
                                        class="flex flex-shrink-0 flex-col items-center pt-3"
                                    >
                                        <div
                                            :class="
                                                idx === 0
                                                    ? 'bg-green-500'
                                                    : idx ===
                                                        form.stops.length - 1
                                                      ? 'bg-red-500'
                                                      : 'bg-amber-500'
                                            "
                                            class="h-3 w-3 rounded-full"
                                        />
                                        <div
                                            v-if="idx < form.stops.length - 1"
                                            class="mt-1 h-8 w-0.5 bg-slate-200"
                                        />
                                    </div>

                                    <!-- Stop form -->
                                    <div
                                        class="flex-1 space-y-2 rounded-lg bg-slate-50 p-3"
                                    >
                                        <div class="flex gap-2">
                                            <input
                                                v-model="stop.stop_name"
                                                placeholder="Tên điểm dừng"
                                                class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                            />
                                            <button
                                                class="text-slate-400 transition-colors hover:text-red-500"
                                                @click="removeStop(idx)"
                                            >
                                                <svg
                                                    class="h-5 w-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <input
                                            v-model="stop.stop_address"
                                            placeholder="Địa chỉ"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                        />
                                        <div class="flex gap-4">
                                            <label
                                                class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"
                                            >
                                                <input
                                                    v-model="stop.is_pickup"
                                                    type="checkbox"
                                                    class="accent-amber-500"
                                                />
                                                Điểm đón
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"
                                            >
                                                <input
                                                    v-model="stop.is_dropoff"
                                                    type="checkbox"
                                                    class="accent-amber-500"
                                                />
                                                Điểm trả
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div
                        class="flex flex-shrink-0 justify-end gap-3 border-t border-slate-200 px-6 py-4"
                    >
                        <button
                            class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                            @click="showModal = false"
                        >
                            Hủy
                        </button>
                        <button
                            :disabled="saving"
                            class="flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-amber-600 disabled:bg-amber-300"
                            @click="saveRoute"
                        >
                            <svg
                                v-if="saving"
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
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            {{ saving ? 'Đang lưu...' : 'Lưu tuyến đường' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
