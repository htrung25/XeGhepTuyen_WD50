<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { adminApi } from '@/api/admin.api';
import { useCan } from '@/composables/useCan';
import { useAdminAuthStore } from '@/stores/admin.auth.store';
import { toast } from 'vue-sonner';

interface RoleBrief {
    id: string;
    name: string;
    slug: string;
    is_super: boolean;
}
interface Staff {
    id: string;
    full_name: string;
    email: string;
    phone: string | null;
    is_active: boolean;
    admin_role: RoleBrief | null;
    last_login_at: string | null;
}

const { can } = useCan();
const auth = useAdminAuthStore();
const canManage = can('admin_staff.manage');

const staff = ref<Staff[]>([]);
const roles = ref<RoleBrief[]>([]);
const loading = ref(true);
const errorMsg = ref('');
const search = ref('');
const statusFilter = ref<'all' | 'active' | 'banned'>('all');

// Modal tạo/sửa
const showModal = ref(false);
const editing = ref<Staff | null>(null);
const form = ref({ full_name: '', email: '', phone: '', admin_role_id: '' });
const saving = ref(false);

// Modal hiển thị mật khẩu tạm
const credModal = ref(false);
const credPassword = ref('');
const credLabel = ref('');

async function fetchStaff() {
    loading.value = true;
    errorMsg.value = '';
    const params: Record<string, unknown> = {};
    if (search.value.trim()) params.search = search.value.trim();
    if (statusFilter.value !== 'all') params.status = statusFilter.value;

    const { data, error } = await adminApi.getAdminStaff(params);
    loading.value = false;
    if (error) {
        errorMsg.value = error;
        return;
    }
    staff.value = data ?? [];
}

async function fetchRoles() {
    const { data } = await adminApi.getRoles();
    roles.value = (data ?? []).filter((r: RoleBrief) => !r.is_super);
}

watchDebounced(search, fetchStaff, { debounce: 350 });

function openCreate() {
    editing.value = null;
    form.value = { full_name: '', email: '', phone: '', admin_role_id: '' };
    showModal.value = true;
}

function openEdit(s: Staff) {
    editing.value = s;
    form.value = {
        full_name: s.full_name,
        email: s.email,
        phone: s.phone ?? '',
        admin_role_id: s.admin_role?.id ?? '',
    };
    showModal.value = true;
}

async function save() {
    saving.value = true;
    if (editing.value) {
        const { error } = await adminApi.updateAdminStaff(editing.value.id, {
            full_name: form.value.full_name,
            email: form.value.email,
            phone: form.value.phone,
            admin_role_id: form.value.admin_role_id,
        });
        saving.value = false;
        if (error) return toast.error(error);
        toast.success('Đã cập nhật nhân viên');
        showModal.value = false;
        fetchStaff();
    } else {
        const { data, error } = await adminApi.createAdminStaff({
            full_name: form.value.full_name,
            email: form.value.email,
            phone: form.value.phone,
            admin_role_id: form.value.admin_role_id,
        });
        saving.value = false;
        if (error) return toast.error(error);
        showModal.value = false;
        fetchStaff();
        credLabel.value = `Mật khẩu đăng nhập cho ${form.value.email}`;
        credPassword.value = data?.temp_password ?? '';
        credModal.value = true;
    }
}

async function toggleBan(s: Staff) {
    const res = s.is_active
        ? await adminApi.banAdminStaff(s.id)
        : await adminApi.unbanAdminStaff(s.id);
    if (res.error) return toast.error(res.error);
    toast.success(s.is_active ? 'Đã khóa nhân viên' : 'Đã mở khóa nhân viên');
    fetchStaff();
}

async function resetPassword(s: Staff) {
    if (!confirm(`Đặt lại mật khẩu cho ${s.full_name}?`)) return;
    const { data, error } = await adminApi.resetAdminStaffPassword(s.id);
    if (error) return toast.error(error);
    credLabel.value = `Mật khẩu mới cho ${s.email}`;
    credPassword.value = data?.temp_password ?? '';
    credModal.value = true;
}

function fmt(d: string | null) {
    return d ? new Date(d).toLocaleString('vi-VN') : '—';
}

onMounted(() => {
    fetchStaff();
    if (canManage) fetchRoles();
});
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Nhân viên admin</h1>
                <p class="mt-0.5 text-sm text-gray-500">Quản lý tài khoản và vai trò nhân viên quản trị.</p>
            </div>
            <button
                v-if="canManage"
                @click="openCreate"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
            >
                + Thêm nhân viên
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Tìm theo tên, email, SĐT..."
                class="min-w-[220px] flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
            />
            <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
                <button
                    v-for="s in [
                        { v: 'all', l: 'Tất cả' },
                        { v: 'active', l: 'Hoạt động' },
                        { v: 'banned', l: 'Bị khóa' },
                    ]"
                    :key="s.v"
                    @click="statusFilter = s.v as typeof statusFilter; fetchStaff()"
                    :class="[
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        statusFilter === s.v ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500',
                    ]"
                >
                    {{ s.l }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="i in 4" :key="i" class="h-14 animate-pulse rounded-xl border border-slate-200 bg-white" />
        </div>
        <div v-else-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-700">{{ errorMsg }}</div>
        <div v-else-if="staff.length === 0" class="rounded-xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
            Chưa có nhân viên admin nào.
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3">Nhân viên</th>
                        <th class="px-5 py-3">Vai trò</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3">Đăng nhập gần nhất</th>
                        <th v-if="canManage" class="px-5 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <tr v-for="s in staff" :key="s.id" class="hover:bg-slate-50/50">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-800">{{ s.full_name }}</p>
                            <p class="text-xs text-slate-400">{{ s.email }} · {{ s.phone || '—' }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                {{ s.admin_role?.name ?? 'Chưa gán' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    s.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
                                ]"
                            >{{ s.is_active ? 'Hoạt động' : 'Bị khóa' }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-400">{{ fmt(s.last_login_at) }}</td>
                        <td v-if="canManage" class="px-5 py-3">
                            <div class="flex justify-end gap-2 text-xs font-semibold">
                                <button @click="openEdit(s)" class="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-600 hover:bg-slate-50">Sửa</button>
                                <button @click="resetPassword(s)" class="rounded-lg border border-blue-200 px-3 py-1.5 text-blue-600 hover:bg-blue-50">Đặt lại MK</button>
                                <button
                                    v-if="s.id !== auth.user?.id"
                                    @click="toggleBan(s)"
                                    :class="[
                                        'rounded-lg border px-3 py-1.5',
                                        s.is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50',
                                    ]"
                                >{{ s.is_active ? 'Khóa' : 'Mở khóa' }}</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal tạo/sửa -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 p-5">
                        <h3 class="text-base font-bold text-slate-900">{{ editing ? 'Sửa nhân viên' : 'Thêm nhân viên' }}</h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Họ tên</label>
                            <input v-model="form.full_name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                                <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Số điện thoại</label>
                                <input v-model="form.phone" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Vai trò</label>
                            <select v-model="form.admin_role_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none">
                                <option value="">— Chọn vai trò —</option>
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <p v-if="!editing" class="rounded-lg bg-blue-50 p-3 text-xs text-blue-700">
                            Hệ thống sẽ tạo mật khẩu tạm và hiển thị 1 lần sau khi tạo.
                        </p>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 p-4">
                        <button @click="showModal = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Đóng</button>
                        <button @click="save" :disabled="saving" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                            {{ saving ? 'Đang lưu...' : 'Lưu' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal mật khẩu tạm -->
        <Teleport to="body">
            <div v-if="credModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                    <h3 class="mb-2 text-base font-bold text-slate-900">Mật khẩu đăng nhập</h3>
                    <p class="mb-3 text-xs text-slate-500">{{ credLabel }}. Hãy sao chép và bàn giao — mật khẩu chỉ hiển thị một lần.</p>
                    <div class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-lg font-bold tracking-wider text-slate-800">
                        {{ credPassword }}
                    </div>
                    <button @click="credModal = false" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Đã lưu</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
