<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { geoApi } from '@/api/geo.api';
import type { Province } from '@/api/geo.api';
import { useCustomerStore } from '@/stores/customer.store';

const router = useRouter();
const store = useCustomerStore();

const tripType = ref<'one_way' | 'round_trip'>('one_way');
const provinces = ref<Province[]>([]);
const fromProvinceCode = ref('');
const fromDistrictCode = ref('');
const toProvinceCode = ref('');
const toDistrictCode = ref('');
const passengers = ref(1);
const travelDate = ref(store.getLocalDateString());
const loadingPopular = ref(true);

const fromProvince = computed(() =>
    provinces.value.find((p) => p.code === fromProvinceCode.value),
);
const toProvince = computed(() =>
    provinces.value.find((p) => p.code === toProvinceCode.value),
);
const fromDistricts = computed(() => fromProvince.value?.districts ?? []);
const toDistricts = computed(() => toProvince.value?.districts ?? []);
const fromCity = computed(() => fromProvince.value?.name ?? '');
const toCity = computed(() => toProvince.value?.name ?? '');
const fromDistrict = computed(() =>
    fromDistricts.value.find((d) => d.code === fromDistrictCode.value),
);
const toDistrict = computed(() =>
    toDistricts.value.find((d) => d.code === toDistrictCode.value),
);

const popularRoutes = ref([
    {
        from: 'Hà Nội',
        to: 'Hải Phòng',
        price: 120000,
        duration: '2 giờ 30 phút',
        trips: 48,
        tag: 'Phổ biến',
    },
    {
        from: 'Hải Phòng',
        to: 'Hà Nội',
        price: 120000,
        duration: '2 giờ 30 phút',
        trips: 45,
        tag: 'Linh hoạt',
    },
    {
        from: 'Hà Nội',
        to: 'Hải Phòng',
        price: 150000,
        duration: '2 giờ',
        trips: 12,
        tag: 'VIP 7 chỗ',
    },
]);

const features = [
    {
        number: '01',
        title: 'Đón trả tận nơi',
        desc: 'Chủ động chọn điểm đón phù hợp, không cần mất thời gian di chuyển ra bến.',
    },
    {
        number: '02',
        title: 'Theo dõi hành trình',
        desc: 'Cập nhật vị trí xe theo thời gian thực để bạn luôn an tâm và đúng giờ.',
    },
    {
        number: '03',
        title: 'Đặt chỗ nhanh chóng',
        desc: 'Chọn chuyến, thanh toán và nhận vé điện tử chỉ trong vài thao tác đơn giản.',
    },
];

const minDate = computed(() => store.getLocalDateString());

// Điểm đi trùng điểm đến là input phi lý — chặn ở FE cho UX rõ ràng
// (BE vẫn là nguồn sự thật: trả 422 nếu lọt qua).
const sameLocation = computed(
    () =>
        fromProvinceCode.value === toProvinceCode.value &&
        fromDistrictCode.value === toDistrictCode.value,
);

function syncDistrict(code: 'from' | 'to') {
    if (
        code === 'from' &&
        !fromDistricts.value.some((d) => d.code === fromDistrictCode.value)
    ) {
        fromDistrictCode.value = '';
    }
    if (
        code === 'to' &&
        !toDistricts.value.some((d) => d.code === toDistrictCode.value)
    ) {
        toDistrictCode.value = '';
    }
}

function swapCities() {
    [fromProvinceCode.value, toProvinceCode.value] = [
        toProvinceCode.value,
        fromProvinceCode.value,
    ];
    [fromDistrictCode.value, toDistrictCode.value] = [
        toDistrictCode.value,
        fromDistrictCode.value,
    ];
}

function adjustPassengers(delta: number) {
    const next = passengers.value + delta;
    if (next >= 1 && next <= 4) passengers.value = next;
}

function search() {
    if (
        !fromProvinceCode.value ||
        !fromDistrictCode.value ||
        !toProvinceCode.value ||
        !toDistrictCode.value
    ) {
        toast.error(
            'Vui lòng chọn đầy đủ tỉnh/thành và quận/huyện điểm đi, điểm đến.',
        );
        return;
    }
    if (!fromDistrict.value || !toDistrict.value || !travelDate.value) return;
    if (sameLocation.value) {
        toast.error('Điểm đến phải khác điểm đi.');
        return;
    }
    store.searchParams = {
        from_city: fromCity.value,
        from_district: fromDistrict.value.name,
        from_province_code: fromProvinceCode.value,
        from_district_code: fromDistrictCode.value,
        to_city: toCity.value,
        to_district: toDistrict.value.name,
        to_province_code: toProvinceCode.value,
        to_district_code: toDistrictCode.value,
        date: travelDate.value,
        passengers: passengers.value,
        trip_type: tripType.value,
    };
    router.push('/search');
}

function searchPopular(from: string, to: string) {
    fromProvinceCode.value = from === 'Hà Nội' ? '01' : '31';
    toProvinceCode.value = to === 'Hà Nội' ? '01' : '31';
    fromDistrictCode.value = from === 'Hà Nội' ? '005' : '303';
    toDistrictCode.value = to === 'Hà Nội' ? '005' : '303';
    search();
}

function fmt(value: number) {
    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
}

onMounted(() => {
    void geoApi.getProvinces().then((items) => {
        provinces.value = items;
        syncDistrict('from');
        syncDistrict('to');
        loadingPopular.value = false;
    });
});
</script>

<template>
    <div class="overflow-hidden bg-white text-slate-950">
        <section class="relative border-b border-sky-100 bg-sky-50">
            <div class="absolute inset-x-0 top-0 h-1 bg-blue-600" />
            <div
                class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-14 sm:px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-20"
            >
                <div class="relative z-10">
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white px-3 py-1.5 text-sm font-semibold text-blue-700 shadow-sm"
                    >
                        <span class="size-2 rounded-full bg-emerald-500" />
                        Tuyến Hà Nội – Hải Phòng mỗi ngày
                    </div>
                    <h1
                        class="max-w-3xl text-4xl leading-snug font-extrabold text-balance sm:text-5xl lg:text-6xl"
                    >
                        Đi chung tuyến,<br />
                        <span class="text-blue-600">nhẹ mọi hành trình.</span>
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-base leading-7 text-pretty text-slate-600 sm:text-lg"
                    >
                        XeGhepTuyen-Fgroup đưa bạn đến đúng nơi, đúng giờ với
                        trải nghiệm đặt chỗ minh bạch và tiện lợi hơn.
                    </p>
                    <div
                        class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-700"
                    >
                        <span class="flex items-center gap-2"
                            ><span
                                class="flex size-5 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-700"
                                >✓</span
                            >Đón tận nơi</span
                        >
                        <span class="flex items-center gap-2"
                            ><span
                                class="flex size-5 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-700"
                                >✓</span
                            >Giá rõ ràng</span
                        >
                        <span class="flex items-center gap-2"
                            ><span
                                class="flex size-5 items-center justify-center rounded-full bg-emerald-100 text-xs text-emerald-700"
                                >✓</span
                            >Theo dõi GPS</span
                        >
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-xl lg:mx-0">
                    <div
                        class="absolute -top-7 -right-5 hidden rounded-2xl bg-amber-300 px-5 py-3 font-bold text-slate-900 shadow-lg sm:block"
                    >
                        Từ 120.000đ/chuyến
                    </div>
                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-7"
                    >
                        <div
                            class="mb-6 flex items-center justify-between gap-4"
                        >
                            <div>
                                <p class="text-sm font-semibold text-blue-600">
                                    Bắt đầu hành trình
                                </p>
                                <h2 class="mt-1 text-xl font-bold text-balance">
                                    Bạn muốn đi đâu?
                                </h2>
                            </div>
                            <div
                                class="flex rounded-xl bg-slate-100 p-1"
                                aria-label="Loại chuyến đi"
                            >
                                <button
                                    v-for="tab in [
                                        { key: 'one_way', label: 'Một chiều' },
                                        { key: 'round_trip', label: 'Khứ hồi' },
                                    ]"
                                    :key="tab.key"
                                    type="button"
                                    :aria-pressed="tripType === tab.key"
                                    :class="[
                                        'rounded-lg px-3 py-2 text-xs font-semibold transition-colors duration-150',
                                        tripType === tab.key
                                            ? 'bg-white text-blue-700 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-800',
                                    ]"
                                    @click="
                                        tripType = tab.key as
                                            | 'one_way'
                                            | 'round_trip'
                                    "
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <div class="relative grid gap-3 sm:grid-cols-2">
                            <label
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
                            >
                                <span
                                    class="block text-xs font-semibold text-slate-500"
                                    >Tỉnh/thành điểm đi</span
                                >
                                <select
                                    v-model="fromProvinceCode"
                                    class="mt-1 w-full cursor-pointer bg-transparent text-base font-bold outline-none"
                                    @change="syncDistrict('from')"
                                >
                                    <option value="" disabled>
                                        Chọn tỉnh/thành
                                    </option>
                                    <option
                                        v-for="province in provinces"
                                        :key="province.code"
                                        :value="province.code"
                                    >
                                        {{ province.name }}
                                    </option>
                                </select>
                            </label>
                            <button
                                type="button"
                                aria-label="Đổi điểm đi và điểm đến"
                                class="absolute top-1/2 left-1/2 z-10 hidden size-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-blue-200 bg-white text-blue-600 shadow-sm transition-transform duration-150 hover:scale-105 sm:flex"
                                @click="swapCities"
                            >
                                <svg
                                    class="size-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7 7h11m0 0-3-3m3 3-3 3M17 17H6m0 0 3 3m-3-3 3-3"
                                    />
                                </svg>
                            </button>
                            <label
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
                            >
                                <span
                                    class="block text-xs font-semibold text-slate-500"
                                    >Huyện/quận điểm đi</span
                                >
                                <select
                                    v-model="fromDistrictCode"
                                    :disabled="!fromProvinceCode"
                                    class="mt-1 w-full cursor-pointer bg-transparent text-base font-bold outline-none"
                                >
                                    <option value="" disabled>
                                        Chọn quận/huyện
                                    </option>
                                    <option
                                        v-for="district in fromDistricts"
                                        :key="district.code"
                                        :value="district.code"
                                    >
                                        {{ district.name }}
                                    </option>
                                </select>
                            </label>
                            <label
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
                            >
                                <span
                                    class="block text-xs font-semibold text-slate-500"
                                    >Tỉnh/thành điểm đến</span
                                >
                                <select
                                    v-model="toProvinceCode"
                                    class="mt-1 w-full cursor-pointer bg-transparent text-base font-bold outline-none"
                                    @change="syncDistrict('to')"
                                >
                                    <option value="" disabled>
                                        Chọn tỉnh/thành
                                    </option>
                                    <option
                                        v-for="province in provinces"
                                        :key="province.code"
                                        :value="province.code"
                                    >
                                        {{ province.name }}
                                    </option>
                                </select>
                            </label>
                            <label
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
                            >
                                <span
                                    class="block text-xs font-semibold text-slate-500"
                                    >Huyện/quận điểm đến</span
                                >
                                <select
                                    v-model="toDistrictCode"
                                    :disabled="!toProvinceCode"
                                    class="mt-1 w-full cursor-pointer bg-transparent text-base font-bold outline-none"
                                >
                                    <option value="" disabled>
                                        Chọn quận/huyện
                                    </option>
                                    <option
                                        v-for="district in toDistricts"
                                        :key="district.code"
                                        :value="district.code"
                                    >
                                        {{ district.name }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <p
                            v-if="sameLocation"
                            role="alert"
                            class="mt-2 flex items-center gap-1.5 text-sm font-medium text-red-600"
                        >
                            <span aria-hidden="true">⚠</span> Điểm đến phải khác
                            điểm đi.
                        </p>

                        <button
                            type="button"
                            class="mx-auto my-2 flex size-9 items-center justify-center rounded-full border border-blue-200 bg-white text-blue-600 sm:hidden"
                            aria-label="Đổi điểm đi và điểm đến"
                            @click="swapCities"
                        >
                            ⇅
                        </button>

                        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr]">
                            <label
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
                            >
                                <span
                                    class="block text-xs font-semibold text-slate-500"
                                    >Ngày khởi hành</span
                                >
                                <input
                                    v-model="travelDate"
                                    type="date"
                                    :min="minDate"
                                    class="mt-1 w-full bg-transparent text-sm font-bold outline-none"
                                />
                            </label>
                            <div
                                class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <div>
                                    <span
                                        class="block text-xs font-semibold text-slate-500"
                                        >Hành khách</span
                                    ><span class="mt-1 block text-sm font-bold"
                                        >{{ passengers }} người</span
                                    >
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        aria-label="Giảm số hành khách"
                                        :disabled="passengers <= 1"
                                        class="flex size-8 items-center justify-center rounded-full border border-slate-300 bg-white font-bold disabled:opacity-40"
                                        @click="adjustPassengers(-1)"
                                    >
                                        −
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="Tăng số hành khách"
                                        :disabled="passengers >= 4"
                                        class="flex size-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white disabled:opacity-40"
                                        @click="adjustPassengers(1)"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="sameLocation"
                            class="mt-4 flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 font-bold text-white shadow-lg transition-all duration-150 hover:bg-blue-700 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none disabled:hover:bg-slate-300 disabled:active:scale-100"
                            @click="search"
                        >
                            Tìm chuyến phù hợp
                            <svg
                                class="size-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9 18 6-6-6-6"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-slate-100 bg-white py-8">
            <div
                class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-5 text-center sm:px-6 md:grid-cols-4 lg:px-8"
            >
                <div>
                    <p
                        class="text-2xl font-extrabold text-blue-600 tabular-nums"
                    >
                        60+
                    </p>
                    <p class="mt-1 text-sm text-slate-500">chuyến mỗi ngày</p>
                </div>
                <div>
                    <p
                        class="text-2xl font-extrabold text-blue-600 tabular-nums"
                    >
                        15 phút
                    </p>
                    <p class="mt-1 text-sm text-slate-500">mỗi khung giờ</p>
                </div>
                <div>
                    <p
                        class="text-2xl font-extrabold text-blue-600 tabular-nums"
                    >
                        4.9/5
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        đánh giá hành khách
                    </p>
                </div>
                <div>
                    <p
                        class="text-2xl font-extrabold text-blue-600 tabular-nums"
                    >
                        24/7
                    </p>
                    <p class="mt-1 text-sm text-slate-500">hỗ trợ tận tâm</p>
                </div>
            </div>
        </section>

        <section class="bg-white py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="mb-10 max-w-2xl">
                    <p class="text-sm font-bold text-blue-600">
                        TRẢI NGHIỆM KHÁC BIỆT
                    </p>
                    <h2
                        class="mt-3 text-3xl leading-snug font-extrabold text-balance sm:text-4xl"
                    >
                        Chuyến đi nhẹ nhàng ngay từ lúc đặt xe
                    </h2>
                    <p class="mt-4 leading-7 text-pretty text-slate-600">
                        Mọi chi tiết được thiết kế để hành trình liên tỉnh của
                        bạn thuận tiện, chủ động và an tâm hơn.
                    </p>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.number"
                        class="rounded-3xl border border-slate-200 p-6 transition-colors duration-150 hover:border-blue-300 hover:bg-sky-50"
                    >
                        <span
                            class="flex size-11 items-center justify-center rounded-2xl bg-blue-600 text-sm font-extrabold text-white"
                            >{{ feature.number }}</span
                        >
                        <h3 class="mt-6 text-xl font-bold">
                            {{ feature.title }}
                        </h3>
                        <p
                            class="mt-3 text-sm leading-6 text-pretty text-slate-600"
                        >
                            {{ feature.desc }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="mb-9 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-blue-600">
                            LỊCH TRÌNH NỔI BẬT
                        </p>
                        <h2
                            class="mt-2 text-3xl leading-snug font-extrabold text-balance"
                        >
                            Chọn chuyến, lên đường
                        </h2>
                    </div>
                    <router-link
                        to="/search"
                        class="hidden text-sm font-bold text-blue-600 hover:underline sm:block"
                        >Xem tất cả chuyến →</router-link
                    >
                </div>
                <div v-if="loadingPopular" class="grid gap-5 md:grid-cols-3">
                    <div
                        v-for="item in 3"
                        :key="item"
                        class="h-56 animate-pulse rounded-3xl border border-slate-200 bg-white p-6"
                    >
                        <div class="h-5 w-24 rounded bg-slate-200" />
                        <div class="mt-7 h-7 w-full rounded bg-slate-100" />
                        <div class="mt-4 h-12 w-full rounded bg-slate-100" />
                    </div>
                </div>
                <div v-else class="grid gap-5 md:grid-cols-3">
                    <button
                        v-for="routeItem in popularRoutes"
                        :key="routeItem.from + routeItem.to + routeItem.tag"
                        type="button"
                        class="group rounded-3xl border border-slate-200 bg-white p-6 text-left shadow-sm transition-transform duration-150 hover:-translate-y-1 hover:border-blue-300 hover:shadow-md"
                        @click="searchPopular(routeItem.from, routeItem.to)"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800"
                                >{{ routeItem.tag }}</span
                            ><span class="text-sm font-bold text-blue-600"
                                >Từ {{ fmt(routeItem.price) }}</span
                            >
                        </div>
                        <div
                            class="mt-7 flex items-center gap-3 text-lg font-extrabold"
                        >
                            <span>{{ routeItem.from }}</span
                            ><span
                                class="h-px flex-1 border-t border-dashed border-slate-300"
                            /><svg
                                class="size-5 shrink-0 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 12h14m-4-4 4 4-4 4"
                                /></svg
                            ><span>{{ routeItem.to }}</span>
                        </div>
                        <div
                            class="mt-7 flex items-center justify-between border-t border-slate-100 pt-4 text-sm text-slate-500"
                        >
                            <span>{{ routeItem.duration }}</span
                            ><span class="tabular-nums"
                                >{{ routeItem.trips }} chuyến/ngày</span
                            >
                        </div>
                    </button>
                </div>
            </div>
        </section>

        <section class="bg-blue-700 py-14 text-white">
            <div
                class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-7 px-5 sm:px-6 md:flex-row md:items-center lg:px-8"
            >
                <div>
                    <p class="text-sm font-bold text-blue-200">
                        SẴN SÀNG LÊN ĐƯỜNG?
                    </p>
                    <h2
                        class="mt-2 text-3xl leading-snug font-extrabold text-balance"
                    >
                        Để XeGhepTuyen-Fgroup lo phần đường còn lại.
                    </h2>
                    <p class="mt-3 text-pretty text-blue-100">
                        Chọn lịch trình phù hợp và giữ chỗ cho chuyến đi hôm
                        nay.
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-2xl bg-amber-300 px-7 py-4 font-extrabold text-slate-950 shadow-lg transition-transform duration-150 hover:-translate-y-0.5"
                    @click="search"
                >
                    Tìm chuyến ngay →
                </button>
            </div>
        </section>
    </div>
</template>
