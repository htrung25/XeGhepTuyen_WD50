<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface HistoryItem {
    id: string;
    category: 'booking' | 'trip' | 'vehicle' | 'driver';
    action: string;
    severity: 'info' | 'success' | 'warning' | 'danger';
    title: string;
    description: string | null;
    metadata: Record<string, unknown> | null;
    actor: { id: string; full_name: string } | null;
    occurred_at: string;
}

interface HistoryMeta {
    current_page: number;
    last_page: number;
    total: number;
}

const items = ref<HistoryItem[]>([]);
const meta = ref<HistoryMeta>({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref('');
const filters = reactive({
    category: '',
    search: '',
    date_from: '',
    date_to: '',
});

const categories = [
    { value: '', label: 'Tất cả' },
    { value: 'trip', label: 'Chuyến xe' },
    { value: 'booking', label: 'Đặt chỗ' },
    { value: 'vehicle', label: 'Xe' },
    { value: 'driver', label: 'Tài xế' },
];

const severityClasses: Record<HistoryItem['severity'], string> = {
    info: 'bg-blue-500 ring-blue-100',
    success: 'bg-emerald-500 ring-emerald-100',
    warning: 'bg-amber-500 ring-amber-100',
    danger: 'bg-red-500 ring-red-100',
};

const categoryLabels: Record<HistoryItem['category'], string> = {
    booking: 'Đặt chỗ',
    trip: 'Chuyến xe',
    vehicle: 'Xe',
    driver: 'Tài xế',
};

async function load(page = 1) {
    loading.value = true;
    error.value = '';
    const params = Object.fromEntries(
        Object.entries({ ...filters, page, per_page: 8 }).filter(
            ([, value]) => value !== '',
        ),
    );
    const response = await operatorApi.getHistory(params);

    if (response.error) {
        error.value = response.error;
        loading.value = false;
        return;
    }

    items.value = (response.data ?? []) as HistoryItem[];
    meta.value = (response.meta ?? meta.value) as HistoryMeta;
    loading.value = false;
}

function resetFilters() {
    filters.category = '';
    filters.search = '';
    filters.date_from = '';
    filters.date_to = '';
    load();
}

function formatTime(value: string) {
    return new Intl.DateTimeFormat('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value));
}

onMounted(() => load());
</script>

<template>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div
            class="flex flex-col gap-4 border-b border-slate-100 px-6 py-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h2 class="font-semibold text-slate-800">Lịch sử vận hành</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Theo dõi đặt chỗ, xuất bến, trễ chuyến và các sự cố
                </p>
            </div>
            <button
                type="button"
                class="self-start rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 lg:self-auto"
                :disabled="loading"
                @click="load(meta.current_page)"
            >
                Làm mới
            </button>
        </div>

        <form
            class="grid gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-4 sm:grid-cols-2 lg:grid-cols-[minmax(180px,1fr)_160px_150px_150px_auto]"
            @submit.prevent="load()"
        >
            <input
                v-model="filters.search"
                type="search"
                placeholder="Tìm biển số, khách, chuyến..."
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
            />
            <select
                v-model="filters.category"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-amber-400"
            >
                <option
                    v-for="category in categories"
                    :key="category.value"
                    :value="category.value"
                >
                    {{ category.label }}
                </option>
            </select>
            <input
                v-model="filters.date_from"
                type="date"
                aria-label="Từ ngày"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-amber-400"
            />
            <input
                v-model="filters.date_to"
                type="date"
                aria-label="Đến ngày"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-amber-400"
            />
            <div class="flex gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Lọc
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 hover:bg-slate-50"
                    @click="resetFilters"
                >
                    Xóa
                </button>
            </div>
        </form>

        <div v-if="loading" class="space-y-5 p-6">
            <div v-for="index in 4" :key="index" class="flex gap-4">
                <div class="h-3 w-3 animate-pulse rounded-full bg-slate-200" />
                <div class="flex-1 space-y-2">
                    <div class="h-4 w-48 animate-pulse rounded bg-slate-200" />
                    <div class="h-3 w-3/4 animate-pulse rounded bg-slate-100" />
                </div>
            </div>
        </div>

        <div v-else-if="error" class="px-6 py-12 text-center">
            <p class="text-sm font-medium text-red-600">{{ error }}</p>
            <button
                type="button"
                class="mt-3 text-sm font-medium text-amber-600 hover:text-amber-700"
                @click="load()"
            >
                Thử lại
            </button>
        </div>

        <div
            v-else-if="items.length === 0"
            class="flex flex-col items-center px-6 py-14 text-center text-slate-400"
        >
            <svg
                class="mb-3 h-10 w-10 text-slate-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <p class="font-medium text-slate-500">Chưa có lịch sử vận hành</p>
            <p class="mt-1 text-sm">
                Các hoạt động mới của nhà xe sẽ xuất hiện tại đây.
            </p>
        </div>

        <div v-else class="px-6 py-2">
            <ol class="divide-y divide-slate-100">
                <li
                    v-for="item in items"
                    :key="item.id"
                    class="relative flex gap-4 py-4"
                >
                    <span
                        class="mt-1.5 h-3 w-3 shrink-0 rounded-full ring-4"
                        :class="severityClasses[item.severity]"
                    />
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-start justify-between gap-2"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-slate-800">
                                    {{ item.title }}
                                </p>
                                <span
                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500"
                                >
                                    {{ categoryLabels[item.category] }}
                                </span>
                            </div>
                            <time
                                class="shrink-0 text-xs text-slate-400"
                                :datetime="item.occurred_at"
                            >
                                {{ formatTime(item.occurred_at) }}
                            </time>
                        </div>
                        <p
                            v-if="item.description"
                            class="mt-1 text-sm leading-6 text-slate-600"
                        >
                            {{ item.description }}
                        </p>
                        <p
                            v-if="item.actor"
                            class="mt-1 text-xs text-slate-400"
                        >
                            Thực hiện bởi {{ item.actor.full_name }}
                        </p>
                    </div>
                </li>
            </ol>
        </div>

        <div
            v-if="!loading && meta.last_page > 1"
            class="flex items-center justify-between border-t border-slate-100 px-6 py-4"
        >
            <p class="text-sm text-slate-500">{{ meta.total }} hoạt động</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="meta.current_page <= 1"
                    @click="load(meta.current_page - 1)"
                >
                    Trước
                </button>
                <span class="text-sm text-slate-500">
                    {{ meta.current_page }}/{{ meta.last_page }}
                </span>
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="load(meta.current_page + 1)"
                >
                    Sau
                </button>
            </div>
        </div>
    </section>
</template>
