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

    if (!oldPassword.value) {
        fieldErrors.value.old = 'Vui lòng nhập mật khẩu tạm thời';
        return;
    }
    if (!newPassword.value) {
        fieldErrors.value.new = 'Vui lòng nhập mật khẩu mới';
        return;
    }
    if (newPassword.value.length < 8) {
        fieldErrors.value.new = 'Mật khẩu mới tối thiểu 8 ký tự';
        return;
    }
    if (newPassword.value === oldPassword.value) {
        fieldErrors.value.new = 'Mật khẩu mới phải khác mật khẩu tạm thời';
        return;
    }
    if (newPassword.value !== confirmPassword.value) {
        fieldErrors.value.confirm = 'Xác nhận mật khẩu không khớp';
        return;
    }

    loading.value = true;
    try {
        const { error: err } = await driverApi.changePassword({
            old_password: oldPassword.value,
            new_password: newPassword.value,
            new_password_confirmation: confirmPassword.value,
        });

        if (err) {
            const msg =
                typeof err === 'string'
                    ? err
                    : 'Đổi mật khẩu thất bại, vui lòng thử lại.';
            if (
                msg.toLowerCase().includes('hiện tại') ||
                msg.toLowerCase().includes('current')
            ) {
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
    <div
        class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-12"
    >
        <!-- Background decorative vectors/gradients -->
        <div
            class="pointer-events-none absolute -top-40 -right-40 h-96 w-96 rounded-full bg-green-500/5 blur-3xl"
        />
        <div
            class="pointer-events-none absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-emerald-500/5 blur-3xl"
        />

        <div class="relative w-full max-w-md">
            <!-- Brand Logo Center -->
            <div class="mb-6 flex items-center justify-center gap-2">
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
                <span class="text-2xl font-black text-slate-900"
                    >XeGhepTuyen<span class="text-green-600"
                        >-Fgroup</span
                    ></span
                >
            </div>

            <!-- Card -->
            <div
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl"
            >
                <!-- Header Title -->
                <div class="px-8 pt-8 pb-5 text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-green-100 bg-green-50 text-green-600"
                    >
                        <svg
                            class="h-6 w-6"
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
                    </div>
                    <h1 class="text-xl font-bold text-slate-900">
                        Đổi mật khẩu bắt buộc
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Tài xế:
                        <span class="font-semibold text-slate-700">{{
                            auth.user?.full_name
                        }}</span>
                    </p>
                </div>

                <!-- Alert banner (Amber, clean styling) -->
                <div
                    class="flex items-start gap-3 border-y border-amber-100 bg-amber-50 px-6 py-4"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-500"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                        />
                    </svg>
                    <p class="text-sm leading-relaxed text-amber-800">
                        Bạn vừa đăng nhập bằng
                        <strong>mật khẩu tạm thời</strong>. Vui lòng cập nhật
                        mật khẩu mới để tăng cường bảo mật tài khoản.
                    </p>
                </div>

                <!-- Form -->
                <form
                    class="space-y-5 px-8 py-7"
                    @submit.prevent="handleSubmit"
                >
                    <!-- Global error -->
                    <div
                        v-if="error"
                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ error }}
                    </div>

                    <!-- Old password -->
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700"
                            >Mật khẩu tạm thời</label
                        >
                        <div class="relative">
                            <input
                                id="old-password"
                                v-model="oldPassword"
                                :type="showOld ? 'text' : 'password'"
                                placeholder="Nhập mật khẩu tạm thời từ SMS"
                                autocomplete="current-password"
                                class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 pr-11 text-base text-slate-900 placeholder-slate-400 transition focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none"
                                :class="
                                    fieldErrors.old
                                        ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                                        : ''
                                "
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                @click="showOld = !showOld"
                                class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                            >
                                <svg
                                    v-if="showOld"
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
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
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <p v-if="fieldErrors.old" class="text-xs text-red-600">
                            {{ fieldErrors.old }}
                        </p>
                    </div>

                    <!-- New password -->
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700"
                            >Mật khẩu mới</label
                        >
                        <div class="relative">
                            <input
                                id="new-password"
                                v-model="newPassword"
                                :type="showNew ? 'text' : 'password'"
                                placeholder="Đặt mật khẩu mới của bạn"
                                autocomplete="new-password"
                                class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 pr-11 text-base text-slate-900 placeholder-slate-400 transition focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none"
                                :class="
                                    fieldErrors.new
                                        ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                                        : ''
                                "
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                @click="showNew = !showNew"
                                class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                            >
                                <svg
                                    v-if="showNew"
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
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
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <p v-if="fieldErrors.new" class="text-xs text-red-600">
                            {{ fieldErrors.new }}
                        </p>

                        <!-- Password strength hint bars -->
                        <div class="flex gap-1 pt-1.5">
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                :class="
                                    newPassword.length >= n * 2
                                        ? newPassword.length >= 12
                                            ? 'bg-green-500'
                                            : newPassword.length >= 8
                                              ? 'bg-amber-400'
                                              : 'bg-red-500'
                                        : 'bg-slate-100'
                                "
                            />
                        </div>
                        <div
                            class="mt-1 flex justify-between text-[11px] text-slate-400"
                        >
                            <span
                                >Độ bảo mật:
                                {{
                                    newPassword.length >= 12
                                        ? 'Mạnh'
                                        : newPassword.length >= 8
                                          ? 'Trung bình'
                                          : 'Yếu'
                                }}</span
                            >
                            <span>Tối thiểu 8 ký tự</span>
                        </div>
                    </div>

                    <!-- Confirm password -->
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700"
                            >Xác nhận mật khẩu mới</label
                        >
                        <div class="relative">
                            <input
                                id="confirm-password"
                                v-model="confirmPassword"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Nhập lại mật khẩu mới để xác nhận"
                                autocomplete="new-password"
                                class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 pr-11 text-base text-slate-900 placeholder-slate-400 transition focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none"
                                :class="
                                    fieldErrors.confirm
                                        ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                                        : confirmPassword &&
                                            confirmPassword === newPassword
                                          ? 'border-green-500 focus:ring-green-500/20'
                                          : ''
                                "
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                @click="showConfirm = !showConfirm"
                                class="absolute top-1/2 right-3.5 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                            >
                                <svg
                                    v-if="showConfirm"
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
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
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </button>
                            <!-- Valid checkmark icon overlay -->
                            <div
                                v-if="
                                    confirmPassword &&
                                    confirmPassword === newPassword
                                "
                                class="absolute top-1/2 right-10 flex -translate-y-1/2 items-center justify-center"
                            >
                                <svg
                                    class="h-5 w-5 text-green-500"
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
                        </div>
                        <p
                            v-if="fieldErrors.confirm"
                            class="text-xs text-red-600"
                        >
                            {{ fieldErrors.confirm }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        id="submit-change-password"
                        type="submit"
                        :disabled="loading"
                        class="relative mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-green-600 text-base font-bold text-white shadow-lg shadow-green-600/10 transition duration-150 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="!loading" class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                            Cập nhật mật khẩu mới
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <svg
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
                            Đang lưu mật khẩu...
                        </span>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs leading-relaxed text-slate-400">
                Khi hoàn tất, bạn sẽ được tự động chuyển hướng về trang chủ tài
                xế.
            </p>
        </div>
    </div>
</template>
