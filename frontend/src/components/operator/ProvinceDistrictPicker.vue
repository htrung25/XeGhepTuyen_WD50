<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { geoApi } from '@/api/geo.api';
import type { Province } from '@/api/geo.api';

const props = defineProps<{
    label: string;
    provinceCode: string;
    districtCode: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:provinceCode', value: string): void;
    (e: 'update:districtCode', value: string): void;
}>();

const provinces = ref<Province[]>([]);
const loading = ref(true);

onMounted(async () => {
    provinces.value = await geoApi.getProvinces();
    loading.value = false;
});

const districts = computed(
    () =>
        provinces.value.find((p) => p.code === props.provinceCode)?.districts ??
        [],
);

// Đổi tỉnh phải reset huyện: giữ huyện cũ sẽ tạo ra cặp tỉnh–huyện không tồn
// tại và BE từ chối ở bước lưu.
const onProvinceChange = (event: Event) => {
    emit('update:provinceCode', (event.target as HTMLSelectElement).value);
    emit('update:districtCode', '');
};

const selectClass =
    'w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400';
</script>

<template>
    <div>
        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
            {{ label }} *
        </label>
        <div class="grid grid-cols-2 gap-2">
            <select
                :value="provinceCode"
                :disabled="disabled || loading"
                :class="selectClass"
                @change="onProvinceChange"
            >
                <option value="">
                    {{ loading ? 'Đang tải...' : 'Chọn tỉnh/thành' }}
                </option>
                <option v-for="p in provinces" :key="p.code" :value="p.code">
                    {{ p.name }}
                </option>
            </select>
            <select
                :value="districtCode"
                :disabled="disabled || !provinceCode"
                :class="selectClass"
                @change="
                    emit(
                        'update:districtCode',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">
                    {{ provinceCode ? 'Chọn quận/huyện' : 'Chọn tỉnh trước' }}
                </option>
                <option v-for="d in districts" :key="d.code" :value="d.code">
                    {{ d.name }}
                </option>
            </select>
        </div>
    </div>
</template>
