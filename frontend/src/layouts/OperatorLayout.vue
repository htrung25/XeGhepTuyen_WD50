<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Toaster } from '@/components/ui/sonner';
import { useOperatorAuthStore } from '@/stores/operator.auth.store';

const route = useRoute();
const router = useRouter();
const auth = useOperatorAuthStore();

const sidebarOpen = ref(true);
const dropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const operatorName = computed(() => auth.user?.full_name ?? 'Nhà xe');
const operatorInitial = computed(() =>
    operatorName.value.charAt(0).toUpperCase(),
);

const navItems = [
    { label: 'Tổng quan', path: '/operator/dashboard', icon: 'home' },
    { label: 'Tuyến đường', path: '/operator/routes', icon: 'map' },
    { label: 'Xe & Tài xế', path: '/operator/vehicles', icon: 'truck' },
    { label: 'Lịch chạy', path: '/operator/trips', icon: 'calendar' },
    { label: 'Đặt chỗ', path: '/operator/bookings', icon: 'ticket' },
    { label: 'Doanh thu', path: '/operator/revenue', icon: 'chart' },
];

const isActive = (path: string) => route.path.startsWith(path);

const logout = async () => {
    auth.logout();
    router.push('/operator/login');
};

function handleClickOutside(event: MouseEvent) {
    const target = event.target as Node;
    if (dropdownRef.value && !dropdownRef.value.contains(target)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-[#F7F9FB]">
        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'w-60' : 'w-16'"
            class="flex flex-shrink-0 flex-col border-r border-slate-200 bg-white transition-all duration-200"
        >
            <!-- Logo -->
            <div
                class="flex h-16 items-center gap-3 border-b border-slate-200 px-4"
            >
                <div
                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-amber-500"
                >
                    <svg
                        class="h-5 w-5 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 17l-1.5-5H4a2 2 0 01-2-2V7a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2.5L16 17H8z"
                        />
                    </svg>
                </div>
                <span v-if="sidebarOpen" class="font-bold text-slate-800">
                    XeGhepTuyen<span class="text-amber-500">-Fgroup</span>
                </span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                <router-link
                    v-for="item in navItems"
                    :key="item.path"
                    :to="item.path"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        isActive(item.path)
                            ? 'border border-amber-200 bg-amber-50 text-amber-700'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                    "
                >
                    <!-- Home icon -->
                    <svg
                        v-if="item.icon === 'home'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        />
                    </svg>
                    <!-- Map icon -->
                    <svg
                        v-if="item.icon === 'map'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                        />
                    </svg>
                    <!-- Truck icon -->
                    <svg
                        v-if="item.icon === 'truck'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 17l-1.5-5H4a2 2 0 01-2-2V7a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2.5L16 17H8z"
                        />
                    </svg>
                    <!-- Calendar icon -->
                    <svg
                        v-if="item.icon === 'calendar'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <!-- Ticket icon -->
                    <svg
                        v-if="item.icon === 'ticket'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                        />
                    </svg>
                    <!-- Chart icon -->
                    <svg
                        v-if="item.icon === 'chart'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                    <!-- User icon -->
                    <svg
                        v-if="item.icon === 'user'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>

                    <span v-if="sidebarOpen">{{ item.label }}</span>
                </router-link>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top header -->
            <header
                class="flex h-16 flex-shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6"
            >
                <div class="flex items-center gap-4">
                    <button
                        class="text-slate-400 transition-colors hover:text-slate-600"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <h2 class="text-sm font-semibold text-slate-700">
                        {{ auth.operator?.company_name }}
                    </h2>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notification bell -->
                    <button
                        class="relative text-slate-400 transition-colors hover:text-slate-600"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <span
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                            >3</span
                        >
                    </button>

                    <!-- Avatar with Dropdown -->
                    <div class="relative" ref="dropdownRef">
                        <button
                            @click="dropdownOpen = !dropdownOpen"
                            class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 transition-colors hover:bg-slate-100 focus:ring-2 focus:ring-slate-100 focus:outline-none"
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-amber-500 text-sm font-semibold text-white"
                            >
                                <img
                                    v-if="auth.operator?.logo_url"
                                    :src="auth.operator.logo_url"
                                    alt="Logo"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ operatorInitial }}</span>
                            </div>
                            <span
                                class="hidden text-sm font-semibold text-slate-700 md:block"
                            >
                                {{ operatorName }}
                            </span>
                            <!-- Arrow icon -->
                            <svg
                                class="h-4 w-4 text-slate-500 transition-transform duration-200"
                                :class="dropdownOpen ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
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
                                v-if="dropdownOpen"
                                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg ring-1 ring-black/5 focus:outline-none"
                            >
                                <!-- Profile link -->
                                <router-link
                                    to="/operator/profile"
                                    @click="dropdownOpen = false"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-slate-50"
                                >
                                    <svg
                                        class="h-4 w-4 text-slate-500"
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
                                    Hồ sơ nhà xe
                                </router-link>
                                <!-- Divider -->
                                <div class="my-1 border-t border-slate-100" />
                                <!-- Logout link -->
                                <button
                                    @click="
                                        dropdownOpen = false;
                                        logout();
                                    "
                                    class="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50"
                                >
                                    <svg
                                        class="h-4 w-4 text-red-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    Đăng xuất
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-auto">
                <router-view />
                <Toaster />
            </main>
        </div>
    </div>
</template>
