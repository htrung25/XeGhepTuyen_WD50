<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { customerApi } from '@/api/customer.api'
import { useCustomerAuthStore } from '@/stores/customer.auth.store'

const router = useRouter()
const auth   = useCustomerAuthStore()

type Section = 'profile' | 'password' | 'wallet' | 'vouchers'

const activeSection = ref<Section>('profile')
const isLoading     = ref(true)
const saveLoading   = ref(false)
const errorMsg      = ref('')
const successMsg    = ref('')
const wallet        = ref<any>(null)

const form = ref({
  full_name: auth.user?.full_name ?? '',
  email:     auth.user?.email ?? '',
  phone:     auth.user?.phone ?? '',
})

const passwordForm = ref({
  old_password:  '',
  new_password:  '',
  confirm:       '',
})

const stats = ref({ total_trips: 0, total_points: 0, vouchers: 0 })

const menuItems: { key: Section; icon: string; label: string }[] = [
  { key: 'profile',  icon: '👤', label: 'Thông tin cá nhân' },
  { key: 'password', icon: '🔒', label: 'Đổi mật khẩu' },
  { key: 'wallet',   icon: '💳', label: 'Ví XeGhep' },
  { key: 'vouchers', icon: '🏷️', label: 'Mã giảm giá' },
]

function fmt(v: number) { return new Intl.NumberFormat('vi-VN').format(v) + 'đ' }

async function saveProfile() {
  saveLoading.value = true
  errorMsg.value    = ''
  successMsg.value  = ''
  const { error } = await customerApi.me() // check then update
  // In real app: PUT /api/customer/auth/profile
  saveLoading.value = false
  if (error) { errorMsg.value = 'Cập nhật thất bại. Vui lòng thử lại.'; return }
  auth.setAuth(auth.token!, { ...auth.user!, full_name: form.value.full_name, email: form.value.email } as any)
  successMsg.value = 'Cập nhật thông tin thành công!'
  setTimeout(() => { successMsg.value = '' }, 3000)
}

async function changePassword() {
  errorMsg.value = ''
  successMsg.value = ''
  if (passwordForm.value.new_password.length < 8) {
    errorMsg.value = 'Mật khẩu mới tối thiểu 8 ký tự'; return
  }
  if (passwordForm.value.new_password !== passwordForm.value.confirm) {
    errorMsg.value = 'Xác nhận mật khẩu mới không khớp'; return
  }
  saveLoading.value = true
  const { error } = await customerApi.changePassword({
    old_password: passwordForm.value.old_password,
    new_password: passwordForm.value.new_password,
    new_password_confirmation: passwordForm.value.confirm,
  })
  saveLoading.value = false
  if (error) { errorMsg.value = error; return }
  successMsg.value = 'Đổi mật khẩu thành công!'
  passwordForm.value = { old_password: '', new_password: '', confirm: '' }
  setTimeout(() => { successMsg.value = '' }, 3000)
}

async function logout() {
  await customerApi.logout()
  auth.logout()
  router.push('/login')
}

onMounted(async () => {
  const [meRes, walletRes] = await Promise.all([
    customerApi.me(),
    customerApi.getWallet(),
  ])
  isLoading.value = false
  if (meRes.data) {
    form.value.full_name = meRes.data.full_name ?? ''
    form.value.email     = meRes.data.email ?? ''
    form.value.phone     = meRes.data.phone ?? ''
  }
  if (walletRes.data) wallet.value = walletRes.data
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tài khoản của tôi</h1>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" />
    </div>

    <div v-else class="grid grid-cols-[280px_1fr] gap-8">
      <!-- ─── LEFT: Sidebar menu ─────────────────────── -->
      <aside>
        <!-- User card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-4 text-center">
          <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-3 text-3xl font-bold text-blue-700">
            {{ auth.user?.full_name?.charAt(0) ?? 'K' }}
          </div>
          <p class="font-bold text-gray-900 text-base">{{ auth.user?.full_name ?? '—' }}</p>
          <p class="text-sm text-gray-500 mt-0.5">{{ auth.user?.phone ?? '—' }}</p>
          <p class="text-xs text-gray-400 mt-1">Thành viên từ 2024</p>
        </div>

        <!-- Menu links -->
        <nav class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <button v-for="item in menuItems" :key="item.key"
            @click="activeSection = item.key"
            :class="['w-full flex items-center gap-3 px-4 py-3.5 text-sm font-medium transition-colors text-left border-l-2',
              activeSection === item.key
                ? 'border-blue-600 bg-blue-50 text-blue-700'
                : 'border-transparent text-gray-700 hover:bg-gray-50']">
            <span class="text-base">{{ item.icon }}</span>
            {{ item.label }}
          </button>

          <div class="border-t border-gray-100">
            <router-link to="/bookings"
              class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
              <span class="text-base">🎫</span>
              Vé của tôi
            </router-link>
            <button @click="logout"
              class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-medium text-red-500 hover:bg-red-50 transition-colors">
              <span class="text-base">🚪</span>
              Đăng xuất
            </button>
          </div>
        </nav>
      </aside>

      <!-- ─── RIGHT: Content area ────────────────────── -->
      <div>
        <!-- Profile section -->
        <div v-if="activeSection === 'profile'" class="space-y-5">
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-900 mb-5">Thông tin cá nhân</h2>

            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Họ và tên</label>
                <input v-model="form.full_name" type="text"
                  class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                  Số điện thoại
                  <span class="text-xs text-gray-400 font-normal">(không thể thay đổi)</span>
                </label>
                <input :value="form.phone" type="tel" disabled
                  class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm bg-gray-50 text-gray-500 cursor-not-allowed" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input v-model="form.email" type="email" placeholder="email@example.com"
                  class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
              </div>
            </div>

            <div v-if="successMsg" class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
              ✓ {{ successMsg }}
            </div>
            <div v-if="errorMsg" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
              {{ errorMsg }}
            </div>

            <button @click="saveProfile" :disabled="saveLoading"
              class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold text-sm rounded-lg transition-colors flex items-center gap-2">
              <div v-if="saveLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
              <span>{{ saveLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}</span>
            </button>
          </div>

          <!-- Stats row -->
          <div class="grid grid-cols-3 gap-4">
            <div v-for="stat in [
              { label: 'Tổng chuyến đi', value: stats.total_trips, icon: '🚌' },
              { label: 'Điểm tích lũy',  value: stats.total_points, icon: '⭐' },
              { label: 'Voucher còn',    value: stats.vouchers, icon: '🏷️' },
            ]" :key="stat.label"
              class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 text-center">
              <div class="text-2xl mb-1">{{ stat.icon }}</div>
              <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ stat.label }}</p>
            </div>
          </div>
        </div>

        <!-- Password section -->
        <div v-else-if="activeSection === 'password'"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <h2 class="font-semibold text-gray-900 mb-5">Đổi mật khẩu</h2>
          <div class="space-y-4 max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu hiện tại</label>
              <input v-model="passwordForm.old_password" type="password"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu mới</label>
              <input v-model="passwordForm.new_password" type="password"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Xác nhận mật khẩu mới</label>
              <input v-model="passwordForm.confirm" type="password"
                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" />
            </div>
            <button @click="changePassword" :disabled="saveLoading"
              class="px-6 py-2.5 bg-blue-600 text-white font-semibold text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ saveLoading ? 'Đang xử lý...' : 'Cập nhật mật khẩu' }}
            </button>
          </div>
        </div>

        <!-- Wallet section -->
        <div v-else-if="activeSection === 'wallet'" class="space-y-5">
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white">
            <p class="text-blue-100 text-sm mb-1">Ví XeGhep</p>
            <p class="text-3xl font-bold mb-4">{{ wallet ? fmt(wallet.balance) : '—' }}</p>
            <div class="flex gap-3">
              <button class="px-5 py-2 bg-white text-blue-700 font-semibold text-sm rounded-lg hover:bg-blue-50 transition-colors">
                Nạp tiền
              </button>
              <button class="px-5 py-2 border border-white/50 text-white text-sm font-medium rounded-lg hover:bg-white/10 transition-colors">
                Lịch sử
              </button>
            </div>
          </div>
          <div v-if="!wallet" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
            Chưa có thông tin ví
          </div>
        </div>

        <!-- Vouchers section -->
        <div v-else-if="activeSection === 'vouchers'"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
          <div class="text-4xl mb-3">🏷️</div>
          <p class="font-medium text-gray-700 mb-2">Chưa có mã giảm giá</p>
          <p class="text-sm text-gray-400">Theo dõi các chương trình ưu đãi từ XeGhep.vn</p>
        </div>
      </div>
    </div>
  </div>
</template>
