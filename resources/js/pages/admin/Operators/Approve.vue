<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { adminApi } from '@/api/admin.api';
import { useCan } from '@/composables/useCan';
const { can } = useCan();


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
    tax_code?: string;
    business_license?: string;
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
const searchQuery = ref('');

// Shared modal state (dùng chung cho cả 2 luồng)
const modalMode = ref<'operator' | 'application'>('operator');
const selectedId = ref('');
const selectedName = ref('');
const selectedStatus = ref('');

const showApproveModal = ref(false);
const commissionRate = ref(10);
const approveLoading = ref(false);

const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectLoading = ref(false);

// Đặt lại mật khẩu nhà xe
const showResetModal = ref(false);
const resetLoading = ref(false);
const resetResult = ref<{ phone: string } | null>(null);

// ─── Operator Detail modal ────────────────────────────────────────────────
const showDetailModal = ref(false);
const detailLoading = ref(false);
const detailOperator = ref<any>(null);
const detailTab = ref<'info' | 'vehicles' | 'drivers'>('info');

async function openDetailOperator(id: string) {
    selectedId.value = id;
    detailOperator.value = null;
    detailTab.value = 'info';
    showDetailModal.value = true;
    detailLoading.value = true;
    const { data, error } = await adminApi.getOperator(id);
    detailLoading.value = false;
    if (error) {
        alert(error);
        showDetailModal.value = false;
        return;
    }
    detailOperator.value = data;
}

const setActiveTab = (tab: 'all' | 'suspended') => {
    activeTab.value = tab;
};

const setAppTab = (tab: 'all' | 'pending' | 'approved' | 'rejected') => {
    appTab.value = tab;
};

const setView = (newView: 'operators' | 'applications') => {
    view.value = newView;
    searchQuery.value = '';
};

const setDetailTab = (tab: 'info' | 'vehicles' | 'drivers') => {
    detailTab.value = tab;
};

const tabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'suspended', label: 'Đình chỉ' },
] as const;

const appTabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ xử lý' },
    { key: 'approved', label: 'Đã duyệt' },
    { key: 'rejected', label: 'Từ chối' },
] as const;

const statusMap: Record<string, { label: string; class: string }> = {
    pending: { label: 'Chờ duyệt', class: 'bg-yellow-100 text-yellow-700' },
    verified: { label: 'Đã duyệt', class: 'bg-green-100 text-green-700' },
    suspended: { label: 'Đình chỉ', class: 'bg-red-100 text-red-700' },
    rejected: { label: 'Từ chối', class: 'bg-gray-100 text-gray-600' },
    approved: { label: 'Đã duyệt', class: 'bg-green-100 text-green-700' },
    contacted: { label: 'Đã liên hệ', class: 'bg-blue-100 text-blue-700' },
};

const filtered = computed(() => {
    let result = operators.value;
    if (activeTab.value !== 'all') {
        result = result.filter((o) => o.status === activeTab.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(
            (o) =>
                (o.company_name || '').toLowerCase().includes(q) ||
                (o.owner_name || '').toLowerCase().includes(q) ||
                (o.phone || '').includes(q) ||
                (o.email || '').toLowerCase().includes(q) ||
                (o.tax_code || '').toLowerCase().includes(q) ||
                (o.business_license || '').toLowerCase().includes(q)
        );
    }
    return result;
});

const filteredApps = computed(() => {
    let result = applications.value;
    if (appTab.value !== 'all') {
        result = result.filter((a) => a.status === appTab.value);
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(
            (a) =>
                (a.company_name || '').toLowerCase().includes(q) ||
                (a.representative_name || '').toLowerCase().includes(q) ||
                (a.phone || '').includes(q) ||
                (a.email || '').toLowerCase().includes(q) ||
                (a.tax_code || '').includes(q)
        );
    }
    return result;
});

const pendingAppCount = computed(
    () => applications.value.filter((a) => a.status === 'pending').length,
);

async function loadOperators() {
    isLoading.value = true;
    errorMsg.value = '';
    const params: Record<string, any> = {};
    if (activeTab.value !== 'all') {
        params.status = activeTab.value;
    }
    if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim();
    }
    const { data, error } = await adminApi.getOperators(params);
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    const raw = (data as any[]) ?? [];
    operators.value = raw.map((o) => ({
        ...o,
        owner_name: o.user?.full_name ?? '',
        phone: o.user?.phone ?? o.phone ?? '',
        email: o.user?.email ?? o.email ?? '',
    }));
    isLoading.value = false;
}

async function loadApplications() {
    appLoading.value = true;
    appError.value = '';
    const params: Record<string, any> = {};
    if (appTab.value !== 'all') {
        params.status = appTab.value;
    }
    if (searchQuery.value.trim()) {
        params.search = searchQuery.value.trim();
    }
    const { data, error } = await adminApi.getPartnerApplications(params);
    if (error) {
        appError.value = error;
        appLoading.value = false;
        return;
    }
    applications.value = (data as PartnerApp[]) ?? [];
    appLoading.value = false;
}

let searchTimeout: any = null;
function triggerSearch() {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (view.value === 'applications') {
        loadApplications();
    } else {
        loadOperators();
    }
}

watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        triggerSearch();
    }, 300);
});

watch(activeTab, () => {
    loadOperators();
});

watch(appTab, () => {
    loadApplications();
});

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
    selectedStatus.value = op.status;
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
    
    let error;
    if (modalMode.value === 'application') {
        const res = await adminApi.rejectPartnerApplication(selectedId.value, {
            reason: rejectReason.value,
        });
        error = res.error;
    } else {
        const isSuspend = selectedStatus.value === 'verified';
        if (isSuspend) {
            const res = await adminApi.suspendOperator(selectedId.value, {
                reason: rejectReason.value,
            });
            error = res.error;
        } else {
            const res = await adminApi.rejectOperator(selectedId.value, {
                reason: rejectReason.value,
            });
            error = res.error;
        }
    }
    
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

async function restoreOperator(op: OperatorDoc) {
    if (
        !confirm(
            `Bạn có chắc chắn muốn khôi phục hoạt động cho nhà xe ${op.company_name}?`
        )
    )
        return;
    const { error } = await adminApi.restoreOperator(op.id);
    if (error) {
        alert(error);
        return;
    }
    await loadOperators();
}

function openResetPassword(op: OperatorDoc) {
    selectedId.value = op.id;
    selectedName.value = op.company_name;
    resetResult.value = null;
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
        <div class="mb-6 border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button
                    @click="setView('applications')"
                    :class="[
                        'shrink-0 border-b-2 py-4 px-1 text-sm font-semibold transition-all flex items-center gap-2',
                        view === 'applications'
                            ? 'border-amber-500 text-amber-600'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                    ]"
                >
                    Đơn đăng ký đối tác
                    <span
                        v-if="pendingAppCount > 0"
                        :class="[
                            'inline-flex h-5 min-w-5 items-center justify-center rounded-full text-xs font-bold px-1.5',
                            view === 'applications'
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-slate-100 text-slate-600'
                        ]"
                    >
                        {{ pendingAppCount }}
                    </span>
                </button>
                <button
                    @click="setView('operators')"
                    :class="[
                        'shrink-0 border-b-2 py-4 px-1 text-sm font-semibold transition-all',
                        view === 'operators'
                            ? 'border-amber-500 text-amber-600'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                    ]"
                >
                    Nhà xe đã có tài khoản
                </button>
            </nav>
        </div>

        <!-- ═══════════ PARTNER APPLICATIONS ═══════════ -->
        <template v-if="view === 'applications'">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-fit gap-1 rounded-lg bg-slate-100 p-0.5 border border-slate-200/50">
                    <button
                        v-for="tab in appTabs"
                        :key="tab.key"
                        @click="setAppTab(tab.key)"
                        :class="[
                            'rounded-md px-3 py-1 text-xs font-semibold transition-all',
                            appTab === tab.key
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
                <!-- Search -->
                <div class="relative w-full max-w-xs">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="searchQuery"
                        @keydown.enter.prevent="triggerSearch"
                        type="text"
                        placeholder="Tìm theo tên, SĐT, MST..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pl-9 pr-4 text-xs placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition-all shadow-sm font-sans"
                    />
                </div>
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
                                (app.status === 'pending' ||
                                    app.status === 'contacted') &&
                                can('partner_applications.review')
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
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-fit gap-1 rounded-lg bg-slate-100 p-0.5 border border-slate-200/50">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="setActiveTab(tab.key)"
                        :class="[
                            'rounded-md px-3 py-1 text-xs font-semibold transition-all',
                            activeTab === tab.key
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>
                <!-- Search -->
                <div class="relative w-full max-w-xs">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="searchQuery"
                        @keydown.enter.prevent="triggerSearch"
                        type="text"
                        placeholder="Tìm theo tên, SĐT, email..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pl-9 pr-4 text-xs placeholder-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition-all shadow-sm font-sans"
                    />
                </div>
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

                        <div class="flex shrink-0 gap-2">
                            <button
                                @click="openDetailOperator(op.id)"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Chi tiết
                            </button>
                            <template v-if="op.status === 'pending' && can('operators.review')">
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
                            </template>
                            <template v-else-if="op.status === 'verified'">
                                <button
                                    v-if="can('operators.reset_password')"
                                    @click="openResetPassword(op)"
                                    class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-50"
                                >
                                    Đặt lại mật khẩu
                                </button>
                                <button
                                    v-if="can('operators.suspend')"
                                    @click="openRejectOperator(op)"
                                    class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                                >
                                    Đình chỉ
                                </button>
                            </template>
                            <template v-else-if="op.status === 'suspended'">
                                <button
                                    v-if="can('operators.suspend')"
                                    @click="restoreOperator(op)"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                                >
                                    Khôi phục
                                </button>
                            </template>
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
                        Hệ thống sẽ tạo mật khẩu mới và gửi cho nhà xe qua SMS.
                        Để bảo đảm quyền lợi nhà xe, admin không xem được mật
                        khẩu. Mật khẩu cũ sẽ không dùng được nữa.
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
                    </div>

                    <p class="mb-4 text-xs text-gray-500">
                        Mật khẩu mới đã được gửi cho nhà xe qua SMS. Nếu nhà xe
                        không nhận được, hãy bấm "Đặt lại mật khẩu" để gửi lại.
                    </p>

                    <div class="flex gap-3">
                        <button
                            @click="showResetModal = false"
                            class="w-full rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                        >
                            Đóng
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>

    <!-- Operator Detail modal -->
    <Teleport to="body">
        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl flex flex-col max-h-[85vh] overflow-hidden">
                <!-- Header -->
                <div class="flex items-start justify-between border-b border-slate-100 p-5 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 overflow-hidden rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-center">
                            <img
                                v-if="detailOperator?.logo_url"
                                :src="detailOperator.logo_url"
                                alt="Logo"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="text-xl font-bold text-red-500">
                                {{ detailOperator?.company_name?.charAt(0) || 'O' }}
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                {{ detailOperator?.company_name || 'Đang tải...' }}
                            </h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    v-if="detailOperator?.status"
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        statusMap[detailOperator.status]?.class ?? 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ statusMap[detailOperator.status]?.label ?? detailOperator.status }}
                                </span>
                                <span class="text-xs text-gray-400">Chiết Khấu: {{ detailOperator?.commission_rate ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="showDetailModal = false"
                        class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Tabs selector -->
                <div class="flex border-b border-slate-100 px-5 shrink-0">
                    <button
                        v-for="t in ([
                            { key: 'info', label: 'Thông tin chung' },
                            { key: 'vehicles', label: `Đội xe (${detailOperator?.vehicles?.length ?? 0})` },
                            { key: 'drivers', label: `Tài xế (${detailOperator?.drivers?.length ?? 0})` }
                        ] as const)"
                        :key="t.key"
                        @click="setDetailTab(t.key)"
                        :class="[
                            'border-b-2 px-4 py-3 text-sm font-semibold transition-colors',
                            detailTab === t.key
                                ? 'border-red-600 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="detailLoading" class="flex flex-col items-center py-20 text-gray-400">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-red-600 border-t-transparent mb-3" />
                        <p class="text-sm">Đang tải thông tin chi tiết...</p>
                    </div>
                    <div v-else-if="detailOperator">
                        <!-- TAB: Info -->
                        <div v-if="detailTab === 'info'" class="space-y-6">
                            <!-- Description -->
                            <div v-if="detailOperator.description" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">Giới thiệu</h4>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ detailOperator.description }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <!-- Contact Info -->
                                <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                                    <h4 class="font-bold text-gray-900 text-sm border-b border-slate-100 pb-2">👤 Thông tin liên hệ</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Người đại diện:</span>
                                            <span class="font-medium text-gray-800">{{ detailOperator.user?.full_name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Số điện thoại:</span>
                                            <span class="font-mono font-medium text-gray-800">{{ detailOperator.user?.phone }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Email liên lạc:</span>
                                            <span class="font-medium text-gray-800">{{ detailOperator.user?.email || '—' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Account -->
                                <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                                    <h4 class="font-bold text-gray-900 text-sm border-b border-slate-100 pb-2">💳 Tài khoản nhận tiền</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Ngân hàng:</span>
                                            <span class="font-medium text-gray-800">{{ detailOperator.bank_name || '—' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Số tài khoản:</span>
                                            <span class="font-mono font-bold text-slate-800">{{ detailOperator.bank_account || '—' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-400">Chủ tài khoản:</span>
                                            <span class="font-semibold text-gray-800 uppercase">{{ detailOperator.bank_account_name || '—' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Legal Info -->
                                <div class="rounded-xl border border-slate-200 p-4 space-y-3 sm:col-span-2">
                                    <h4 class="font-bold text-gray-900 text-sm border-b border-slate-100 pb-2">📜 Hồ sơ pháp lý</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-400 block mb-1">Mã số thuế:</span>
                                            <span class="font-mono font-semibold text-gray-800 block bg-gray-50 p-2.5 rounded border border-slate-100 font-medium">
                                                {{ detailOperator.tax_code || 'Chưa cập nhật' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block mb-1">Giấy phép kinh doanh:</span>
                                            <span class="font-mono font-semibold text-gray-800 block bg-gray-50 p-2.5 rounded border border-slate-100 font-medium">
                                                {{ detailOperator.business_license || 'Chưa cập nhật' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: Vehicles -->
                        <div v-else-if="detailTab === 'vehicles'">
                            <div v-if="!detailOperator.vehicles || detailOperator.vehicles.length === 0" class="text-center py-10 text-gray-400">
                                <p class="text-sm">Nhà xe chưa cập nhật thông tin xe nào.</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="v in detailOperator.vehicles"
                                    :key="v.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 p-4 bg-slate-50 transition-colors hover:bg-slate-100"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-xl font-bold text-red-500">
                                            🚐
                                        </div>
                                        <div>
                                            <h5 class="font-mono font-bold text-gray-900 text-sm">{{ v.plate_number }}</h5>
                                            <p class="text-xs text-gray-500 mt-0.5 font-medium">
                                                Loại: {{ v.vehicle_type }} · {{ v.seat_count }} chỗ · Năm SX: {{ v.manufacture_year || '—' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold border',
                                            v.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-slate-100 text-slate-500 border-slate-200'
                                        ]"
                                    >
                                        {{ v.is_active ? 'Hoạt động' : 'Tạm dừng' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: Drivers -->
                        <div v-else-if="detailTab === 'drivers'">
                            <div v-if="!detailOperator.drivers || detailOperator.drivers.length === 0" class="text-center py-10 text-gray-400">
                                <p class="text-sm">Nhà xe chưa cập nhật thông tin tài xế nào.</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="d in detailOperator.drivers"
                                    :key="d.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 p-4 bg-slate-50 transition-colors hover:bg-slate-100"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 overflow-hidden rounded-full bg-slate-200 flex items-center justify-center">
                                            <img
                                                v-if="d.photo_url"
                                                :src="d.photo_url"
                                                alt="Avatar"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="text-sm font-bold text-gray-400">
                                                {{ d.full_name?.charAt(0) }}
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-gray-900 text-sm">{{ d.full_name }}</h5>
                                            <p class="text-xs text-gray-500 mt-0.5 font-medium">
                                                SĐT: <span class="font-mono">{{ d.phone }}</span> · GPLX Hạng: {{ d.documents?.driver_license_class || '—' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            statusMap[d.status]?.class ?? 'bg-gray-100 text-gray-600'
                                        ]"
                                    >
                                        {{ statusMap[d.status]?.label ?? d.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-slate-100 p-4 shrink-0 flex justify-end">
                    <button
                        @click="showDetailModal = false"
                        class="rounded-lg bg-gray-800 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
