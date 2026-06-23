<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { adminApi } from '@/api/admin.api';

interface User {
    id: string;
    full_name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    is_active: boolean;
    is_verified: boolean;
    created_at: string;
    bookings_count?: number;
}

const users = ref<User[]>([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
const statusFilter = ref<'all' | 'active' | 'banned'>('all');
const page = ref(1);
const totalPages = ref(1);
const totalCount = ref(0);

const banModal = ref(false);
const banTarget = ref<User | null>(null);
const banReason = ref('');
const banLoading = ref(false);

async function fetchUsers() {
    loading.value = true;
    error.value = '';
    const params: Record<string, unknown> = { page: page.value };
    if (search.value.trim()) params.search = search.value.trim();
    if (statusFilter.value !== 'all') params.status = statusFilter.value;
    const { data, error: err } = await adminApi.getUsers(params);
    loading.value = false;
    if (err) {
        error.value = err;
        return;
    }
    users.value = data.data ?? data;
    totalPages.value = data.meta?.last_page ?? 1;
    totalCount.value = data.meta?.total ?? users.value.length;
}

async function openBanModal(user: User) {
    banTarget.value = user;
    banReason.value = '';
    banModal.value = true;
}

async function confirmBan() {
    if (!banTarget.value || !banReason.value.trim()) return;
    banLoading.value = true;
    const { error: err } = await adminApi.banUser(banTarget.value.id, {
        reason: banReason.value,
    });
    banLoading.value = false;
    if (err) {
        alert(err);
        return;
    }
    banModal.value = false;
    fetchUsers();
}

function onSearch() {
    page.value = 1;
    fetchUsers();
}

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('vi-VN');
}

onMounted(fetchUsers);
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Quản lý người dùng
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ totalCount }} khách hàng trong hệ thống
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4"
        >
            <!-- Search -->
            <div class="relative min-w-[220px] flex-1">
                <svg
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Tìm theo tên, SĐT..."
                    class="w-full rounded-lg border border-gray-200 py-2 pr-4 pl-9 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                    @keyup.enter="onSearch"
                />
            </div>

            <!-- Status tabs -->
            <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
                <button
                    v-for="s in [
                        { v: 'all', l: 'Tất cả' },
                        { v: 'active', l: 'Hoạt động' },
                        { v: 'banned', l: 'Bị khoá' },
                    ]"
                    :key="s.v"
                    @click="
                        statusFilter = s.v as typeof statusFilter.value;
                        page = 1;
                        fetchUsers();
                    "
                    :class="[
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        statusFilter === s.v
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    {{ s.l }}
                </button>
            </div>

            <button
                @click="onSearch"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
            >
                Tìm kiếm
            </button>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white p-16 text-center"
        >
            <div
                class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-red-600 border-t-transparent"
            />
            <p class="text-sm text-gray-500">Đang tải...</p>
        </div>

        <!-- Error -->
        <div
            v-else-if="error"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center"
        >
            <p class="mb-4 text-sm text-red-500">{{ error }}</p>
            <button
                @click="fetchUsers"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white"
            >
                Thử lại
            </button>
        </div>

        <!-- Table -->
        <div
            v-else
            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Người dùng
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Liên hệ
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Xác thực
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Đặt vé
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Ngày tạo
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="users.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-sm text-gray-400"
                            >
                                Không có dữ liệu
                            </td>
                        </tr>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <!-- User info -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-red-100"
                                    >
                                        <img
                                            v-if="user.avatar_url"
                                            :src="user.avatar_url"
                                            class="h-9 w-9 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-sm font-semibold text-red-600"
                                        >
                                            {{
                                                user.full_name
                                                    ?.charAt(0)
                                                    ?.toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ user.full_name }}
                                        </p>
                                        <p
                                            class="font-mono text-xs text-gray-400"
                                        >
                                            {{ user.id.slice(0, 8) }}...
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <!-- Contact -->
                            <td class="px-4 py-3">
                                <p class="text-gray-800">{{ user.phone }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ user.email ?? '—' }}
                                </p>
                            </td>
                            <!-- Verified -->
                            <td class="px-4 py-3 text-center">
                                <span
                                    v-if="user.is_verified"
                                    class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs text-green-700"
                                >
                                    <svg
                                        class="h-3 w-3"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    Đã xác thực
                                </span>
                                <span v-else class="text-xs text-gray-400"
                                    >Chưa xác thực</span
                                >
                            </td>
                            <!-- Bookings count -->
                            <td
                                class="px-4 py-3 text-center font-medium text-gray-700"
                            >
                                {{ user.bookings_count ?? '—' }}
                            </td>
                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        user.is_active
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-red-50 text-red-700',
                                    ]"
                                >
                                    {{
                                        user.is_active ? 'Hoạt động' : 'Bị khoá'
                                    }}
                                </span>
                            </td>
                            <!-- Date -->
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ fmtDate(user.created_at) }}
                            </td>
                            <!-- Actions -->
                            <td class="px-4 py-3 text-center">
                                <button
                                    v-if="user.is_active"
                                    @click="openBanModal(user)"
                                    class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100"
                                >
                                    Khoá
                                </button>
                                <span v-else class="text-xs text-gray-400"
                                    >Đã khoá</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="totalPages > 1"
                class="flex items-center justify-between border-t border-gray-200 px-4 py-3"
            >
                <p class="text-xs text-gray-500">
                    Trang {{ page }} / {{ totalPages }}
                </p>
                <div class="flex gap-2">
                    <button
                        :disabled="page <= 1"
                        @click="
                            page--;
                            fetchUsers();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs transition-colors hover:bg-gray-50 disabled:opacity-40"
                    >
                        ← Trước
                    </button>
                    <button
                        :disabled="page >= totalPages"
                        @click="
                            page++;
                            fetchUsers();
                        "
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs transition-colors hover:bg-gray-50 disabled:opacity-40"
                    >
                        Sau →
                    </button>
                </div>
            </div>
        </div>

        <!-- Ban Modal -->
        <Teleport to="body">
            <div
                v-if="banModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="banModal = false"
                />
                <div
                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                        Khoá tài khoản
                    </h3>
                    <p class="mb-4 text-sm text-gray-500">
                        Khoá tài khoản
                        <strong>{{ banTarget?.full_name }}</strong> ({{
                            banTarget?.phone
                        }})?
                    </p>
                    <div class="mb-4">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Lý do khoá
                            <span class="text-red-500">*</span></label
                        >
                        <textarea
                            v-model="banReason"
                            rows="3"
                            placeholder="Nhập lý do..."
                            class="w-full resize-none rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="banModal = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Huỷ
                        </button>
                        <button
                            @click="confirmBan"
                            :disabled="!banReason.trim() || banLoading"
                            class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                        >
                            <svg
                                v-if="banLoading"
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
                            Xác nhận khoá
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
