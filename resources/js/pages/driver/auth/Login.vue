<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { driverApi } from '@/api/driver.api'
import { useDriverAuthStore } from '@/stores/driver.auth.store'

const router = useRouter()
const route  = useRoute()
const auth   = useDriverAuthStore()

const phone    = ref('')
const password = ref('')
const showPw   = ref(false)
const loading  = ref(false)
const error    = ref('')

async function handleLogin() {
  error.value = ''
  if (!phone.value.trim()) { error.value = 'Vui lòng nhập số điện thoại'; return }
  if (!password.value)     { error.value = 'Vui lòng nhập mật khẩu'; return }
  if (!/^(0[3|5|7|8|9])[0-9]{8}$/.test(phone.value.trim())) {
    error.value = 'Số điện thoại không hợp lệ (10 số, bắt đầu 03/05/07/08/09)'
    return
  }
  loading.value = true
  const { data, error: err } = await driverApi.login({ phone: phone.value.trim(), password: password.value })
  loading.value = false
  if (err) { error.value = typeof err === 'string' ? err : 'Đăng nhập thất bại. Kiểm tra lại thông tin.'; return }
  auth.setAuth(data.token, data.user, data.driver)
  const redirect = route.query.redirect as string | undefined
  router.push(redirect ?? '/driver/dashboard')
}
</script>

<template>
  <div class="min-h-screen flex">

    <!-- ─── LEFT: Green gradient illustration panel ─────────── -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-green-800 via-green-700 to-green-600 flex-col items-center justify-center p-12 relative overflow-hidden">

      <!-- Decorative circles -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-32 translate-x-32" />
      <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/5 rounded-full translate-y-40 -translate-x-40" />

      <!-- Logo -->
      <div class="flex items-center gap-3 mb-12">
        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
          <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
        </div>
        <span class="text-white text-3xl font-black">XeGhep<span class="text-green-300">.vn</span></span>
      </div>

      <!-- Illustration — driver + van -->
      <div class="relative w-80 h-56 mb-10">
        <!-- Van body -->
        <div class="absolute bottom-0 left-4 right-4 h-28 bg-white/15 rounded-2xl border-2 border-white/20 backdrop-blur-sm">
          <!-- Windows -->
          <div class="absolute top-3 left-8 right-8 flex gap-3">
            <div class="flex-1 h-8 bg-white/25 rounded-lg" />
            <div class="flex-1 h-8 bg-white/25 rounded-lg" />
            <div class="flex-1 h-8 bg-white/25 rounded-lg" />
          </div>
          <!-- XeGhep label -->
          <div class="absolute bottom-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-green-500 rounded-full">
            <span class="text-white text-xs font-black tracking-widest">XeGhep</span>
          </div>
          <!-- Wheels -->
          <div class="absolute -bottom-4 left-8 w-8 h-8 bg-white/30 rounded-full border-2 border-white/40" />
          <div class="absolute -bottom-4 right-8 w-8 h-8 bg-white/30 rounded-full border-2 border-white/40" />
        </div>
        <!-- Driver figure -->
        <div class="absolute bottom-28 left-1/2 -translate-x-1/2">
          <div class="w-14 h-14 bg-white/20 rounded-full border-2 border-white/30 flex items-center justify-center">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
          </div>
        </div>
        <!-- Route dots -->
        <div class="absolute top-2 left-2 flex flex-col gap-2">
          <div class="w-3 h-3 bg-white rounded-full" />
          <div class="w-1 h-8 bg-white/40 rounded-full mx-1" />
          <div class="w-3 h-3 bg-green-300 rounded-full" />
        </div>
        <!-- Stars (rating) -->
        <div class="absolute top-4 right-2 flex gap-0.5">
          <svg v-for="i in 5" :key="i" class="w-4 h-4 text-yellow-300 fill-yellow-300" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
        </div>
      </div>

      <!-- Tagline -->
      <div class="text-center">
        <h2 class="text-white text-2xl font-bold mb-2">Nền tảng tài xế xe ghép tuyến</h2>
        <p class="text-green-200 text-base">Hà Nội ↔ Hải Phòng · Quản lý chuyến đi dễ dàng</p>
      </div>

      <!-- Stats row -->
      <div class="flex gap-8 mt-10">
        <div class="text-center">
          <p class="text-white text-2xl font-black">500+</p>
          <p class="text-green-200 text-xs font-medium">Tài xế đối tác</p>
        </div>
        <div class="w-px bg-white/20" />
        <div class="text-center">
          <p class="text-white text-2xl font-black">98%</p>
          <p class="text-green-200 text-xs font-medium">Tỷ lệ hài lòng</p>
        </div>
        <div class="w-px bg-white/20" />
        <div class="text-center">
          <p class="text-white text-2xl font-black">24/7</p>
          <p class="text-green-200 text-xs font-medium">Hỗ trợ tài xế</p>
        </div>
      </div>
    </div>

    <!-- ─── RIGHT: Login form ──────────────────────────────── -->
    <div class="flex-1 flex flex-col items-center justify-center bg-white px-6 py-12 min-h-screen lg:min-h-0">

      <!-- Mobile logo (shown only on small screens) -->
      <div class="lg:hidden flex items-center gap-2 mb-8">
        <div class="w-9 h-9 bg-green-600 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
          </svg>
        </div>
        <span class="text-xl font-black text-gray-900">XeGhep<span class="text-green-600">.vn</span></span>
      </div>

      <!-- Form card -->
      <div class="w-full max-w-md">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-2xl font-black text-gray-900">Đăng nhập Tài xế</h1>
          <p class="text-gray-500 text-sm mt-1.5">Nhập thông tin tài khoản tài xế của bạn</p>
        </div>

        <!-- Error alert -->
        <div v-if="error"
          class="mb-5 flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
          <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          {{ error }}
        </div>

        <!-- Phone -->
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Số điện thoại</label>
          <input v-model="phone" type="tel" inputmode="numeric" placeholder="09xxxxxxxx"
            class="w-full h-12 px-4 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-400 transition-colors"
            @keyup.enter="handleLogin" />
        </div>

        <!-- Password -->
        <div class="mb-2">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu</label>
          <div class="relative">
            <input v-model="password" :type="showPw ? 'text' : 'password'" placeholder="••••••••"
              class="w-full h-12 px-4 pr-12 border border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder-gray-400 transition-colors"
              @keyup.enter="handleLogin" />
            <button type="button" @click="showPw = !showPw"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path v-if="!showPw" stroke-linecap="round" stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                <path v-else stroke-linecap="round" stroke-linejoin="round"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Forgot password -->
        <div class="text-right mb-6">
          <a href="#" class="text-sm text-green-600 hover:text-green-700 font-medium transition-colors">
            Quên mật khẩu?
          </a>
        </div>

        <!-- Submit button -->
        <button @click="handleLogin" :disabled="loading || !phone || !password"
          class="w-full h-12 bg-green-600 hover:bg-green-700 active:bg-green-800 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-base rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2">
          <div v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <span>{{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}</span>
        </button>

        <!-- Divider -->
        <div class="flex items-center gap-3 my-6">
          <div class="flex-1 h-px bg-gray-200" />
          <span class="text-xs text-gray-400 font-medium">HOẶC</span>
          <div class="flex-1 h-px bg-gray-200" />
        </div>

        <!-- Register link -->
        <p class="text-center text-sm text-gray-500">
          Chưa có tài khoản?
          <a href="#" class="text-green-600 hover:text-green-700 font-semibold ml-1 transition-colors">
            Đăng ký làm tài xế
          </a>
        </p>

        <!-- Hotline -->
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <a href="tel:18009999" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            Hotline hỗ trợ tài xế: 1800-9999 (miễn phí)
          </a>
        </div>
      </div>
    </div>

  </div>
</template>
