<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { operatorApi } from '@/api/operator.api';
import MapboxMap from '@/components/MapboxMap.vue';
import type { MapMarker } from '@/components/MapboxMap.vue';

interface LiveTrip {
    id: string;
    tracking_code: string | null;
    vehicle_plate: string | null;
    driver_name: string | null;
    lat: number;
    lng: number;
    location_updated_at: string | null;
}

const router = useRouter();
const trips = ref<LiveTrip[]>([]);
const loading = ref(true);
const error = ref('');
let refreshTimer: ReturnType<typeof setInterval> | null = null;

const markers = computed<MapMarker[]>(() =>
    trips.value
        .filter((trip) => trip.lat !== 0 && trip.lng !== 0)
        .map((trip) => ({
            id: trip.id,
            lat: trip.lat,
            lng: trip.lng,
            color: '#f59e0b',
            label: `${trip.vehicle_plate ?? 'Chưa rõ biển số'} · ${trip.driver_name ?? 'Chưa rõ tài xế'}`,
        })),
);

async function load() {
    const response = await operatorApi.getDashboardMap();
    if (response.error) {
        error.value = response.error;
    } else {
        trips.value = (response.data ?? []) as LiveTrip[];
        error.value = '';
    }
    loading.value = false;
}

function selectTrip(id: string) {
    router.push({ path: '/operator/trips', query: { trip: id } });
}

onMounted(() => {
    load();
    refreshTimer = setInterval(load, 30_000);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <section
        class="relative min-h-[390px] overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm"
    >
        <MapboxMap :markers="markers" :zoom="8" @select="selectTrip" />

        <div
            class="pointer-events-none absolute top-5 left-5 z-10 min-w-52 rounded-xl border border-white/70 bg-white/95 p-4 shadow-lg backdrop-blur"
        >
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span
                        v-if="trips.length > 0"
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"
                    />
                    <span
                        class="relative inline-flex h-2.5 w-2.5 rounded-full"
                        :class="
                            trips.length > 0 ? 'bg-emerald-500' : 'bg-slate-300'
                        "
                    />
                </span>
                <h2 class="font-semibold text-slate-800">Định vị trực tuyến</h2>
            </div>
            <p v-if="loading" class="mt-2 text-sm text-slate-500">
                Đang tải vị trí xe...
            </p>
            <p v-else-if="error" class="mt-2 text-sm text-red-600">
                Không thể tải vị trí xe
            </p>
            <p v-else class="mt-2 text-lg font-semibold text-slate-700">
                {{ trips.length }} xe đang trên đường
            </p>
            <p
                v-if="!loading && trips.length > markers.length"
                class="mt-1 text-xs text-slate-400"
            >
                {{ trips.length - markers.length }} xe chưa gửi vị trí GPS
            </p>
        </div>

        <router-link
            to="/operator/trips"
            class="absolute right-5 bottom-5 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-amber-500 text-2xl text-white shadow-lg transition-transform hover:scale-105 hover:bg-amber-600"
            aria-label="Xem danh sách chuyến"
        >
            +
        </router-link>
    </section>
</template>
