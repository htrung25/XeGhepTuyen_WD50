<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const router = useRouter();
const route = useRoute();
const auth = useDriverAuthStore();

const phone = ref('');
const password = ref('');
const showPw = ref(false);
const loading = ref(false);
const error = ref('');

async function handleLogin() {
    error.value = '';
    if (!phone.value.trim()) {
        error.value = 'Vui lòng nhập số điện thoại';
        return;
    }
    if (!password.value) {
        error.value = 'Vui lòng nhập mật khẩu';
        return;
    }
    if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(phone.value.trim())) {
        error.value =
            'Số điện thoại không hợp lệ (10 số, bắt đầu 03/05/07/08/09)';
        return;
    }
    loading.value = true;
    const { data, error: err } = await driverApi.login({
        phone: phone.value.trim(),
        password: password.value,
    });
    loading.value = false;
    if (err) {
        error.value =
            typeof err === 'string'
                ? err
                : 'Đăng nhập thất bại. Kiểm tra lại thông tin.';
        return;
    }
    auth.setAuth(data.token, data.user, data.driver);
    const redirect = route.query.redirect as string | undefined;
    router.push(redirect ?? '/driver/dashboard');
}
</script>

<template>
    <div class="flex min-h-screen">
        <!-- ─── LEFT: Green gradient illustration panel ─────────── -->
        <div
            class="relative hidden flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-green-800 via-green-700 to-green-600 p-12 lg:flex lg:w-1/2"
        >
            <!-- Decorative circles -->
            <div
                class="absolute top-0 right-0 h-64 w-64 translate-x-32 -translate-y-32 rounded-full bg-white/5"
            />
            <div
                class="absolute bottom-0 left-0 h-80 w-80 -translate-x-40 translate-y-40 rounded-full bg-white/5"
            />

            <!-- Logo -->
            <div class="mb-12 flex items-center gap-3">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20"
                >
                    <svg
                        class="h-7 w-7 text-white"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                        />
                    </svg>
                </div>
                <span class="text-3xl font-black text-white"
                    >XeGhep<span class="text-green-300">.vn</span></span
                >
            </div>

            <!-- Illustration — driver + van -->
            <div class="relative mb-10 h-56 w-80">
                <!-- Van body -->
                <div
                    class="absolute right-4 bottom-0 left-4 h-28 rounded-2xl border-2 border-white/20 bg-white/15 backdrop-blur-sm"
                >
                    <!-- Windows -->
                    <div class="absolute top-3 right-8 left-8 flex gap-3">
                        <div class="h-8 flex-1 rounded-lg bg-white/25" />
                        <div class="h-8 flex-1 rounded-lg bg-white/25" />
                        <div class="h-8 flex-1 rounded-lg bg-white/25" />
                    </div>
                    <!-- XeGhep label -->
                    <div
                        class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-green-500 px-4 py-1"
                    >
                        <span
                            class="text-xs font-black tracking-widest text-white"
                            >XeGhep</span
                        >
                    </div>
                    <!-- Wheels -->
                    <div
                        class="absolute -bottom-4 left-8 h-8 w-8 rounded-full border-2 border-white/40 bg-white/30"
                    />
                    <div
                        class="absolute right-8 -bottom-4 h-8 w-8 rounded-full border-2 border-white/40 bg-white/30"
                    />
                </div>
                <!-- Driver figure -->
                <div class="absolute bottom-28 left-1/2 -translate-x-1/2">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-white/30 bg-white/20"
                    >
                        <svg
                            class="h-8 w-8 text-white"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                            />
                        </svg>
                    </div>
                </div>
                <!-- Route dots -->
                <div class="absolute top-2 left-2 flex flex-col gap-2">
                    <div class="h-3 w-3 rounded-full bg-white" />
                    <div class="mx-1 h-8 w-1 rounded-full bg-white/40" />
                    <div class="h-3 w-3 rounded-full bg-green-300" />
                </div>
                <!-- Stars (rating) -->
                <div class="absolute top-4 right-2 flex gap-0.5">
                    <svg
                        v-for="i in 5"
                        :key="i"
                        class="h-4 w-4 fill-yellow-300 text-yellow-300"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                        />
                    </svg>
                </div>
            </div>

            <!-- Tagline -->
            <div class="text-center">
                <h2 class="mb-2 text-2xl font-bold text-white">
                    Nền tảng tài xế xe ghép tuyến
                </h2>
                <p class="text-base text-green-200">
                    Hà Nội ↔ Hải Phòng · Quản lý chuyến đi dễ dàng
                </p>
            </div>

            <!-- Stats row -->
            <div class="mt-10 flex gap-8">
                <div class="text-center">
                    <p class="text-2xl font-black text-white">500+</p>
                    <p class="text-xs font-medium text-green-200">
                        Tài xế đối tác
                    </p>
                </div>
                <div class="w-px bg-white/20" />
                <div class="text-center">
                    <p class="text-2xl font-black text-white">98%</p>
                    <p class="text-xs font-medium text-green-200">
                        Tỷ lệ hài lòng
                    </p>
                </div>
                <div class="w-px bg-white/20" />
                <div class="text-center">
                    <p class="text-2xl font-black text-white">24/7</p>
                    <p class="text-xs font-medium text-green-200">
                        Hỗ trợ tài xế
                    </p>
                </div>
            </div>
        </div>

        <!-- ─── RIGHT: Login form ──────────────────────────────── -->
        <div
            class="flex min-h-screen flex-1 flex-col items-center justify-center bg-white px-6 py-12 lg:min-h-0"
        >
            <!-- Mobile logo (shown only on small screens) -->
            <div class="mb-8 flex items-center gap-2 lg:hidden">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-600"
                >
                    <svg
                        class="h-5 w-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                        />
                    </svg>
                </div>
                <span class="text-xl font-black text-gray-900"
                    >XeGhep<span class="text-green-600">.vn</span></span
                >
            </div>

            <!-- Form card -->
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-black text-gray-900">
                        Đăng nhập Tài xế
                    </h1>
                    <p class="mt-1.5 text-sm text-gray-500">
                        Nhập thông tin tài khoản tài xế của bạn
                    </p>
                </div>

                <!-- Error alert -->
                <div
                    v-if="error"
                    class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >
                    <svg
                        class="h-5 w-5 flex-shrink-0 text-red-500"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    {{ error }}
                </div>

                <!-- Phone -->
                <div class="mb-4">
                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                        >Số điện thoại</label
                    >
                    <input
                        v-model="phone"
                        type="tel"
                        inputmode="numeric"
                        placeholder="09xxxxxxxx"
                        class="h-12 w-full rounded-xl border border-gray-300 px-4 text-base placeholder-gray-400 transition-colors focus:border-transparent focus:ring-2 focus:ring-green-500 focus:outline-none"
                        @keyup.enter="handleLogin"
                    />
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                        >Mật khẩu</label
                    >
                    <div class="relative">
                        <input
                            v-model="password"
                            :type="showPw ? 'text' : 'password'"
                            placeholder="••••••••"
                            class="h-12 w-full rounded-xl border border-gray-300 px-4 pr-12 text-base placeholder-gray-400 transition-colors focus:border-transparent focus:ring-2 focus:ring-green-500 focus:outline-none"
                            @keyup.enter="handleLogin"
                        />
                        <button
                            type="button"
                            @click="showPw = !showPw"
                            class="absolute top-1/2 right-3 -translate-y-1/2 p-1 text-gray-400 transition-colors hover:text-gray-600"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    v-if="!showPw"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                                <path
                                    v-else
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Forgot password -->
                <div class="mb-6 text-right">
                    <a
                        href="#"
                        class="text-sm font-medium text-green-600 transition-colors hover:text-green-700"
                    >
                        Quên mật khẩu?
                    </a>
                </div>

                <!-- Submit button -->
                <button
                    @click="handleLogin"
                    :disabled="loading || !phone || !password"
                    class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-green-600 text-base font-bold text-white shadow-sm transition-colors hover:bg-green-700 active:bg-green-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <div
                        v-if="loading"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                    />
                    <span>{{
                        loading ? 'Đang đăng nhập...' : 'Đăng nhập'
                    }}</span>
                </button>

                <!-- Divider -->
                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-gray-200" />
                    <span class="text-xs font-medium text-gray-400">HOẶC</span>
                    <div class="h-px flex-1 bg-gray-200" />
                </div>

                <!-- Register link -->
                <p class="text-center text-sm text-gray-500">
                    Chưa có tài khoản?
                    <a
                        href="#"
                        class="ml-1 font-semibold text-green-600 transition-colors hover:text-green-700"
                    >
                        Đăng ký làm tài xế
                    </a>
                </p>

                <!-- Hotline -->
                <div class="mt-8 border-t border-gray-100 pt-6 text-center">
                    <a
                        href="tel:18009999"
                        class="inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700"
                    >
                        <svg
                            class="h-4 w-4 text-green-600"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            />
                        </svg>
                        Hotline hỗ trợ tài xế: 1800-9999 (miễn phí)
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
