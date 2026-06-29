<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { driverApi } from '@/api/driver.api';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const auth = useDriverAuthStore();

const profile = ref<any>(null);
const reviews = ref<any[]>([]);
const isLoading = ref(true);
const saveLoading = ref(false);
const pwLoading = ref(false);
const saveMsg = ref('');
const saveError = ref('');
const pwMsg = ref('');
const pwError = ref('');

const form = ref({
    full_name: auth.user?.full_name ?? '',
    email: auth.user?.email ?? '',
    birth_date: '',
});

const pwForm = ref({
    old_password: '',
    new_password: '',
    confirm_password: '',
});

function docStatus(doc: { status?: string; expires_at?: string }) {
    const status = doc.status ?? 'verified';
    if (status === 'expired')
        return {
            icon: '❌',
            cls: 'text-red-600 bg-red-50',
            label: 'Hết hạn',
            action: 'Cập nhật',
            actionCls: 'text-red-600',
        };
    if (doc.expires_at) {
        const daysLeft = Math.floor(
            (new Date(doc.expires_at).getTime() - Date.now()) / 86400000,
        );
        if (daysLeft <= 30)
            return {
                icon: '⚠️',
                cls: 'text-amber-600 bg-amber-50',
                label: `Sắp hết hạn (${daysLeft} ngày)`,
                action: 'Cập nhật',
                actionCls: 'text-amber-600',
            };
    }
    if (status === 'pending')
        return {
            icon: '⏳',
            cls: 'text-yellow-600 bg-yellow-50',
            label: 'Đang chờ duyệt',
            action: 'Xem ảnh',
            actionCls: 'text-gray-600',
        };
    return {
        icon: '✅',
        cls: 'text-green-700 bg-green-50',
        label: 'Đã xác minh',
        action: 'Xem ảnh',
        actionCls: 'text-gray-500',
    };
}

const docs = computed(() => [
    {
        label: 'CMND / CCCD',
        status: profile.value?.id_card_status ?? 'verified',
        expires_at: undefined,
    },
    {
        label: 'Giấy phép lái xe',
        status: auth.driver?.license_expiry ? 'verified' : 'verified',
        expires_at: auth.driver?.license_expiry,
    },
    {
        label: 'Đăng kiểm xe',
        status: profile.value?.registration_status ?? 'verified',
        expires_at: profile.value?.registration_expiry,
    },
    {
        label: 'Bảo hiểm xe',
        status: 'verified' as string,
        expires_at: profile.value?.insurance_expiry,
    },
]);

const stats = computed(() => [
    { label: 'Tổng chuyến đi', value: auth.user?.total_trips ?? 0 },
    { label: 'Tháng này', value: profile.value?.month_trips ?? 0 },
    {
        label: 'Tỷ lệ hoàn thành',
        value: (profile.value?.completion_rate ?? 98) + '%',
    },
]);

async function saveProfile() {
    saveLoading.value = true;
    saveMsg.value = '';
    saveError.value = '';
    
    const res = await driverApi.updateProfile({
        full_name: form.value.full_name,
        email: form.value.email,
        birth_date: form.value.birth_date || null,
    });
    
    saveLoading.value = false;
    
    if (res.error) {
        saveError.value = res.error;
        toast.error(res.error);
    } else {
        // Sync auth store + persist to localStorage
        const updated = {
            ...auth.user!,
            full_name: form.value.full_name,
            email: form.value.email,
            birth_date: form.value.birth_date || null,
        } as any;
        auth.user = updated;
        localStorage.setItem('driver_user', JSON.stringify(updated));
        
        saveMsg.value = 'Cập nhật thông tin thành công!';
        toast.success('Cập nhật thông tin thành công!');
        setTimeout(() => {
            saveMsg.value = '';
        }, 3000);
    }
}

async function updatePassword() {
    pwMsg.value = '';
    pwError.value = '';
    if (pwForm.value.new_password !== pwForm.value.confirm_password) {
        pwError.value = 'Mật khẩu xác nhận không khớp';
        toast.error('Mật khẩu xác nhận không khớp');
        return;
    }
    if (pwForm.value.new_password.length < 8) {
        pwError.value = 'Mật khẩu phải có ít nhất 8 ký tự';
        toast.error('Mật khẩu phải có ít nhất 8 ký tự');
        return;
    }
    
    pwLoading.value = true;
    const res = await driverApi.changePassword({
        old_password: pwForm.value.old_password,
        new_password: pwForm.value.new_password,
        new_password_confirmation: pwForm.value.confirm_password,
    });
    pwLoading.value = false;
    
    if (res.error) {
        pwError.value = res.error;
        toast.error(res.error);
    } else {
        pwForm.value = { old_password: '', new_password: '', confirm_password: '' };
        pwMsg.value = 'Cập nhật mật khẩu thành công!';
        toast.success('Cập nhật mật khẩu thành công!');
        setTimeout(() => {
            pwMsg.value = '';
        }, 3000);
    }
}

onMounted(async () => {
    isLoading.value = true;
    const [meRes] = await Promise.all([driverApi.me()]);
    isLoading.value = false;
    if (meRes.data) {
        profile.value = meRes.data;
        const u = meRes.data.user ?? meRes.data;
        if (u) {
            form.value.full_name = u.full_name ?? auth.user?.full_name ?? '';
            form.value.email = u.email ?? auth.user?.email ?? '';
            form.value.birth_date = u.birth_date ?? '';
            // Sync auth store with latest server data
            auth.user = { ...auth.user!, ...u } as any;
            localStorage.setItem('driver_user', JSON.stringify(auth.user));
        }
    }
    reviews.value = profile.value?.recent_reviews ?? [
        {
            id: 1,
            customer_name: 'Nguyễn Thị A',
            rating: 5,
            comment: 'Tài xế rất thân thiện, đúng giờ!',
            date: '2024-06-10',
        },
        {
            id: 2,
            customer_name: 'Trần Văn B',
            rating: 4,
            comment: 'Xe sạch, đi êm ái.',
            date: '2024-06-08',
        },
        {
            id: 3,
            customer_name: 'Lê Thị C',
            rating: 5,
            comment: 'Tuyệt vời, sẽ đặt lại lần sau.',
            date: '2024-06-05',
        },
    ];
});
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <!-- Page title -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Hồ sơ tài xế</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Quản lý thông tin cá nhân và giấy tờ
            </p>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid grid-cols-[35%_1fr] gap-6">
            <div class="space-y-4">
                <div class="h-64 animate-pulse rounded-xl bg-gray-200" />
                <div class="h-32 animate-pulse rounded-xl bg-gray-200" />
                <div class="h-48 animate-pulse rounded-xl bg-gray-200" />
            </div>
            <div class="space-y-4">
                <div class="h-48 animate-pulse rounded-xl bg-gray-200" />
                <div class="h-64 animate-pulse rounded-xl bg-gray-200" />
            </div>
        </div>

        <div v-else class="grid grid-cols-[35%_1fr] items-start gap-6">
            <!-- ─── LEFT column ───────────────────────────────────── -->
            <div class="space-y-4">
                <!-- Profile card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 text-center shadow-sm"
                >
                    <!-- Avatar with upload overlay -->
                    <div class="relative mx-auto mb-3 h-24 w-24">
                        <div
                            class="flex h-24 w-24 items-center justify-center rounded-full bg-green-100 text-4xl font-black text-green-700"
                        >
                            {{ auth.user?.full_name?.charAt(0) ?? 'T' }}
                        </div>
                        <button
                            class="absolute right-0 bottom-0 flex h-8 w-8 items-center justify-center rounded-full bg-green-600 shadow-md transition-colors hover:bg-green-700"
                        >
                            <svg
                                class="h-4 w-4 text-white"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </button>
                    </div>

                    <h2 class="text-lg font-black text-gray-900">
                        {{ auth.user?.full_name }}
                    </h2>

                    <!-- Verified badge -->
                    <span
                        v-if="auth.user?.is_verified"
                        class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Đã xác minh
                    </span>

                    <!-- Rating -->
                    <div class="mt-3 flex items-center justify-center gap-1.5">
                        <div class="flex gap-0.5">
                            <svg
                                v-for="i in 5"
                                :key="i"
                                :class="[
                                    'h-4 w-4',
                                    i <= Math.round(auth.user?.rating_avg ?? 5)
                                        ? 'fill-yellow-400 text-yellow-400'
                                        : 'fill-gray-200 text-gray-200',
                                ]"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                />
                            </svg>
                        </div>
                        <span class="text-base font-black text-gray-800">{{
                            auth.user?.rating_avg?.toFixed(1) ?? '4.8'
                        }}</span>
                        <span class="text-sm text-gray-400"
                            >· {{ profile?.review_count ?? 0 }} đánh giá</span
                        >
                    </div>

                    <!-- Stats -->
                    <div
                        class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4"
                    >
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="text-center"
                        >
                            <p class="text-lg font-black text-gray-900">
                                {{ stat.value }}
                            </p>
                            <p class="text-xs leading-tight text-gray-400">
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Vehicle info card -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 font-semibold text-gray-900"
                    >
                        🚐 Phương tiện của tôi
                    </h3>
                    <!-- Đã được nhà xe gán xe mặc định -->
                    <div v-if="profile?.vehicle" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Biển số</span>
                            <span class="font-mono font-black text-gray-900">{{
                                profile.vehicle.plate_number
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Hãng / Model</span>
                            <span class="font-semibold text-gray-800"
                                >{{ profile.vehicle.brand }}
                                {{ profile.vehicle.model }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Năm / Màu</span>
                            <span class="font-semibold text-gray-800"
                                >{{ profile.vehicle.year }} ·
                                {{ profile.vehicle.color }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Số chỗ</span>
                            <span class="font-semibold text-gray-800"
                                >{{ profile.vehicle.seat_count }} chỗ</span
                            >
                        </div>
                    </div>
                    <!-- Chưa được gán xe mặc định -->
                    <div v-else class="py-4 text-center">
                        <p class="text-sm text-gray-400">
                            Nhà xe chưa gán xe mặc định cho bạn.
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Xe của từng chuyến sẽ hiển thị trong chi tiết
                            chuyến.
                        </p>
                    </div>
                </div>

                <!-- Recent reviews -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Đánh giá gần đây
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="rev in reviews"
                            :key="rev.id"
                            class="px-4 py-3"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <span
                                    class="text-sm font-medium text-gray-800"
                                    >{{ rev.customer_name }}</span
                                >
                                <div class="flex gap-0.5">
                                    <svg
                                        v-for="i in 5"
                                        :key="i"
                                        :class="[
                                            'h-3 w-3',
                                            i <= rev.rating
                                                ? 'fill-yellow-400 text-yellow-400'
                                                : 'fill-gray-200 text-gray-200',
                                        ]"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs leading-relaxed text-gray-500">
                                {{ rev.comment }}
                            </p>
                            <p class="mt-1 text-xs text-gray-300">
                                {{
                                    new Date(rev.date).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── RIGHT column ──────────────────────────────────── -->
            <div class="space-y-5">
                <!-- Personal info form -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Thông tin cá nhân
                    </h3>

                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Họ và tên</label
                            >
                            <input
                                v-model="form.full_name"
                                type="text"
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Số điện thoại
                                <span class="text-xs font-normal text-gray-400"
                                    >(không đổi được)</span
                                >
                            </label>
                            <input
                                :value="auth.user?.phone"
                                type="tel"
                                disabled
                                class="h-11 w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3.5 text-sm text-gray-400"
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
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Ngày sinh</label
                            >
                            <input
                                v-model="form.birth_date"
                                type="date"
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div
                        v-if="saveMsg"
                        class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                    >
                        ✓ {{ saveMsg }}
                    </div>
                    <div
                        v-if="saveError"
                        class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                    >
                        {{ saveError }}
                    </div>

                    <button
                        @click="saveProfile"
                        :disabled="saveLoading"
                        class="flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:opacity-60"
                    >
                        <div
                            v-if="saveLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        {{ saveLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </button>
                </div>

                <!-- Documents table -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">
                            Giấy tờ & Tài liệu
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Loại giấy tờ
                                    </th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Trạng thái
                                    </th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Ngày hết hạn
                                    </th>
                                    <th
                                        class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Hành động
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="doc in docs"
                                    :key="doc.label"
                                    class="transition-colors hover:bg-gray-50"
                                >
                                    <td
                                        class="px-5 py-3.5 font-medium text-gray-800"
                                    >
                                        {{ doc.label }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                                docStatus(doc).cls,
                                            ]"
                                        >
                                            {{ docStatus(doc).icon }}
                                            {{ docStatus(doc).label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-500">
                                        {{
                                            doc.expires_at
                                                ? new Date(
                                                      doc.expires_at,
                                                  ).toLocaleDateString('vi-VN')
                                                : '—'
                                        }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <button
                                            :class="[
                                                'text-xs font-semibold transition-colors hover:underline',
                                                docStatus(doc).actionCls,
                                            ]"
                                        >
                                            {{ docStatus(doc).action }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Change password -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Đổi mật khẩu
                    </h3>
                    <div class="mb-4 grid grid-cols-3 gap-4">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Mật khẩu hiện tại</label
                            >
                            <input
                                v-model="pwForm.old_password"
                                type="password"
                                placeholder="••••••••"
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Mật khẩu mới</label
                            >
                            <input
                                v-model="pwForm.new_password"
                                type="password"
                                placeholder="••••••••"
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Xác nhận</label
                            >
                            <input
                                v-model="pwForm.confirm_password"
                                type="password"
                                placeholder="••••••••"
                                class="h-11 w-full rounded-lg border border-gray-300 px-3.5 text-sm transition-colors focus:ring-2 focus:ring-green-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div
                        v-if="pwMsg"
                        class="mb-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                    >
                        ✓ {{ pwMsg }}
                    </div>
                    <div
                        v-if="pwError"
                        class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600"
                    >
                        {{ pwError }}
                    </div>

                    <button
                        @click="updatePassword"
                        :disabled="pwLoading"
                        class="flex items-center gap-2 rounded-lg bg-gray-800 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-gray-900 disabled:opacity-60"
                    >
                        <div
                            v-if="pwLoading"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                        />
                        {{
                            pwLoading ? 'Đang cập nhật...' : 'Cập nhật mật khẩu'
                        }}
                    </button>
                </div>

                <!-- Support -->
                <div
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-xl"
                        >
                            📞
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                Liên hệ hỗ trợ tài xế
                            </p>
                            <p class="text-sm font-semibold text-green-600">
                                1800-9999 (miễn phí 24/7)
                            </p>
                        </div>
                    </div>
                    <a
                        href="tel:18009999"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700"
                    >
                        Gọi ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
