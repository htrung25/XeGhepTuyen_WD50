<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomerStore } from '@/stores/customer.store';

const router = useRouter();
const store = useCustomerStore();

const tripType = ref<'one_way' | 'round_trip'>('one_way');
const fromCity = ref('Hà Nội');
const toCity = ref('Hải Phòng');
const passengers = ref(1);
const travelDate = ref(new Date().toISOString().split('T')[0]);
const loadingPopular = ref(true);

const popularRoutes = ref([
    {
        from: 'Hà Nội',
        to: 'Hải Phòng',
        price: 120000,
        duration: '~2.5 giờ',
        desc: 'Tuyến phổ biến nhất',
        trips: 48,
    },
    {
        from: 'Hải Phòng',
        to: 'Hà Nội',
        price: 120000,
        duration: '~2.5 giờ',
        desc: 'Chiều ngược lại',
        trips: 45,
    },
    {
        from: 'Hà Nội',
        to: 'Hải Phòng',
        price: 150000,
        duration: '~2 giờ',
        desc: 'Xe VIP 7 chỗ',
        trips: 12,
    },
]);

const features = [
    {
        icon: '📍',
        title: 'Đón tận nơi',
        desc: 'Chọn từ 10+ điểm đón cố định trên tuyến, nhập địa chỉ cụ thể để tài xế tìm dễ hơn.',
    },
    {
        icon: '📡',
        title: 'Theo dõi GPS',
        desc: 'Xem vị trí xe real-time trên bản đồ, biết chính xác xe đến điểm đón sau bao nhiêu phút.',
    },
    {
        icon: '💳',
        title: 'Thanh toán online',
        desc: 'Thanh toán qua MoMo, VNPay hoặc ví XeGhep. Nhận vé QR điện tử ngay lập tức.',
    },
];

const minDate = computed(() => new Date().toISOString().split('T')[0]);

function swapCities() {
    [fromCity.value, toCity.value] = [toCity.value, fromCity.value];
}

function adjustPassengers(delta: number) {
    const next = passengers.value + delta;
    if (next >= 1 && next <= 4) passengers.value = next;
}

function search() {
    if (!fromCity.value || !toCity.value || !travelDate.value) return;
    store.searchParams = {
        from_city: fromCity.value,
        to_city: toCity.value,
        date: travelDate.value,
        passengers: passengers.value,
        trip_type: tripType.value,
    };
    router.push('/search');
}

function searchPopular(from: string, to: string) {
    fromCity.value = from;
    toCity.value = to;
    search();
}

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

onMounted(() => {
    loadingPopular.value = false;
});
</script>

<template>
    <div>
        <!-- ─── Hero Section ─────────────────────────────────────── -->
        <section
            class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-600 to-blue-800 py-20"
        >
            <!-- Background decoration -->
            <div class="absolute inset-0 opacity-10">
                <div
                    class="absolute top-10 left-10 h-40 w-40 rounded-full bg-white blur-3xl"
                />
                <div
                    class="absolute right-20 bottom-10 h-60 w-60 rounded-full bg-blue-300 blur-3xl"
                />
            </div>

            <div class="relative mx-auto max-w-5xl px-6">
                <!-- Hero text -->
                <div class="mb-10 text-center">
                    <h1
                        class="mb-4 text-4xl leading-tight font-bold text-white md:text-5xl"
                    >
                        Đặt xe ghép<br />
                        <span class="text-blue-200">Hà Nội ↔ Hải Phòng</span>
                    </h1>
                    <p class="mx-auto max-w-lg text-lg text-blue-100">
                        Đón tận nơi · Theo dõi GPS real-time · Thanh toán điện
                        tử
                    </p>
                </div>

                <!-- Search Card -->
                <div
                    class="mx-auto max-w-2xl rounded-2xl bg-white p-6 shadow-2xl md:p-8"
                >
                    <!-- Trip type tabs -->
                    <div
                        class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1"
                    >
                        <button
                            v-for="tab in [
                                { key: 'one_way', label: 'Một chiều' },
                                { key: 'round_trip', label: 'Khứ hồi' },
                            ]"
                            :key="tab.key"
                            @click="tripType = tab.key as any"
                            :class="[
                                'rounded-lg px-5 py-2 text-sm font-medium transition-all',
                                tripType === tab.key
                                    ? 'bg-white font-semibold text-blue-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700',
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- From / To row -->
                    <div
                        class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3"
                    >
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3 transition-colors hover:border-blue-300"
                        >
                            <label
                                class="mb-1 block text-xs font-medium text-gray-500"
                                >Điểm đi</label
                            >
                            <select
                                v-model="fromCity"
                                class="w-full cursor-pointer appearance-none bg-transparent text-sm font-semibold text-gray-900 focus:outline-none"
                            >
                                <option value="Hà Nội">Hà Nội</option>
                                <option value="Hải Phòng">Hải Phòng</option>
                            </select>
                        </div>

                        <button
                            @click="swapCities"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 transition-colors hover:bg-blue-100"
                        >
                            <svg
                                class="h-4 w-4 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                        </button>

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3 transition-colors hover:border-blue-300"
                        >
                            <label
                                class="mb-1 block text-xs font-medium text-gray-500"
                                >Điểm đến</label
                            >
                            <select
                                v-model="toCity"
                                class="w-full cursor-pointer appearance-none bg-transparent text-sm font-semibold text-gray-900 focus:outline-none"
                            >
                                <option value="Hà Nội">Hà Nội</option>
                                <option value="Hải Phòng">Hải Phòng</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date + Passengers + Button -->
                    <div class="grid grid-cols-[1fr_auto_auto] items-end gap-3">
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3 transition-colors hover:border-blue-300"
                        >
                            <label
                                class="mb-1 block text-xs font-medium text-gray-500"
                                >Ngày đi</label
                            >
                            <input
                                v-model="travelDate"
                                type="date"
                                :min="minDate"
                                class="w-full cursor-pointer bg-transparent text-sm font-semibold text-gray-900 focus:outline-none"
                            />
                        </div>

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3"
                        >
                            <label
                                class="mb-1 block text-xs font-medium text-gray-500"
                                >Hành khách</label
                            >
                            <div class="flex items-center gap-2">
                                <button
                                    @click="adjustPassengers(-1)"
                                    :disabled="passengers <= 1"
                                    class="flex h-6 w-6 items-center justify-center rounded-full border border-gray-300 text-sm font-bold text-gray-600 transition-colors hover:border-blue-400 disabled:opacity-40"
                                >
                                    −
                                </button>
                                <span
                                    class="w-4 text-center text-sm font-bold text-gray-900"
                                    >{{ passengers }}</span
                                >
                                <button
                                    @click="adjustPassengers(1)"
                                    :disabled="passengers >= 4"
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-40"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <button
                            @click="search"
                            class="flex h-full min-h-[64px] items-center gap-2 rounded-xl bg-blue-600 px-8 text-sm font-bold whitespace-nowrap text-white shadow-lg shadow-blue-200 transition-colors hover:bg-blue-700"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            Tìm chuyến
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Features ─────────────────────────────────────────── -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-5xl px-6">
                <h2 class="mb-10 text-center text-2xl font-bold text-gray-900">
                    Tại sao chọn XeGhep.vn?
                </h2>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="group rounded-2xl bg-gray-50 p-6 text-center transition-colors hover:bg-blue-50"
                    >
                        <div class="mb-4 text-4xl">{{ f.icon }}</div>
                        <h3
                            class="mb-2 font-bold text-gray-900 transition-colors group-hover:text-blue-700"
                        >
                            {{ f.title }}
                        </h3>
                        <p class="text-sm leading-relaxed text-gray-500">
                            {{ f.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Popular Routes ────────────────────────────────────── -->
        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-5xl px-6">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900">
                        Chuyến phổ biến
                    </h2>
                    <router-link
                        to="/search"
                        class="text-sm font-medium text-blue-600 hover:underline"
                    >
                        Xem tất cả →
                    </router-link>
                </div>

                <!-- Skeletons -->
                <div
                    v-if="loadingPopular"
                    class="grid grid-cols-1 gap-5 md:grid-cols-3"
                >
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="h-40 animate-pulse rounded-2xl bg-white p-5"
                    >
                        <div class="mb-3 h-5 w-3/4 rounded bg-gray-200" />
                        <div class="mb-2 h-3 w-1/2 rounded bg-gray-100" />
                        <div class="mt-6 h-8 rounded bg-gray-100" />
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div
                        v-for="r in popularRoutes"
                        :key="r.from + r.to + r.desc"
                        class="group cursor-pointer rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-blue-200 hover:shadow-md"
                        @click="searchPopular(r.from, r.to)"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span
                                class="text-base font-bold text-gray-900 transition-colors group-hover:text-blue-700"
                            >
                                {{ r.from }} → {{ r.to }}
                            </span>
                            <span
                                class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600"
                            >
                                {{ fmt(r.price) }}
                            </span>
                        </div>
                        <p class="mb-1 text-xs text-gray-400">
                            {{ r.duration }}
                        </p>
                        <p class="text-xs text-gray-500">{{ r.desc }}</p>
                        <div
                            class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4"
                        >
                            <span class="text-xs text-gray-400"
                                >{{ r.trips }} chuyến/ngày</span
                            >
                            <span
                                class="text-xs font-medium text-blue-600 group-hover:underline"
                                >Xem chuyến →</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── CTA Banner ────────────────────────────────────────── -->
        <section class="bg-blue-600 py-16">
            <div class="mx-auto max-w-5xl px-6 text-center">
                <h2 class="mb-4 text-2xl font-bold text-white md:text-3xl">
                    Sẵn sàng lên đường?
                </h2>
                <p class="mx-auto mb-8 max-w-md text-blue-100">
                    Đặt vé ngay hôm nay, được đón tận nơi, không cần ra bến xe.
                </p>
                <button
                    @click="search"
                    class="rounded-xl bg-white px-8 py-4 text-sm font-bold text-blue-700 shadow-lg transition-colors hover:bg-blue-50"
                >
                    Tìm chuyến ngay
                </button>
            </div>
        </section>
    </div>
</template>
