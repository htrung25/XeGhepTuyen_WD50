<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const router = useRouter();
const auth = useCustomerAuthStore();

type Section = 'profile' | 'password' | 'wallet' | 'vouchers';

const activeSection = ref<Section>('profile');
const isLoading = ref(true);
const saveLoading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const wallet = ref<any>(null);

const form = ref({
    full_name: auth.user?.full_name ?? '',
    email: auth.user?.email ?? '',
    phone: auth.user?.phone ?? '',
});

const passwordForm = ref({
    old_password: '',
    new_password: '',
    confirm: '',
});

const stats = ref({ total_trips: 0, total_points: 0, vouchers: 0 });

const menuItems: { key: Section; icon: string; label: string }[] = [
    { key: 'profile', icon: '👤', label: 'Thông tin cá nhân' },
    { key: 'password', icon: '🔒', label: 'Đổi mật khẩu' },
    { key: 'wallet', icon: '💳', label: 'Ví XeGhep' },
    { key: 'vouchers', icon: '🏷️', label: 'Mã giảm giá' },
];

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN').format(v) + 'đ';
}

async function saveProfile() {
    saveLoading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    const { error } = await customerApi.me(); // check then update
    // In real app: PUT /api/customer/auth/profile
    saveLoading.value = false;
    if (error) {
        errorMsg.value = 'Cập nhật thất bại. Vui lòng thử lại.';
        return;
    }
    auth.setAuth(auth.token!, {
        ...auth.user!,
        full_name: form.value.full_name,
        email: form.value.email,
    } as any);
    successMsg.value = 'Cập nhật thông tin thành công!';
    setTimeout(() => {
        successMsg.value = '';
    }, 3000);
}

async function changePassword() {
    errorMsg.value = '';
    successMsg.value = '';
    if (passwordForm.value.new_password.length < 8) {
        errorMsg.value = 'Mật khẩu mới tối thiểu 8 ký tự';
        return;
    }
    if (passwordForm.value.new_password !== passwordForm.value.confirm) {
        errorMsg.value = 'Xác nhận mật khẩu mới không khớp';
        return;
    }
    saveLoading.value = true;
    const { error } = await customerApi.changePassword({
        old_password: passwordForm.value.old_password,
        new_password: passwordForm.value.new_password,
        new_password_confirmation: passwordForm.value.confirm,
    });
    saveLoading.value = false;
    if (error) {
        errorMsg.value = error;
        return;
    }
    successMsg.value = 'Đổi mật khẩu thành công!';
    passwordForm.value = { old_password: '', new_password: '', confirm: '' };
    setTimeout(() => {
        successMsg.value = '';
    }, 3000);
}

async function logout() {
    await customerApi.logout();
    auth.logout();
    router.push('/login');
}

onMounted(async () => {
    const [meRes, walletRes] = await Promise.all([
        customerApi.me(),
        customerApi.getWallet(),
    ]);
    isLoading.value = false;
    if (meRes.data) {
        form.value.full_name = meRes.data.full_name ?? '';
        form.value.email = meRes.data.email ?? '';
        form.value.phone = meRes.data.phone ?? '';
    }
    if (walletRes.data) wallet.value = walletRes.data;
});
</script>

<template>
    <div class="mx-auto max-w-5xl px-6 py-8">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Tài khoản của tôi</h1>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"
            />
        </div>

        <div v-else class="grid grid-cols-[280px_1fr] gap-8">
            <!-- ─── LEFT: Sidebar menu ─────────────────────── -->
            <aside>
                <!-- User card -->
                <div
                    class="mb-4 rounded-xl border border-gray-200 bg-white p-5 text-center shadow-sm"
                >
                    <div
                        class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-full bg-blue-100 text-3xl font-bold text-blue-700"
                    >
                        {{ auth.user?.full_name?.charAt(0) ?? 'K' }}
                    </div>
                    <p class="text-base font-bold text-gray-900">
                        {{ auth.user?.full_name ?? '—' }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ auth.user?.phone ?? '—' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Thành viên từ 2024</p>
                </div>

                <!-- Menu links -->
                <nav
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <button
                        v-for="item in menuItems"
                        :key="item.key"
                        @click="activeSection = item.key"
                        :class="[
                            'flex w-full items-center gap-3 border-l-2 px-4 py-3.5 text-left text-sm font-medium transition-colors',
                            activeSection === item.key
                                ? 'border-blue-600 bg-blue-50 text-blue-700'
                                : 'border-transparent text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <span class="text-base">{{ item.icon }}</span>
                        {{ item.label }}
                    </button>

                    <div class="border-t border-gray-100">
                        <router-link
                            to="/bookings"
                            class="flex w-full items-center gap-3 px-4 py-3.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            <span class="text-base">🎫</span>
                            Vé của tôi
                        </router-link>
                        <button
                            @click="logout"
                            class="flex w-full items-center gap-3 px-4 py-3.5 text-sm font-medium text-red-500 transition-colors hover:bg-red-50"
                        >
                            <span class="text-base">🚪</span>
                            Đăng xuất
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- ─── RIGHT: Content area ────────────────────── -->
            <div>
                <!-- Profile section -->
                <div v-if="activeSection === 'profile'" class="space-y-5">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <h2 class="mb-5 font-semibold text-gray-900">
                            Thông tin cá nhân
                        </h2>

                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >Họ và tên</label
                                >
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-gray-700"
                                >
                                    Số điện thoại
                                    <span
                                        class="text-xs font-normal text-gray-400"
                                        >(không thể thay đổi)</span
                                    >
                                </label>
                                <input
                                    :value="form.phone"
                                    type="tel"
                                    disabled
                                    class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >Email</label
                                >
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="email@example.com"
                                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <div
                            v-if="successMsg"
                            class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                        >
                            ✓ {{ successMsg }}
                        </div>
                        <div
                            v-if="errorMsg"
                            class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                        >
                            {{ errorMsg }}
                        </div>

                        <button
                            @click="saveProfile"
                            :disabled="saveLoading"
                            class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60"
                        >
                            <div
                                v-if="saveLoading"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            <span>{{
                                saveLoading ? 'Đang lưu...' : 'Lưu thay đổi'
                            }}</span>
                        </button>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-4">
                        <div
                            v-for="stat in [
                                {
                                    label: 'Tổng chuyến đi',
                                    value: stats.total_trips,
                                    icon: '🚌',
                                },
                                {
                                    label: 'Điểm tích lũy',
                                    value: stats.total_points,
                                    icon: '⭐',
                                },
                                {
                                    label: 'Voucher còn',
                                    value: stats.vouchers,
                                    icon: '🏷️',
                                },
                            ]"
                            :key="stat.label"
                            class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm"
                        >
                            <div class="mb-1 text-2xl">{{ stat.icon }}</div>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ stat.value }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Password section -->
                <div
                    v-else-if="activeSection === 'password'"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-5 font-semibold text-gray-900">
                        Đổi mật khẩu
                    </h2>
                    <div class="max-w-md space-y-4">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Mật khẩu hiện tại</label
                            >
                            <input
                                v-model="passwordForm.old_password"
                                type="password"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Mật khẩu mới</label
                            >
                            <input
                                v-model="passwordForm.new_password"
                                type="password"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Xác nhận mật khẩu mới</label
                            >
                            <input
                                v-model="passwordForm.confirm"
                                type="password"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                        </div>
                        <button
                            @click="changePassword"
                            :disabled="saveLoading"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{
                                saveLoading
                                    ? 'Đang xử lý...'
                                    : 'Cập nhật mật khẩu'
                            }}
                        </button>
                    </div>
                </div>

                <!-- Wallet section -->
                <div v-else-if="activeSection === 'wallet'" class="space-y-5">
                    <div
                        class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white"
                    >
                        <p class="mb-1 text-sm text-blue-100">Ví XeGhep</p>
                        <p class="mb-4 text-3xl font-bold">
                            {{ wallet ? fmt(wallet.balance) : '—' }}
                        </p>
                        <div class="flex gap-3">
                            <button
                                class="rounded-lg bg-white px-5 py-2 text-sm font-semibold text-blue-700 transition-colors hover:bg-blue-50"
                            >
                                Nạp tiền
                            </button>
                            <button
                                class="rounded-lg border border-white/50 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-white/10"
                            >
                                Lịch sử
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="!wallet"
                        class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-400"
                    >
                        Chưa có thông tin ví
                    </div>
                </div>

                <!-- Vouchers section -->
                <div
                    v-else-if="activeSection === 'vouchers'"
                    class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm"
                >
                    <div class="mb-3 text-4xl">🏷️</div>
                    <p class="mb-2 font-medium text-gray-700">
                        Chưa có mã giảm giá
                    </p>
                    <p class="text-sm text-gray-400">
                        Theo dõi các chương trình ưu đãi từ XeGhep.vn
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
