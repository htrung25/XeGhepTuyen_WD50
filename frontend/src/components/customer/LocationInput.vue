<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import LocationMapPicker from '@/components/customer/LocationMapPicker.vue';
import LocationSearchBox from '@/components/customer/LocationSearchBox.vue';
import type { PlaceSuggestion } from '@/composables/usePlaceSearch';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const props = defineProps<{
    label: string;
    placeholder?: string;
    modelValue: string;
    lat: number | null;
    lng: number | null;
    error?: string;
    cityBias?: string;
    boundary?: ServiceAreaBoundary | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:lat': [value: number | null];
    'update:lng': [value: number | null];
}>();

const token = import.meta.env.VITE_MAPBOX_TOKEN as string | undefined;
const searchValue = shallowRef(props.modelValue);
const isMapOpen = shallowRef(false);
const isLocating = shallowRef(false);
const localError = shallowRef('');
const pendingAddress = shallowRef('');
const pendingCoords = shallowRef<[number, number] | null>(null);

const isConfirmed = computed(
    () => !!props.modelValue.trim() && props.lat !== null && props.lng !== null,
);
const visibleError = computed(() => props.error || localError.value);

watch(
    () => props.modelValue,
    (value) => {
        searchValue.value = value;
    },
);

function updateSearchValue(value: string): void {
    searchValue.value = value;
    emit('update:modelValue', value);
}

function markAsEditing(): void {
    localError.value = '';
    emit('update:lat', null);
    emit('update:lng', null);
}

function clearLocation(): void {
    localError.value = '';
    pendingAddress.value = '';
    pendingCoords.value = null;
    emit('update:modelValue', '');
    emit('update:lat', null);
    emit('update:lng', null);
}

function openMap(coords: [number, number] | null = null, address = ''): void {
    localError.value = '';
    pendingCoords.value =
        coords ??
        (props.lng !== null && props.lat !== null
            ? [props.lng, props.lat]
            : null);
    pendingAddress.value = address || props.modelValue;
    isMapOpen.value = true;
}

function selectSuggestion(place: PlaceSuggestion): void {
    updateSearchValue(place.address);
    emit('update:lat', null);
    emit('update:lng', null);
    openMap(place.coordinates, place.address);
}

function useCurrentLocation(): void {
    localError.value = '';

    if (!navigator.geolocation) {
        localError.value = 'Thiết bị không hỗ trợ định vị GPS.';
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false;
            openMap(
                [position.coords.longitude, position.coords.latitude],
                'Vị trí hiện tại',
            );
        },
        () => {
            isLocating.value = false;
            localError.value =
                'Không thể lấy vị trí hiện tại. Hãy cấp quyền GPS hoặc chọn trên bản đồ.';
        },
        {
            enableHighAccuracy: true,
            maximumAge: 30_000,
            timeout: 10_000,
        },
    );
}

function closeMap(): void {
    isMapOpen.value = false;
}

function confirmLocation(payload: {
    address: string;
    lat: number;
    lng: number;
}): void {
    searchValue.value = payload.address;
    localError.value = '';
    emit('update:modelValue', payload.address);
    emit('update:lat', payload.lat);
    emit('update:lng', payload.lng);
    isMapOpen.value = false;
}
</script>

<template>
    <div class="relative w-full space-y-1.5">
        <label class="block text-sm font-semibold text-slate-700">
            {{ label }} <span class="text-red-500">*</span>
        </label>

        <div class="flex gap-2">
            <LocationSearchBox
                :model-value="searchValue"
                :placeholder="
                    placeholder || 'Tìm số nhà, tên đường hoặc địa điểm'
                "
                :token="token"
                :city-bias="cityBias"
                :boundary="boundary"
                :confirmed="isConfirmed"
                :locating="isLocating"
                :has-error="!!visibleError"
                @update:model-value="updateSearchValue"
                @editing="markAsEditing"
                @select="selectSuggestion"
                @clear="clearLocation"
                @use-current-location="useCurrentLocation"
            />

            <button
                type="button"
                class="flex h-12 shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-green-300 hover:bg-green-50 hover:text-green-700"
                @click="openMap()"
            >
                <svg
                    class="h-5 w-5 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L16 4m0 13V4m0 0L9 7"
                    />
                </svg>
                Bản đồ
            </button>
        </div>

        <p v-if="visibleError" class="text-xs text-red-600" role="alert">
            {{ visibleError }}
        </p>
        <p
            v-else-if="isConfirmed"
            class="flex items-center gap-1 text-xs font-medium text-green-700"
        >
            <span aria-hidden="true">✓</span>
            Địa điểm đã được xác nhận trên bản đồ
        </p>
        <p v-else class="text-xs text-slate-500">
            Chọn một gợi ý hoặc mở bản đồ để xác nhận chính xác vị trí.
        </p>

        <LocationMapPicker
            v-if="isMapOpen"
            :token="token"
            :initial-address="pendingAddress"
            :initial-coords="pendingCoords"
            :city-bias="cityBias"
            :boundary="boundary"
            @close="closeMap"
            @confirm="confirmLocation"
        />
    </div>
</template>
