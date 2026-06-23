<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi } from '@/api/customer.api';
import { useCustomerAuthStore } from '@/stores/customer.auth.store';

const route = useRoute();
const router = useRouter();
const auth = useCustomerAuthStore();

const hideHeader = computed(() => !!route.meta.hideNav);
const mobileMenu = ref(false);

const navLinks = [
    { label: 'Trang chủ', path: '/home' },
    { label: 'Lịch trình', path: '/bookings' },
    { label: 'Về chúng tôi', path: '#about' },
];

function isActive(path: string) {
    if (path === '/home') return route.path === '/home' || route.path === '/';
    return route.path.startsWith(path);
}

async function logout() {
    await customerApi.logout();
    auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50">
        <!-- ─── Desktop Header ───────────────────────────────────── -->
        <header
            v-if="!hideHeader"
            class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6"
            >
                <!-- Logo -->
                <router-link
                    to="/home"
                    class="flex shrink-0 items-center gap-2"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600"
                    >
                        <svg
                            class="h-5 w-5 text-white"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"
                            />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-gray-900"
                        >XeGhep<span class="text-blue-600">.vn</span></span
                    >
                </router-link>

                <!-- Desktop Nav -->
                <nav class="hidden items-center gap-1 md:flex">
                    <router-link
                        v-for="link in navLinks"
                        :key="link.path"
                        :to="link.path"
                        :class="[
                            'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                            isActive(link.path)
                                ? 'bg-blue-50 text-blue-600'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        {{ link.label }}
                    </router-link>
                </nav>

                <!-- Auth buttons -->
                <div class="hidden items-center gap-3 md:flex">
                    <router-link
                        to="/partner"
                        class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                        Trở thành đối tác
                    </router-link>
                    <template v-if="auth.isAuthenticated">
                        <router-link
                            to="/bookings"
                            class="px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:text-blue-600"
                        >
                            Vé của tôi
                        </router-link>
                        <div
                            class="flex cursor-pointer items-center gap-2"
                            @click="router.push('/profile')"
                        >
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100"
                            >
                                <span class="text-sm font-bold text-blue-600">{{
                                    auth.user?.full_name?.charAt(0) ?? 'K'
                                }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{
                                auth.user?.full_name?.split(' ').pop()
                            }}</span>
                        </div>
                        <button
                            @click="logout"
                            class="px-2 py-1 text-sm text-gray-500 transition-colors hover:text-red-500"
                        >
                            Đăng xuất
                        </button>
                    </template>
                    <template v-else>
                        <router-link
                            to="/login"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Đăng nhập
                        </router-link>
                        <router-link
                            to="/register"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Đăng ký
                        </router-link>
                    </template>
                </div>

                <!-- Mobile menu button -->
                <button
                    @click="mobileMenu = !mobileMenu"
                    class="rounded-lg p-2 hover:bg-gray-100 md:hidden"
                >
                    <svg
                        class="h-5 w-5 text-gray-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
            </div>

            <!-- Mobile menu dropdown -->
            <div
                v-if="mobileMenu"
                class="space-y-1 border-t border-gray-100 bg-white px-4 py-3 md:hidden"
            >
                <router-link
                    v-for="link in navLinks"
                    :key="link.path"
                    :to="link.path"
                    @click="mobileMenu = false"
                    class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    {{ link.label }}
                </router-link>
                <router-link
                    to="/partner"
                    @click="mobileMenu = false"
                    class="block rounded-lg px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50"
                >
                    Trở thành đối tác
                </router-link>
                <div class="flex gap-2 border-t border-gray-100 pt-2">
                    <router-link
                        to="/login"
                        @click="mobileMenu = false"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-center text-sm font-medium"
                    >
                        Đăng nhập
                    </router-link>
                    <router-link
                        to="/register"
                        @click="mobileMenu = false"
                        class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white"
                    >
                        Đăng ký
                    </router-link>
                </div>
            </div>
        </header>

        <!-- ─── Page Content ─────────────────────────────────────── -->
        <main class="flex-1">
            <router-view />
        </main>

        <!-- ─── Footer ────────────────────────────────────────────── -->
        <footer
            v-if="!hideHeader"
            class="mt-auto bg-gray-900 py-12 text-gray-300"
        >
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <div class="mb-3 flex items-center gap-2">
                            <div
                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600"
                            >
                                <svg
                                    class="h-4 w-4 text-white"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"
                                    />
                                </svg>
                            </div>
                            <span class="text-lg font-bold text-white"
                                >XeGhep<span class="text-blue-400"
                                    >.vn</span
                                ></span
                            >
                        </div>
                        <p
                            class="max-w-xs text-sm leading-relaxed text-gray-400"
                        >
                            Nền tảng đặt xe ghép tuyến Hà Nội – Hải Phòng. Đón
                            tận nơi, theo dõi GPS real-time, thanh toán điện tử.
                        </p>
                        <p class="mt-3 text-sm font-semibold text-white">
                            Hotline:
                            <span class="text-blue-400">1900 xxxx</span>
                        </p>
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold text-white">
                            Dịch vụ
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Đặt vé xe</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Theo dõi chuyến</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Lịch sử vé</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Ví XeGhep</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold text-white">
                            Hỗ trợ
                        </h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Câu hỏi thường gặp</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Chính sách hủy vé</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Điều khoản dịch vụ</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#"
                                    class="transition-colors hover:text-white"
                                    >Liên hệ</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div
                    class="flex flex-col items-center justify-between gap-3 border-t border-gray-800 pt-6 md:flex-row"
                >
                    <p class="text-xs text-gray-500">
                        © 2024 XeGhep.vn. Tất cả quyền được bảo lưu.
                    </p>
                    <div class="flex gap-4 text-xs text-gray-500">
                        <a href="#" class="transition-colors hover:text-white"
                            >Bảo mật</a
                        >
                        <a href="#" class="transition-colors hover:text-white"
                            >Cookie</a
                        >
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
