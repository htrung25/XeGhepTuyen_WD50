<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface HistoryItem {
    id: string;
    category: 'booking' | 'trip' | 'vehicle' | 'driver';
    action: string;
    severity: 'info' | 'success' | 'warning' | 'danger';
    title: string;
    description: string | null;
    metadata: Record<string, unknown> | null;
    occurred_at: string;
}

const items = ref<HistoryItem[]>([]);
const loading = ref(true);
const error = ref('');

const iconClasses: Record<HistoryItem['severity'], string> = {
    info: 'bg-blue-100 text-blue-600',
    success: 'bg-emerald-100 text-emerald-600',
    warning: 'bg-amber-100 text-amber-600',
    danger: 'bg-red-100 text-red-600',
};

const recentItems = computed(() => items.value.slice(0, 5));

function metadataText(item: HistoryItem, key: string): string {
    return String(item.metadata?.[key] ?? '');
}

function displayTitle(item: HistoryItem): string {
    const plate = metadataText(item, 'plate_number');
    const contact = metadataText(item, 'contact_name');

    if (item.action === 'trip_departed') return `Xe ${plate} đã xuất bến`;
    if (item.action === 'trip_departure_delayed')
        return `Xe ${plate} xuất bến trễ`;
    if (item.action === 'trip_arrived') return `Xe ${plate} đã đến nơi`;
    if (item.action === 'trip_arrival_delayed') return `Xe ${plate} đến trễ`;
    if (item.action === 'booking_confirmed' && contact)
        return `Đặt chỗ mới từ ${contact}`;
    if (item.action.includes('vehicle') && plate)
        return `Xe ${plate} · ${item.title}`;

    return item.title;
}

function displaySubtitle(item: HistoryItem): string {
    const route = metadataText(item, 'route');
    const bookingCode = metadataText(item, 'booking_code');
    const reason = metadataText(item, 'reason');

    if (reason) return reason;
    if (route) return route;
    if (bookingCode) return `Mã đặt chỗ ${bookingCode}`;
    return item.description ?? '';
}

function relativeTime(value: string): string {
    const seconds = Math.max(
        0,
        Math.floor((Date.now() - new Date(value).getTime()) / 1000),
    );
    if (seconds < 60) return 'Vừa xong';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} phút trước`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} giờ trước`;
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value));
}

async function load() {
    loading.value = true;
    error.value = '';
    const response = await operatorApi.getHistory({ per_page: 5 });
    if (response.error) {
        error.value = response.error;
    } else {
        items.value = (response.data ?? []) as HistoryItem[];
    }
    loading.value = false;
}

onMounted(load);
</script>

<template>
    <section
        class="flex min-h-[390px] flex-col rounded-xl border border-slate-200 bg-white shadow-sm"
    >
        <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <div>
                <h2 class="font-semibold text-slate-800">Lịch sử gần đây</h2>
                <p class="mt-0.5 text-xs text-slate-400">
                    5 hoạt động mới nhất
                </p>
            </div>
            <router-link
                to="/operator/history"
                class="text-sm font-medium text-amber-600 hover:text-amber-700"
            >
                Xem tất cả →
            </router-link>
        </div>

        <div v-if="loading" class="space-y-5 px-6 py-5">
            <div v-for="index in 4" :key="index" class="flex gap-3">
                <div
                    class="h-10 w-10 animate-pulse rounded-full bg-slate-100"
                />
                <div class="flex-1 space-y-2 pt-1">
                    <div class="h-4 w-3/4 animate-pulse rounded bg-slate-100" />
                    <div class="h-3 w-1/2 animate-pulse rounded bg-slate-100" />
                </div>
            </div>
        </div>

        <div
            v-else-if="error"
            class="flex flex-1 flex-col items-center justify-center px-6 text-center"
        >
            <p class="text-sm text-red-600">{{ error }}</p>
            <button
                type="button"
                class="mt-2 text-sm font-medium text-amber-600"
                @click="load"
            >
                Thử lại
            </button>
        </div>

        <div
            v-else-if="recentItems.length === 0"
            class="flex flex-1 flex-col items-center justify-center px-6 text-center"
        >
            <svg
                class="mb-2 h-9 w-9 text-slate-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <p class="text-sm font-medium text-slate-500">
                Chưa có hoạt động mới
            </p>
        </div>

        <ol v-else class="divide-y divide-slate-100 px-6 pb-3">
            <li
                v-for="item in recentItems"
                :key="item.id"
                class="flex items-center gap-3 py-3"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                    :class="iconClasses[item.severity]"
                >
                    <svg
                        v-if="item.category === 'booking'"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10-3v6m3-3h-6"
                        />
                    </svg>
                    <svg
                        v-else-if="item.severity === 'danger'"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v3m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 13h2l2-5h9l3 5h2v5h-2a2 2 0 11-4 0H9a2 2 0 11-4 0H3v-5z"
                        />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                        {{ displayTitle(item) }}
                    </p>
                    <p class="mt-0.5 truncate text-sm text-slate-500">
                        {{ displaySubtitle(item) }}
                    </p>
                </div>
                <time
                    class="shrink-0 text-xs text-slate-400"
                    :datetime="item.occurred_at"
                >
                    {{ relativeTime(item.occurred_at) }}
                </time>
            </li>
        </ol>
    </section>
</template>
