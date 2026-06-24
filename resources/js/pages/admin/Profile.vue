<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { adminApi } from '@/api/admin.api';
import { useAdminAuthStore } from '@/stores/admin.auth.store';
import { toast } from 'vue-sonner';

const authStore = useAdminAuthStore();

const isLoading = ref(true);
const saveLoading = ref(false);
const pwLoading = ref(false);

const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string>('');
const fileInput = ref<HTMLInputElement | null>(null);

const form = ref({
    full_name: '',
    email: '',
    phone: '',
});

const pwForm = ref({
    old_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const fetchProfile = async () => {
    isLoading.value = true;
    const res = await adminApi.me();
    isLoading.value = false;
    if (res.data) {
        form.value.full_name = res.data.full_name ?? '';
        form.value.email = res.data.email ?? '';
        form.value.phone = res.data.phone ?? '';
    } else {
        toast.error(res.error ?? 'Không thể tải thông tin hồ sơ');
    }
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
        avatarFile.value = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const saveProfile = async () => {
    saveLoading.value = true;
    
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('full_name', form.value.full_name);
        formData.append('email', form.value.email);
        formData.append('phone', form.value.phone);
        
        if (avatarFile.value) {
            formData.append('avatar', avatarFile.value);
        }
        
        const res = await adminApi.updateProfile(formData);
        
        if (res.error) {
            toast.error(res.error);
        } else {
            toast.success('Cập nhật hồ sơ thành công!');
            avatarFile.value = null;
            avatarPreview.value = '';
            
            if (res.data) {
                // Sync auth store
                authStore.updateUser({
                    full_name: res.data.full_name,
                    email: res.data.email,
                    phone: res.data.phone,
                    avatar_url: res.data.avatar_url,
                });
            }
            await fetchProfile();
        }
    } catch (e: any) {
        toast.error('Đã xảy ra lỗi khi cập nhật hồ sơ.');
    } finally {
        saveLoading.value = false;
    }
};

const changePassword = async () => {
    if (!pwForm.value.old_password) {
        toast.error('Vui lòng nhập mật khẩu hiện tại');
        return;
    }
    if (!pwForm.value.new_password || pwForm.value.new_password.length < 8) {
        toast.error('Mật khẩu mới tối thiểu phải có 8 ký tự');
        return;
    }
    if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
        toast.error('Xác nhận mật khẩu mới không khớp');
        return;
    }
    
    pwLoading.value = true;
    
    try {
        const res = await adminApi.changePassword({
            old_password: pwForm.value.old_password,
            new_password: pwForm.value.new_password,
            new_password_confirmation: pwForm.value.new_password_confirmation,
        });
        
        if (res.error) {
            toast.error(res.error);
        } else {
            toast.success('Thay đổi mật khẩu thành công!');
            pwForm.value = {
                old_password: '',
                new_password: '',
                new_password_confirmation: '',
            };
        }
    } catch (e: any) {
        toast.error('Đã xảy ra lỗi khi đổi mật khẩu.');
    } finally {
        pwLoading.value = false;
    }
};

onMounted(fetchProfile);
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6 pb-12">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Thông tin cá nhân Admin</h1>
        </div>

        <div v-if="isLoading" class="animate-pulse space-y-6">
            <div class="h-48 rounded-xl bg-white border border-slate-100 p-6" />
            <div class="h-64 rounded-xl bg-white border border-slate-100 p-6" />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Info Panel -->
            <div class="space-y-6 md:col-span-1">
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col items-center text-center">
                    <!-- Avatar Upload -->
                    <div class="relative group cursor-pointer" @click="triggerFileInput">
                        <div class="h-28 w-28 overflow-hidden rounded-full border-4 border-slate-100 bg-slate-50 flex items-center justify-center relative">
                            <img
                                v-if="avatarPreview || authStore.user?.avatar_url"
                                :src="avatarPreview || authStore.user?.avatar_url || undefined"
                                alt="Avatar"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="text-3xl font-extrabold text-red-600">
                                {{ authStore.user?.full_name?.charAt(0).toUpperCase() ?? 'A' }}
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-xs font-semibold text-white">Thay đổi</span>
                        </div>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileChange"
                    />

                    <h3 class="mt-4 font-bold text-gray-900 text-lg">{{ authStore.user?.full_name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ authStore.user?.email }}</p>
                    <span class="mt-3.5 inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 border border-red-100">
                        🛡️ Quản trị viên
                    </span>
                </div>
            </div>

            <!-- Forms Panel -->
            <div class="space-y-6 md:col-span-2">
                <!-- Profile Settings -->
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold text-gray-900 mb-5 pb-3 border-b border-slate-100">💻 Thông tin cơ bản</h2>
                    <form @submit.prevent="saveProfile" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Họ và tên</label>
                                <input
                                    v-model="form.full_name"
                                    type="text"
                                    required
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Số điện thoại</label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Email đăng nhập</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                            />
                        </div>

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="saveLoading"
                                class="rounded-lg bg-red-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60 flex items-center gap-2 cursor-pointer"
                            >
                                <span v-if="saveLoading">Đang lưu...</span>
                                <span v-else>Lưu thay đổi</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Changes -->
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold text-gray-900 mb-5 pb-3 border-b border-slate-100">🔒 Đổi mật khẩu</h2>
                    <form @submit.prevent="changePassword" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-gray-700">Mật khẩu hiện tại</label>
                            <input
                                v-model="pwForm.old_password"
                                type="password"
                                required
                                class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Mật khẩu mới</label>
                                <input
                                    v-model="pwForm.new_password"
                                    type="password"
                                    required
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Xác nhận mật khẩu mới</label>
                                <input
                                    v-model="pwForm.new_password_confirmation"
                                    type="password"
                                    required
                                    class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100 transition-shadow"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="pwLoading"
                                class="rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-black disabled:opacity-60 flex items-center gap-2 cursor-pointer"
                            >
                                <span v-if="pwLoading">Đang cập nhật...</span>
                                <span v-else>Cập nhật mật khẩu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
