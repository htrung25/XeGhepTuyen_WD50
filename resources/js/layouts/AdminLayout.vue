<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Toaster } from '@/components/ui/sonner';
import { adminApi } from '@/api/admin.api';
import {
    useAdminNotifications
    
} from '@/composables/useAdminNotifications';
import type {AdminNotification} from '@/composables/useAdminNotifications';
import { useCan } from '@/composables/useCan';
import { useAdminAuthStore } from '@/stores/admin.auth.store';

const route = useRoute();
const router = useRouter();
const authStore = useAdminAuthStore();
const { can } = useCan();

const sidebarCollapsed = ref(false);

// Thông báo admin (polling)
const {
    items: notifItems,
    unreadCount: notifCount,
    load: loadNotifs,
    markRead: markNotifRead,
    markAllRead: markAllNotifsRead,
} = useAdminNotifications();
const notifOpen = ref(false);
const notifRef = ref<HTMLElement | null>(null);

function toggleNotif() {
    notifOpen.value = !notifOpen.value;
    if (notifOpen.value) loadNotifs();
}

async function onNotifClick(n: AdminNotification) {
    await markNotifRead(n);
    notifOpen.value = false;
    if (n.data?.link) router.push(n.data.link);
}

// Số việc đang CHỜ XỬ LÝ theo mục sidebar (badge) — từ trạng thái thực tế của hệ
// thống (không phụ thuộc thông báo), nên đơn/tài xế/yêu cầu cũ vẫn hiện badge.
const pendingCounts = ref<Record<string, number>>({});
async function loadPendingCounts() {
    const { data } = await adminApi.getPendingCounts();
    pendingCounts.value = (data as Record<string, number>) ?? {};
}

const adminName = computed(() => authStore.user?.full_name ?? 'Admin');
const adminInitial = computed(() => adminName.value.charAt(0).toUpperCase());

const profileDropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

function handleClickOutside(event: MouseEvent) {
    const target = event.target as Node;
    if (dropdownRef.value && !dropdownRef.value.contains(target)) {
        profileDropdownOpen.value = false;
    }
    if (notifRef.value && !notifRef.value.contains(target)) {
        notifOpen.value = false;
    }
}

let pendingTimer: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
    document.addEventListener('click', handleClickOutside);
    loadPendingCounts();
    pendingTimer = setInterval(loadPendingCounts, 60000);
    // Làm mới quyền hiện tại (phòng khi vai trò bị admin khác thay đổi).
    const { data } = await adminApi.me();
    if (data) authStore.updateUser(data);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (pendingTimer) clearInterval(pendingTimer);
});

const navItems = [
    { path: '/admin/dashboard', label: 'Tổng quan', icon: 'chart', permission: 'dashboard.view' },
    { path: '/admin/operators', label: 'Nhà xe', icon: 'building', permission: 'operators.view' },
    { path: '/admin/drivers', label: 'Tài xế', icon: 'user-check', permission: 'drivers.view' },
    { path: '/admin/users', label: 'Người dùng', icon: 'users', permission: 'users.view' },
    { path: '/admin/bookings', label: 'Đặt vé', icon: 'ticket', permission: 'bookings.view' },
    { path: '/admin/trips', label: 'Chuyến đi', icon: 'map', permission: 'trips.view' },
    { path: '/admin/finance', label: 'Tài chính', icon: 'cash', permission: 'finance.view' },
    { path: '/admin/vouchers', label: 'Voucher', icon: 'tag', permission: 'vouchers.view' },
    { path: '/admin/audit-logs', label: 'Nhật ký hệ thống', icon: 'history', permission: 'audit_logs.view' },
    { path: '/admin/roles', label: 'Phân quyền', icon: 'shield', permission: 'admin_roles.view' },
    { path: '/admin/staff', label: 'Nhân viên', icon: 'user-cog', permission: 'admin_staff.view' },
];

function isActive(path: string) {
    return route.path.startsWith(path);
}

async function handleLogout() {
    await adminApi.logout();
    authStore.logout();
    router.push('/admin/login');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-slate-50">
        <!-- Sidebar -->
        <aside
            :class="[
                'flex shrink-0 flex-col bg-gray-900 transition-all duration-300',
                sidebarCollapsed ? 'w-16' : 'w-60',
            ]"
        >
            <!-- Logo -->
            <div
                class="flex items-center gap-3 border-b border-gray-800 px-4 py-5"
            >
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-600"
                >
                    <svg
                        class="h-5 w-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </div>
                <span
                    v-if="!sidebarCollapsed"
                    class="truncate text-base font-bold text-white"
                >
                    XeGhep Admin
                </span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-4">
                <template v-for="item in navItems" :key="item.path">
                    <router-link
                        v-if="!item.permission || can(item.permission)"
                        :to="item.path"
                        :class="[
                            'relative mx-2 mb-0.5 flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors',
                            isActive(item.path)
                                ? 'bg-red-600 text-white'
                                : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                        ]"
                    >
                        <!-- Chart icon -->
                        <svg
                            v-if="item.icon === 'chart'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <!-- Building icon -->
                        <svg
                            v-else-if="item.icon === 'building'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                        <!-- User-check icon -->
                        <svg
                            v-else-if="item.icon === 'user-check'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <!-- Users icon -->
                        <svg
                            v-else-if="item.icon === 'users'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <!-- Map icon -->
                        <svg
                            v-else-if="item.icon === 'map'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                            />
                        </svg>
                        <!-- Cash icon -->
                        <svg
                            v-else-if="item.icon === 'cash'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <!-- Tag icon -->
                        <svg
                            v-else-if="item.icon === 'tag'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                        <!-- Ticket icon -->
                        <svg
                            v-else-if="item.icon === 'ticket'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                            />
                        </svg>
                        <!-- History icon -->
                        <svg
                            v-else-if="item.icon === 'history'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <!-- Shield icon (Phân quyền) -->
                        <svg
                            v-else-if="item.icon === 'shield'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        <!-- User-cog icon (Nhân viên) -->
                        <svg
                            v-else-if="item.icon === 'user-cog'"
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        <span v-if="!sidebarCollapsed" class="truncate">{{
                            item.label
                        }}</span>
                        <!-- Badge số việc đang chờ xử lý (mở rộng) -->
                        <span
                            v-if="!sidebarCollapsed && pendingCounts[item.path]"
                            class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white"
                        >
                            {{
                                pendingCounts[item.path] > 9
                                    ? '9+'
                                    : pendingCounts[item.path]
                            }}
                        </span>
                        <!-- Chấm đỏ khi sidebar thu gọn -->
                        <span
                            v-else-if="sidebarCollapsed && pendingCounts[item.path]"
                            class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-gray-900"
                        />
                    </router-link>
                </template>
            </nav>

        </aside>

        <!-- Main content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Top header -->
            <header
                class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6 py-3.5"
            >
                <div class="flex items-center gap-3">
                    <button
                        @click="sidebarCollapsed = !sidebarCollapsed"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <span class="text-sm font-medium text-gray-700"
                        >Hệ thống quản trị XeGhep</span
                    >
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notification bell -->
                    <div class="relative" ref="notifRef">
                        <button
                            @click="toggleNotif"
                            class="relative rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                            <span
                                v-if="notifCount > 0"
                                class="absolute top-1 right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] leading-none text-white"
                            >
                                {{ notifCount > 9 ? '9+' : notifCount }}
                            </span>
                        </button>

                        <!-- Notification panel -->
                        <div
                            v-if="notifOpen"
                            class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                        >
                            <div
                                class="flex items-center justify-between border-b border-slate-100 px-4 py-3"
                            >
                                <span class="text-sm font-semibold text-gray-800"
                                    >Thông báo</span
                                >
                                <button
                                    v-if="notifCount > 0"
                                    @click="markAllNotifsRead"
                                    class="text-xs font-medium text-red-600 hover:text-red-700"
                                >
                                    Đọc tất cả
                                </button>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <p
                                    v-if="notifItems.length === 0"
                                    class="px-4 py-8 text-center text-sm text-gray-400"
                                >
                                    Chưa có thông báo
                                </p>
                                <button
                                    v-for="n in notifItems"
                                    :key="n.id"
                                    @click="onNotifClick(n)"
                                    class="flex w-full gap-3 border-b border-slate-50 px-4 py-3 text-left transition-colors hover:bg-slate-50"
                                    :class="!n.is_read ? 'bg-red-50/40' : ''"
                                >
                                    <span
                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                        :class="
                                            !n.is_read ? 'bg-red-500' : 'bg-transparent'
                                        "
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block text-sm font-semibold text-gray-800"
                                            >{{ n.title }}</span
                                        >
                                        <span
                                            class="block text-xs leading-snug text-gray-500"
                                            >{{ n.body }}</span
                                        >
                                        <span
                                            class="mt-0.5 block text-[10px] text-gray-400"
                                            >{{
                                                new Date(n.sent_at).toLocaleString(
                                                    'vi-VN',
                                                )
                                            }}</span
                                        >
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Avatar with Dropdown -->
                    <div class="relative" ref="dropdownRef">
                        <button
                            @click="profileDropdownOpen = !profileDropdownOpen"
                            class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-100 cursor-pointer"
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-sm font-semibold text-white overflow-hidden"
                            >
                                <img
                                    v-if="authStore.user?.avatar_url"
                                    :src="authStore.user.avatar_url"
                                    alt="Avatar"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ adminInitial }}</span>
                            </div>
                            <span class="hidden text-sm font-semibold text-gray-700 md:block">
                                {{ adminName }}
                            </span>
                            <!-- Arrow icon -->
                            <svg
                                class="h-4 w-4 text-gray-500 transition-transform duration-200"
                                :class="profileDropdownOpen ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <div
                                v-if="profileDropdownOpen"
                                class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg ring-1 ring-black/5 focus:outline-none z-50"
                            >
                                <!-- Profile link -->
                                <router-link
                                    to="/admin/profile"
                                    @click="profileDropdownOpen = false"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-slate-50"
                                >
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Trang cá nhân
                                </router-link>
                                <!-- Divider -->
                                <div class="my-1 border-t border-slate-100" />
                                <!-- Logout link -->
                                <button
                                    @click="
                                        profileDropdownOpen = false;
                                        handleLogout();
                                    "
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-600 transition-colors hover:bg-red-50 text-left cursor-pointer"
                                >
                                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Đăng xuất
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <router-view />
                <Toaster />
            </main>
        </div>
    </div>
</template>
