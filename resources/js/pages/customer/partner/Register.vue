<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { customerApi } from '@/api/customer.api'

// ─── Form state ────────────────────────────────────────────────────────────
const form = reactive({
  company_name: '',
  tax_code: '',
  address: '',
  representative_name: '',
  phone: '',
  email: '',
})

// Cơ cấu đội xe theo loại — số lượng từng loại, tổng tự cộng
const fleet = reactive<Record<string, number | ''>>({
  sedan_4: '',
  mpv_7: '',
  van_9: '',
  minibus_16: '',
})

const fleetTypes = [
  { key: 'sedan_4', label: 'Xe 4 chỗ', hint: 'Sedan / 4–5 chỗ' },
  { key: 'mpv_7', label: 'Xe 7 chỗ', hint: 'MPV / SUV 7 chỗ' },
  { key: 'van_9', label: 'Xe 9 chỗ – Limousine', hint: 'Van / Limousine 9 chỗ' },
  { key: 'minibus_16', label: 'Xe 16 chỗ', hint: 'Minibus 16 chỗ' },
]

const totalVehicles = computed(() =>
  fleetTypes.reduce((sum, t) => sum + (Number(fleet[t.key]) || 0), 0),
)

const licenseFile = ref<File | null>(null)
const fleetImages = ref<File[]>([])

const errors = reactive<Record<string, string>>({})
const isSubmitting = ref(false)
const submitError = ref('')
const submitted = ref(false)

const benefits = [
  { title: 'Tăng doanh thu', desc: 'Tối ưu hóa tỷ lệ lấp đầy ghế trống, gia tăng doanh thu đáng kể cho mỗi chuyến đi của bạn.', icon: 'M2 11l1-5h14l1 5M5 11v6m10-6v6M3 17h14M6 14h.01M14 14h.01' },
  { title: 'Quản lý dễ dàng', desc: 'Hệ thống quản lý chuyến đi, tài xế và doanh thu trực quan, dễ sử dụng trên mọi thiết bị.', icon: 'M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM10 14a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z' },
  { title: 'Hỗ trợ 24/7', desc: 'Đội ngũ chăm sóc đối tác luôn sẵn sàng hỗ trợ giải quyết mọi vấn đề phát sinh 24/7.', icon: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zM9 9a1 1 0 100-2 1 1 0 000 2zm6 0a1 1 0 100-2 1 1 0 000 2z' },
  { title: 'Tiếp cận khách hàng', desc: 'Cơ hội tiếp cận mạng lưới hàng triệu khách hàng đang sử dụng nền tảng của chúng tôi mỗi ngày.', icon: 'M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z' },
]

// ─── File handlers ─────────────────────────────────────────────────────────
function onLicenseChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  licenseFile.value = file ?? null
}

function onFleetChange(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  fleetImages.value = files.slice(0, 5)
}

// ─── Validation ────────────────────────────────────────────────────────────
const phoneRegex = /^(0[3|5|7|8|9])+([0-9]{8})$/

function validate(): boolean {
  Object.keys(errors).forEach(k => delete errors[k])

  if (!form.company_name.trim()) errors.company_name = 'Vui lòng nhập tên nhà xe / công ty'
  if (!form.tax_code.trim()) errors.tax_code = 'Vui lòng nhập mã số thuế'
  if (!form.address.trim()) errors.address = 'Vui lòng nhập địa chỉ trụ sở'
  if (totalVehicles.value < 1) errors.fleet_breakdown = 'Vui lòng khai báo ít nhất 1 xe trong đội xe'
  if (!form.representative_name.trim()) errors.representative_name = 'Vui lòng nhập họ tên người đại diện'
  if (!form.phone.trim()) errors.phone = 'Vui lòng nhập số điện thoại'
  else if (!phoneRegex.test(form.phone)) errors.phone = 'Số điện thoại không hợp lệ'
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errors.email = 'Email không hợp lệ'

  return Object.keys(errors).length === 0
}

const hasErrors = computed(() => Object.keys(errors).length > 0)

// ─── Submit ────────────────────────────────────────────────────────────────
async function onSubmit() {
  submitError.value = ''
  if (!validate()) return

  isSubmitting.value = true
  const fd = new FormData()
  fd.append('company_name', form.company_name)
  fd.append('tax_code', form.tax_code)
  fd.append('address', form.address)
  fleetTypes.forEach(t => fd.append(`fleet_breakdown[${t.key}]`, String(Number(fleet[t.key]) || 0)))
  fd.append('representative_name', form.representative_name)
  fd.append('phone', form.phone)
  if (form.email) fd.append('email', form.email)
  if (licenseFile.value) fd.append('business_license', licenseFile.value)
  fleetImages.value.forEach(img => fd.append('fleet_images[]', img))

  const { error } = await customerApi.submitPartnerApplication(fd)
  isSubmitting.value = false

  if (error) {
    submitError.value = error
    return
  }
  submitted.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function scrollToForm() {
  document.getElementById('registration-form')?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
  <div class="bg-slate-50">
    <!-- ─── Hero Section ─────────────────────────────────────────────────── -->
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-slate-100">
      <div class="absolute top-0 right-0 w-1/2 h-full opacity-10 pointer-events-none"
        style="background: radial-gradient(circle at 80% 20%, #2563eb 0%, transparent 60%)" />
      <div class="max-w-7xl mx-auto px-6 py-16 lg:py-24 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center relative z-10">
        <div class="flex flex-col gap-5">
          <div class="inline-flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-full w-max shadow-sm">
            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span class="text-sm font-medium text-gray-600">Mạng lưới xe ghép số 1 Việt Nam</span>
          </div>
          <h1 class="text-3xl md:text-5xl font-bold text-gray-900 leading-tight">
            Hợp tác cùng <span class="text-blue-600">XeGhep.vn</span><br />
            Nâng tầm dịch vụ vận tải của bạn
          </h1>
          <p class="text-lg text-gray-600 max-w-xl leading-relaxed">
            Gia nhập mạng lưới vận tải chuyên nghiệp, tiếp cận hàng triệu khách hàng và tối ưu hóa doanh thu ngay hôm nay.
            Quy trình đăng ký nhanh chóng, hỗ trợ 24/7.
          </p>
          <div>
            <button type="button" @click="scrollToForm"
              class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl font-semibold transition-colors shadow-md">
              <span>Đăng ký trở thành đối tác</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </button>
          </div>
          <div class="mt-4 flex items-center gap-4">
            <div class="flex -space-x-3">
              <div v-for="i in 3" :key="i"
                class="w-10 h-10 rounded-full border-2 border-white bg-blue-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                </svg>
              </div>
            </div>
            <p class="text-sm text-gray-600">
              <strong class="text-gray-900">Hơn 500+</strong> nhà xe đã tin tưởng và hợp tác
            </p>
          </div>
        </div>
        <div class="relative hidden lg:block">
          <div class="absolute inset-0 bg-blue-600 opacity-5 rounded-[2rem] translate-x-4 translate-y-4" />
          <div class="rounded-[2rem] shadow-2xl relative z-10 w-full aspect-[4/3] bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center">
            <svg class="w-32 h-32 text-white/90" fill="currentColor" viewBox="0 0 20 20">
              <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm7 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM2.5 5h15l-1.5 8h-12L2.5 5z" />
            </svg>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Benefits Section ─────────────────────────────────────────────── -->
    <section class="py-16 px-6 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-10">
          <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Tại sao chọn hợp tác cùng chúng tôi?</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Chúng tôi cung cấp nền tảng công nghệ toàn diện giúp bạn tối ưu hóa hoạt động kinh doanh vận tải.
          </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="b in benefits" :key="b.title"
            class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 flex flex-col items-start gap-3 hover:-translate-y-1 transition-transform">
            <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" :d="b.icon" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 text-lg">{{ b.title }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed">{{ b.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ─── Registration Form Section ────────────────────────────────────── -->
    <section id="registration-form" class="py-16 px-6 bg-slate-50">
      <div class="max-w-4xl mx-auto">
        <!-- Success state -->
        <div v-if="submitted"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 md:p-12 text-center">
          <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Gửi yêu cầu thành công!</h2>
          <p class="text-gray-600 max-w-md mx-auto">
            Cảm ơn bạn đã quan tâm hợp tác cùng XeGhep.vn. Đội ngũ của chúng tôi sẽ liên hệ lại trong vòng
            <strong>24h làm việc</strong> để hoàn tất quy trình.
          </p>
        </div>

        <!-- Form -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 border-t-4 border-t-blue-600 p-6 md:p-10">
          <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">Đăng ký thông tin đối tác</h2>
            <p class="text-gray-600">
              Vui lòng điền đầy đủ thông tin bên dưới, chúng tôi sẽ liên hệ lại trong vòng 24h làm việc.
            </p>
          </div>

          <div v-if="submitError"
            class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 text-sm">
            {{ submitError }}
          </div>
          <div v-else-if="hasErrors"
            class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4 text-amber-700 text-sm">
            Vui lòng kiểm tra lại các thông tin được đánh dấu bên dưới.
          </div>

          <form class="space-y-10" @submit.prevent="onSubmit">
            <!-- Business Info -->
            <div class="space-y-5">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Thông tin doanh nghiệp
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Tên nhà xe / Công ty <span class="text-red-500">*</span></label>
                  <input v-model="form.company_name" type="text" placeholder="Nhập tên doanh nghiệp"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.company_name ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.company_name" class="mt-1 text-xs text-red-500">{{ errors.company_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Mã số thuế <span class="text-red-500">*</span></label>
                  <input v-model="form.tax_code" type="text" placeholder="Nhập mã số thuế"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.tax_code ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.tax_code" class="mt-1 text-xs text-red-500">{{ errors.tax_code }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Địa chỉ trụ sở <span class="text-red-500">*</span></label>
                  <input v-model="form.address" type="text" placeholder="Nhập địa chỉ đầy đủ"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.address ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.address" class="mt-1 text-xs text-red-500">{{ errors.address }}</p>
                </div>
              </div>
            </div>

            <!-- Fleet Info -->
            <div class="space-y-5">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm11 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 8h13l3 4v4.5h-2M3 8v8.5h2m11 0H8" />
                </svg>
                Thông tin đội xe
              </h3>
              <p class="text-sm text-gray-500 -mt-2">
                Khai số lượng xe theo từng loại. Tổng số xe sẽ được tính tự động.
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="t in fleetTypes" :key="t.key"
                  class="flex items-center justify-between gap-3 border border-gray-200 rounded-lg px-4 py-3">
                  <div>
                    <p class="text-sm font-medium text-gray-800">{{ t.label }}</p>
                    <p class="text-xs text-gray-400">{{ t.hint }}</p>
                  </div>
                  <input v-model="fleet[t.key]" type="number" min="0" placeholder="0"
                    class="w-20 h-11 px-3 text-center border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
              </div>
              <div class="flex items-center justify-between rounded-lg bg-blue-50 border border-blue-100 px-4 py-3">
                <span class="text-sm font-medium text-gray-700">Tổng đội xe</span>
                <span class="text-lg font-bold text-blue-600">{{ totalVehicles }} xe</span>
              </div>
              <p v-if="errors.fleet_breakdown" class="text-xs text-red-500">{{ errors.fleet_breakdown }}</p>
            </div>

            <!-- Contact Info -->
            <div class="space-y-5">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Thông tin liên hệ
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Họ tên người đại diện <span class="text-red-500">*</span></label>
                  <input v-model="form.representative_name" type="text" placeholder="Nhập họ và tên"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.representative_name ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.representative_name" class="mt-1 text-xs text-red-500">{{ errors.representative_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Số điện thoại <span class="text-red-500">*</span></label>
                  <input v-model="form.phone" type="tel" placeholder="Nhập số điện thoại"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.phone ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.phone" class="mt-1 text-xs text-red-500">{{ errors.phone }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1.5">Email</label>
                  <input v-model="form.email" type="email" placeholder="Nhập địa chỉ email"
                    :class="['w-full h-12 px-4 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                      errors.email ? 'border-red-400' : 'border-gray-300']" />
                  <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email }}</p>
                </div>
              </div>
            </div>

            <!-- Uploads -->
            <div class="space-y-5">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pb-2 border-b border-gray-100">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Hồ sơ đính kèm
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center text-center bg-slate-50 hover:bg-blue-50 transition-colors cursor-pointer">
                  <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <span class="text-sm font-medium text-gray-800 mb-1">Giấy phép kinh doanh</span>
                  <span class="text-xs text-gray-500">{{ licenseFile ? licenseFile.name : 'Click để tải lên (PDF, JPG)' }}</span>
                  <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="onLicenseChange" />
                </label>
                <label class="border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center text-center bg-slate-50 hover:bg-blue-50 transition-colors cursor-pointer">
                  <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <span class="text-sm font-medium text-gray-800 mb-1">Hình ảnh đội xe</span>
                  <span class="text-xs text-gray-500">{{ fleetImages.length ? `Đã chọn ${fleetImages.length} ảnh` : 'Click để tải lên (Tối đa 5 ảnh)' }}</span>
                  <input type="file" accept=".jpg,.jpeg,.png" multiple class="hidden" @change="onFleetChange" />
                </label>
              </div>
            </div>

            <!-- Submit -->
            <div class="pt-6 border-t border-gray-100 flex justify-end">
              <button type="submit" :disabled="isSubmitting"
                class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white px-8 py-4 rounded-lg font-semibold w-full md:w-auto transition-colors shadow-md">
                <span>{{ isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu đăng ký' }}</span>
                <svg v-if="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>
