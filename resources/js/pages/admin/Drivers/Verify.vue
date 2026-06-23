<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { adminApi } from '@/api/admin.api'

interface DriverDoc {
  id: string
  full_name: string
  phone: string
  photo_url: string
  operator_name: string
  status: string
  created_at: string
  documents: {
    id_card_front?: string
    id_card_back?: string
    driver_license?: string
    driver_license_number?: string
    driver_license_class?: string
    driver_license_expiry?: string
  }
}

const drivers = ref<DriverDoc[]>([])
const isLoading = ref(true)
const errorMsg = ref('')
const activeTab = ref<'all' | 'pending' | 'verified' | 'suspended'>('pending')
const zoomedImage = ref<string | null>(null)

// Action modals
const showRejectModal = ref(false)
const selectedDriver = ref<DriverDoc | null>(null)
const rejectReason = ref('')
const actionLoading = ref(false)

// Modal hiển thị mật khẩu (sau duyệt hoặc cấp lại)
const credModal = ref(false)
const credResult = ref<{ phone: string; temp_password: string } | null>(null)
const credTitle = ref('')
const credCopied = ref(false)

const tabs = [
  { key: 'all',       label: 'Tất cả' },
  { key: 'pending',   label: 'Chờ duyệt' },
  { key: 'verified',  label: 'Đã duyệt' },
  { key: 'suspended', label: 'Đình chỉ' },
]

const statusMap: Record<string, { label: string; class: string }> = {
  pending:   { label: 'Chờ duyệt', class: 'bg-yellow-100 text-yellow-700' },
  verified:  { label: 'Đã duyệt',  class: 'bg-green-100 text-green-700' },
  suspended: { label: 'Đình chỉ',  class: 'bg-red-100 text-red-700' },
  rejected:  { label: 'Từ chối',   class: 'bg-gray-100 text-gray-600' },
}

const filtered = computed(() => {
  if (activeTab.value === 'all') return drivers.value
  return drivers.value.filter(d => d.status === activeTab.value)
})

function isLicenseExpiringSoon(expiry?: string) {
  if (!expiry) return false
  const exp = new Date(expiry)
  const sixMonths = new Date()
  sixMonths.setMonth(sixMonths.getMonth() + 6)
  return exp < sixMonths
}

async function loadDrivers() {
  isLoading.value = true
  errorMsg.value = ''
  const { data, error } = await adminApi.getDrivers()
  if (error) { errorMsg.value = error; isLoading.value = false; return }
  drivers.value = (data as DriverDoc[]) ?? []
  isLoading.value = false
}

async function approveDriver(d: DriverDoc) {
  if (!confirm(`Xác nhận duyệt tài xế ${d.full_name}? Hệ thống sẽ cấp mật khẩu đăng nhập và gửi SMS.`)) return
  const { data, error } = await adminApi.verifyDriver(d.id)
  if (error) { alert(error); return }
  credTitle.value = 'Đã duyệt tài xế & cấp mật khẩu'
  credResult.value = data
  credCopied.value = false
  credModal.value = true
  await loadDrivers()
}

async function resetDriverPassword(d: DriverDoc) {
  if (!confirm(`Cấp lại mật khẩu cho tài xế ${d.full_name}? Mật khẩu cũ sẽ không dùng được nữa.`)) return
  const { data, error } = await adminApi.resetDriverPassword(d.id)
  if (error) { alert(error); return }
  credTitle.value = 'Đã cấp lại mật khẩu tài xế'
  credResult.value = data
  credCopied.value = false
  credModal.value = true
}

async function copyCredPassword() {
  if (!credResult.value) return
  try {
    await navigator.clipboard.writeText(credResult.value.temp_password)
    credCopied.value = true
    setTimeout(() => (credCopied.value = false), 2000)
  } catch { /* clipboard không khả dụng */ }
}

function openReject(d: DriverDoc) {
  selectedDriver.value = d
  rejectReason.value = ''
  showRejectModal.value = true
}

async function confirmReject() {
  if (!selectedDriver.value || !rejectReason.value.trim()) return
  actionLoading.value = true
  const isSuspend = selectedDriver.value.status === 'verified'
  const { error } = isSuspend
    ? await adminApi.suspendDriver(selectedDriver.value.id, { reason: rejectReason.value })
    : await adminApi.rejectDriver(selectedDriver.value.id, { reason: rejectReason.value })
  actionLoading.value = false
  if (error) { alert(error); return }
  showRejectModal.value = false
  await loadDrivers()
}

onMounted(loadDrivers)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold text-gray-900">Xét duyệt tài xế</h1>
      <button @click="loadDrivers" class="text-sm text-red-600 hover:text-red-700 font-medium">Làm mới</button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
      <button v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key as typeof activeTab.value"
        :class="[
          'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
        ]">
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-slate-200 p-6 animate-pulse h-52" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg"
      class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 flex items-center gap-3">
      {{ errorMsg }}
      <button @click="loadDrivers" class="ml-auto text-sm underline">Thử lại</button>
    </div>

    <!-- Empty -->
    <div v-else-if="filtered.length === 0"
      class="bg-white rounded-xl border border-slate-200 py-16 text-center">
      <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
      <p class="text-gray-500 font-medium">Không có tài xế nào</p>
    </div>

    <!-- Driver cards -->
    <div v-else class="space-y-5">
      <div v-for="d in filtered" :key="d.id"
        class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex gap-6">
          <!-- Left: driver info -->
          <div class="w-44 shrink-0">
            <div class="w-20 h-20 rounded-xl bg-gray-100 overflow-hidden mb-3">
              <img v-if="d.photo_url" :src="d.photo_url" :alt="d.full_name" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-400">
                {{ d.full_name.charAt(0) }}
              </div>
            </div>
            <p class="font-semibold text-gray-900 text-sm">{{ d.full_name }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ d.phone }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ d.operator_name }}</p>
            <span :class="['inline-flex mt-2 px-2 py-0.5 rounded-full text-xs font-medium',
              statusMap[d.status]?.class ?? 'bg-gray-100 text-gray-600']">
              {{ statusMap[d.status]?.label ?? d.status }}
            </span>
            <p class="text-xs text-gray-400 mt-1.5">
              Đăng ký: {{ new Date(d.created_at).toLocaleDateString('vi-VN') }}
            </p>
          </div>

          <!-- Center: documents -->
          <div class="flex-1 min-w-0">
            <!-- ID card images -->
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Chứng minh nhân dân</p>
            <div class="flex gap-3 mb-4">
              <div class="relative group cursor-zoom-in"
                @click="zoomedImage = d.documents?.id_card_front ?? null">
                <div class="w-36 h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                  <img v-if="d.documents?.id_card_front"
                    :src="d.documents.id_card_front" alt="CMND mặt trước"
                    class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs text-center p-2">
                    Chưa có ảnh
                  </div>
                </div>
                <p class="text-xs text-gray-500 text-center mt-1">Mặt trước</p>
              </div>
              <div class="relative group cursor-zoom-in"
                @click="zoomedImage = d.documents?.id_card_back ?? null">
                <div class="w-36 h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                  <img v-if="d.documents?.id_card_back"
                    :src="d.documents.id_card_back" alt="CMND mặt sau"
                    class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs text-center p-2">
                    Chưa có ảnh
                  </div>
                </div>
                <p class="text-xs text-gray-500 text-center mt-1">Mặt sau</p>
              </div>
            </div>

            <!-- Driver license -->
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Giấy phép lái xe</p>
            <div class="flex items-start gap-4">
              <div class="cursor-zoom-in"
                @click="zoomedImage = d.documents?.driver_license ?? null">
                <div class="w-36 h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                  <img v-if="d.documents?.driver_license"
                    :src="d.documents.driver_license" alt="GPLX"
                    class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs text-center p-2">
                    Chưa có ảnh
                  </div>
                </div>
              </div>
              <div class="text-sm space-y-1.5">
                <div class="flex gap-6">
                  <div>
                    <p class="text-xs text-gray-400">Số GPLX</p>
                    <p class="font-mono font-medium text-gray-800">{{ d.documents?.driver_license_number ?? '—' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-400">Hạng</p>
                    <p class="font-medium text-gray-800">{{ d.documents?.driver_license_class ?? '—' }}</p>
                  </div>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Ngày hết hạn</p>
                  <p :class="['font-medium', isLicenseExpiringSoon(d.documents?.driver_license_expiry)
                    ? 'text-red-600 font-semibold' : 'text-gray-800']">
                    {{ d.documents?.driver_license_expiry
                        ? new Date(d.documents.driver_license_expiry).toLocaleDateString('vi-VN')
                        : '—' }}
                    <span v-if="isLicenseExpiringSoon(d.documents?.driver_license_expiry)"
                      class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded ml-1">
                      Sắp hết hạn
                    </span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Checklist -->
            <div class="mt-4 flex gap-4 flex-wrap">
              <span class="flex items-center gap-1 text-xs text-green-700">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                CMND hợp lệ
              </span>
              <span :class="['flex items-center gap-1 text-xs',
                isLicenseExpiringSoon(d.documents?.driver_license_expiry) ? 'text-red-600' : 'text-green-700']">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                GPLX còn hạn
              </span>
              <span class="flex items-center gap-1 text-xs text-green-700">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Hạng phù hợp
              </span>
            </div>
          </div>

          <!-- Right: action panel -->
          <div v-if="d.status === 'pending'" class="w-44 shrink-0 flex flex-col gap-2">
            <button @click="approveDriver(d)"
              class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
              Duyệt tài xế
            </button>
            <button @click="openReject(d)"
              class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
              Từ chối
            </button>
            <button
              class="w-full px-4 py-2.5 border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors">
              Yêu cầu bổ sung
            </button>
          </div>
          <div v-else-if="d.status === 'verified'" class="w-44 shrink-0 flex flex-col gap-2">
            <button @click="resetDriverPassword(d)"
              class="w-full px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
              Cấp lại mật khẩu
            </button>
            <button @click="openReject(d)"
              class="w-full px-4 py-2.5 border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium rounded-lg transition-colors">
              Đình chỉ
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Image zoom overlay -->
  <Teleport to="body">
    <div v-if="zoomedImage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
      @click="zoomedImage = null">
      <img :src="zoomedImage" class="max-w-2xl max-h-[80vh] rounded-xl object-contain" />
    </div>
  </Teleport>

  <!-- Credentials modal (sau duyệt / cấp lại mật khẩu) -->
  <Teleport to="body">
    <div v-if="credModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <h3 class="text-lg font-bold text-gray-900">{{ credTitle }}</h3>
        </div>
        <p class="text-sm text-gray-500 mb-4">Đã gửi SMS thông tin đăng nhập cho tài xế (nếu cấu hình).</p>

        <div v-if="credResult" class="space-y-2 mb-4 p-4 bg-gray-50 rounded-xl text-sm">
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Đăng nhập (SĐT):</span>
            <span class="font-mono font-medium text-gray-900">{{ credResult.phone }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Mật khẩu tạm:</span>
            <span class="font-mono font-bold text-amber-700 text-base tracking-wider">{{ credResult.temp_password }}</span>
          </div>
        </div>
        <p class="text-xs text-red-500 mb-4">
          Mật khẩu sẽ không hiển thị lại sau khi đóng. Nhà xe cũng có thể tự "Cấp lại mật khẩu" cho tài xế.
        </p>

        <div class="flex gap-3">
          <button @click="copyCredPassword"
            class="flex-1 px-4 py-2.5 border border-amber-300 text-amber-700 rounded-lg text-sm font-medium hover:bg-amber-50 transition-colors">
            {{ credCopied ? 'Đã sao chép ✓' : 'Sao chép mật khẩu' }}
          </button>
          <button @click="credModal = false"
            class="flex-1 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-sm font-medium transition-colors">
            Đóng
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Reject modal -->
  <Teleport to="body">
    <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Từ chối tài xế</h3>
        <p class="text-sm text-gray-500 mb-5">{{ selectedDriver?.full_name }}</p>
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Lý do <span class="text-red-500">*</span></label>
          <textarea v-model="rejectReason" rows="3" placeholder="Nhập lý do từ chối..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none" />
        </div>
        <div class="flex gap-3">
          <button @click="showRejectModal = false"
            class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Hủy
          </button>
          <button @click="confirmReject" :disabled="actionLoading || !rejectReason.trim()"
            class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition-colors">
            {{ actionLoading ? 'Đang xử lý...' : 'Xác nhận từ chối' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
