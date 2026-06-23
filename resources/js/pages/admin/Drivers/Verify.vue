<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { adminApi } from '@/api/admin.api';

interface DriverDoc {
    id: string;
    full_name: string;
    phone: string;
    photo_url: string;
    operator_name: string;
    status: string;
    created_at: string;
    documents: {
        id_card_front?: string;
        id_card_back?: string;
        driver_license?: string;
        driver_license_number?: string;
        driver_license_class?: string;
        driver_license_expiry?: string;
    };
}

const drivers = ref<DriverDoc[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const activeTab = ref<'all' | 'pending' | 'verified' | 'suspended'>('pending');
const zoomedImage = ref<string | null>(null);

// Action modals
const showRejectModal = ref(false);
const selectedDriver = ref<DriverDoc | null>(null);
const rejectReason = ref('');
const actionLoading = ref(false);

// Modal hiển thị mật khẩu (sau duyệt hoặc cấp lại)
const credModal = ref(false);
const credResult = ref<{ phone: string; temp_password: string } | null>(null);
const credTitle = ref('');
const credCopied = ref(false);

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'verified', label: 'Đã duyệt' },
    { key: 'suspended', label: 'Đình chỉ' },
];

const statusMap: Record<string, { label: string; class: string }> = {
    pending: { label: 'Chờ duyệt', class: 'bg-yellow-100 text-yellow-700' },
    verified: { label: 'Đã duyệt', class: 'bg-green-100 text-green-700' },
    suspended: { label: 'Đình chỉ', class: 'bg-red-100 text-red-700' },
    rejected: { label: 'Từ chối', class: 'bg-gray-100 text-gray-600' },
};

const filtered = computed(() => {
    if (activeTab.value === 'all') return drivers.value;
    return drivers.value.filter((d) => d.status === activeTab.value);
});

function isLicenseExpiringSoon(expiry?: string) {
    if (!expiry) return false;
    const exp = new Date(expiry);
    const sixMonths = new Date();
    sixMonths.setMonth(sixMonths.getMonth() + 6);
    return exp < sixMonths;
}

async function loadDrivers() {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await adminApi.getDrivers();
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    drivers.value = (data as DriverDoc[]) ?? [];
    isLoading.value = false;
}

async function approveDriver(d: DriverDoc) {
    if (
        !confirm(
            `Xác nhận duyệt tài xế ${d.full_name}? Hệ thống sẽ cấp mật khẩu đăng nhập và gửi SMS.`,
        )
    )
        return;
    const { data, error } = await adminApi.verifyDriver(d.id);
    if (error) {
        alert(error);
        return;
    }
    credTitle.value = 'Đã duyệt tài xế & cấp mật khẩu';
    credResult.value = data;
    credCopied.value = false;
    credModal.value = true;
    await loadDrivers();
}

async function resetDriverPassword(d: DriverDoc) {
    if (
        !confirm(
            `Cấp lại mật khẩu cho tài xế ${d.full_name}? Mật khẩu cũ sẽ không dùng được nữa.`,
        )
    )
        return;
    const { data, error } = await adminApi.resetDriverPassword(d.id);
    if (error) {
        alert(error);
        return;
    }
    credTitle.value = 'Đã cấp lại mật khẩu tài xế';
    credResult.value = data;
    credCopied.value = false;
    credModal.value = true;
}

async function copyCredPassword() {
    if (!credResult.value) return;
    try {
        await navigator.clipboard.writeText(credResult.value.temp_password);
        credCopied.value = true;
        setTimeout(() => (credCopied.value = false), 2000);
    } catch {
        /* clipboard không khả dụng */
    }
}

function openReject(d: DriverDoc) {
    selectedDriver.value = d;
    rejectReason.value = '';
    showRejectModal.value = true;
}

async function confirmReject() {
    if (!selectedDriver.value || !rejectReason.value.trim()) return;
    actionLoading.value = true;
    const isSuspend = selectedDriver.value.status === 'verified';
    const { error } = isSuspend
        ? await adminApi.suspendDriver(selectedDriver.value.id, {
              reason: rejectReason.value,
          })
        : await adminApi.rejectDriver(selectedDriver.value.id, {
              reason: rejectReason.value,
          });
    actionLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    showRejectModal.value = false;
    await loadDrivers();
}

onMounted(loadDrivers);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Xét duyệt tài xế</h1>
            <button
                @click="loadDrivers"
                class="text-sm font-medium text-red-600 hover:text-red-700"
            >
                Làm mới
            </button>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key as typeof activeTab.value"
                :class="[
                    'rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
                    activeTab === tab.key
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-4">
            <div
                v-for="i in 3"
                :key="i"
                class="h-52 animate-pulse rounded-xl border border-slate-200 bg-white p-6"
            />
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            {{ errorMsg }}
            <button @click="loadDrivers" class="ml-auto text-sm underline">
                Thử lại
            </button>
        </div>

        <!-- Empty -->
        <div
            v-else-if="filtered.length === 0"
            class="rounded-xl border border-slate-200 bg-white py-16 text-center"
        >
            <svg
                class="mx-auto mb-3 h-12 w-12 text-gray-300"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
            </svg>
            <p class="font-medium text-gray-500">Không có tài xế nào</p>
        </div>

        <!-- Driver cards -->
        <div v-else class="space-y-5">
            <div
                v-for="d in filtered"
                :key="d.id"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex gap-6">
                    <!-- Left: driver info -->
                    <div class="w-44 shrink-0">
                        <div
                            class="mb-3 h-20 w-20 overflow-hidden rounded-xl bg-gray-100"
                        >
                            <img
                                v-if="d.photo_url"
                                :src="d.photo_url"
                                :alt="d.full_name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-2xl font-bold text-gray-400"
                            >
                                {{ d.full_name.charAt(0) }}
                            </div>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ d.full_name }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ d.phone }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ d.operator_name }}
                        </p>
                        <span
                            :class="[
                                'mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                statusMap[d.status]?.class ??
                                    'bg-gray-100 text-gray-600',
                            ]"
                        >
                            {{ statusMap[d.status]?.label ?? d.status }}
                        </span>
                        <p class="mt-1.5 text-xs text-gray-400">
                            Đăng ký:
                            {{
                                new Date(d.created_at).toLocaleDateString(
                                    'vi-VN',
                                )
                            }}
                        </p>
                    </div>

                    <!-- Center: documents -->
                    <div class="min-w-0 flex-1">
                        <!-- ID card images -->
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            Chứng minh nhân dân
                        </p>
                        <div class="mb-4 flex gap-3">
                            <div
                                class="group relative cursor-zoom-in"
                                @click="
                                    zoomedImage =
                                        d.documents?.id_card_front ?? null
                                "
                            >
                                <div
                                    class="h-24 w-36 overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
                                >
                                    <img
                                        v-if="d.documents?.id_card_front"
                                        :src="d.documents.id_card_front"
                                        alt="CMND mặt trước"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center p-2 text-center text-xs text-gray-400"
                                    >
                                        Chưa có ảnh
                                    </div>
                                </div>
                                <p
                                    class="mt-1 text-center text-xs text-gray-500"
                                >
                                    Mặt trước
                                </p>
                            </div>
                            <div
                                class="group relative cursor-zoom-in"
                                @click="
                                    zoomedImage =
                                        d.documents?.id_card_back ?? null
                                "
                            >
                                <div
                                    class="h-24 w-36 overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
                                >
                                    <img
                                        v-if="d.documents?.id_card_back"
                                        :src="d.documents.id_card_back"
                                        alt="CMND mặt sau"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center p-2 text-center text-xs text-gray-400"
                                    >
                                        Chưa có ảnh
                                    </div>
                                </div>
                                <p
                                    class="mt-1 text-center text-xs text-gray-500"
                                >
                                    Mặt sau
                                </p>
                            </div>
                        </div>

                        <!-- Driver license -->
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >
                            Giấy phép lái xe
                        </p>
                        <div class="flex items-start gap-4">
                            <div
                                class="cursor-zoom-in"
                                @click="
                                    zoomedImage =
                                        d.documents?.driver_license ?? null
                                "
                            >
                                <div
                                    class="h-24 w-36 overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
                                >
                                    <img
                                        v-if="d.documents?.driver_license"
                                        :src="d.documents.driver_license"
                                        alt="GPLX"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center p-2 text-center text-xs text-gray-400"
                                    >
                                        Chưa có ảnh
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-1.5 text-sm">
                                <div class="flex gap-6">
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Số GPLX
                                        </p>
                                        <p
                                            class="font-mono font-medium text-gray-800"
                                        >
                                            {{
                                                d.documents
                                                    ?.driver_license_number ??
                                                '—'
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">
                                            Hạng
                                        </p>
                                        <p class="font-medium text-gray-800">
                                            {{
                                                d.documents
                                                    ?.driver_license_class ??
                                                '—'
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">
                                        Ngày hết hạn
                                    </p>
                                    <p
                                        :class="[
                                            'font-medium',
                                            isLicenseExpiringSoon(
                                                d.documents
                                                    ?.driver_license_expiry,
                                            )
                                                ? 'font-semibold text-red-600'
                                                : 'text-gray-800',
                                        ]"
                                    >
                                        {{
                                            d.documents?.driver_license_expiry
                                                ? new Date(
                                                      d.documents
                                                          .driver_license_expiry,
                                                  ).toLocaleDateString('vi-VN')
                                                : '—'
                                        }}
                                        <span
                                            v-if="
                                                isLicenseExpiringSoon(
                                                    d.documents
                                                        ?.driver_license_expiry,
                                                )
                                            "
                                            class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-600"
                                        >
                                            Sắp hết hạn
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="mt-4 flex flex-wrap gap-4">
                            <span
                                class="flex items-center gap-1 text-xs text-green-700"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                CMND hợp lệ
                            </span>
                            <span
                                :class="[
                                    'flex items-center gap-1 text-xs',
                                    isLicenseExpiringSoon(
                                        d.documents?.driver_license_expiry,
                                    )
                                        ? 'text-red-600'
                                        : 'text-green-700',
                                ]"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                GPLX còn hạn
                            </span>
                            <span
                                class="flex items-center gap-1 text-xs text-green-700"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Hạng phù hợp
                            </span>
                        </div>
                    </div>

                    <!-- Right: action panel -->
                    <div
                        v-if="d.status === 'pending'"
                        class="flex w-44 shrink-0 flex-col gap-2"
                    >
                        <button
                            @click="approveDriver(d)"
                            class="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700"
                        >
                            Duyệt tài xế
                        </button>
                        <button
                            @click="openReject(d)"
                            class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                        >
                            Từ chối
                        </button>
                        <button
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Yêu cầu bổ sung
                        </button>
                    </div>
                    <div
                        v-else-if="d.status === 'verified'"
                        class="flex w-44 shrink-0 flex-col gap-2"
                    >
                        <button
                            @click="resetDriverPassword(d)"
                            class="w-full rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200"
                        >
                            Cấp lại mật khẩu
                        </button>
                        <button
                            @click="openReject(d)"
                            class="w-full rounded-lg border border-red-300 px-4 py-2.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                        >
                            Đình chỉ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image zoom overlay -->
    <Teleport to="body">
        <div
            v-if="zoomedImage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
            @click="zoomedImage = null"
        >
            <img
                :src="zoomedImage"
                class="max-h-[80vh] max-w-2xl rounded-xl object-contain"
            />
        </div>
    </Teleport>

    <!-- Credentials modal (sau duyệt / cấp lại mật khẩu) -->
    <Teleport to="body">
        <div
            v-if="credModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-1 flex items-center gap-2">
                    <svg
                        class="h-5 w-5 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ credTitle }}
                    </h3>
                </div>
                <p class="mb-4 text-sm text-gray-500">
                    Đã gửi SMS thông tin đăng nhập cho tài xế (nếu cấu hình).
                </p>

                <div
                    v-if="credResult"
                    class="mb-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Đăng nhập (SĐT):</span>
                        <span class="font-mono font-medium text-gray-900">{{
                            credResult.phone
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Mật khẩu tạm:</span>
                        <span
                            class="font-mono text-base font-bold tracking-wider text-amber-700"
                            >{{ credResult.temp_password }}</span
                        >
                    </div>
                </div>
                <p class="mb-4 text-xs text-red-500">
                    Mật khẩu sẽ không hiển thị lại sau khi đóng. Nhà xe cũng có
                    thể tự "Cấp lại mật khẩu" cho tài xế.
                </p>

                <div class="flex gap-3">
                    <button
                        @click="copyCredPassword"
                        class="flex-1 rounded-lg border border-amber-300 px-4 py-2.5 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-50"
                    >
                        {{ credCopied ? 'Đã sao chép ✓' : 'Sao chép mật khẩu' }}
                    </button>
                    <button
                        @click="credModal = false"
                        class="flex-1 rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Reject modal -->
    <Teleport to="body">
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-1 text-lg font-semibold text-gray-900">
                    Từ chối tài xế
                </h3>
                <p class="mb-5 text-sm text-gray-500">
                    {{ selectedDriver?.full_name }}
                </p>
                <div class="mb-5">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                        >Lý do <span class="text-red-500">*</span></label
                    >
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="Nhập lý do từ chối..."
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                    />
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showRejectModal = false"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="confirmReject"
                        :disabled="actionLoading || !rejectReason.trim()"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        {{
                            actionLoading ? 'Đang xử lý...' : 'Xác nhận từ chối'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
