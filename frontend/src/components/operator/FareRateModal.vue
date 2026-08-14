<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface RouteRow {
    id: string;
    name: string;
    origin_city: string;
    origin_district: string | null;
    dest_city: string;
    dest_district: string | null;
    distance_km: number;
    base_fare: number | null;
    price_per_km: number | null;
}

const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>();

const rows = ref<RouteRow[]>([]);
const roundingStep = ref(1000);
const loading = ref(true);
const saving = ref(false);
const errorMsg = ref('');

onMounted(async () => {
    const { data, error } = await operatorApi.getFareRates();
    loading.value = false;
    if (error) {
        errorMsg.value = error;
        return;
    }
    roundingStep.value = Number(data?.rounding_step ?? 1000);
    rows.value = ((data?.routes ?? []) as any[]).map((r) => ({
        id: r.id,
        name: r.name,
        origin_city: r.origin_city,
        origin_district: r.origin_district ?? null,
        dest_city: r.dest_city,
        dest_district: r.dest_district ?? null,
        distance_km: Number(r.distance_km),
        base_fare: r.price_per_km === null ? null : Number(r.base_fare ?? 0),
        price_per_km: r.price_per_km === null ? null : Number(r.price_per_km),
    }));
});

const unpricedRoutes = computed(() =>
    rows.value.filter((r) => r.price_per_km === null),
);

const place = (city: string, district: string | null) =>
    district ? `${district}, ${city}` : city;

const fmtMoney = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

const priceOf = (row: RouteRow) => {
    if (row.price_per_km === null) return null;
    const raw = Number(row.base_fare ?? 0) + row.price_per_km * row.distance_km;
    const step = roundingStep.value || 1;
    return Math.ceil(raw / step) * step;
};

/** Mở ô nhập giá cho một tuyến (đơn giá mặc định gợi ý) */
const startPricing = (row: RouteRow) => {
    row.base_fare = 0;
    row.price_per_km = 1000;
};

const clearPricing = (row: RouteRow) => {
    row.base_fare = null;
    row.price_per_km = null;
};

const save = async () => {
    errorMsg.value = '';

    const invalid = rows.value.find(
        (r) =>
            r.price_per_km !== null &&
            (!Number.isFinite(Number(r.price_per_km)) ||
                Number(r.price_per_km) < 0),
    );
    if (invalid) {
        errorMsg.value = `Đơn giá của tuyến "${invalid.name}" không hợp lệ`;
        return;
    }

    saving.value = true;
    const { error } = await operatorApi.saveFareRates(
        rows.value
            .filter((r) => r.price_per_km !== null)
            .map((r) => ({
                route_id: r.id,
                base_fare: Number(r.base_fare ?? 0),
                price_per_km: Number(r.price_per_km),
            })),
    );
    saving.value = false;

    if (error) {
        errorMsg.value = error;
        return;
    }
    emit('saved');
    emit('close');
};

const inputClass =
    'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none';
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="flex flex-shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            Cấu hình giá vé
                        </h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Chọn tuyến rồi đặt đơn giá mỗi km. Giá vé = phí mở
                            cửa + đơn giá × số km, làm tròn lên
                            {{ fmtMoney(roundingStep) }}
                        </p>
                    </div>
                    <button
                        class="text-slate-400 transition-colors hover:text-slate-600"
                        @click="emit('close')"
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

                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div
                        v-if="errorMsg"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ errorMsg }}
                    </div>

                    <div v-if="loading" class="space-y-3">
                        <div
                            v-for="i in 3"
                            :key="i"
                            class="h-12 animate-pulse rounded-lg bg-slate-100"
                        />
                    </div>

                    <div
                        v-else-if="rows.length === 0"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
                    >
                        Chưa có tuyến nào. Hãy tạo tuyến trước, sau đó quay lại
                        đây để gán giá vé.
                    </div>

                    <template v-else>
                        <p
                            v-if="unpricedRoutes.length"
                            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                        >
                            {{ unpricedRoutes.length }} tuyến chưa có giá vé —
                            chưa gán giá thì không lên lịch chạy được.
                        </p>

                        <!-- Bảng giá theo tuyến -->
                        <table class="w-full">
                            <thead>
                                <tr
                                    class="text-left text-xs text-slate-500 uppercase"
                                >
                                    <th class="pr-2 pb-2">Tuyến</th>
                                    <th class="pr-2 pb-2">Km</th>
                                    <th class="pr-2 pb-2">Phí mở cửa (đ)</th>
                                    <th class="pr-2 pb-2">Đơn giá / km (đ)</th>
                                    <th class="pr-2 pb-2">Giá vé</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in rows"
                                    :key="row.id"
                                    class="align-top"
                                >
                                    <td class="py-3 pr-2 text-sm">
                                        <div class="font-medium text-slate-800">
                                            {{ row.name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{
                                                place(
                                                    row.origin_city,
                                                    row.origin_district,
                                                )
                                            }}
                                            →
                                            {{
                                                place(
                                                    row.dest_city,
                                                    row.dest_district,
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td
                                        class="py-3 pr-2 text-sm text-slate-600"
                                    >
                                        {{ row.distance_km }}
                                    </td>
                                    <td class="py-1.5 pr-2">
                                        <input
                                            v-if="row.price_per_km !== null"
                                            v-model.number="row.base_fare"
                                            type="number"
                                            min="0"
                                            step="1000"
                                            :class="inputClass"
                                        />
                                        <span
                                            v-else
                                            class="text-sm text-slate-400"
                                            >—</span
                                        >
                                    </td>
                                    <td class="py-1.5 pr-2">
                                        <input
                                            v-if="row.price_per_km !== null"
                                            v-model.number="row.price_per_km"
                                            type="number"
                                            min="0"
                                            step="100"
                                            :class="inputClass"
                                        />
                                        <button
                                            v-else
                                            class="text-sm font-medium text-amber-600 underline"
                                            @click="startPricing(row)"
                                        >
                                            Chưa có giá — gán ngay
                                        </button>
                                    </td>
                                    <td
                                        class="py-3.5 pr-2 text-sm font-medium text-slate-700"
                                    >
                                        {{
                                            priceOf(row) === null
                                                ? '—'
                                                : fmtMoney(priceOf(row)!)
                                        }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <button
                                            v-if="row.price_per_km !== null"
                                            class="text-slate-400 transition-colors hover:text-red-500"
                                            title="Bỏ giá của tuyến này"
                                            @click="clearPricing(row)"
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
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <p class="text-xs text-slate-500">
                            Bỏ giá của một tuyến sẽ đưa tuyến đó về trạng thái
                            “chưa có giá” và không lên lịch chạy được.
                        </p>
                    </template>
                </div>

                <div
                    class="flex flex-shrink-0 justify-end gap-3 border-t border-slate-200 px-6 py-4"
                >
                    <button
                        class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                        @click="emit('close')"
                    >
                        Hủy
                    </button>
                    <button
                        :disabled="saving || loading || rows.length === 0"
                        class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-amber-600 disabled:bg-amber-300"
                        @click="save"
                    >
                        {{ saving ? 'Đang lưu...' : 'Lưu bảng giá' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
