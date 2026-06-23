<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { adminApi } from '@/api/admin.api'

interface OperatorDoc {
  id: string
  company_name: string
  owner_name: string
  phone: string
  email: string
  status: string
  commission_rate: number
  created_at: string
  documents: { business_license?: string; transport_license?: string }
  declared_fleet_total?: number
  declared_fleet_summary?: string
  actual_vehicles_count?: number
}

interface PartnerApp {
  id: string
  company_name: string
  tax_code: string
  address: string
  vehicle_count: number
  fleet_summary: string
  fleet_breakdown: Record<string, number>
  representative_name: string
  phone: string
  email: string | null
  business_license_url: string | null
  fleet_images: string[]
  status: string
  status_label: string
  note: string | null
  created_at: string
}

// View: nhà xe (operator account) vs đơn đăng ký đối tác (lead)
const view = ref<'operators' | 'applications'>('applications')

// ─── Operators ───────────────────────────────────────────────────────────
const operators = ref<OperatorDoc[]>([])
const isLoading = ref(true)
const errorMsg = ref('')
const activeTab = ref<'all' | 'suspended'>('all')

// ─── Partner applications ──────────────────────────────────────────────────
const applications = ref<PartnerApp[]>([])
const appLoading = ref(true)
const appError = ref('')
const appTab = ref<'all' | 'pending' | 'approved' | 'rejected'>('pending')

// Shared modal state (dùng chung cho cả 2 luồng)
const modalMode = ref<'operator' | 'application'>('operator')
const selectedId = ref('')
const selectedName = ref('')

const showApproveModal = ref(false)
const commissionRate = ref(10)
const approveLoading = ref(false)

const showRejectModal = ref(false)
const rejectReason = ref('')
const rejectLoading = ref(false)

// Đặt lại mật khẩu nhà xe
const showResetModal = ref(false)
const resetLoading = ref(false)
const resetResult = ref<{ phone: string; temp_password: string } | null>(null)
const copied = ref(false)

const tabs = [
  { key: 'all',       label: 'Tất cả' },
  { key: 'suspended', label: 'Đình chỉ' },
]

const appTabs = [
  { key: 'all',      label: 'Tất cả' },
  { key: 'pending',  label: 'Chờ xử lý' },
  { key: 'approved', label: 'Đã duyệt' },
  { key: 'rejected', label: 'Từ chối' },
]

const statusMap: Record<string, { label: string; class: string }> = {
  pending:   { label: 'Chờ duyệt', class: 'bg-yellow-100 text-yellow-700' },
  verified:  { label: 'Đã duyệt',  class: 'bg-green-100 text-green-700' },
  suspended: { label: 'Đình chỉ',  class: 'bg-red-100 text-red-700' },
  rejected:  { label: 'Từ chối',   class: 'bg-gray-100 text-gray-600' },
  approved:  { label: 'Đã duyệt',  class: 'bg-green-100 text-green-700' },
  contacted: { label: 'Đã liên hệ', class: 'bg-blue-100 text-blue-700' },
}

const filtered = computed(() => {
  if (activeTab.value === 'all') return operators.value
  return operators.value.filter(o => o.status === activeTab.value)
})

const filteredApps = computed(() => {
  if (appTab.value === 'all') return applications.value
  return applications.value.filter(a => a.status === appTab.value)
})

const pendingAppCount = computed(() => applications.value.filter(a => a.status === 'pending').length)

async function loadOperators() {
  isLoading.value = true
  errorMsg.value = ''
  const { data, error } = await adminApi.getOperators()
  if (error) { errorMsg.value = error; isLoading.value = false; return }
  operators.value = (data as OperatorDoc[]) ?? []
  isLoading.value = false
}

async function loadApplications() {
  appLoading.value = true
  appError.value = ''
  const { data, error } = await adminApi.getPartnerApplications()
  if (error) { appError.value = error; appLoading.value = false; return }
  applications.value = (data as PartnerApp[]) ?? []
  appLoading.value = false
}

// ─── Approve / Reject (branch theo modalMode) ───────────────────────────────
function openApproveOperator(op: OperatorDoc) {
  modalMode.value = 'operator'
  selectedId.value = op.id
  selectedName.value = op.company_name
  commissionRate.value = op.commission_rate || 10
  showApproveModal.value = true
}
function openApproveApp(app: PartnerApp) {
  modalMode.value = 'application'
  selectedId.value = app.id
  selectedName.value = app.company_name
  commissionRate.value = 10
  showApproveModal.value = true
}
function openRejectOperator(op: OperatorDoc) {
  modalMode.value = 'operator'
  selectedId.value = op.id
  selectedName.value = op.company_name
  rejectReason.value = ''
  showRejectModal.value = true
}
function openRejectApp(app: PartnerApp) {
  modalMode.value = 'application'
  selectedId.value = app.id
  selectedName.value = app.company_name
  rejectReason.value = ''
  showRejectModal.value = true
}

async function confirmApprove() {
  if (!selectedId.value) return
  approveLoading.value = true
  const { error } = modalMode.value === 'application'
    ? await adminApi.approvePartnerApplication(selectedId.value, { commission_rate: commissionRate.value })
    : await adminApi.verifyOperator(selectedId.value, { commission_rate: commissionRate.value })
  approveLoading.value = false
  if (error) { alert(error); return }
  showApproveModal.value = false
  modalMode.value === 'application' ? await loadApplications() : await loadOperators()
}

async function confirmReject() {
  if (!selectedId.value || !rejectReason.value.trim()) return
  rejectLoading.value = true
  const { error } = modalMode.value === 'application'
    ? await adminApi.rejectPartnerApplication(selectedId.value, { reason: rejectReason.value })
    : await adminApi.rejectOperator(selectedId.value, { reason: rejectReason.value })
  rejectLoading.value = false
  if (error) { alert(error); return }
  showRejectModal.value = false
  modalMode.value === 'application' ? await loadApplications() : await loadOperators()
}

function openResetPassword(op: OperatorDoc) {
  selectedId.value = op.id
  selectedName.value = op.company_name
  resetResult.value = null
  copied.value = false
  showResetModal.value = true
}

async function confirmReset() {
  if (!selectedId.value) return
  resetLoading.value = true
  const { data, error } = await adminApi.resetOperatorPassword(selectedId.value)
  resetLoading.value = false
  if (error) { alert(error); return }
  resetResult.value = data
}

async function copyPassword() {
  if (!resetResult.value) return
  try {
    await navigator.clipboard.writeText(resetResult.value.temp_password)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch { /* clipboard không khả dụng — admin tự copy thủ công */ }
}

onMounted(() => {
  loadApplications()
  loadOperators()
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-bold text-gray-900">Quản lý nhà xe</h1>
      <button @click="view === 'applications' ? loadApplications() : loadOperators()"
        class="text-sm text-red-600 hover:text-red-700 font-medium">
        Làm mới
      </button>
    </div>

    <!-- View switch -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
      <button @click="view = 'applications'"
        :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2',
          view === 'applications' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        Đơn đăng ký đối tác
        <span v-if="pendingAppCount > 0"
          class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-semibold">
          {{ pendingAppCount }}
        </span>
      </button>
      <button @click="view = 'operators'"
        :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
          view === 'operators' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        Nhà xe đã có tài khoản
      </button>
    </div>

    <!-- ═══════════ PARTNER APPLICATIONS ═══════════ -->
    <template v-if="view === 'applications'">
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
        <button v-for="tab in appTabs" :key="tab.key"
          @click="appTab = tab.key as typeof appTab.value"
          :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
            appTab === tab.key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          {{ tab.label }}
        </button>
      </div>

      <div v-if="appLoading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse h-40" />
      </div>

      <div v-else-if="appError"
        class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 flex items-center gap-3">
        {{ appError }}
        <button @click="loadApplications" class="ml-auto text-sm underline">Thử lại</button>
      </div>

      <div v-else-if="filteredApps.length === 0"
        class="bg-white rounded-xl border border-slate-200 py-16 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-gray-500 font-medium">Không có đơn đăng ký nào</p>
        <p class="text-gray-400 text-sm mt-1">Chưa có đơn ở trạng thái này</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="app in filteredApps" :key="app.id"
          class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <div class="flex items-start gap-5">
            <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
              <span class="text-xl font-bold text-blue-500">{{ app.company_name.charAt(0) }}</span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 flex-wrap">
                <h3 class="font-semibold text-gray-900 text-base">{{ app.company_name }}</h3>
                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  statusMap[app.status]?.class ?? 'bg-gray-100 text-gray-600']">
                  {{ app.status_label }}
                </span>
              </div>
              <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1.5 text-sm text-gray-600">
                <span>👤 {{ app.representative_name }}</span>
                <span>📞 {{ app.phone }}</span>
                <span v-if="app.email">✉️ {{ app.email }}</span>
                <span>🧾 MST: {{ app.tax_code }}</span>
                <span class="col-span-2 md:col-span-1">🚐 {{ app.vehicle_count }} xe · {{ app.fleet_summary }}</span>
              </div>
              <p class="text-sm text-gray-500 mt-1.5">📍 {{ app.address }}</p>
              <p class="text-xs text-gray-400 mt-1.5">
                Gửi lúc: {{ new Date(app.created_at).toLocaleString('vi-VN') }}
              </p>
              <p v-if="app.note" class="text-xs text-red-500 mt-1">Ghi chú: {{ app.note }}</p>

              <div class="flex gap-3 mt-3 flex-wrap">
                <a v-if="app.business_license_url" :href="app.business_license_url" target="_blank"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs rounded-lg transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Giấy phép kinh doanh
                </a>
                <a v-for="(img, i) in app.fleet_images" :key="i" :href="img" target="_blank"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs rounded-lg transition-colors">
                  🖼️ Ảnh xe {{ i + 1 }}
                </a>
              </div>
            </div>

            <div v-if="app.status === 'pending' || app.status === 'contacted'" class="flex gap-2 shrink-0">
              <button @click="openApproveApp(app)"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                Duyệt &amp; tạo tài khoản
              </button>
              <button @click="openRejectApp(app)"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                Từ chối
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════ OPERATORS ═══════════ -->
    <template v-else>
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit mb-6">
        <button v-for="tab in tabs" :key="tab.key"
          @click="activeTab = tab.key as typeof activeTab.value"
          :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
            activeTab === tab.key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          {{ tab.label }}
        </button>
      </div>

      <div v-if="isLoading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-xl border border-slate-200 p-5 animate-pulse h-36" />
      </div>

      <div v-else-if="errorMsg"
        class="bg-red-50 border border-red-200 rounded-xl p-5 text-red-700 flex items-center gap-3">
        {{ errorMsg }}
        <button @click="loadOperators" class="ml-auto text-sm underline">Thử lại</button>
      </div>

      <div v-else-if="filtered.length === 0"
        class="bg-white rounded-xl border border-slate-200 py-16 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
        </svg>
        <p class="text-gray-500 font-medium">Không có nhà xe nào</p>
        <p class="text-gray-400 text-sm mt-1">Chưa có nhà xe ở trạng thái này</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="op in filtered" :key="op.id"
          class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <div class="flex items-start gap-5">
            <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
              <span class="text-xl font-bold text-gray-400">{{ op.company_name.charAt(0) }}</span>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 flex-wrap">
                <h3 class="font-semibold text-gray-900 text-base">{{ op.company_name }}</h3>
                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                  statusMap[op.status]?.class ?? 'bg-gray-100 text-gray-600']">
                  {{ statusMap[op.status]?.label ?? op.status }}
                </span>
              </div>
              <div class="mt-2 grid grid-cols-3 gap-3 text-sm text-gray-600">
                <span>👤 {{ op.owner_name }}</span>
                <span>📞 {{ op.phone }}</span>
                <span>✉️ {{ op.email }}</span>
              </div>
              <p class="text-xs text-gray-400 mt-1.5">
                Đăng ký: {{ new Date(op.created_at).toLocaleDateString('vi-VN') }}
              </p>

              <!-- Đối chiếu đội xe: khai lúc đăng ký vs thực tế đã thêm -->
              <div v-if="(op.declared_fleet_total ?? 0) > 0"
                class="mt-2 inline-flex items-center gap-2 text-xs px-2.5 py-1 rounded-lg"
                :class="(op.actual_vehicles_count ?? 0) >= (op.declared_fleet_total ?? 0)
                  ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">
                🚐 Đội xe: đã thêm {{ op.actual_vehicles_count ?? 0 }}/{{ op.declared_fleet_total }} xe khai báo
                <span class="text-gray-400">({{ op.declared_fleet_summary }})</span>
              </div>

              <div class="flex gap-3 mt-3">
                <a v-if="op.documents?.business_license"
                  :href="op.documents.business_license" target="_blank"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs rounded-lg transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Giấy phép kinh doanh
                </a>
              </div>
            </div>

            <div v-if="op.status === 'pending'" class="flex gap-2 shrink-0">
              <button @click="openApproveOperator(op)"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                Duyệt
              </button>
              <button @click="openRejectOperator(op)"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                Từ chối
              </button>
            </div>
            <div v-else-if="op.status === 'verified'" class="flex gap-2 shrink-0">
              <button @click="openResetPassword(op)"
                class="px-4 py-2 border border-amber-300 text-amber-700 hover:bg-amber-50 text-sm font-medium rounded-lg transition-colors">
                Đặt lại mật khẩu
              </button>
              <button @click="openRejectOperator(op)"
                class="px-4 py-2 border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium rounded-lg transition-colors">
                Đình chỉ
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Approve modal -->
  <Teleport to="body">
    <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">
          {{ modalMode === 'application' ? 'Duyệt đơn & tạo tài khoản nhà xe' : 'Xác nhận duyệt nhà xe' }}
        </h3>
        <p class="text-sm text-gray-500 mb-5">{{ selectedName }}</p>

        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Tỷ lệ hoa hồng (%)</label>
          <input v-model.number="commissionRate" type="number" min="0" max="30" step="0.5"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          <p class="text-xs text-gray-400 mt-1">Nền tảng thu {{ commissionRate }}% trên mỗi giao dịch</p>
        </div>

        <div class="flex gap-3">
          <button @click="showApproveModal = false"
            class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Hủy
          </button>
          <button @click="confirmApprove" :disabled="approveLoading"
            class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition-colors">
            {{ approveLoading ? 'Đang xử lý...' : 'Xác nhận duyệt' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Reject modal -->
  <Teleport to="body">
    <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">
          {{ modalMode === 'application' ? 'Từ chối đơn đăng ký' : 'Từ chối / Đình chỉ nhà xe' }}
        </h3>
        <p class="text-sm text-gray-500 mb-5">{{ selectedName }}</p>

        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Lý do <span class="text-red-500">*</span></label>
          <textarea v-model="rejectReason" rows="3" placeholder="Nhập lý do..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none" />
        </div>

        <div class="flex gap-3">
          <button @click="showRejectModal = false"
            class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
            Hủy
          </button>
          <button @click="confirmReject" :disabled="rejectLoading || !rejectReason.trim()"
            class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition-colors">
            {{ rejectLoading ? 'Đang xử lý...' : 'Xác nhận' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Reset password modal -->
  <Teleport to="body">
    <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <!-- Bước xác nhận -->
        <template v-if="!resetResult">
          <h3 class="text-lg font-semibold text-gray-900 mb-1">Đặt lại mật khẩu nhà xe</h3>
          <p class="text-sm text-gray-500 mb-4">{{ selectedName }}</p>
          <div class="mb-5 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
            Hệ thống sẽ tạo mật khẩu tạm mới, gửi SMS cho nhà xe và hiển thị để bạn chuyển trực tiếp.
            Mật khẩu cũ sẽ không dùng được nữa.
          </div>
          <div class="flex gap-3">
            <button @click="showResetModal = false"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
              Hủy
            </button>
            <button @click="confirmReset" :disabled="resetLoading"
              class="flex-1 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition-colors">
              {{ resetLoading ? 'Đang xử lý...' : 'Đặt lại mật khẩu' }}
            </button>
          </div>
        </template>

        <!-- Bước kết quả -->
        <template v-else>
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Đã đặt lại mật khẩu</h3>
          </div>
          <p class="text-sm text-gray-500 mb-4">{{ selectedName }}</p>

          <div class="space-y-2 mb-4 p-4 bg-gray-50 rounded-xl text-sm">
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Đăng nhập (SĐT):</span>
              <span class="font-mono font-medium text-gray-900">{{ resetResult.phone }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-500">Mật khẩu tạm:</span>
              <span class="font-mono font-bold text-amber-700 text-base tracking-wider">{{ resetResult.temp_password }}</span>
            </div>
          </div>

          <p class="text-xs text-gray-500 mb-4">
            SMS đã được gửi (nếu ESMS đã cấu hình). Nếu nhà xe không nhận được, hãy chuyển mật khẩu này trực tiếp —
            <span class="text-red-500">mật khẩu sẽ không hiển thị lại sau khi đóng.</span>
          </p>

          <div class="flex gap-3">
            <button @click="copyPassword"
              class="flex-1 px-4 py-2.5 border border-amber-300 text-amber-700 rounded-lg text-sm font-medium hover:bg-amber-50 transition-colors">
              {{ copied ? 'Đã sao chép ✓' : 'Sao chép mật khẩu' }}
            </button>
            <button @click="showResetModal = false"
              class="flex-1 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-sm font-medium transition-colors">
              Đóng
            </button>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>
