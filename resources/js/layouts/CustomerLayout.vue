<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCustomerAuthStore } from '@/stores/customer.auth.store'
import { customerApi } from '@/api/customer.api'

const route  = useRoute()
const router = useRouter()
const auth   = useCustomerAuthStore()

const hideHeader = computed(() => !!route.meta.hideNav)
const mobileMenu = ref(false)

const navLinks = [
  { label: 'Trang chủ',   path: '/home' },
  { label: 'Lịch trình',  path: '/bookings' },
  { label: 'Về chúng tôi', path: '#about' },
]

function isActive(path: string) {
  if (path === '/home') return route.path === '/home' || route.path === '/'
  return route.path.startsWith(path)
}

async function logout() {
  await customerApi.logout()
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- ─── Desktop Header ───────────────────────────────────── -->
    <header v-if="!hideHeader"
      class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">

        <!-- Logo -->
        <router-link to="/home" class="flex items-center gap-2 shrink-0">
          <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"/>
            </svg>
          </div>
          <span class="font-bold text-gray-900 text-lg tracking-tight">XeGhep<span class="text-blue-600">.vn</span></span>
        </router-link>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-1">
          <router-link v-for="link in navLinks" :key="link.path" :to="link.path"
            :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors',
              isActive(link.path) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50']">
            {{ link.label }}
          </router-link>
        </nav>

        <!-- Auth buttons -->
        <div class="hidden md:flex items-center gap-3">
          <router-link to="/partner"
            class="flex items-center gap-1.5 px-3 py-2 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Trở thành đối tác
          </router-link>
          <template v-if="auth.isAuthenticated">
            <router-link to="/bookings"
              class="text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors px-3 py-2">
              Vé của tôi
            </router-link>
            <div class="flex items-center gap-2 cursor-pointer" @click="router.push('/profile')">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-sm font-bold text-blue-600">{{ auth.user?.full_name?.charAt(0) ?? 'K' }}</span>
              </div>
              <span class="text-sm font-medium text-gray-700">{{ auth.user?.full_name?.split(' ').pop() }}</span>
            </div>
            <button @click="logout"
              class="text-sm text-gray-500 hover:text-red-500 transition-colors px-2 py-1">
              Đăng xuất
            </button>
          </template>
          <template v-else>
            <router-link to="/login"
              class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              Đăng nhập
            </router-link>
            <router-link to="/register"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              Đăng ký
            </router-link>
          </template>
        </div>

        <!-- Mobile menu button -->
        <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>

      <!-- Mobile menu dropdown -->
      <div v-if="mobileMenu" class="md:hidden bg-white border-t border-gray-100 px-4 py-3 space-y-1">
        <router-link v-for="link in navLinks" :key="link.path" :to="link.path"
          @click="mobileMenu = false"
          class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
          {{ link.label }}
        </router-link>
        <router-link to="/partner" @click="mobileMenu = false"
          class="block px-3 py-2 rounded-lg text-sm font-semibold text-blue-600 hover:bg-blue-50">
          Trở thành đối tác
        </router-link>
        <div class="pt-2 border-t border-gray-100 flex gap-2">
          <router-link to="/login" @click="mobileMenu = false"
            class="flex-1 text-center px-3 py-2 text-sm font-medium border border-gray-300 rounded-lg">
            Đăng nhập
          </router-link>
          <router-link to="/register" @click="mobileMenu = false"
            class="flex-1 text-center px-3 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg">
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
    <footer v-if="!hideHeader" class="bg-gray-900 text-gray-300 py-12 mt-auto">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <div class="md:col-span-2">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z"/>
                </svg>
              </div>
              <span class="font-bold text-white text-lg">XeGhep<span class="text-blue-400">.vn</span></span>
            </div>
            <p class="text-sm text-gray-400 leading-relaxed max-w-xs">
              Nền tảng đặt xe ghép tuyến Hà Nội – Hải Phòng. Đón tận nơi, theo dõi GPS real-time, thanh toán điện tử.
            </p>
            <p class="mt-3 text-sm font-semibold text-white">Hotline: <span class="text-blue-400">1900 xxxx</span></p>
          </div>
          <div>
            <h4 class="text-white font-semibold text-sm mb-3">Dịch vụ</h4>
            <ul class="space-y-2 text-sm text-gray-400">
              <li><a href="#" class="hover:text-white transition-colors">Đặt vé xe</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Theo dõi chuyến</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Lịch sử vé</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Ví XeGhep</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold text-sm mb-3">Hỗ trợ</h4>
            <ul class="space-y-2 text-sm text-gray-400">
              <li><a href="#" class="hover:text-white transition-colors">Câu hỏi thường gặp</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Chính sách hủy vé</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Điều khoản dịch vụ</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Liên hệ</a></li>
            </ul>
          </div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row items-center justify-between gap-3">
          <p class="text-xs text-gray-500">© 2024 XeGhep.vn. Tất cả quyền được bảo lưu.</p>
          <div class="flex gap-4 text-xs text-gray-500">
            <a href="#" class="hover:text-white transition-colors">Bảo mật</a>
            <a href="#" class="hover:text-white transition-colors">Cookie</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
