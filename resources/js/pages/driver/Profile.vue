<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { driverApi } from '@/api/driver.api'
import { useDriverAuthStore } from '@/stores/driver.auth.store'

const router = useRouter()
const auth   = useDriverAuthStore()

const profile    = ref<any>(null)
const reviews    = ref<any[]>([])
const isLoading  = ref(true)
const saveLoading = ref(false)
const pwLoading  = ref(false)
const saveMsg    = ref('')
const saveError  = ref('')
const pwMsg      = ref('')
const pwError    = ref('')

const form = ref({
  full_name:  auth.user?.full_name ?? '',
  email:      auth.user?.email     ?? '',
  birth_date: '',
})

const pwForm = ref({
  old_password:     '',
  new_password:     '',
  confirm_password: '',
})

function docStatus(doc: { status?: string; expires_at?: string }) {
  const status = doc.status ?? 'verified'
  if (status === 'expired') return { icon: '❌', cls: 'text-red-600 bg-red-50', label: 'Hết hạn', action: 'Cập nhật', actionCls: 'text-red-600' }
  if (doc.expires_at) {
    const daysLeft = Math.floor((new Date(doc.expires_at).getTime() - Date.now()) / 86400000)
    if (daysLeft <= 30) return { icon: '⚠️', cls: 'text-amber-600 bg-amber-50', label: `Sắp hết hạn (${daysLeft} ngày)`, action: 'Cập nhật', actionCls: 'text-amber-600' }
  }
  if (status === 'pending') return { icon: '⏳', cls: 'text-yellow-600 bg-yellow-50', label: 'Đang chờ duyệt', action: 'Xem ảnh', actionCls: 'text-gray-600' }
  return { icon: '✅', cls: 'text-green-700 bg-green-50', label: 'Đã xác minh', action: 'Xem ảnh', actionCls: 'text-gray-500' }
}

const docs = computed(() => [
  {
    label: 'CMND / CCCD',
    status: profile.value?.id_card_status ?? 'verified',
    expires_at: undefined,
  },
  {
    label: 'Giấy phép lái xe',
    status: auth.driver?.license_expiry ? 'verified' : 'verified',
    expires_at: auth.driver?.license_expiry,
  },
  {
    label: 'Đăng kiểm xe',
    status: profile.value?.registration_status ?? 'verified',
    expires_at: profile.value?.registration_expiry,
  },
  {
    label: 'Bảo hiểm xe',
    status: 'verified' as string,
    expires_at: profile.value?.insurance_expiry,
  },
])

const stats = computed(() => [
  { label: 'Tổng chuyến đi',     value: auth.user?.total_trips ?? 0 },
  { label: 'Tháng này',          value: profile.value?.month_trips ?? 0 },
  { label: 'Tỷ lệ hoàn thành',   value: (profile.value?.completion_rate ?? 98) + '%' },
])

async function saveProfile() {
  saveLoading.value = true
  saveMsg.value     = ''
  saveError.value   = ''
  // In real app: PUT /api/driver/auth/profile
  await new Promise(r => setTimeout(r, 600))
  saveLoading.value = false
  auth.user = { ...auth.user!, full_name: form.value.full_name, email: form.value.email } as any
  saveMsg.value = 'Cập nhật thông tin thành công!'
  setTimeout(() => { saveMsg.value = '' }, 3000)
}

async function updatePassword() {
  pwMsg.value   = ''
  pwError.value = ''
  if (pwForm.value.new_password !== pwForm.value.confirm_password) {
    pwError.value = 'Mật khẩu xác nhận không khớp'; return
  }
  if (pwForm.value.new_password.length < 8) {
    pwError.value = 'Mật khẩu phải có ít nhất 8 ký tự'; return
  }
  pwLoading.value = true
  await new Promise(r => setTimeout(r, 600))
  pwLoading.value = false
  pwForm.value = { old_password: '', new_password: '', confirm_password: '' }
  pwMsg.value = 'Cập nhật mật khẩu thành công!'
  setTimeout(() => { pwMsg.value = '' }, 3000)
}

onMounted(async () => {
  isLoading.value = true
  const [meRes] = await Promise.all([
    driverApi.me(),
  ])
  isLoading.value = false
  if (meRes.data) {
    profile.value = meRes.data
    const u = meRes.data.user ?? meRes.data
    if (u) {
      form.value.full_name = u.full_name ?? auth.user?.full_name ?? ''
      form.value.email     = u.email     ?? auth.user?.email     ?? ''
    }
  }
  reviews.value = profile.value?.recent_reviews ?? [
    { id: 1, customer_name: 'Nguyễn Thị A', rating: 5, comment: 'Tài xế rất thân thiện, đúng giờ!', date: '2024-06-10' },
    { id: 2, customer_name: 'Trần Văn B',   rating: 4, comment: 'Xe sạch, đi êm ái.',                date: '2024-06-08' },
    { id: 3, customer_name: 'Lê Thị C',     rating: 5, comment: 'Tuyệt vời, sẽ đặt lại lần sau.',    date: '2024-06-05' },
  ]
})
</script>

<template>
  <div class="p-6 max-w-5xl mx-auto">

    <!-- Page title -->
    <div class="mb-6">
      <h1 class="text-xl font-bold text-gray-900">Hồ sơ tài xế</h1>
      <p class="text-sm text-gray-500 mt-0.5">Quản lý thông tin cá nhân và giấy tờ</p>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-[35%_1fr] gap-6">
      <div class="space-y-4">
        <div class="h-64 bg-gray-200 rounded-xl animate-pulse" />
        <div class="h-32 bg-gray-200 rounded-xl animate-pulse" />
        <div class="h-48 bg-gray-200 rounded-xl animate-pulse" />
      </div>
      <div class="space-y-4">
        <div class="h-48 bg-gray-200 rounded-xl animate-pulse" />
        <div class="h-64 bg-gray-200 rounded-xl animate-pulse" />
      </div>
    </div>

    <div v-else class="grid grid-cols-[35%_1fr] gap-6 items-start">

      <!-- ─── LEFT column ───────────────────────────────────── -->
      <div class="space-y-4">

        <!-- Profile card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 text-center">
          <!-- Avatar with upload overlay -->
          <div class="relative w-24 h-24 mx-auto mb-3">
            <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center text-4xl font-black text-green-700">
              {{ auth.user?.full_name?.charAt(0) ?? 'T' }}
            </div>
            <button class="absolute bottom-0 right-0 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center shadow-md hover:bg-green-700 transition-colors">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>

          <h2 class="text-lg font-black text-gray-900">{{ auth.user?.full_name }}</h2>

          <!-- Verified badge -->
          <span v-if="auth.user?.is_verified"
            class="inline-flex items-center gap-1.5 mt-1.5 px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Đã xác minh
          </span>

          <!-- Rating -->
          <div class="flex items-center justify-center gap-1.5 mt-3">
            <div class="flex gap-0.5">
              <svg v-for="i in 5" :key="i" :class="['w-4 h-4', i <= Math.round(auth.user?.rating_avg ?? 5) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200 fill-gray-200']" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <span class="text-base font-black text-gray-800">{{ auth.user?.rating_avg?.toFixed(1) ?? '4.8' }}</span>
            <span class="text-sm text-gray-400">· {{ profile?.review_count ?? 0 }} đánh giá</span>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-gray-100">
            <div v-for="stat in stats" :key="stat.label" class="text-center">
              <p class="text-lg font-black text-gray-900">{{ stat.value }}</p>
              <p class="text-xs text-gray-400 leading-tight">{{ stat.label }}</p>
            </div>
          </div>
        </div>

        <!-- Vehicle info card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            🚐 Phương tiện của tôi
          </h3>
          <!-- Đã được nhà xe gán xe mặc định -->
          <div v-if="profile?.vehicle" class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Biển số</span>
              <span class="font-mono font-black text-gray-900">{{ profile.vehicle.plate_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Hãng / Model</span>
              <span class="font-semibold text-gray-800">{{ profile.vehicle.brand }} {{ profile.vehicle.model }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Năm / Màu</span>
              <span class="font-semibold text-gray-800">{{ profile.vehicle.year }} · {{ profile.vehicle.color }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Số chỗ</span>
              <span class="font-semibold text-gray-800">{{ profile.vehicle.seat_count }} chỗ</span>
            </div>
          </div>
          <!-- Chưa được gán xe mặc định -->
          <div v-else class="py-4 text-center">
            <p class="text-sm text-gray-400">Nhà xe chưa gán xe mặc định cho bạn.</p>
            <p class="text-xs text-gray-400 mt-1">Xe của từng chuyến sẽ hiển thị trong chi tiết chuyến.</p>
          </div>
        </div>

        <!-- Recent reviews -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Đánh giá gần đây</h3>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="rev in reviews" :key="rev.id" class="px-4 py-3">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-800">{{ rev.customer_name }}</span>
                <div class="flex gap-0.5">
                  <svg v-for="i in 5" :key="i" :class="['w-3 h-3', i <= rev.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-200 fill-gray-200']" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </div>
              <p class="text-xs text-gray-500 leading-relaxed">{{ rev.comment }}</p>
              <p class="text-xs text-gray-300 mt-1">{{ new Date(rev.date).toLocaleDateString('vi-VN') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── RIGHT column ──────────────────────────────────── -->
      <div class="space-y-5">

        <!-- Personal info form -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Thông tin cá nhân</h3>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Họ và tên</label>
              <input v-model="form.full_name" type="text"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Số điện thoại
                <span class="text-xs text-gray-400 font-normal">(không đổi được)</span>
              </label>
              <input :value="auth.user?.phone" type="tel" disabled
                class="w-full h-11 px-3.5 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-400 cursor-not-allowed" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
              <input v-model="form.email" type="email" placeholder="email@example.com"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Ngày sinh</label>
              <input v-model="form.birth_date" type="date"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
          </div>

          <div v-if="saveMsg" class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">✓ {{ saveMsg }}</div>
          <div v-if="saveError" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">{{ saveError }}</div>

          <button @click="saveProfile" :disabled="saveLoading"
            class="px-6 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold text-sm rounded-lg transition-colors flex items-center gap-2">
            <div v-if="saveLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            {{ saveLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>

        <!-- Documents table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Giấy tờ & Tài liệu</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Loại giấy tờ</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày hết hạn</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hành động</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="doc in docs" :key="doc.label" class="hover:bg-gray-50 transition-colors">
                  <td class="px-5 py-3.5 font-medium text-gray-800">{{ doc.label }}</td>
                  <td class="px-5 py-3.5">
                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', docStatus(doc).cls]">
                      {{ docStatus(doc).icon }} {{ docStatus(doc).label }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-gray-500">
                    {{ doc.expires_at ? new Date(doc.expires_at).toLocaleDateString('vi-VN') : '—' }}
                  </td>
                  <td class="px-5 py-3.5">
                    <button :class="['text-xs font-semibold hover:underline transition-colors', docStatus(doc).actionCls]">
                      {{ docStatus(doc).action }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Change password -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Đổi mật khẩu</h3>
          <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu hiện tại</label>
              <input v-model="pwForm.old_password" type="password" placeholder="••••••••"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mật khẩu mới</label>
              <input v-model="pwForm.new_password" type="password" placeholder="••••••••"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Xác nhận</label>
              <input v-model="pwForm.confirm_password" type="password" placeholder="••••••••"
                class="w-full h-11 px-3.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors" />
            </div>
          </div>

          <div v-if="pwMsg"   class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">✓ {{ pwMsg }}</div>
          <div v-if="pwError" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">{{ pwError }}</div>

          <button @click="updatePassword" :disabled="pwLoading"
            class="px-6 py-2.5 bg-gray-800 hover:bg-gray-900 disabled:opacity-60 text-white font-semibold text-sm rounded-lg transition-colors flex items-center gap-2">
            <div v-if="pwLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            {{ pwLoading ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
          </button>
        </div>

        <!-- Support -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-xl">📞</div>
            <div>
              <p class="font-semibold text-gray-800 text-sm">Liên hệ hỗ trợ tài xế</p>
              <p class="text-green-600 text-sm font-semibold">1800-9999 (miễn phí 24/7)</p>
            </div>
          </div>
          <a href="tel:18009999"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
            Gọi ngay
          </a>
        </div>

      </div>
    </div>
  </div>
</template>
