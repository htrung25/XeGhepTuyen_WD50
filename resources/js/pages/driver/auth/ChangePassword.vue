<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const router = useRouter();
const auth = useDriverAuthStore();

const oldPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showOld = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);
const loading = ref(false);
const error = ref('');
const fieldErrors = ref<Record<string, string>>({});

async function handleSubmit() {
    error.value = '';
    fieldErrors.value = {};

    if (!oldPassword.value) { fieldErrors.value.old = 'Vui lòng nhập mật khẩu tạm thời'; return; }
    if (!newPassword.value) { fieldErrors.value.new = 'Vui lòng nhập mật khẩu mới'; return; }
    if (newPassword.value.length < 8) { fieldErrors.value.new = 'Mật khẩu mới tối thiểu 8 ký tự'; return; }
    if (newPassword.value === oldPassword.value) { fieldErrors.value.new = 'Mật khẩu mới phải khác mật khẩu tạm thời'; return; }
    if (newPassword.value !== confirmPassword.value) { fieldErrors.value.confirm = 'Xác nhận mật khẩu không khớp'; return; }

    loading.value = true;
    try {
        const { data, error: err } = await driverApi.changePassword({
            old_password: oldPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: confirmPassword.value,
        });

        if (err) {
            const msg = typeof err === 'string' ? err : 'Đổi mật khẩu thất bại, vui lòng thử lại.';
            if (msg.toLowerCase().includes('hiện tại') || msg.toLowerCase().includes('current')) {
                fieldErrors.value.old = 'Mật khẩu tạm thời không chính xác';
            } else {
                error.value = msg;
            }
            return;
        }

        // Xoá cờ bắt buộc đổi mật khẩu trong store
        auth.clearMustChangePassword();
        router.replace('/driver/dashboard');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-green-950 via-green-900 to-emerald-800 p-4">
        <!-- Decorative blobs -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-green-500/10 blur-3xl" />
            <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl" />
        </div>

        <div class="relative w-full max-w-md">
            <!-- Card -->
            <div class="overflow-hidden rounded-3xl bg-white/10 shadow-2xl backdrop-blur-xl ring-1 ring-white/20">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-8 py-7 text-center">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 ring-2 ring-white/30">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-white">Đổi mật khẩu bắt buộc</h1>
                    <p class="mt-1 text-sm text-green-100">Tài xế {{ auth.user?.full_name }}</p>
                </div>

                <!-- Alert banner -->
                <div class="flex items-start gap-3 bg-amber-500/20 px-6 py-4 ring-1 ring-amber-400/30">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-sm leading-relaxed text-amber-200">
                        Tài khoản của bạn đang dùng <strong>mật khẩu tạm thời</strong>.
                        Vui lòng đặt mật khẩu mới để bảo vệ tài khoản trước khi tiếp tục.
                    </p>
                </div>

                <!-- Form -->
                <form class="space-y-5 px-8 py-7" @submit.prevent="handleSubmit">
                    <!-- Global error -->
                    <div v-if="error" class="rounded-xl bg-red-500/20 px-4 py-3 text-sm text-red-200 ring-1 ring-red-400/40">
                        {{ error }}
                    </div>

                    <!-- Old password -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-green-100">Mật khẩu tạm thời</label>
                        <div class="relative">
                            <input
                                id="old-password"
                                v-model="oldPassword"
                                :type="showOld ? 'text' : 'password'"
                                placeholder="Nhập mật khẩu tạm thời đã nhận qua SMS"
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 pr-11 text-sm text-white placeholder-green-300/50 outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400/30"
                                :class="fieldErrors.old ? 'border-red-400 focus:ring-red-400/30' : ''"
                            />
                            <button type="button" tabindex="-1" @click="showOld = !showOld"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-green-300/70 hover:text-green-200 transition">
                                <svg v-if="showOld" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="fieldErrors.old" class="text-xs text-red-300">{{ fieldErrors.old }}</p>
                    </div>

                    <!-- New password -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-green-100">Mật khẩu mới</label>
                        <div class="relative">
                            <input
                                id="new-password"
                                v-model="newPassword"
                                :type="showNew ? 'text' : 'password'"
                                placeholder="Tối thiểu 8 ký tự"
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 pr-11 text-sm text-white placeholder-green-300/50 outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400/30"
                                :class="fieldErrors.new ? 'border-red-400 focus:ring-red-400/30' : ''"
                            />
                            <button type="button" tabindex="-1" @click="showNew = !showNew"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-green-300/70 hover:text-green-200 transition">
                                <svg v-if="showNew" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="fieldErrors.new" class="text-xs text-red-300">{{ fieldErrors.new }}</p>
                        <!-- Strength hint -->
                        <div class="flex gap-1 pt-0.5">
                            <div v-for="n in 4" :key="n"
                                class="h-1 flex-1 rounded-full transition-all duration-300"
                                :class="newPassword.length >= n * 2 ? (newPassword.length >= 12 ? 'bg-green-400' : newPassword.length >= 8 ? 'bg-yellow-400' : 'bg-red-400') : 'bg-white/10'"
                            />
                        </div>
                    </div>

                    <!-- Confirm password -->
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-green-100">Xác nhận mật khẩu mới</label>
                        <div class="relative">
                            <input
                                id="confirm-password"
                                v-model="confirmPassword"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Nhập lại mật khẩu mới"
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 pr-11 text-sm text-white placeholder-green-300/50 outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400/30"
                                :class="fieldErrors.confirm ? 'border-red-400 focus:ring-red-400/30' : (confirmPassword && confirmPassword === newPassword ? 'border-green-400' : '')"
                            />
                            <button type="button" tabindex="-1" @click="showConfirm = !showConfirm"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-green-300/70 hover:text-green-200 transition">
                                <svg v-if="showConfirm" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                            <!-- Match indicator -->
                            <svg v-if="confirmPassword && confirmPassword === newPassword"
                                class="absolute right-10 top-1/2 -translate-y-1/2 h-4 w-4 text-green-400"
                                fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p v-if="fieldErrors.confirm" class="text-xs text-red-300">{{ fieldErrors.confirm }}</p>
                    </div>

                    <!-- Submit -->
                    <button
                        id="submit-change-password"
                        type="submit"
                        :disabled="loading"
                        class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 py-3.5 text-sm font-semibold text-white shadow-lg shadow-green-500/30 transition-all duration-200 hover:from-green-400 hover:to-emerald-400 hover:shadow-green-400/40 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span v-if="!loading" class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Xác nhận đổi mật khẩu
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Đang xử lý...
                        </span>
                    </button>

                    <p class="text-center text-xs text-green-300/60">
                        Sau khi đổi mật khẩu, bạn sẽ được chuyển vào ứng dụng tự động.
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
