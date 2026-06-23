<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { adminApi } from '@/api/admin.api';

interface OperatorDoc {
    id: string;
    company_name: string;
    owner_name: string;
    phone: string;
    email: string;
    status: string;
    commission_rate: number;
    created_at: string;
    documents: { business_license?: string; transport_license?: string };
    declared_fleet_total?: number;
    declared_fleet_summary?: string;
    actual_vehicles_count?: number;
}

interface PartnerApp {
    id: string;
    company_name: string;
    tax_code: string;
    address: string;
    vehicle_count: number;
    fleet_summary: string;
    fleet_breakdown: Record<string, number>;
    representative_name: string;
    phone: string;
    email: string | null;
    business_license_url: string | null;
    fleet_images: string[];
    status: string;
    status_label: string;
    note: string | null;
    created_at: string;
}

// View: nhà xe (operator account) vs đơn đăng ký đối tác (lead)
const view = ref<'operators' | 'applications'>('applications');

// ─── Operators ───────────────────────────────────────────────────────────
const operators = ref<OperatorDoc[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const activeTab = ref<'all' | 'suspended'>('all');

// ─── Partner applications ──────────────────────────────────────────────────
const applications = ref<PartnerApp[]>([]);
const appLoading = ref(true);
const appError = ref('');
const appTab = ref<'all' | 'pending' | 'approved' | 'rejected'>('pending');

// Shared modal state (dùng chung cho cả 2 luồng)
const modalMode = ref<'operator' | 'application'>('operator');
const selectedId = ref('');
const selectedName = ref('');

const showApproveModal = ref(false);
const commissionRate = ref(10);
const approveLoading = ref(false);

const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectLoading = ref(false);

// Đặt lại mật khẩu nhà xe
const showResetModal = ref(false);
const resetLoading = ref(false);
const resetResult = ref<{ phone: string; temp_password: string } | null>(null);
const copied = ref(false);

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'suspended', label: 'Đình chỉ' },
];

const appTabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ xử lý' },
    { key: 'approved', label: 'Đã duyệt' },
    { key: 'rejected', label: 'Từ chối' },
];

const statusMap: Record<string, { label: string; class: string }> = {
    pending: { label: 'Chờ duyệt', class: 'bg-yellow-100 text-yellow-700' },
    verified: { label: 'Đã duyệt', class: 'bg-green-100 text-green-700' },
    suspended: { label: 'Đình chỉ', class: 'bg-red-100 text-red-700' },
    rejected: { label: 'Từ chối', class: 'bg-gray-100 text-gray-600' },
    approved: { label: 'Đã duyệt', class: 'bg-green-100 text-green-700' },
    contacted: { label: 'Đã liên hệ', class: 'bg-blue-100 text-blue-700' },
};

const filtered = computed(() => {
    if (activeTab.value === 'all') return operators.value;
    return operators.value.filter((o) => o.status === activeTab.value);
});

const filteredApps = computed(() => {
    if (appTab.value === 'all') return applications.value;
    return applications.value.filter((a) => a.status === appTab.value);
});

const pendingAppCount = computed(
    () => applications.value.filter((a) => a.status === 'pending').length,
);

async function loadOperators() {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await adminApi.getOperators();
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    operators.value = (data as OperatorDoc[]) ?? [];
    isLoading.value = false;
}

async function loadApplications() {
    appLoading.value = true;
    appError.value = '';
    const { data, error } = await adminApi.getPartnerApplications();
    if (error) {
        appError.value = error;
        appLoading.value = false;
        return;
    }
    applications.value = (data as PartnerApp[]) ?? [];
    appLoading.value = false;
}

// ─── Approve / Reject (branch theo modalMode) ───────────────────────────────
function openApproveOperator(op: OperatorDoc) {
    modalMode.value = 'operator';
    selectedId.value = op.id;
    selectedName.value = op.company_name;
    commissionRate.value = op.commission_rate || 10;
    showApproveModal.value = true;
}
function openApproveApp(app: PartnerApp) {
    modalMode.value = 'application';
    selectedId.value = app.id;
    selectedName.value = app.company_name;
    commissionRate.value = 10;
    showApproveModal.value = true;
}
function openRejectOperator(op: OperatorDoc) {
    modalMode.value = 'operator';
    selectedId.value = op.id;
    selectedName.value = op.company_name;
    rejectReason.value = '';
    showRejectModal.value = true;
}
function openRejectApp(app: PartnerApp) {
    modalMode.value = 'application';
    selectedId.value = app.id;
    selectedName.value = app.company_name;
    rejectReason.value = '';
    showRejectModal.value = true;
}

async function confirmApprove() {
    if (!selectedId.value) return;
    approveLoading.value = true;
    const { error } =
        modalMode.value === 'application'
            ? await adminApi.approvePartnerApplication(selectedId.value, {
                  commission_rate: commissionRate.value,
              })
            : await adminApi.verifyOperator(selectedId.value, {
                  commission_rate: commissionRate.value,
              });
    approveLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    showApproveModal.value = false;
    if (modalMode.value === 'application') {
        await loadApplications();
    } else {
        await loadOperators();
    }
}

async function confirmReject() {
    if (!selectedId.value || !rejectReason.value.trim()) return;
    rejectLoading.value = true;
    const { error } =
        modalMode.value === 'application'
            ? await adminApi.rejectPartnerApplication(selectedId.value, {
                  reason: rejectReason.value,
              })
            : await adminApi.rejectOperator(selectedId.value, {
                  reason: rejectReason.value,
              });
    rejectLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    showRejectModal.value = false;
    if (modalMode.value === 'application') {
        await loadApplications();
    } else {
        await loadOperators();
    }
}

function openResetPassword(op: OperatorDoc) {
    selectedId.value = op.id;
    selectedName.value = op.company_name;
    resetResult.value = null;
    copied.value = false;
    showResetModal.value = true;
}

async function confirmReset() {
    if (!selectedId.value) return;
    resetLoading.value = true;
    const { data, error } = await adminApi.resetOperatorPassword(
        selectedId.value,
    );
    resetLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    resetResult.value = data;
}

async function copyPassword() {
    if (!resetResult.value) return;
    try {
        await navigator.clipboard.writeText(resetResult.value.temp_password);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* clipboard không khả dụng — admin tự copy thủ công */
    }
}

onMounted(() => {
    loadApplications();
    loadOperators();
});
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Quản lý nhà xe</h1>
            <button
                @click="
                    view === 'applications'
                        ? loadApplications()
                        : loadOperators()
                "
                class="text-sm font-medium text-red-600 hover:text-red-700"
            >
                Làm mới
            </button>
        </div>

        <!-- View switch -->
        <div class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
            <button
                @click="view = 'applications'"
                :class="[
                    'flex items-center gap-2 rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
                    view === 'applications'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                Đơn đăng ký đối tác
                <span
                    v-if="pendingAppCount > 0"
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-semibold text-white"
                >
                    {{ pendingAppCount }}
                </span>
            </button>
            <button
                @click="view = 'operators'"
                :class="[
                    'rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
                    view === 'operators'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                Nhà xe đã có tài khoản
            </button>
        </div>

        <!-- ═══════════ PARTNER APPLICATIONS ═══════════ -->
        <template v-if="view === 'applications'">
            <div class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
                <button
                    v-for="tab in appTabs"
                    :key="tab.key"
                    @click="appTab = tab.key as typeof appTab.value"
                    :class="[
                        'rounded-lg px-4 py-1.5 text-sm font-medium transition-colors',
                        appTab === tab.key
                            ? 'bg-white text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="appLoading" class="space-y-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-40 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
                />
            </div>

            <div
                v-else-if="appError"
                class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
            >
                {{ appError }}
                <button
                    @click="loadApplications"
                    class="ml-auto text-sm underline"
                >
                    Thử lại
                </button>
            </div>

            <div
                v-else-if="filteredApps.length === 0"
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
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <p class="font-medium text-gray-500">
                    Không có đơn đăng ký nào
                </p>
                <p class="mt-1 text-sm text-gray-400">
                    Chưa có đơn ở trạng thái này
                </p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="app in filteredApps"
                    :key="app.id"
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start gap-5">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-50"
                        >
                            <span class="text-xl font-bold text-blue-500">{{
                                app.company_name.charAt(0)
                            }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3
                                    class="text-base font-semibold text-gray-900"
                                >
                                    {{ app.company_name }}
                                </h3>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        statusMap[app.status]?.class ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ app.status_label }}
                                </span>
                            </div>
                            <div
                                class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm text-gray-600 md:grid-cols-3"
                            >
                                <span>👤 {{ app.representative_name }}</span>
                                <span>📞 {{ app.phone }}</span>
                                <span v-if="app.email">✉️ {{ app.email }}</span>
                                <span>🧾 MST: {{ app.tax_code }}</span>
                                <span class="col-span-2 md:col-span-1"
                                    >🚐 {{ app.vehicle_count }} xe ·
                                    {{ app.fleet_summary }}</span
                                >
                            </div>
                            <p class="mt-1.5 text-sm text-gray-500">
                                📍 {{ app.address }}
                            </p>
                            <p class="mt-1.5 text-xs text-gray-400">
                                Gửi lúc:
                                {{
                                    new Date(app.created_at).toLocaleString(
                                        'vi-VN',
                                    )
                                }}
                            </p>
                            <p
                                v-if="app.note"
                                class="mt-1 text-xs text-red-500"
                            >
                                Ghi chú: {{ app.note }}
                            </p>

                            <div class="mt-3 flex flex-wrap gap-3">
                                <a
                                    v-if="app.business_license_url"
                                    :href="app.business_license_url"
                                    target="_blank"
                                    class="flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs text-blue-700 transition-colors hover:bg-blue-100"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Giấy phép kinh doanh
                                </a>
                                <a
                                    v-for="(img, i) in app.fleet_images"
                                    :key="i"
                                    :href="img"
                                    target="_blank"
                                    class="flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-1.5 text-xs text-gray-700 transition-colors hover:bg-gray-100"
                                >
                                    🖼️ Ảnh xe {{ i + 1 }}
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="
                                app.status === 'pending' ||
                                app.status === 'contacted'
                            "
                            class="flex shrink-0 gap-2"
                        >
                            <button
                                @click="openApproveApp(app)"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                            >
                                Duyệt &amp; tạo tài khoản
                            </button>
                            <button
                                @click="openRejectApp(app)"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                            >
                                Từ chối
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ═══════════ OPERATORS ═══════════ -->
        <template v-else>
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

            <div v-if="isLoading" class="space-y-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-36 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
                />
            </div>

            <div
                v-else-if="errorMsg"
                class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
            >
                {{ errorMsg }}
                <button
                    @click="loadOperators"
                    class="ml-auto text-sm underline"
                >
                    Thử lại
                </button>
            </div>

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
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"
                    />
                </svg>
                <p class="font-medium text-gray-500">Không có nhà xe nào</p>
                <p class="mt-1 text-sm text-gray-400">
                    Chưa có nhà xe ở trạng thái này
                </p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="op in filtered"
                    :key="op.id"
                    class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start gap-5">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-gray-100"
                        >
                            <span class="text-xl font-bold text-gray-400">{{
                                op.company_name.charAt(0)
                            }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3
                                    class="text-base font-semibold text-gray-900"
                                >
                                    {{ op.company_name }}
                                </h3>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        statusMap[op.status]?.class ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        statusMap[op.status]?.label ?? op.status
                                    }}
                                </span>
                            </div>
                            <div
                                class="mt-2 grid grid-cols-3 gap-3 text-sm text-gray-600"
                            >
                                <span>👤 {{ op.owner_name }}</span>
                                <span>📞 {{ op.phone }}</span>
                                <span>✉️ {{ op.email }}</span>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">
                                Đăng ký:
                                {{
                                    new Date(op.created_at).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}
                            </p>

                            <!-- Đối chiếu đội xe: khai lúc đăng ký vs thực tế đã thêm -->
                            <div
                                v-if="(op.declared_fleet_total ?? 0) > 0"
                                class="mt-2 inline-flex items-center gap-2 rounded-lg px-2.5 py-1 text-xs"
                                :class="
                                    (op.actual_vehicles_count ?? 0) >=
                                    (op.declared_fleet_total ?? 0)
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-amber-50 text-amber-700'
                                "
                            >
                                🚐 Đội xe: đã thêm
                                {{ op.actual_vehicles_count ?? 0 }}/{{
                                    op.declared_fleet_total
                                }}
                                xe khai báo
                                <span class="text-gray-400"
                                    >({{ op.declared_fleet_summary }})</span
                                >
                            </div>

                            <div class="mt-3 flex gap-3">
                                <a
                                    v-if="op.documents?.business_license"
                                    :href="op.documents.business_license"
                                    target="_blank"
                                    class="flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs text-blue-700 transition-colors hover:bg-blue-100"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Giấy phép kinh doanh
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="op.status === 'pending'"
                            class="flex shrink-0 gap-2"
                        >
                            <button
                                @click="openApproveOperator(op)"
                                class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                            >
                                Duyệt
                            </button>
                            <button
                                @click="openRejectOperator(op)"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
                            >
                                Từ chối
                            </button>
                        </div>
                        <div
                            v-else-if="op.status === 'verified'"
                            class="flex shrink-0 gap-2"
                        >
                            <button
                                @click="openResetPassword(op)"
                                class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-50"
                            >
                                Đặt lại mật khẩu
                            </button>
                            <button
                                @click="openRejectOperator(op)"
                                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                            >
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
        <div
            v-if="showApproveModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-1 text-lg font-semibold text-gray-900">
                    {{
                        modalMode === 'application'
                            ? 'Duyệt đơn & tạo tài khoản nhà xe'
                            : 'Xác nhận duyệt nhà xe'
                    }}
                </h3>
                <p class="mb-5 text-sm text-gray-500">{{ selectedName }}</p>

                <div class="mb-5">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                        >Tỷ lệ hoa hồng (%)</label
                    >
                    <input
                        v-model.number="commissionRate"
                        type="number"
                        min="0"
                        max="30"
                        step="0.5"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        Nền tảng thu {{ commissionRate }}% trên mỗi giao dịch
                    </p>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="showApproveModal = false"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="confirmApprove"
                        :disabled="approveLoading"
                        class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-60"
                    >
                        {{
                            approveLoading ? 'Đang xử lý...' : 'Xác nhận duyệt'
                        }}
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
                    {{
                        modalMode === 'application'
                            ? 'Từ chối đơn đăng ký'
                            : 'Từ chối / Đình chỉ nhà xe'
                    }}
                </h3>
                <p class="mb-5 text-sm text-gray-500">{{ selectedName }}</p>

                <div class="mb-5">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                        >Lý do <span class="text-red-500">*</span></label
                    >
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="Nhập lý do..."
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
                        :disabled="rejectLoading || !rejectReason.trim()"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        {{ rejectLoading ? 'Đang xử lý...' : 'Xác nhận' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Reset password modal -->
    <Teleport to="body">
        <div
            v-if="showResetModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <!-- Bước xác nhận -->
                <template v-if="!resetResult">
                    <h3 class="mb-1 text-lg font-semibold text-gray-900">
                        Đặt lại mật khẩu nhà xe
                    </h3>
                    <p class="mb-4 text-sm text-gray-500">{{ selectedName }}</p>
                    <div
                        class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        Hệ thống sẽ tạo mật khẩu tạm mới, gửi SMS cho nhà xe và
                        hiển thị để bạn chuyển trực tiếp. Mật khẩu cũ sẽ không
                        dùng được nữa.
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="showResetModal = false"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Hủy
                        </button>
                        <button
                            @click="confirmReset"
                            :disabled="resetLoading"
                            class="flex-1 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-700 disabled:opacity-60"
                        >
                            {{
                                resetLoading
                                    ? 'Đang xử lý...'
                                    : 'Đặt lại mật khẩu'
                            }}
                        </button>
                    </div>
                </template>

                <!-- Bước kết quả -->
                <template v-else>
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
                        <h3 class="text-lg font-semibold text-gray-900">
                            Đã đặt lại mật khẩu
                        </h3>
                    </div>
                    <p class="mb-4 text-sm text-gray-500">{{ selectedName }}</p>

                    <div
                        class="mb-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Đăng nhập (SĐT):</span>
                            <span class="font-mono font-medium text-gray-900">{{
                                resetResult.phone
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Mật khẩu tạm:</span>
                            <span
                                class="font-mono text-base font-bold tracking-wider text-amber-700"
                                >{{ resetResult.temp_password }}</span
                            >
                        </div>
                    </div>

                    <p class="mb-4 text-xs text-gray-500">
                        SMS đã được gửi (nếu ESMS đã cấu hình). Nếu nhà xe không
                        nhận được, hãy chuyển mật khẩu này trực tiếp —
                        <span class="text-red-500"
                            >mật khẩu sẽ không hiển thị lại sau khi đóng.</span
                        >
                    </p>

                    <div class="flex gap-3">
                        <button
                            @click="copyPassword"
                            class="flex-1 rounded-lg border border-amber-300 px-4 py-2.5 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-50"
                        >
                            {{ copied ? 'Đã sao chép ✓' : 'Sao chép mật khẩu' }}
                        </button>
                        <button
                            @click="showResetModal = false"
                            class="flex-1 rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                        >
                            Đóng
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>
