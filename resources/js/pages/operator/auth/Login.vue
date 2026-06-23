<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { operatorApi } from '@/api/operator.api';
import { useOperatorAuthStore } from '@/stores/operator.auth.store';

const router = useRouter();
const authStore = useOperatorAuthStore();

const form = ref({ phone: '', password: '' });
const showPassword = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
    if (!form.value.phone || !form.value.password) {
        errorMessage.value = 'Vui lòng nhập đầy đủ số điện thoại và mật khẩu';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    const { data, error } = await operatorApi.login(form.value);

    isLoading.value = false;

    if (error) {
        errorMessage.value = error;
        return;
    }

    authStore.setAuth(data.token, data.user, data.operator);
    router.push('/operator/dashboard');
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F7F9FB] p-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div
                class="rounded-2xl bg-white p-8 shadow-[0_12px_24px_rgba(0,0,0,0.08)]"
            >
                <!-- Logo -->
                <div class="mb-8 flex items-center justify-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500"
                    >
                        <svg
                            class="h-6 w-6 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 17l-1.5-5H4a2 2 0 01-2-2V7a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2.5L16 17H8z"
                            />
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-slate-800"
                        >XeGhep<span class="text-amber-500">.vn</span></span
                    >
                </div>

                <!-- Title -->
                <div class="mb-8 text-center">
                    <h1 class="text-xl font-semibold text-slate-800">
                        Đăng nhập dành cho Nhà xe
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Quản lý chuyến đi và doanh thu của bạn
                    </p>
                </div>

                <!-- Error alert -->
                <div
                    v-if="errorMessage"
                    class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <p class="text-sm text-red-700">{{ errorMessage }}</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- Phone -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >Số điện thoại</label
                        >
                        <input
                            v-model="form.phone"
                            type="tel"
                            placeholder="0901234567"
                            autocomplete="tel"
                            class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-800 placeholder-slate-400 transition-colors focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >Mật khẩu</label
                        >
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-800 placeholder-slate-400 transition-colors focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none"
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600"
                                @click="showPassword = !showPassword"
                            >
                                <svg
                                    v-if="!showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
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
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 py-3 font-semibold text-white transition-colors hover:bg-amber-600 disabled:bg-amber-300"
                    >
                        <svg
                            v-if="isLoading"
                            class="h-5 w-5 animate-spin"
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
                        {{ isLoading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                    </button>
                </form>

                <!-- Register link -->
                <p class="mt-6 text-center text-sm text-slate-500">
                    Chưa có tài khoản?
                    <router-link
                        to="/operator/register"
                        class="font-semibold text-amber-600 hover:text-amber-700"
                    >
                        Đăng ký ngay
                    </router-link>
                </p>
            </div>

            <!-- Footer note -->
            <p class="mt-6 text-center text-xs text-slate-400">
                © 2024 XeGhep.vn · Nền tảng ghép xe tuyến cố định
            </p>
        </div>
    </div>
</template>
