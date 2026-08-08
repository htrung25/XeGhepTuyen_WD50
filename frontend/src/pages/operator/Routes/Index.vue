<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';
import type {
    OperatorRoutePayload,
    OperatorRouteStopPayload,
} from '@/api/operator.api';

interface StopForm {
    id?: string;
    stop_name: string;
    address: string;
    stop_order: number;
    lat: string;
    lng: string;
    offset_minutes: number;
    is_pickup: boolean;
    is_dropoff: boolean;
}

interface RouteRow {
    id: string;
    name: string;
    origin_city: string;
    dest_city: string;
    distance_km: number;
    est_duration_min: number;
    base_price: number;
    stops_count?: number;
    stops?: StopForm[];
    is_active: boolean;
    is_round_trip: boolean;
}

const routes = ref<RouteRow[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');

// Modal state
const showModal = ref(false);
const saving = ref(false);
const saveError = ref('');
const editingId = ref<string | null>(null);

const form = ref({
    name: 'Hà Nội → Hải Phòng',
    origin_city: 'Hà Nội',
    dest_city: 'Hải Phòng',
    distance_km: 105,
    est_duration_min: 150,
    base_price: 120000,
    is_round_trip: false,
    is_active: true,
    stops: [] as StopForm[],
});

const addStop = () => {
    if (form.value.stops.length > 0) {
        form.value.stops[form.value.stops.length - 1].is_dropoff = false;
    }
    const isFirst = form.value.stops.length === 0;
    form.value.stops.push({
        stop_name: '',
        address: '',
        stop_order: form.value.stops.length + 1,
        lat: '',
        lng: '',
        offset_minutes: form.value.stops.length === 0 ? 0 : 60,
        is_pickup: isFirst,
        is_dropoff: !isFirst,
    });
};

const removeStop = (idx: number) => {
    form.value.stops.splice(idx, 1);
    form.value.stops.forEach((stop, index) => {
        stop.stop_order = index + 1;
        stop.is_pickup = index === 0;
        stop.is_dropoff = index === form.value.stops.length - 1;
    });
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
    editingId.value = null;
    form.value = {
        name: 'Hà Nội → Hải Phòng',
        origin_city: 'Hà Nội',
        dest_city: 'Hải Phòng',
        distance_km: 105,
        est_duration_min: 150,
        base_price: 120000,
        is_round_trip: false,
        is_active: true,
        stops: [],
    };
    addStop();
    addStop();
    showModal.value = true;
    saveError.value = '';
};

const openEdit = async (row: RouteRow) => {
    saveError.value = '';
    const { data, error } = await operatorApi.getRoute(row.id);
    if (error || !data) {
        errorMsg.value = error ?? 'Không thể tải chi tiết tuyến đường';
        return;
    }

    editingId.value = row.id;
    form.value = {
        name: data.name,
        origin_city: data.origin_city,
        dest_city: data.dest_city,
        distance_km: Number(data.distance_km),
        est_duration_min: Number(data.est_duration_min),
        base_price: Number(data.base_price),
        is_round_trip: Boolean(data.is_round_trip),
        is_active: Boolean(data.is_active),
        stops: (data.stops ?? []).map((stop: any) => ({
            id: stop.id,
            stop_name: stop.stop_name,
            address: stop.address,
            stop_order: Number(stop.stop_order),
            lat: String(stop.lat),
            lng: String(stop.lng),
            offset_minutes: Number(stop.offset_minutes),
            is_pickup: Boolean(stop.is_pickup),
            is_dropoff: Boolean(stop.is_dropoff),
        })),
    };
    showModal.value = true;
};

const saveRoute = async () => {
    if (!form.value.name.trim()) {
        saveError.value = 'Vui lòng nhập tên tuyến';
        return;
    }
    if (!editingId.value && form.value.stops.length < 2) {
        saveError.value = 'Tuyến phải có ít nhất 2 điểm dừng';
        return;
    }
    if (
        !editingId.value &&
        form.value.stops.some(
            (stop) =>
                !stop.stop_name.trim() ||
                !stop.address.trim() ||
                stop.lat.trim() === '' ||
                stop.lng.trim() === '',
        )
    ) {
        saveError.value =
            'Vui lòng nhập đủ tên, địa chỉ và tọa độ cho mọi điểm dừng';
        return;
    }

    const routeFields = {
        name: form.value.name.trim(),
        origin_city: form.value.origin_city.trim(),
        dest_city: form.value.dest_city.trim(),
        distance_km: Number(form.value.distance_km),
        est_duration_min: Number(form.value.est_duration_min),
        base_price: Number(form.value.base_price),
        is_round_trip: form.value.is_round_trip,
        is_active: form.value.is_active,
    };

    const stops: OperatorRouteStopPayload[] = form.value.stops.map((stop) => ({
        stop_name: stop.stop_name.trim(),
        address: stop.address.trim(),
        lat: Number(stop.lat),
        lng: Number(stop.lng),
        stop_order: stop.stop_order,
        offset_minutes: Number(stop.offset_minutes),
        is_pickup: stop.is_pickup,
        is_dropoff: stop.is_dropoff,
    }));

    saving.value = true;
    saveError.value = '';
    const { error } = editingId.value
        ? await operatorApi.updateRoute(editingId.value, routeFields)
        : await operatorApi.createRoute({
              ...routeFields,
              stops,
          } satisfies OperatorRoutePayload);
    saving.value = false;
    if (error) {
        saveError.value = error;
        return;
    }
    showModal.value = false;
    editingId.value = null;
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
                                {{ route.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ route.origin_city }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ route.dest_city }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{
                                    route.stops_count ??
                                    route.stops?.length ??
                                    0
                                }}
                                điểm
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
                                        @click="openEdit(route)"
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
                            {{
                                editingId
                                    ? 'Chỉnh sửa tuyến đường'
                                    : 'Thêm tuyến đường mới'
                            }}
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

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Tên tuyến *</label
                                >
                                <input
                                    v-model="form.name"
                                    placeholder="VD: Hà Nội → Hải Phòng"
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
                                    min="1"
                                    max="2000"
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
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Thời gian dự kiến (phút)</label
                                >
                                <input
                                    v-model.number="form.est_duration_min"
                                    type="number"
                                    min="1"
                                    max="1440"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Giá vé cơ bản (đ)</label
                                >
                                <input
                                    v-model.number="form.base_price"
                                    type="number"
                                    min="50000"
                                    max="500000"
                                    step="1000"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                />
                            </div>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"
                            >
                                <input
                                    v-model="form.is_round_trip"
                                    type="checkbox"
                                    class="accent-amber-500"
                                />
                                Có khai thác chiều về
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"
                            >
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="accent-amber-500"
                                />
                                Đang hoạt động
                            </label>
                        </div>

                        <!-- Stops timeline -->
                        <div v-if="!editingId">
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
                                            v-model="stop.address"
                                            placeholder="Địa chỉ"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                        />
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <input
                                                v-model="stop.lat"
                                                inputmode="decimal"
                                                placeholder="Vĩ độ"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                            />
                                            <input
                                                v-model="stop.lng"
                                                inputmode="decimal"
                                                placeholder="Kinh độ"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                            />
                                            <input
                                                v-model.number="
                                                    stop.offset_minutes
                                                "
                                                type="number"
                                                min="0"
                                                placeholder="Phút từ điểm đầu"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none"
                                            />
                                        </div>
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
                        <div
                            v-else
                            class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700"
                        >
                            Điểm dừng không được thay đổi tại màn hình này để
                            bảo toàn lịch chuyến đã tạo.
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
                            {{
                                saving
                                    ? 'Đang lưu...'
                                    : editingId
                                      ? 'Lưu thay đổi'
                                      : 'Tạo tuyến đường'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
