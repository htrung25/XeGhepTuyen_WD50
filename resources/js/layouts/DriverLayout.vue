<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { driverApi } from '@/api/driver.api';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const route = useRoute();
const router = useRouter();
const auth = useDriverAuthStore();

const sidebarOpen = ref(true);

const navItems = [
    { label: 'Tổng quan', path: '/driver/dashboard', icon: 'home' },
    { label: 'Lịch chạy', path: '/driver/schedule', icon: 'calendar' },
    { label: 'Thu nhập', path: '/driver/earnings', icon: 'money' },
    { label: 'Hồ sơ', path: '/driver/profile', icon: 'user' },
];

const isActive = (path: string) => route.path.startsWith(path);

async function logout() {
    await driverApi.logout();
    auth.logout();
    router.push('/driver/login');
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- ─── Sidebar ──────────────────────────────────────────── -->
        <aside
            :class="sidebarOpen ? 'w-60' : 'w-16'"
            class="flex flex-shrink-0 flex-col bg-gray-900 transition-all duration-200"
        >
            <!-- Logo -->
            <div
                class="flex h-16 items-center gap-3 border-b border-gray-800 px-4"
            >
                <div
                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-600"
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
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                        />
                    </svg>
                </div>
                <span v-if="sidebarOpen" class="font-bold text-white">
                    XeGhep<span class="text-green-400">.vn</span>
                </span>
            </div>

            <!-- Nav -->
            <nav class="mt-2 flex-1 space-y-0.5 px-2 py-3">
                <router-link
                    v-for="item in navItems"
                    :key="item.path"
                    :to="item.path"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        isActive(item.path)
                            ? 'bg-green-700 text-white'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-gray-200'
                    "
                >
                    <!-- Home -->
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
                    <!-- Calendar -->
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
                    <!-- Money -->
                    <svg
                        v-if="item.icon === 'money'"
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <!-- User -->
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

            <!-- Bottom: user + logout -->
            <div class="space-y-1 border-t border-gray-800 p-3">
                <div
                    v-if="sidebarOpen"
                    class="flex items-center gap-2 px-3 py-2"
                >
                    <div
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-700 text-sm font-bold text-white"
                    >
                        {{ auth.user?.full_name?.charAt(0) ?? 'T' }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-white">
                            {{ auth.user?.full_name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">
                            {{ auth.driver?.operator?.company_name }}
                        </p>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:bg-gray-800 hover:text-red-400"
                >
                    <svg
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                    </svg>
                    <span v-if="sidebarOpen">Đăng xuất</span>
                </button>
            </div>
        </aside>

        <!-- ─── Main content ─────────────────────────────────────── -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top header -->
            <header
                class="flex h-14 flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-5 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="text-gray-400 transition-colors hover:text-gray-600"
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
                    <span class="text-sm text-gray-500">Cổng tài xế</span>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Stars -->
                    <div
                        v-if="auth.user?.rating_avg"
                        class="flex items-center gap-1 text-sm text-gray-600"
                    >
                        <svg
                            class="h-4 w-4 fill-yellow-400 text-yellow-400"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                        </svg>
                        <span class="font-medium">{{
                            auth.user.rating_avg.toFixed(1)
                        }}</span>
                    </div>
                    <!-- Avatar -->
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-sm font-semibold text-white"
                    >
                        {{ auth.user?.full_name?.charAt(0) ?? 'T' }}
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>
