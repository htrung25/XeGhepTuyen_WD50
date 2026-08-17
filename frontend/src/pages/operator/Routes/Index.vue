<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { geoApi } from '@/api/geo.api';
import { operatorApi } from '@/api/operator.api';
import type { OperatorRoutePayload } from '@/api/operator.api';
import FareRateModal from '@/components/operator/FareRateModal.vue';
import ProvinceDistrictPicker from '@/components/operator/ProvinceDistrictPicker.vue';

interface RouteRow {
    id: string;
    name: string;
    origin_city: string;
    origin_district?: string | null;
    dest_city: string;
    dest_district?: string | null;
    distance_km: number;
    est_duration_min: number;
    base_price: number;
    is_active: boolean;
    is_round_trip: boolean;
}

const routes = ref<RouteRow[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const routeSearch = ref('');
const routeStatusFilter = ref('all');
let routeSearchTimer: ReturnType<typeof setTimeout> | undefined;

// Modal state
const showModal = ref(false);
const showFareModal = ref(false);
const saving = ref(false);
const saveError = ref('');
const editingId = ref<string | null>(null);

const emptyForm = () => ({
    name: '',
    origin_province_code: '',
    origin_district_code: '',
    dest_province_code: '',
    dest_district_code: '',
    distance_km: 105,
    est_duration_min: 150,
    is_round_trip: false,
    is_active: true,
});

const form = ref(emptyForm());

const fmtMoney = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

const loadRoutes = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await operatorApi.getRoutes({
        search: routeSearch.value.trim() || undefined,
        status: routeStatusFilter.value,
    });
    isLoading.value = false;
    if (error) {
        errorMsg.value = 'Không thể tải danh sách tuyến đường';
        return;
    }
    routes.value = data ?? [];
};

const resetRouteFilters = () => {
    routeSearch.value = '';
    routeStatusFilter.value = 'all';
};

watch([routeSearch, routeStatusFilter], () => {
    if (routeSearchTimer) clearTimeout(routeSearchTimer);
    routeSearchTimer = setTimeout(() => loadRoutes(), 300);
});

// Backend tính lại giá vé của các tuyến khi bảng giá đổi ⇒ nạp lại danh sách.
const onFareRatesSaved = () => loadRoutes();

const openCreate = () => {
    editingId.value = null;
    form.value = emptyForm();
    saveError.value = '';
    showModal.value = true;
};

const openEdit = async (row: RouteRow) => {
    saveError.value = '';
    const { data, error } = await operatorApi.getRoute(row.id);
    if (error || !data) {
        errorMsg.value = error ?? 'Không thể tải chi tiết tuyến đường';
        return;
    }

    // routes chỉ lưu TÊN tỉnh/huyện; dropdown làm việc bằng mã nên phải tra ngược.
    const origin = await geoApi.resolveCodes(
        data.origin_city,
        data.origin_district,
    );
    const dest = await geoApi.resolveCodes(data.dest_city, data.dest_district);

    editingId.value = row.id;
    form.value = {
        name: data.name,
        origin_province_code: origin.provinceCode,
        origin_district_code: origin.districtCode,
        dest_province_code: dest.provinceCode,
        dest_district_code: dest.districtCode,
        distance_km: Number(data.distance_km),
        est_duration_min: Number(data.est_duration_min),
        is_round_trip: Boolean(data.is_round_trip),
        is_active: Boolean(data.is_active),
    };
    showModal.value = true;
};

const suggestedName = computed(() => {
    const label = (provinceCode: string, districtCode: string) => {
        const province = provincesCache.value.find(
            (p) => p.code === provinceCode,
        );
        if (!province) return '';
        const district = province.districts.find(
            (d) => d.code === districtCode,
        );
        return district ? `${district.name}, ${province.name}` : province.name;
    };

    const from = label(
        form.value.origin_province_code,
        form.value.origin_district_code,
    );
    const to = label(
        form.value.dest_province_code,
        form.value.dest_district_code,
    );

    return from && to ? `${from} → ${to}` : '';
});

const provincesCache = ref<Awaited<ReturnType<typeof geoApi.getProvinces>>>([]);

const applySuggestedName = () => {
    if (suggestedName.value) form.value.name = suggestedName.value;
};

const saveRoute = async () => {
    saveError.value = '';

    if (
        !form.value.origin_province_code ||
        !form.value.origin_district_code ||
        !form.value.dest_province_code ||
        !form.value.dest_district_code
    ) {
        saveError.value = 'Vui lòng chọn đủ tỉnh và huyện cho điểm đi/điểm đến';
        return;
    }

    if (form.value.origin_province_code === form.value.dest_province_code) {
        saveError.value = 'Tỉnh điểm đến phải khác tỉnh điểm đi';
        return;
    }

    const name = form.value.name.trim() || suggestedName.value;
    if (!name) {
        saveError.value = 'Vui lòng nhập tên tuyến';
        return;
    }

    const payload: OperatorRoutePayload = {
        name,
        origin_province_code: form.value.origin_province_code,
        origin_district_code: form.value.origin_district_code,
        dest_province_code: form.value.dest_province_code,
        dest_district_code: form.value.dest_district_code,
        distance_km: Number(form.value.distance_km),
        est_duration_min: Number(form.value.est_duration_min),
        is_round_trip: form.value.is_round_trip,
        is_active: form.value.is_active,
    };

    saving.value = true;
    const { error } = editingId.value
        ? await operatorApi.updateRoute(editingId.value, payload)
        : await operatorApi.createRoute(payload);
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

const placeLabel = (city: string, district?: string | null) =>
    district ? `${district}, ${city}` : city;

onMounted(async () => {
    provincesCache.value = await geoApi.getProvinces();
    await loadRoutes();
});
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
                    Thiết lập tuyến theo tỉnh/huyện, giá vé tính theo km
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    class="flex items-center gap-2 rounded-lg border border-amber-500 px-4 py-2 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-50"
                    @click="showFareModal = true"
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
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Cấu hình giá vé
                </button>
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
            <!-- Search & filters -->
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <div class="grid gap-3 md:grid-cols-4">
                    <label class="relative md:col-span-2">
                        <span class="sr-only">Tìm kiếm tuyến đường</span>
                        <svg
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-4-4" />
                        </svg>
                        <input
                            v-model="routeSearch"
                            type="search"
                            placeholder="Tìm tên tuyến, tỉnh hoặc huyện..."
                            class="w-full rounded-lg border border-slate-200 py-2 pr-3 pl-9 text-sm transition outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        />
                    </label>
                    <select
                        v-model="routeStatusFilter"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                    >
                        <option value="all">Tất cả trạng thái</option>
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Tạm ngừng</option>
                    </select>
                    <button
                        v-if="routeSearch || routeStatusFilter !== 'all'"
                        type="button"
                        class="rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 md:text-right"
                        @click="resetRouteFilters"
                    >
                        Xóa bộ lọc
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    {{ routes.length }} tuyến phù hợp
                </p>
            </div>

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
                <p class="font-medium">
                    {{
                        routeSearch || routeStatusFilter !== 'all'
                            ? 'Không tìm thấy tuyến phù hợp'
                            : 'Chưa có tuyến đường nào'
                    }}
                </p>
                <button
                    v-if="!routeSearch && routeStatusFilter === 'all'"
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
                                Khoảng cách
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                            >
                                Giá vé
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
                                {{
                                    placeLabel(
                                        route.origin_city,
                                        route.origin_district,
                                    )
                                }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{
                                    placeLabel(
                                        route.dest_city,
                                        route.dest_district,
                                    )
                                }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ route.distance_km }} km
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <span
                                    v-if="Number(route.base_price) > 0"
                                    class="text-slate-700"
                                >
                                    {{ fmtMoney(Number(route.base_price)) }}
                                </span>
                                <button
                                    v-else
                                    class="text-amber-600 underline"
                                    @click="showFareModal = true"
                                >
                                    Chưa cấu hình
                                </button>
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

        <!-- Cấu hình giá vé -->
        <FareRateModal
            v-if="showFareModal"
            @close="showFareModal = false"
            @saved="onFareRatesSaved"
        />

        <!-- Create / Edit Modal -->
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

                        <ProvinceDistrictPicker
                            v-model:province-code="form.origin_province_code"
                            v-model:district-code="form.origin_district_code"
                            label="Điểm đi"
                        />
                        <ProvinceDistrictPicker
                            v-model:province-code="form.dest_province_code"
                            v-model:district-code="form.dest_district_code"
                            label="Điểm đến"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                                    >Tên tuyến *</label
                                >
                                <div class="flex gap-2">
                                    <input
                                        v-model="form.name"
                                        :placeholder="
                                            suggestedName ||
                                            'VD: Hà Nội → Hải Phòng'
                                        "
                                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                                    />
                                    <button
                                        v-if="suggestedName"
                                        class="flex-shrink-0 rounded-lg border border-slate-200 px-3 text-sm text-slate-600 hover:bg-slate-50"
                                        @click="applySuggestedName"
                                    >
                                        Dùng gợi ý
                                    </button>
                                </div>
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

                        <!-- Giá vé gán sau, ở màn Cấu hình giá vé (chọn tuyến) -->
                        <div
                            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                        >
                            <span>
                                Giá vé không nhập ở đây: lưu tuyến xong, vào
                                <strong>Cấu hình giá vé</strong> chọn tuyến này
                                và đặt đơn giá mỗi km. Chưa có giá thì không lên
                                lịch chạy được.
                            </span>
                            <button
                                class="flex-shrink-0 font-medium text-amber-600 underline"
                                @click="showFareModal = true"
                            >
                                Cấu hình giá vé
                            </button>
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
