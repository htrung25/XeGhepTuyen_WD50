<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { adminApi } from '@/api/admin.api';
import { useAdminAuthStore } from '@/stores/admin.auth.store';

const router = useRouter();
const authStore = useAdminAuthStore();

const form = ref({ email: '', password: '' });
const showPassword = ref(false);
const isLoading = ref(false);
const errorMsg = ref('');

async function handleLogin() {
    if (!form.value.email || !form.value.password) {
        errorMsg.value = 'Vui lòng nhập đầy đủ thông tin';
        return;
    }
    isLoading.value = true;
    errorMsg.value = '';

    const { data, error } = await adminApi.login(form.value);
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    authStore.setAuth(data.token, data.user);
    router.push('/admin/dashboard');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-900 p-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="rounded-2xl bg-white p-8 shadow-2xl">
                <!-- Logo -->
                <div class="mb-8 flex items-center justify-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-600"
                    >
                        <svg
                            class="h-6 w-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-gray-900"
                            >XeGhep</span
                        >
                        <span class="text-xl font-bold text-red-600"
                            >&nbsp;Admin</span
                        >
                    </div>
                </div>

                <h1 class="mb-2 text-center text-2xl font-bold text-gray-900">
                    Đăng nhập Quản trị viên
                </h1>
                <p class="mb-8 text-center text-sm text-gray-500">
                    Truy cập hệ thống quản trị XeGhep
                </p>

                <!-- Error alert -->
                <div
                    v-if="errorMsg"
                    class="mb-5 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    {{ errorMsg }}
                </div>

                <!-- Form -->
                <form @submit.prevent="handleLogin" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Email</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            placeholder="admin@xeghep.vn"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition focus:border-transparent focus:ring-2 focus:ring-red-500 focus:outline-none"
                            :disabled="isLoading"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Mật khẩu</label
                        >
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm transition focus:border-transparent focus:ring-2 focus:ring-red-500 focus:outline-none"
                                :disabled="isLoading"
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                @click="showPassword = !showPassword"
                            >
                                <svg
                                    v-if="!showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
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
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
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
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        <svg
                            v-if="isLoading"
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
                        {{ isLoading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
                    </button>
                </form>

                <!-- Security notice -->
                <p
                    class="mt-6 flex items-center justify-center gap-1 text-center text-xs text-gray-400"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                    Chỉ dành cho quản trị viên hệ thống
                </p>
            </div>
        </div>
    </div>
</template>
