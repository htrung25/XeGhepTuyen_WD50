<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { geoApi } from '@/api/geo.api';
import type { Province } from '@/api/geo.api';
import { operatorApi } from '@/api/operator.api';

interface RateRow {
    province_code: string;
    district_code: string;
    base_fare: number;
    price_per_km: number;
}

const emit = defineEmits<{ (e: 'close'): void; (e: 'saved'): void }>();

const provinces = ref<Province[]>([]);
const rows = ref<RateRow[]>([]);
const roundingStep = ref(1000);
const loading = ref(true);
const saving = ref(false);
const errorMsg = ref('');

const districtsOf = (provinceCode: string) =>
    provinces.value.find((p) => p.code === provinceCode)?.districts ?? [];

onMounted(async () => {
    provinces.value = await geoApi.getProvinces();
    const { data, error } = await operatorApi.getFareRates();
    loading.value = false;
    if (error) {
        errorMsg.value = error;
        return;
    }
    roundingStep.value = Number(data?.rounding_step ?? 1000);
    rows.value = (data?.rates ?? []).map((r: any) => ({
        province_code: r.province_code ?? '',
        district_code: r.district_code ?? '',
        base_fare: Number(r.base_fare),
        price_per_km: Number(r.price_per_km),
    }));
    if (rows.value.length === 0) addRow();
});

const addRow = () => {
    rows.value.push({
        province_code: '',
        district_code: '',
        base_fare: 0,
        price_per_km: 1000,
    });
};

const removeRow = (idx: number) => rows.value.splice(idx, 1);

const onProvinceChange = (row: RateRow, value: string) => {
    row.province_code = value;
    row.district_code = '';
};

const preview = (row: RateRow, km: number) => {
    const raw = Number(row.base_fare) + Number(row.price_per_km) * km;
    const step = roundingStep.value || 1;
    return Math.ceil(raw / step) * step;
};

const fmtMoney = (n: number) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

// Có dòng mặc định (không chọn tỉnh) thì mọi tuyến đều tính được giá; thiếu nó
// thì tuyến ở tỉnh chưa khai báo sẽ bị BE từ chối khi tạo.
const hasDefaultRow = computed(() =>
    rows.value.some((r) => r.province_code === ''),
);

const save = async () => {
    errorMsg.value = '';

    if (
        rows.value.some((r) => r.district_code !== '' && r.province_code === '')
    ) {
        errorMsg.value = 'Đã chọn quận/huyện thì phải chọn tỉnh/thành';
        return;
    }

    const seen = new Set<string>();
    for (const row of rows.value) {
        const key = `${row.province_code}|${row.district_code}`;
        if (seen.has(key)) {
            errorMsg.value = 'Có hai dòng giá trùng khu vực';
            return;
        }
        seen.add(key);
    }

    saving.value = true;
    const { error } = await operatorApi.saveFareRates(
        rows.value.map((r) => ({
            province_code: r.province_code || null,
            district_code: r.district_code || null,
            base_fare: Number(r.base_fare),
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

const selectClass =
    'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400';
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
                            Giá vé mỗi tuyến = phí mở cửa + đơn giá × số km, làm
                            tròn lên {{ fmtMoney(roundingStep) }}
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

                    <div
                        v-if="!loading && !hasDefaultRow"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                    >
                        Chưa có dòng giá mặc định (bỏ trống tỉnh/thành). Tuyến
                        xuất phát từ khu vực chưa khai báo sẽ không tạo được.
                    </div>

                    <div v-if="loading" class="space-y-3">
                        <div
                            v-for="i in 3"
                            :key="i"
                            class="h-12 animate-pulse rounded-lg bg-slate-100"
                        />
                    </div>

                    <table v-else class="w-full">
                        <thead>
                            <tr
                                class="text-left text-xs text-slate-500 uppercase"
                            >
                                <th class="pr-2 pb-2">Khu vực điểm đi</th>
                                <th class="pr-2 pb-2">Phí mở cửa (đ)</th>
                                <th class="pr-2 pb-2">Đơn giá / km (đ)</th>
                                <th class="pr-2 pb-2">100 km</th>
                                <th class="pb-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, idx) in rows"
                                :key="idx"
                                class="align-top"
                            >
                                <td class="py-1.5 pr-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select
                                            :value="row.province_code"
                                            :class="selectClass"
                                            @change="
                                                onProvinceChange(
                                                    row,
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value,
                                                )
                                            "
                                        >
                                            <option value="">
                                                Mặc định (mọi tỉnh)
                                            </option>
                                            <option
                                                v-for="p in provinces"
                                                :key="p.code"
                                                :value="p.code"
                                            >
                                                {{ p.name }}
                                            </option>
                                        </select>
                                        <select
                                            v-model="row.district_code"
                                            :disabled="!row.province_code"
                                            :class="selectClass"
                                        >
                                            <option value="">Cả tỉnh</option>
                                            <option
                                                v-for="d in districtsOf(
                                                    row.province_code,
                                                )"
                                                :key="d.code"
                                                :value="d.code"
                                            >
                                                {{ d.name }}
                                            </option>
                                        </select>
                                    </div>
                                </td>
                                <td class="py-1.5 pr-2">
                                    <input
                                        v-model.number="row.base_fare"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        :class="inputClass"
                                    />
                                </td>
                                <td class="py-1.5 pr-2">
                                    <input
                                        v-model.number="row.price_per_km"
                                        type="number"
                                        min="0"
                                        step="100"
                                        :class="inputClass"
                                    />
                                </td>
                                <td
                                    class="py-3.5 pr-2 text-sm font-medium text-slate-700"
                                >
                                    {{ fmtMoney(preview(row, 100)) }}
                                </td>
                                <td class="py-3 text-right">
                                    <button
                                        class="text-slate-400 transition-colors hover:text-red-500"
                                        @click="removeRow(idx)"
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

                    <button
                        v-if="!loading"
                        class="flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-700"
                        @click="addRow"
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
                        Thêm dòng giá
                    </button>

                    <p class="text-xs text-slate-500">
                        Khi tạo tuyến, hệ thống tra bảng giá theo khu vực
                        <strong>điểm đi</strong>: ưu tiên dòng đúng quận/huyện,
                        rồi đến dòng cả tỉnh, cuối cùng là dòng mặc định.
                    </p>
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
                        :disabled="saving || loading"
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
