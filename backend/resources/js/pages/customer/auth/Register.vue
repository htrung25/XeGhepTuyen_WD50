<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const router = useRouter();
const auth = useCustomerAuthStore();

const form = ref({
    full_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const showPw = ref(false);
const showPw2 = ref(false);
const loading = ref(false);
const errors = ref<Record<string, string>>({});

function validate() {
    errors.value = {};
    if (!form.value.full_name.trim())
        errors.value.full_name = 'Vui lòng nhập họ tên';
    if (!form.value.phone.trim())
        errors.value.phone = 'Vui lòng nhập số điện thoại';
    else if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(form.value.phone))
        errors.value.phone = 'Số điện thoại không hợp lệ (VD: 09xxxxxxxx)';
    if (
        form.value.email &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
    )
        errors.value.email = 'Email không hợp lệ';
    if (!form.value.password) errors.value.password = 'Vui lòng nhập mật khẩu';
    else if (form.value.password.length < 6)
        errors.value.password = 'Mật khẩu tối thiểu 6 ký tự';
    if (form.value.password !== form.value.password_confirmation)
        errors.value.password_confirmation = 'Mật khẩu xác nhận không khớp';
    return Object.keys(errors.value).length === 0;
}

async function handleRegister() {
    if (!validate()) return;
    loading.value = true;
    errors.value = {};
    const { data, error } = await customerApi.register({
        full_name: form.value.full_name,
        phone: form.value.phone,
        email: form.value.email || undefined,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
    });
    loading.value = false;
    if (error) {
        errors.value.general = error;
        return;
    }
    auth.setAuth(data.token, data.user);
    router.push('/home');
}
</script>

<template>
    <div class="flex min-h-screen">
        <!-- ─── Left panel (ẩn trên mobile) ──────────────────────────────── -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-800 via-blue-700 to-blue-500 p-12 lg:flex lg:w-5/12"
        >
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

            <!-- Content -->
            <div class="relative z-10 flex flex-1 flex-col justify-center py-8">
                <h2 class="text-3xl leading-tight font-bold text-white">
                    Tham gia<br />XeGhep.vn ngay
                </h2>
                <p class="mt-3 leading-relaxed text-blue-200">
                    Tạo tài khoản miễn phí và trải nghiệm đặt vé nhanh nhất cho
                    tuyến Hà Nội ↔ Hải Phòng.
                </p>

                <!-- Benefits -->
                <div class="mt-8 space-y-4">
                    <div
                        v-for="item in [
                            {
                                icon: '🎟️',
                                title: 'Đặt vé nhanh 30 giây',
                                desc: 'Chọn ghế, thanh toán, nhận vé ngay',
                            },
                            {
                                icon: '📍',
                                title: 'Theo dõi xe thực tế',
                                desc: 'Biết chính xác xe đang ở đâu',
                            },
                            {
                                icon: '💰',
                                title: 'Ví điện tử tiện lợi',
                                desc: 'Nạp tiền, hoàn tiền tự động',
                            },
                        ]"
                        :key="item.title"
                        class="flex items-start gap-3"
                    >
                        <div
                            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-white/15 text-lg"
                        >
                            {{ item.icon }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ item.title }}
                            </p>
                            <p class="mt-0.5 text-xs text-blue-200">
                                {{ item.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
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
        <div
            class="flex flex-1 items-center justify-center overflow-y-auto bg-gray-50 p-6"
        >
            <div class="w-full max-w-md py-8">
                <!-- Logo (mobile only) -->
                <div
                    class="mb-7 flex items-center justify-center gap-2 lg:hidden"
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
                                d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm10 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"
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
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Tạo tài khoản
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Điền thông tin để bắt đầu đặt vé
                        </p>
                    </div>

                    <!-- General error -->
                    <div
                        v-if="errors.general"
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
                        <p class="text-sm text-red-700">{{ errors.general }}</p>
                    </div>

                    <form @submit.prevent="handleRegister" class="space-y-4">
                        <!-- Full name -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Họ và tên <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                placeholder="Nguyễn Văn A"
                                autocomplete="name"
                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="
                                    errors.full_name
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-200'
                                "
                            />
                            <p
                                v-if="errors.full_name"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ errors.full_name }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Số điện thoại
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                inputmode="numeric"
                                placeholder="09xxxxxxxx"
                                autocomplete="tel"
                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="
                                    errors.phone
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-200'
                                "
                            />
                            <p
                                v-if="errors.phone"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ errors.phone }}
                            </p>
                        </div>

                        <!-- Email (optional) -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Email
                                <span
                                    class="ml-1 text-xs font-normal text-gray-400"
                                    >(tùy chọn)</span
                                >
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="example@email.com"
                                autocomplete="email"
                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="
                                    errors.email
                                        ? 'border-red-400 bg-red-50'
                                        : 'border-gray-200'
                                "
                            />
                            <p
                                v-if="errors.email"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Mật khẩu <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPw ? 'text' : 'password'"
                                    placeholder="Tối thiểu 6 ký tự"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border px-4 py-3 pr-11 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="
                                        errors.password
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-gray-200'
                                    "
                                />
                                <button
                                    type="button"
                                    @click="showPw = !showPw"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600"
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
                            <p
                                v-if="errors.password"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ errors.password }}
                            </p>
                        </div>

                        <!-- Confirm password -->
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Xác nhận mật khẩu
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPw2 ? 'text' : 'password'"
                                    placeholder="Nhập lại mật khẩu"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border px-4 py-3 pr-11 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="
                                        errors.password_confirmation
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-gray-200'
                                    "
                                />
                                <button
                                    type="button"
                                    @click="showPw2 = !showPw2"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            v-if="!showPw2"
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
                            <p
                                v-if="errors.password_confirmation"
                                class="mt-1.5 flex items-center gap-1 text-xs text-red-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="loading"
                            class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
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
                            {{
                                loading
                                    ? 'Đang tạo tài khoản...'
                                    : 'Tạo tài khoản'
                            }}
                        </button>
                    </form>

                    <!-- Login link -->
                    <p class="mt-5 text-center text-sm text-gray-600">
                        Đã có tài khoản?
                        <router-link
                            to="/login"
                            class="font-semibold text-blue-600 transition-colors hover:text-blue-700"
                        >
                            Đăng nhập
                        </router-link>
                    </p>
                </div>

                <p class="mt-5 text-center text-xs text-gray-400">
                    © 2024 XeGhep.vn · Nền tảng ghép xe tuyến cố định
                </p>
            </div>
        </div>
    </div>
</template>
