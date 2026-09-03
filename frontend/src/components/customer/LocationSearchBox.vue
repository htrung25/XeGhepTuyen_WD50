<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import { usePlaceSearch } from '@/composables/usePlaceSearch';
import type { PlaceSuggestion } from '@/composables/usePlaceSearch';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const props = defineProps<{
    modelValue: string;
    placeholder: string;
    token?: string;
    cityBias?: string;
    boundary?: ServiceAreaBoundary | null;
    confirmed: boolean;
    locating?: boolean;
    hasError?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    editing: [];
    select: [place: PlaceSuggestion];
    clear: [];
    'use-current-location': [];
}>();

const isFocused = shallowRef(false);
const highlightedIndex = shallowRef(-1);

const {
    suggestions,
    isSearching,
    searchError,
    lastCompletedQuery,
    search,
    clearSuggestions,
} = usePlaceSearch({
    token: props.token,
    cityBias: () => props.cityBias,
    boundary: () => props.boundary,
});

const normalizedQuery = computed(() => props.modelValue.trim());
const showDropdown = computed(() => isFocused.value);
const showEmptyState = computed(
    () =>
        !isSearching.value &&
        !searchError.value &&
        normalizedQuery.value.length >= 2 &&
        lastCompletedQuery.value === normalizedQuery.value &&
        suggestions.value.length === 0,
);

watch(
    () => props.modelValue,
    () => {
        highlightedIndex.value = -1;
    },
);

function handleInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    emit('update:modelValue', value);
    emit('editing');
    search(value);
}

function handleFocus(): void {
    isFocused.value = true;
    if (normalizedQuery.value.length >= 2) search(props.modelValue);
}

function handleBlur(): void {
    window.setTimeout(() => {
        isFocused.value = false;
    }, 150);
}

function chooseSuggestion(place: PlaceSuggestion): void {
    emit('update:modelValue', place.address);
    emit('select', place);
    clearSuggestions();
    isFocused.value = false;
}

function clearInput(): void {
    emit('update:modelValue', '');
    emit('clear');
    clearSuggestions();
}

function useCurrentLocation(): void {
    emit('use-current-location');
    clearSuggestions();
    isFocused.value = false;
}

function handleKeydown(event: KeyboardEvent): void {
    if (!showDropdown.value) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = Math.min(
            highlightedIndex.value + 1,
            suggestions.value.length - 1,
        );
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1);
    } else if (event.key === 'Enter' && highlightedIndex.value >= 0) {
        event.preventDefault();
        const place = suggestions.value[highlightedIndex.value];
        if (place) chooseSuggestion(place);
    } else if (event.key === 'Escape') {
        isFocused.value = false;
    }
}
</script>

<template>
    <div class="relative min-w-0 flex-1">
        <div
            class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center"
            :class="confirmed ? 'text-green-600' : 'text-slate-400'"
        >
            <svg
                v-if="confirmed"
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7"
                />
            </svg>
            <svg
                v-else
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
        </div>

        <input
            :value="modelValue"
            type="search"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="showDropdown"
            :placeholder="placeholder"
            class="h-12 w-full rounded-xl border bg-white pr-10 pl-11 text-base text-slate-900 transition placeholder:text-slate-400 focus:ring-2 focus:outline-none"
            :class="[
                hasError
                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                    : confirmed
                      ? 'border-green-300 focus:border-green-500 focus:ring-green-500/20'
                      : 'border-slate-200 focus:border-green-500 focus:ring-green-500/20',
            ]"
            @input="handleInput"
            @focus="handleFocus"
            @blur="handleBlur"
            @keydown="handleKeydown"
        />

        <div class="absolute inset-y-0 right-3 flex items-center gap-1">
            <span
                v-if="isSearching"
                class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-green-600"
                aria-label="Đang tìm địa điểm"
            />
            <button
                v-else-if="modelValue"
                type="button"
                aria-label="Xóa địa điểm"
                class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                @mousedown.prevent="clearInput"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
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

        <div
            v-if="showDropdown"
            class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <button
                type="button"
                class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-green-50"
                @mousedown.prevent="useCurrentLocation"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 2v2m0 16v2M4 12H2m20 0h-2m-2.343-5.657-1.414 1.414M7.757 16.243l-1.414 1.414m0-11.314 1.414 1.414m8.486 8.486 1.414 1.414M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                </span>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">
                        {{
                            locating ? 'Đang lấy vị trí...' : 'Vị trí hiện tại'
                        }}
                    </span>
                    <span class="block text-xs text-slate-500">
                        Dùng GPS rồi tinh chỉnh ghim trên bản đồ
                    </span>
                </span>
            </button>

            <div v-if="suggestions.length" class="max-h-72 overflow-y-auto">
                <p
                    class="px-4 pt-3 pb-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase"
                >
                    Địa điểm gợi ý
                </p>
                <button
                    v-for="(place, index) in suggestions"
                    :key="place.id"
                    type="button"
                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition"
                    :class="
                        highlightedIndex === index
                            ? 'bg-green-50'
                            : 'hover:bg-slate-50'
                    "
                    @mousedown.prevent="chooseSuggestion(place)"
                >
                    <span
                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 21s6-4.35 6-11a6 6 0 10-12 0c0 6.65 6 11 6 11z"
                            />
                            <circle cx="12" cy="10" r="2" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span
                            class="block truncate text-sm font-semibold text-slate-900"
                        >
                            {{ place.name }}
                        </span>
                        <span
                            v-if="place.secondaryText"
                            class="mt-0.5 line-clamp-2 block text-xs leading-relaxed text-slate-500"
                        >
                            {{ place.secondaryText }}
                        </span>
                    </span>
                </button>
            </div>

            <p
                v-else-if="searchError"
                class="px-4 py-3 text-sm text-red-600"
                role="alert"
            >
                {{ searchError }}
            </p>
            <p
                v-else-if="showEmptyState"
                class="px-4 py-4 text-center text-sm text-slate-500"
            >
                Không tìm thấy địa điểm phù hợp. Hãy thử tên đường, số nhà hoặc
                địa danh gần đó.
            </p>
            <p
                v-else-if="normalizedQuery.length === 1"
                class="px-4 py-3 text-xs text-slate-500"
            >
                Nhập thêm ít nhất một ký tự để tìm kiếm.
            </p>
        </div>
    </div>
</template>
