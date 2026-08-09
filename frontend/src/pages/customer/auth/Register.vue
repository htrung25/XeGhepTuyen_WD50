<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import AuthHomeLink from '@/components/customer/auth/AuthHomeLink.vue';
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
const otp = ref('');
const otpSent = ref(false);
const otpLoading = ref(false);
const otpVerifying = ref(false);
const verificationToken = ref<string | null>(null);
const verifiedPhone = ref<string | null>(null);
const resendSeconds = ref(0);
const errors = ref<Record<string, string>>({});
let countdownTimer: ReturnType<typeof setInterval> | null = null;

watch(
    () => form.value.phone,
    (phone) => {
        if (verifiedPhone.value && phone !== verifiedPhone.value) {
            verificationToken.value = null;
            verifiedPhone.value = null;
            otp.value = '';
            otpSent.value = false;
        }
    },
);

onUnmounted(() => {
    if (countdownTimer) clearInterval(countdownTimer);
});

function isValidPhone() {
    if (!form.value.phone.trim()) {
        errors.value.phone = 'Vui lòng nhập số điện thoại';
        return false;
    }
    if (!/^(0[35789])[0-9]{8}$/.test(form.value.phone)) {
        errors.value.phone = 'Số điện thoại không hợp lệ (VD: 09xxxxxxxx)';
        return false;
    }
    delete errors.value.phone;
    return true;
}

function startResendCountdown() {
    resendSeconds.value = 60;
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        resendSeconds.value -= 1;
        if (resendSeconds.value <= 0 && countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }, 1000);
}

async function handleSendOtp() {
    if (!isValidPhone() || resendSeconds.value > 0) return;
    otpLoading.value = true;
    delete errors.value.general;
    const { error } = await customerApi.sendOtp({ phone: form.value.phone });
    otpLoading.value = false;
    if (error) {
        errors.value.general = error;
        return;
    }
    otpSent.value = true;
    verificationToken.value = null;
    verifiedPhone.value = null;
    startResendCountdown();
}

async function handleVerifyOtp() {
    if (!/^\d{6}$/.test(otp.value)) {
        errors.value.otp = 'Mã OTP phải gồm 6 chữ số';
        return;
    }
    otpVerifying.value = true;
    delete errors.value.otp;
    delete errors.value.general;
    const { data, error } = await customerApi.verifyOtp({
        phone: form.value.phone,
        otp: otp.value,
    });
    otpVerifying.value = false;
    if (error || !data?.verification_token) {
        errors.value.otp = error ?? 'Không thể xác thực OTP';
        return;
    }
    verificationToken.value = data.verification_token;
    verifiedPhone.value = form.value.phone;
}

function validate() {
    errors.value = {};
    if (!form.value.full_name.trim())
        errors.value.full_name = 'Vui lòng nhập họ tên';
    if (!form.value.phone.trim())
        errors.value.phone = 'Vui lòng nhập số điện thoại';
    else if (!/^(0[35789])[0-9]{8}$/.test(form.value.phone))
        errors.value.phone = 'Số điện thoại không hợp lệ (VD: 09xxxxxxxx)';
    if (
        form.value.email &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
    )
        errors.value.email = 'Email không hợp lệ';
    if (!form.value.password) errors.value.password = 'Vui lòng nhập mật khẩu';
    else if (form.value.password.length < 8)
        errors.value.password = 'Mật khẩu tối thiểu 8 ký tự';
    if (form.value.password !== form.value.password_confirmation)
        errors.value.password_confirmation = 'Mật khẩu xác nhận không khớp';
    if (!verificationToken.value || verifiedPhone.value !== form.value.phone)
        errors.value.otp = 'Vui lòng xác thực số điện thoại';
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
        verification_token: verificationToken.value!,
    });
    loading.value = false;
    if (error || !data?.token || !data?.user) {
        errors.value.general = error ?? 'Đăng ký thất bại, vui lòng thử lại';
        return;
    }
    auth.setAuth(data.token, data.user);
    router.push('/home');
}
</script>

<template>
    <div class="relative flex min-h-dvh">
        <AuthHomeLink />

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
            <div class="relative z-10 mt-10 flex items-center gap-3">
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
                    XeGhepTuyen<span class="text-blue-200">-Fgroup</span>
                </span>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-1 flex-col justify-center py-8">
                <h2 class="text-3xl leading-tight font-bold text-white">
                    Tham gia<br />XeGhepTuyen-Fgroup ngay
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
            class="flex flex-1 items-center justify-center overflow-y-auto bg-gray-50 p-4 sm:p-6"
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
                        >XeGhepTuyen<span class="text-blue-600"
                            >-Fgroup</span
                        ></span
                    >
                </div>

                <!-- Card -->
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8"
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
                            <div class="flex gap-2">
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    inputmode="numeric"
                                    maxlength="10"
                                    placeholder="09xxxxxxxx"
                                    autocomplete="tel"
                                    class="min-w-0 flex-1 rounded-xl border px-4 py-3 text-sm transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="
                                        errors.phone
                                            ? 'border-red-400 bg-red-50'
                                            : 'border-gray-200'
                                    "
                                />
                                <button
                                    type="button"
                                    :disabled="
                                        otpLoading ||
                                        resendSeconds > 0 ||
                                        !!verificationToken
                                    "
                                    class="shrink-0 rounded-xl border border-blue-200 px-3 text-sm font-semibold text-blue-600 transition-colors hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="handleSendOtp"
                                >
                                    <template v-if="verificationToken"
                                        >Đã xác thực</template
                                    >
                                    <template v-else-if="otpLoading"
                                        >Đang gửi...</template
                                    >
                                    <template v-else-if="resendSeconds > 0">
                                        Gửi lại {{ resendSeconds }}s
                                    </template>
                                    <template v-else>{{
                                        otpSent ? 'Gửi lại' : 'Gửi OTP'
                                    }}</template>
                                </button>
                            </div>
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

                        <!-- OTP verification -->
                        <div v-if="otpSent || verificationToken">
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Mã xác thực <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="otp"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    autocomplete="one-time-code"
                                    placeholder="Nhập 6 chữ số"
                                    :disabled="!!verificationToken"
                                    class="min-w-0 flex-1 rounded-xl border px-4 py-3 text-center text-sm tracking-[0.35em] transition-colors focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-green-50 disabled:text-green-700"
                                    :class="
                                        errors.otp
                                            ? 'border-red-400 bg-red-50'
                                            : verificationToken
                                              ? 'border-green-300'
                                              : 'border-gray-200'
                                    "
                                />
                                <button
                                    v-if="!verificationToken"
                                    type="button"
                                    :disabled="otpVerifying || otp.length !== 6"
                                    class="shrink-0 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="handleVerifyOtp"
                                >
                                    {{
                                        otpVerifying
                                            ? 'Đang kiểm tra...'
                                            : 'Xác thực'
                                    }}
                                </button>
                            </div>
                            <p
                                v-if="verificationToken"
                                class="mt-1.5 text-xs font-medium text-green-600"
                            >
                                Số điện thoại đã được xác thực
                            </p>
                            <p
                                v-else-if="errors.otp"
                                class="mt-1.5 text-xs text-red-500"
                            >
                                {{ errors.otp }}
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
                                    placeholder="Tối thiểu 8 ký tự"
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
                                    class="absolute top-1/2 right-1 flex h-11 w-11 -translate-y-1/2 items-center justify-center text-gray-400 transition-colors hover:text-gray-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                                    :aria-label="
                                        showPw ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
                                    "
                                    :aria-pressed="showPw"
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
                                    class="absolute top-1/2 right-1 flex h-11 w-11 -translate-y-1/2 items-center justify-center text-gray-400 transition-colors hover:text-gray-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                                    :aria-label="
                                        showPw2
                                            ? 'Ẩn mật khẩu xác nhận'
                                            : 'Hiện mật khẩu xác nhận'
                                    "
                                    :aria-pressed="showPw2"
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
                    © 2026 XeGhepTuyen-Fgroup · Nền tảng ghép xe tuyến cố định
                </p>
            </div>
        </div>
    </div>
</template>
