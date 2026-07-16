<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';

const route = useRoute();
const router = useRouter();
const bookingId = route.params.id as string;

const booking = ref<any>(null);
const isLoading = ref(true);
const submitLoading = ref(false);
const errorMsg = ref('');
const submitted = ref(false);

const driverRating = ref(0);
const vehicleRating = ref(0);
const serviceRating = ref(0);
const comment = ref('');
const selectedTags = ref<string[]>([]);

const tags = [
    'Đúng giờ',
    'Thân thiện',
    'Xe sạch sẽ',
    'Lái xe an toàn',
    'Đón đúng điểm',
    'Giá hợp lý',
    'Xe thoải mái',
    'Tài xế nhiệt tình',
];

const hoverDriver = ref(0);
const hoverVehicle = ref(0);
const hoverService = ref(0);

function starLabel(rating: number) {
    return (
        ['', 'Tệ', 'Không hài lòng', 'Bình thường', 'Tốt', 'Tuyệt vời'][
            rating
        ] ?? ''
    );
}

function toggleTag(tag: string) {
    const i = selectedTags.value.indexOf(tag);
    if (i >= 0) selectedTags.value.splice(i, 1);
    else selectedTags.value.push(tag);
}

async function submit() {
    if (!driverRating.value || !vehicleRating.value || !serviceRating.value) {
        errorMsg.value = 'Vui lòng đánh giá tất cả 3 mục trước khi gửi.';
        return;
    }
    submitLoading.value = true;
    errorMsg.value = '';
    const { error } = await customerApi.submitReview({
        booking_id: bookingId,
        driver_rating: driverRating.value,
        vehicle_rating: vehicleRating.value,
        service_rating: serviceRating.value,
        comment: comment.value.trim() || undefined,
        tags: selectedTags.value.length ? selectedTags.value : undefined,
    });
    submitLoading.value = false;
    if (error) {
        errorMsg.value = error as string;
        return;
    }
    submitted.value = true;
}

onMounted(async () => {
    if (!bookingId) {
        router.replace('/bookings');
        return;
    }
    const { data } = await customerApi.getBooking(bookingId);
    isLoading.value = false;
    booking.value = data;
});
</script>

<template>
    <div class="mx-auto max-w-xl px-6 py-10">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
            />
        </div>

        <!-- Success -->
        <div v-else-if="submitted" class="py-16 text-center">
            <div
                class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-green-100"
            >
                <svg
                    class="h-10 w-10 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </div>
            <h2 class="mb-2 text-2xl font-bold text-gray-900">Cảm ơn bạn!</h2>
            <p class="mb-8 text-gray-500">
                Đánh giá của bạn giúp cộng đồng XeGhep.vn ngày càng tốt hơn.
            </p>
            <router-link
                to="/bookings"
                class="rounded-xl bg-blue-600 px-8 py-3 font-semibold text-white transition-colors hover:bg-blue-700"
            >
                Về danh sách vé
            </router-link>
        </div>

        <div v-else>
            <!-- Trip summary card -->
            <div
                v-if="booking"
                class="mb-6 flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
            >
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700"
                >
                    {{
                        booking.trip?.driver?.user?.full_name?.charAt(0) ?? 'T'
                    }}
                </div>
                <div>
                    <p class="font-semibold text-gray-900">
                        {{ booking.trip?.route?.origin_city ?? 'Hà Nội' }} →
                        {{ booking.trip?.route?.dest_city ?? 'Hải Phòng' }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ booking.trip?.driver?.user?.full_name ?? 'Tài xế' }}
                        ·
                        {{
                            booking.trip?.depart_at
                                ? new Date(
                                      booking.trip.depart_at,
                                  ).toLocaleDateString('vi-VN')
                                : '—'
                        }}
                    </p>
                    <p class="mt-0.5 text-xs font-medium text-green-600">
                        Hành trình hoàn thành
                    </p>
                </div>
            </div>

            <!-- Review form -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-lg font-bold text-gray-900">
                    Chia sẻ trải nghiệm của bạn
                </h2>

                <!-- Rating rows -->
                <div class="mb-6 space-y-5">
                    <div
                        v-for="row in [
                            { label: 'Tài xế', icon: '👤', model: 'driver' },
                            {
                                label: 'Chất lượng xe & tiện nghi',
                                icon: '🚌',
                                model: 'vehicle',
                            },
                            {
                                label: 'Dịch vụ tổng thể',
                                icon: '👍',
                                model: 'service',
                            },
                        ]"
                        :key="row.model"
                        class="flex items-center gap-4"
                    >
                        <div class="flex w-52 shrink-0 items-center gap-2">
                            <span class="text-xl">{{ row.icon }}</span>
                            <span class="text-sm font-medium text-gray-700">{{
                                row.label
                            }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                v-for="star in 5"
                                :key="star"
                                @click="
                                    row.model === 'driver'
                                        ? (driverRating = star)
                                        : row.model === 'vehicle'
                                          ? (vehicleRating = star)
                                          : (serviceRating = star)
                                "
                                @mouseenter="
                                    row.model === 'driver'
                                        ? (hoverDriver = star)
                                        : row.model === 'vehicle'
                                          ? (hoverVehicle = star)
                                          : (hoverService = star)
                                "
                                @mouseleave="
                                    row.model === 'driver'
                                        ? (hoverDriver = 0)
                                        : row.model === 'vehicle'
                                          ? (hoverVehicle = 0)
                                          : (hoverService = 0)
                                "
                                class="transition-transform hover:scale-110"
                            >
                                <svg
                                    :class="[
                                        'h-8 w-8 transition-colors',
                                        (row.model === 'driver'
                                            ? hoverDriver || driverRating
                                            : row.model === 'vehicle'
                                              ? hoverVehicle || vehicleRating
                                              : hoverService ||
                                                serviceRating) >= star
                                            ? 'fill-yellow-400 text-yellow-400'
                                            : 'fill-gray-200 text-gray-200',
                                    ]"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </button>
                            <span class="ml-2 w-24 text-sm text-gray-500">
                                {{
                                    row.model === 'driver'
                                        ? starLabel(hoverDriver || driverRating)
                                        : row.model === 'vehicle'
                                          ? starLabel(
                                                hoverVehicle || vehicleRating,
                                            )
                                          : starLabel(
                                                hoverService || serviceRating,
                                            )
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick tags -->
                <div class="mb-5">
                    <p class="mb-3 text-sm font-medium text-gray-700">
                        Gắn thẻ nhanh
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in tags"
                            :key="tag"
                            @click="toggleTag(tag)"
                            :class="[
                                'rounded-full border px-3.5 py-1.5 text-sm font-medium transition-all',
                                selectedTags.includes(tag)
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-600 hover:border-blue-400 hover:text-blue-600',
                            ]"
                        >
                            {{ tag }}
                        </button>
                    </div>
                </div>

                <!-- Comment -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700"
                        >Nhận xét thêm</label
                    >
                    <div class="relative">
                        <textarea
                            v-model="comment"
                            rows="4"
                            maxlength="500"
                            placeholder="Chia sẻ thêm về chuyến đi của bạn (không bắt buộc)..."
                            class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <span
                            class="absolute right-3 bottom-3 text-xs text-gray-400"
                            >{{ comment.length }}/500</span
                        >
                    </div>
                </div>

                <!-- Error -->
                <div
                    v-if="errorMsg"
                    class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                >
                    {{ errorMsg }}
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-4">
                    <button
                        @click="submit"
                        :disabled="submitLoading"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-60"
                    >
                        <div
                            v-if="submitLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        <span>{{
                            submitLoading ? 'Đang gửi...' : 'Gửi đánh giá'
                        }}</span>
                    </button>
                    <router-link
                        to="/bookings"
                        class="text-sm font-medium text-gray-500 transition-colors hover:text-gray-700"
                    >
                        Bỏ qua
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>
