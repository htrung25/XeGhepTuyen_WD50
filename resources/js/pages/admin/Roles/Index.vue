<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { adminApi } from '@/api/admin.api';
import { useCan } from '@/composables/useCan';
import { toast } from 'vue-sonner';

interface Role {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    permissions: string[];
    is_super: boolean;
    is_system: boolean;
    users_count?: number;
}

interface PermissionItem {
    key: string;
    label: string;
}
interface PermissionGroup {
    module: string;
    permissions: PermissionItem[];
}

const { can } = useCan();
const canManage = can('admin_roles.manage');

const roles = ref<Role[]>([]);
const catalog = ref<PermissionGroup[]>([]);
const loading = ref(true);
const errorMsg = ref('');

// Modal tạo/sửa
const showModal = ref(false);
const editing = ref<Role | null>(null);
const form = ref<{ name: string; description: string; permissions: string[] }>({
    name: '',
    description: '',
    permissions: [],
});
const saving = ref(false);

async function fetchAll() {
    loading.value = true;
    errorMsg.value = '';
    const [rolesRes, catRes] = await Promise.all([
        adminApi.getRoles(),
        adminApi.getPermissionCatalog(),
    ]);
    loading.value = false;
    if (rolesRes.error) {
        errorMsg.value = rolesRes.error;
        return;
    }
    roles.value = rolesRes.data ?? [];
    catalog.value = catRes.data ?? [];
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', description: '', permissions: [] };
    showModal.value = true;
}

function openEdit(role: Role) {
    editing.value = role;
    form.value = {
        name: role.name,
        description: role.description ?? '',
        permissions: [...role.permissions],
    };
    showModal.value = true;
}

function togglePermission(key: string) {
    const i = form.value.permissions.indexOf(key);
    if (i >= 0) form.value.permissions.splice(i, 1);
    else form.value.permissions.push(key);
}

function isModuleAllChecked(group: PermissionGroup): boolean {
    return group.permissions.every((p) => form.value.permissions.includes(p.key));
}

function toggleModule(group: PermissionGroup) {
    const allChecked = isModuleAllChecked(group);
    for (const p of group.permissions) {
        const i = form.value.permissions.indexOf(p.key);
        if (allChecked && i >= 0) form.value.permissions.splice(i, 1);
        if (!allChecked && i < 0) form.value.permissions.push(p.key);
    }
}

// Vai trò hệ thống: không cho sửa quyền
const lockPermissions = () => !!editing.value?.is_system;

async function save() {
    if (!form.value.name.trim()) {
        toast.error('Vui lòng nhập tên vai trò');
        return;
    }
    saving.value = true;
    const payload: Record<string, unknown> = {
        name: form.value.name.trim(),
        description: form.value.description.trim() || null,
    };
    if (!lockPermissions()) payload.permissions = form.value.permissions;

    const res = editing.value
        ? await adminApi.updateRole(editing.value.id, payload)
        : await adminApi.createRole(payload);
    saving.value = false;

    if (res.error) {
        toast.error(res.error);
        return;
    }
    toast.success(editing.value ? 'Đã cập nhật vai trò' : 'Đã tạo vai trò');
    showModal.value = false;
    fetchAll();
}

async function remove(role: Role) {
    if (!confirm(`Xóa vai trò "${role.name}"?`)) return;
    const { error } = await adminApi.deleteRole(role.id);
    if (error) {
        toast.error(error);
        return;
    }
    toast.success('Đã xóa vai trò');
    fetchAll();
}

onMounted(fetchAll);
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Phân quyền — Vai trò</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Tạo vai trò và gán quyền cho nhân viên admin.
                </p>
            </div>
            <button
                v-if="canManage"
                @click="openCreate"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
            >
                + Tạo vai trò
            </button>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="i in 4" :key="i" class="h-20 animate-pulse rounded-xl border border-slate-200 bg-white" />
        </div>

        <div v-else-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-700">
            {{ errorMsg }}
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="role in roles"
                :key="role.id"
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-800">{{ role.name }}</h3>
                        <p class="text-xs text-slate-400">{{ role.description || '—' }}</p>
                    </div>
                    <span
                        v-if="role.is_super"
                        class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700"
                    >Super</span>
                    <span
                        v-else-if="role.is_system"
                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600"
                    >Hệ thống</span>
                </div>
                <div class="mb-4 flex flex-wrap gap-3 text-xs text-slate-500">
                    <span>{{ role.is_super ? 'Toàn quyền' : role.permissions.length + ' quyền' }}</span>
                    <span>· {{ role.users_count ?? 0 }} nhân viên</span>
                </div>
                <div v-if="canManage" class="mt-auto flex gap-2">
                    <button
                        @click="openEdit(role)"
                        class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        {{ role.is_system ? 'Xem' : 'Sửa' }}
                    </button>
                    <button
                        v-if="!role.is_system"
                        @click="remove(role)"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                    >
                        Xóa
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal tạo/sửa -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            >
                <div class="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 p-5">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ editing ? 'Sửa vai trò' : 'Tạo vai trò' }}
                        </h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="flex-1 space-y-5 overflow-y-auto p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Tên vai trò</label>
                                <input
                                    v-model="form.name"
                                    :disabled="lockPermissions()"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none disabled:bg-slate-50"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Mô tả</label>
                                <input
                                    v-model="form.description"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        <p v-if="lockPermissions()" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-700">
                            Vai trò hệ thống — không thể chỉnh sửa quyền.
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="group in catalog"
                                :key="group.module"
                                class="rounded-xl border border-slate-200 p-4"
                            >
                                <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <input
                                        type="checkbox"
                                        :checked="isModuleAllChecked(group)"
                                        :disabled="lockPermissions()"
                                        @change="toggleModule(group)"
                                    />
                                    {{ group.module }}
                                </label>
                                <div class="grid grid-cols-1 gap-2 pl-6 sm:grid-cols-2">
                                    <label
                                        v-for="p in group.permissions"
                                        :key="p.key"
                                        class="flex items-center gap-2 text-xs text-slate-600"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="form.permissions.includes(p.key)"
                                            :disabled="lockPermissions()"
                                            @change="togglePermission(p.key)"
                                        />
                                        {{ p.label }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-100 p-4">
                        <button
                            @click="showModal = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Đóng
                        </button>
                        <button
                            v-if="!lockPermissions()"
                            @click="save"
                            :disabled="saving"
                            class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ saving ? 'Đang lưu...' : 'Lưu' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
