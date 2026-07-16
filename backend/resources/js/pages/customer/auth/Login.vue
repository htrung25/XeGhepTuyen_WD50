<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const router = useRouter();
const route = useRoute();
const auth = useCustomerAuthStore();

const phone = ref('');
const password = ref('');
const showPw = ref(false);
const loading = ref(false);
const error = ref('');

async function handleLogin() {
    if (!phone.value.trim() || !password.value) return;
    loading.value = true;
    error.value = '';
    const { data, error: err } = await customerApi.login({
        phone: phone.value.trim(),
        password: password.value,
    });
    loading.value = false;
    if (err) {
        error.value = err;
        return;
    }
    auth.setAuth(data.token, data.user);
    const redirect = route.query.redirect as string | undefined;
    router.push(redirect ?? '/home');
}
</script>

<template>
    <div class="flex min-h-screen">
        <!-- ─── Left panel (ẩn trên mobile) ──────────────────────────────── -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-800 via-blue-700 to-blue-500 p-12 lg:flex lg:w-1/2"
        >
            <!-- Background circles decoration -->
            <div
                class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-white/5"
            />
            <div
                class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white/5"
            />

            <!-- Logo -->
            <div class="relative z-10 flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur"
                >
                    <svg
                        class="h-6 w-6 text-white"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M17 8h1a4 4 0 110 8h-1v1a1 1 0 01-2 0V7a1 1 0 012 0v1zm0 6h1a2 2 0 000-4h-1v4zM3 8a1 1 0 011-1h8a4 4 0 010 8H4a1 1 0 01-1-1V8zm2 1v6h7a2 2 0 000-4H5V9z"
                        />
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">
                    XeGhep<span class="text-blue-200">.vn</span>
                </span>
            </div>

            <!-- Illustration -->
            <div
                class="relative z-10 flex flex-1 flex-col items-center justify-center py-12"
            >
                <svg viewBox="0 0 320 220" class="w-full max-w-xs" fill="none">
                    <!-- Road -->
                    <rect
                        x="20"
                        y="160"
                        width="280"
                        height="8"
                        rx="4"
                        fill="white"
                        fill-opacity="0.15"
                    />
                    <rect
                        x="90"
                        y="162"
                        width="40"
                        height="4"
                        rx="2"
                        fill="white"
                        fill-opacity="0.4"
                    />
                    <rect
                        x="150"
                        y="162"
                        width="40"
                        height="4"
                        rx="2"
                        fill="white"
                        fill-opacity="0.4"
                    />
                    <rect
                        x="210"
                        y="162"
                        width="40"
                        height="4"
                        rx="2"
                        fill="white"
                        fill-opacity="0.4"
                    />
                    <!-- Bus body -->
                    <rect
                        x="40"
                        y="100"
                        width="180"
                        height="64"
                        rx="12"
                        fill="white"
                        fill-opacity="0.9"
                    />
                    <rect
                        x="50"
                        y="110"
                        width="50"
                        height="36"
                        rx="6"
                        fill="#BFDBFE"
                    />
                    <rect
                        x="108"
                        y="110"
                        width="50"
                        height="36"
                        rx="6"
                        fill="#BFDBFE"
                    />
                    <rect
                        x="166"
                        y="110"
                        width="40"
                        height="36"
                        rx="6"
                        fill="#BFDBFE"
                    />
                    <!-- Wheels -->
                    <circle
                        cx="78"
                        cy="168"
                        r="14"
                        fill="white"
                        fill-opacity="0.25"
                    />
                    <circle
                        cx="78"
                        cy="168"
                        r="8"
                        fill="white"
                        fill-opacity="0.5"
                    />
                    <circle
                        cx="182"
                        cy="168"
                        r="14"
                        fill="white"
                        fill-opacity="0.25"
                    />
                    <circle
                        cx="182"
                        cy="168"
                        r="8"
                        fill="white"
                        fill-opacity="0.5"
                    />
                    <!-- Bus front -->
                    <rect
                        x="220"
                        y="108"
                        width="40"
                        height="56"
                        rx="10"
                        fill="white"
                        fill-opacity="0.85"
                    />
                    <rect
                        x="226"
                        y="116"
                        width="28"
                        height="22"
                        rx="4"
                        fill="#93C5FD"
                    />
                    <!-- Driver -->
                    <circle
                        cx="238"
                        cy="90"
                        r="10"
                        fill="white"
                        fill-opacity="0.8"
                    />
                    <path
                        d="M228 108 Q238 98 248 108"
                        stroke="white"
                        stroke-width="3"
                        fill="none"
                        stroke-opacity="0.8"
                        stroke-linecap="round"
                    />
                    <!-- Route dots -->
                    <circle
                        cx="30"
                        cy="80"
                        r="5"
                        fill="white"
                        fill-opacity="0.7"
                    />
                    <circle
                        cx="60"
                        cy="60"
                        r="4"
                        fill="white"
                        fill-opacity="0.5"
                    />
                    <circle
                        cx="100"
                        cy="50"
                        r="6"
                        fill="white"
                        fill-opacity="0.8"
                    />
                    <circle
                        cx="160"
                        cy="45"
                        r="4"
                        fill="white"
                        fill-opacity="0.5"
                    />
                    <circle
                        cx="220"
                        cy="55"
                        r="5"
                        fill="white"
                        fill-opacity="0.7"
                    />
                    <circle
                        cx="270"
                        cy="70"
                        r="6"
                        fill="white"
                        fill-opacity="0.8"
                    />
                    <!-- Connecting line -->
                    <path
                        d="M30 80 Q60 60 100 50 Q160 45 220 55 Q250 62 270 70"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-dasharray="4 3"
                        stroke-opacity="0.4"
                        fill="none"
                    />
                    <!-- Stars -->
                    <path
                        d="M280 30 l2 5 5 0 -4 3 2 5 -5-3 -5 3 2-5 -4-3 5 0z"
                        fill="white"
                        fill-opacity="0.7"
                    />
                    <path
                        d="M20 40 l1.5 3.5 3.5 0 -2.8 2.1 1.4 3.5 -3.6-2.1 -3.6 2.1 1.4-3.5 -2.8-2.1 3.5 0z"
                        fill="white"
                        fill-opacity="0.5"
                    />
                </svg>

                <h2
                    class="mt-6 text-center text-2xl leading-snug font-bold text-white"
                >
                    Đặt vé xe<br />nhanh & tiện lợi
                </h2>
                <p class="mt-2 max-w-xs text-center text-sm text-blue-200">
                    Hà Nội ↔ Hải Phòng — Hàng trăm chuyến mỗi ngày, chọn ghế
                    theo ý thích
                </p>
            </div>

            <!-- Stats row -->
            <div class="relative z-10 grid grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-xl font-bold text-white">500+</p>
                    <p class="mt-0.5 text-xs text-blue-200">Khách hàng</p>
                </div>
                <div class="border-x border-white/20 text-center">
                    <p class="text-xl font-bold text-white">50+</p>
                    <p class="mt-0.5 text-xs text-blue-200">Chuyến/ngày</p>
                </div>
                <div class="text-center">
                    <p class="text-xl font-bold text-white">4.9★</p>
                    <p class="mt-0.5 text-xs text-blue-200">Đánh giá</p>
                </div>
            </div>
        </div>

        <!-- ─── Right panel — form ────────────────────────────────────────── -->
        <div class="flex flex-1 items-center justify-center bg-gray-50 p-6">
            <div class="w-full max-w-md">
                <!-- Logo (chỉ hiện trên mobile khi left panel ẩn) -->
                <div
                    class="mb-8 flex items-center justify-center gap-2 lg:hidden"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600"
                    >
                        <svg
                            class="h-5 w-5 text-white"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm10 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 5h2.5l1.5 6h11l1.5-6H21"
                            />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900"
                        >XeGhep<span class="text-blue-600">.vn</span></span
                    >
                </div>

                <!-- Card -->
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm"
                >
                    <div class="mb-7">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Đăng nhập
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Nhập thông tin để tiếp tục đặt vé
                        </p>
                    </div>

                    <!-- Error -->
                    <div
                        v-if="error"
                        class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5"
                    >
                        <svg
                            class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p class="text-sm text-red-700">{{ error }}</p>
                    </div>

                    <form @submit.prevent="handleLogin" class="space-y-5">
                        <!-- Phone -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Số điện thoại
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute top-1/2 left-3.5 -translate-y-1/2 text-gray-400"
                                >
                                    <svg
                                        class="h-4.5 w-4.5"
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
                                </span>
                                <input
                                    v-model="phone"
                                    type="tel"
                                    inputmode="numeric"
                                    placeholder="09xxxxxxxx"
                                    autocomplete="tel"
                                    class="w-full rounded-xl border border-gray-200 py-3 pr-4 pl-10 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    @keyup.enter="handleLogin"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Mật khẩu</label
                                >
                                <a
                                    href="#"
                                    class="text-xs text-blue-600 hover:text-blue-700"
                                    >Quên mật khẩu?</a
                                >
                            </div>
                            <div class="relative">
                                <span
                                    class="absolute top-1/2 left-3.5 -translate-y-1/2 text-gray-400"
                                >
                                    <svg
                                        class="h-4.5 w-4.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        />
                                    </svg>
                                </span>
                                <input
                                    v-model="password"
                                    :type="showPw ? 'text' : 'password'"
                                    placeholder="••••••••"
                                    autocomplete="current-password"
                                    class="w-full rounded-xl border border-gray-200 py-3 pr-11 pl-10 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    @keyup.enter="handleLogin"
                                />
                                <button
                                    type="button"
                                    @click="showPw = !showPw"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600"
                                >
                                    <svg
                                        v-if="!showPw"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
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
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="loading || !phone || !password"
                            class="mt-1 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                        >
                            <svg
                                v-if="loading"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-6 flex items-center gap-3">
                        <div class="h-px flex-1 bg-gray-100" />
                        <span class="text-xs text-gray-400">hoặc</span>
                        <div class="h-px flex-1 bg-gray-100" />
                    </div>

                    <!-- Register link -->
                    <p class="text-center text-sm text-gray-600">
                        Chưa có tài khoản?
                        <router-link
                            to="/register"
                            class="font-semibold text-blue-600 transition-colors hover:text-blue-700"
                        >
                            Đăng ký ngay
                        </router-link>
                    </p>

                    <!-- Skip -->
                    <p class="mt-3 text-center">
                        <router-link
                            to="/home"
                            class="text-xs text-gray-400 transition-colors hover:text-gray-600"
                        >
                            Tiếp tục không đăng nhập →
                        </router-link>
                    </p>
                </div>

                <!-- Footer -->
                <p class="mt-5 text-center text-xs text-gray-400">
                    © 2024 XeGhep.vn · Nền tảng ghép xe tuyến cố định
                </p>
            </div>
        </div>
    </div>
</template>
