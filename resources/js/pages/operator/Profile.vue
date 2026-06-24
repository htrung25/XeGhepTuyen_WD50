<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';
import { useOperatorAuthStore } from '@/stores/operator.auth.store';
import { toast } from 'vue-sonner';

const auth = useOperatorAuthStore();

const isLoading = ref(true);
const saveLoading = ref(false);
const pwLoading = ref(false);

const saveError = ref('');
const pwError = ref('');

const logoFile = ref<File | null>(null);
const logoPreview = ref<string>('');
const fileInput = ref<HTMLInputElement | null>(null);

const form = ref({
    full_name: '',
    email: '',
    company_name: '',
    bank_account: '',
    bank_name: '',
    bank_account_name: '',
    description: '',
});

const pwForm = ref({
    old_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const operatorData = ref<any>(null);

const statusConfig: Record<string, { label: string; class: string; icon: string }> = {
    verified: { label: 'Đã xác minh', class: 'bg-green-100 text-green-700 border-green-200', icon: '✓' },
    pending: { label: 'Chờ duyệt', class: 'bg-amber-100 text-amber-700 border-amber-200', icon: '⏳' },
    rejected: { label: 'Từ chối', class: 'bg-red-100 text-red-700 border-red-200', icon: '✗' },
};

const getStatusBadge = (status: string) => {
    return statusConfig[status] || { label: status?.toUpperCase(), class: 'bg-slate-100 text-slate-700 border-slate-200', icon: '•' };
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 2048 * 1024) {
            toast.error('Kích thước ảnh không được vượt quá 2MB');
            return;
        }
        logoFile.value = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const fetchProfile = async () => {
    isLoading.value = true;
    const res = await operatorApi.me();
    isLoading.value = false;
    if (res.data) {
        operatorData.value = res.data;
        form.value.full_name = res.data.full_name ?? '';
        form.value.email = res.data.email ?? '';
        
        const op = res.data.operator;
        if (op) {
            form.value.company_name = op.company_name ?? '';
            form.value.bank_account = op.bank_account ?? '';
            form.value.bank_name = op.bank_name ?? '';
            form.value.bank_account_name = op.bank_account_name ?? '';
            form.value.description = op.description ?? '';
        }
    } else {
        toast.error(res.error ?? 'Không thể tải thông tin hồ sơ');
    }
};

const saveProfile = async () => {
    saveLoading.value = true;
    saveError.value = '';
    
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        
        formData.append('full_name', form.value.full_name);
        formData.append('email', form.value.email || '');
        formData.append('company_name', form.value.company_name);
        formData.append('bank_account', form.value.bank_account || '');
        formData.append('bank_name', form.value.bank_name || '');
        formData.append('bank_account_name', form.value.bank_account_name || '');
        formData.append('description', form.value.description || '');
        
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        }
        
        const res = await operatorApi.updateProfile(formData);
        
        if (res.error) {
            saveError.value = res.error;
            toast.error(res.error);
        } else {
            toast.success('Cập nhật hồ sơ thành công!');
            logoFile.value = null;
            logoPreview.value = '';
            
            if (res.data) {
                const updated = res.data;
                // Sync auth store
                auth.user = {
                    ...auth.user!,
                    full_name: updated.full_name,
                    email: updated.email,
                };
                auth.operator = {
                    ...auth.operator!,
                    company_name: updated.company_name,
                    logo_url: updated.logo_url,
                    description: updated.description,
                };
                localStorage.setItem('operator_user', JSON.stringify(auth.user));
                localStorage.setItem('operator_info', JSON.stringify(auth.operator));
            }
            await fetchProfile();
        }
    } catch (e: any) {
        saveError.value = 'Đã xảy ra lỗi khi cập nhật hồ sơ.';
        toast.error('Đã xảy ra lỗi khi cập nhật hồ sơ.');
    } finally {
        saveLoading.value = false;
    }
};

const changePassword = async () => {
    pwError.value = '';
    
    if (!pwForm.value.old_password) {
        pwError.value = 'Vui lòng nhập mật khẩu hiện tại';
        return;
    }
    if (!pwForm.value.new_password) {
        pwError.value = 'Vui lòng nhập mật khẩu mới';
        return;
    }
    if (pwForm.value.new_password.length < 8) {
        pwError.value = 'Mật khẩu mới phải từ 8 ký tự trở lên';
        return;
    }
    if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
        pwError.value = 'Xác nhận mật khẩu mới không khớp';
        return;
    }
    
    pwLoading.value = true;
    const res = await operatorApi.changePassword({
        old_password: pwForm.value.old_password,
        new_password: pwForm.value.new_password,
        new_password_confirmation: pwForm.value.new_password_confirmation,
    });
    pwLoading.value = false;
    
    if (res.error) {
        pwError.value = res.error;
        toast.error(res.error);
    } else {
        toast.success('Đổi mật khẩu thành công!');
        pwForm.value = {
            old_password: '',
            new_password: '',
            new_password_confirmation: '',
        };
    }
};

onMounted(() => {
    fetchProfile();
});
</script>

<template>
    <div class="mx-auto max-w-5xl p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Hồ sơ nhà xe</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    Cập nhật thông tin công ty, chi tiết ngân hàng và quản lý tài khoản.
                </p>
            </div>
            <button
                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-50"
                @click="fetchProfile"
                :disabled="isLoading"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    :class="{ 'animate-spin': isLoading }"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
                Làm mới
            </button>
        </div>

        <!-- Skeleton Loading -->
        <div v-if="isLoading" class="grid grid-cols-1 gap-6 md:grid-cols-[320px_1fr]">
            <div class="space-y-6">
                <div class="h-72 animate-pulse rounded-xl border border-slate-200 bg-white" />
                <div class="h-48 animate-pulse rounded-xl border border-slate-200 bg-white" />
            </div>
            <div class="space-y-6">
                <div class="h-[480px] animate-pulse rounded-xl border border-slate-200 bg-white" />
                <div class="h-80 animate-pulse rounded-xl border border-slate-200 bg-white" />
            </div>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-[320px_1fr] items-start">
            <!-- LEFT COLUMN -->
            <div class="space-y-6">
                <!-- Company summary & logo card -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <!-- Logo Upload Container -->
                    <div class="relative mx-auto mb-4 h-28 w-28 group">
                        <div class="h-28 w-28 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center">
                            <!-- Preview uploaded image -->
                            <img
                                v-if="logoPreview || operatorData?.operator?.logo_url"
                                :src="logoPreview || operatorData?.operator?.logo_url"
                                alt="Company Logo"
                                class="h-full w-full object-cover"
                            />
                            <!-- Placeholder when no logo -->
                            <div v-else class="text-center p-2">
                                <span class="text-3xl font-black text-amber-500">
                                    {{ form.company_name?.charAt(0) || 'O' }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Overlay trigger -->
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="absolute -bottom-2 -right-2 flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-md transition-colors"
                            title="Tải logo lên"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <input
                            type="file"
                            ref="fileInput"
                            @change="onFileChange"
                            accept="image/*"
                            class="hidden"
                        />
                    </div>

                    <div class="text-center">
                        <h2 class="text-lg font-bold text-slate-800 line-clamp-1">
                            {{ form.company_name || 'Nhà xe chưa đặt tên' }}
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5 font-medium">ID: {{ operatorData?.operator?.id }}</p>

                        <!-- Status badge -->
                        <div class="mt-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-0.5 text-xs font-semibold"
                                :class="getStatusBadge(operatorData?.operator?.status).class"
                            >
                                <span class="font-bold">{{ getStatusBadge(operatorData?.operator?.status).icon }}</span>
                                {{ getStatusBadge(operatorData?.operator?.status).label }}
                            </span>
                        </div>
                    </div>

                    <!-- System configuration stats -->
                    <div class="mt-5 border-t border-slate-100 pt-4 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400">Chiết khấu hệ thống</span>
                            <span class="font-bold text-slate-700 bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded">
                                {{ operatorData?.operator?.commission_rate ?? 0 }}%
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400">Điện thoại liên hệ</span>
                            <span class="font-medium text-slate-700 font-mono">{{ operatorData?.phone }}</span>
                        </div>
                    </div>
                </div>

                <!-- Legal details card -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-slate-800 text-sm mb-3">Thông tin pháp lý</h3>
                    <p class="text-xs text-slate-400 mb-4">Các tài liệu pháp lý này chỉ được sửa đổi bởi Quản trị viên hệ thống.</p>
                    
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="block text-slate-400 mb-1">Mã số thuế</span>
                            <span class="font-mono font-semibold text-slate-800 block bg-slate-50 p-2 rounded border border-slate-100">
                                {{ operatorData?.operator?.tax_code || 'Chưa cập nhật' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-slate-400 mb-1">Giấy phép kinh doanh</span>
                            <span class="font-mono font-semibold text-slate-800 block bg-slate-50 p-2 rounded border border-slate-100">
                                {{ operatorData?.operator?.business_license || 'Chưa cập nhật' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6">
                <!-- Profile details form -->
                <form @submit.prevent="saveProfile" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Thông tin tài khoản &amp; Liên hệ</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Cập nhật thông tin định danh và tài khoản giao dịch ngân hàng để thực hiện thanh toán.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Full Name -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Họ và tên người liên hệ</label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                required
                                placeholder="Họ và tên"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Địa chỉ email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="email@domain.com"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Company Name -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Tên nhà xe / Tên doanh nghiệp</label>
                            <input
                                v-model="form.company_name"
                                type="text"
                                required
                                placeholder="Nhập tên thương hiệu nhà xe hiển thị cho khách hàng"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Description -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Mô tả giới thiệu nhà xe</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Viết giới thiệu ngắn về chất lượng dịch vụ của nhà xe..."
                                class="w-full rounded-lg border border-slate-200 p-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none resize-none"
                            />
                        </div>

                        <!-- Bank Name -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Tên ngân hàng nhận tiền</label>
                            <input
                                v-model="form.bank_name"
                                type="text"
                                placeholder="Ví dụ: Vietcombank, Techcombank..."
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Bank Account -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Số tài khoản ngân hàng</label>
                            <input
                                v-model="form.bank_account"
                                type="text"
                                placeholder="Nhập số tài khoản"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none font-mono"
                            />
                        </div>

                        <!-- Bank Account Name -->
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Tên chủ tài khoản ngân hàng</label>
                            <input
                                v-model="form.bank_account_name"
                                type="text"
                                placeholder="Ví dụ: NGUYEN VAN A"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none uppercase"
                            />
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div
                        v-if="saveError"
                        class="rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm text-red-600 flex items-start gap-2"
                    >
                        <span class="font-bold">✗</span>
                        <span>{{ saveError }}</span>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                        <button
                            type="submit"
                            :disabled="saveLoading"
                            class="flex items-center gap-2 rounded-lg bg-amber-500 hover:bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60 shadow-sm"
                        >
                            <div
                                v-if="saveLoading"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            {{ saveLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}
                        </button>
                    </div>
                </form>

                <!-- Change Password Form -->
                <form @submit.prevent="changePassword" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Đổi mật khẩu</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Cập nhật mật khẩu để bảo vệ an toàn cho tài khoản nhà xe của bạn.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Old Password -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Mật khẩu hiện tại</label>
                            <input
                                v-model="pwForm.old_password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- New Password -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Mật khẩu mới</label>
                            <input
                                v-model="pwForm.new_password"
                                type="password"
                                required
                                placeholder="Tối thiểu 8 ký tự"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-slate-700">Xác nhận mật khẩu mới</label>
                            <input
                                v-model="pwForm.new_password_confirmation"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="h-10 w-full rounded-lg border border-slate-200 px-3.5 text-sm transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div
                        v-if="pwError"
                        class="rounded-lg border border-red-200 bg-red-50 p-3.5 text-sm text-red-600 flex items-start gap-2"
                    >
                        <span class="font-bold">✗</span>
                        <span>{{ pwError }}</span>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                        <button
                            type="submit"
                            :disabled="pwLoading"
                            class="flex items-center gap-2 rounded-lg bg-slate-800 hover:bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60 shadow-sm"
                        >
                            <div
                                v-if="pwLoading"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            />
                            {{ pwLoading ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
