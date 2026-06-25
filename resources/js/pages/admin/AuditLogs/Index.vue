<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { adminApi } from '@/api/admin.api';

interface UserBrief {
    id: string;
    full_name: string;
    phone: string;
    email: string | null;
}

interface AuditLogDoc {
    id: string;
    user: UserBrief | null;
    action: string;
    model_type: string | null;
    model_id: string | null;
    description: string | null;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

// State
const logs = ref<AuditLogDoc[]>([]);
const loading = ref(true);
const errorMsg = ref('');

// Filters & Pagination
const search = ref('');
const filterAction = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const page = ref(1);
const totalPages = ref(1);
const totalLogs = ref(0);

// Detail Modal
const selectedLog = ref<AuditLogDoc | null>(null);
const showDetailModal = ref(false);

const actionLabels: Record<string, { label: string; bg: string; text: string }> = {
    ban_user: { label: 'Khóa người dùng', bg: 'bg-red-50 border-red-200', text: 'text-red-700' },
    unban_user: { label: 'Mở khóa người dùng', bg: 'bg-green-50 border-green-200', text: 'text-green-700' },
    approve_operator: { label: 'Duyệt nhà xe', bg: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-700' },
    reject_operator: { label: 'Từ chối nhà xe', bg: 'bg-rose-50 border-rose-200', text: 'text-rose-700' },
    suspend_operator: { label: 'Đình chỉ nhà xe', bg: 'bg-amber-50 border-amber-200', text: 'text-amber-700' },
    restore_operator: { label: 'Khôi phục nhà xe', bg: 'bg-teal-50 border-teal-200', text: 'text-teal-700' },
    reset_operator_password: { label: 'Đặt lại MK nhà xe', bg: 'bg-blue-50 border-blue-200', text: 'text-blue-700' },
    approve_partner_application: { label: 'Duyệt đơn đối tác', bg: 'bg-violet-50 border-violet-200', text: 'text-violet-700' },
    reject_partner_application: { label: 'Từ chối đơn đối tác', bg: 'bg-pink-50 border-pink-200', text: 'text-pink-700' },
    approve_driver: { label: 'Duyệt tài xế', bg: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-700' },
    reject_driver: { label: 'Từ chối tài xế', bg: 'bg-rose-50 border-rose-200', text: 'text-rose-700' },
    suspend_driver: { label: 'Đình chỉ tài xế', bg: 'bg-amber-50 border-amber-200', text: 'text-amber-700' },
    reset_driver_password: { label: 'Đặt lại MK tài xế', bg: 'bg-blue-50 border-blue-200', text: 'text-blue-700' },
    create_voucher: { label: 'Tạo voucher', bg: 'bg-indigo-50 border-indigo-200', text: 'text-indigo-700' },
    update_voucher: { label: 'Cập nhật voucher', bg: 'bg-sky-50 border-sky-200', text: 'text-sky-700' },
    toggle_voucher: { label: 'Bật/tắt voucher', bg: 'bg-purple-50 border-purple-200', text: 'text-purple-700' },
    delete_voucher: { label: 'Xóa voucher', bg: 'bg-red-50 border-red-200', text: 'text-red-700' },
    cancel_trip: { label: 'Hủy chuyến đi', bg: 'bg-red-50 border-red-200', text: 'text-red-700' },
    auto_resolve_trips: { label: 'Xử lý chuyến quá giờ', bg: 'bg-slate-50 border-slate-200', text: 'text-slate-700' },
    payout_operator: { label: 'Quyết toán nhà xe', bg: 'bg-lime-50 border-lime-200', text: 'text-lime-700' },
};

function getActionStyle(action: string) {
    return actionLabels[action] ?? { label: action, bg: 'bg-slate-50 border-slate-200', text: 'text-slate-700' };
}

async function fetchLogs() {
    loading.value = true;
    errorMsg.value = '';
    
    const params: Record<string, any> = {
        page: page.value,
        per_page: 20
    };

    if (search.value.trim()) params.search = search.value.trim();
    if (filterAction.value) params.action = filterAction.value;
    if (dateFrom.value) params.date_from = dateFrom.value;
    if (dateTo.value) params.date_to = dateTo.value;

    const { data, meta, error } = await adminApi.getAuditLogs(params);
    loading.value = false;

    if (error) {
        errorMsg.value = error;
        return;
    }

    logs.value = data ?? [];
    totalPages.value = meta?.last_page ?? 1;
    totalLogs.value = meta?.total ?? 0;
}

let searchTimeout: any = null;
function handleSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchLogs();
    }, 300);
}

function handleFilterChange() {
    page.value = 1;
    fetchLogs();
}

function resetFilters() {
    search.value = '';
    filterAction.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    page.value = 1;
    fetchLogs();
}

function changePage(p: number) {
    if (p < 1 || p > totalPages.value) return;
    page.value = p;
    fetchLogs();
}

function openDetail(log: AuditLogDoc) {
    selectedLog.value = log;
    showDetailModal.value = true;
}

function formatJson(val: any): string {
    if (!val) return 'Trống';
    try {
        return JSON.stringify(val, null, 2);
    } catch {
        return String(val);
    }
}

onMounted(() => {
    fetchLogs();
});
</script>

<template>
    <div class="mx-auto max-w-7xl p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900 font-sans">Nhật ký hệ thống (Audit Logs)</h1>
                <p class="mt-0.5 text-xs text-gray-500">Ghi lại toàn bộ thao tác quản trị viên để đối soát và bảo mật.</p>
            </div>
            <button
                @click="fetchLogs"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50"
            >
                Làm mới
            </button>
        </div>

        <!-- Filters Row -->
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/60 bg-white p-4 shadow-sm md:grid-cols-4">
            <!-- Search Keyword -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    @input="handleSearchInput"
                    type="text"
                    placeholder="Tìm theo tên admin, mô tả, SĐT..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-xs outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all font-sans"
                />
            </div>

            <!-- Action Type -->
            <div>
                <select
                    v-model="filterAction"
                    @change="handleFilterChange"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all font-sans text-slate-700"
                >
                    <option value="">Tất cả thao tác</option>
                    <option v-for="(cfg, act) in actionLabels" :key="act" :value="act">
                        {{ cfg.label }}
                    </option>
                </select>
            </div>

            <!-- Date Range From -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 shrink-0 font-medium">Từ ngày:</span>
                <input
                    v-model="dateFrom"
                    @change="handleFilterChange"
                    type="date"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all font-sans text-slate-700"
                />
            </div>

            <!-- Date Range To & Reset -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 shrink-0 font-medium">Đến ngày:</span>
                <input
                    v-model="dateTo"
                    @change="handleFilterChange"
                    type="date"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all font-sans text-slate-700"
                />
                <button
                    @click="resetFilters"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-all"
                    title="Xóa bộ lọc"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 5" :key="i" class="h-16 animate-pulse rounded-xl border border-slate-200 bg-white" />
        </div>

        <div v-else-if="errorMsg" class="flex flex-col items-center justify-center rounded-xl border border-red-150 bg-red-50 py-12 text-center">
            <p class="text-sm font-semibold text-red-700 mb-3">{{ errorMsg }}</p>
            <button @click="fetchLogs" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-red-700 transition-all">Thử lại</button>
        </div>

        <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
            <svg class="mb-3 h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-semibold text-slate-500 font-sans">Không tìm thấy nhật ký hệ thống</p>
            <p class="text-xs text-slate-400 mt-1 font-sans">Vui lòng điều chỉnh lại bộ lọc tìm kiếm.</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-5 py-4">Thời gian</th>
                            <th class="px-5 py-4">Người thực hiện</th>
                            <th class="px-5 py-4">Thao tác</th>
                            <th class="px-5 py-4">Chi tiết hoạt động</th>
                            <th class="px-5 py-4 text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                            <!-- Time -->
                            <td class="px-5 py-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                {{ log.created_at }}
                            </td>
                            <!-- Admin User -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div v-if="log.user" class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600 border border-slate-200/50 uppercase">
                                        {{ log.user.full_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ log.user.full_name }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono">{{ log.user.phone }}</p>
                                    </div>
                                </div>
                                <span v-else class="text-slate-400 italic">Hệ thống tự động</span>
                            </td>
                            <!-- Action Badge -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-lg border px-2 py-1 text-[10px] font-semibold tracking-wide',
                                        getActionStyle(log.action).bg,
                                        getActionStyle(log.action).text
                                    ]"
                                >
                                    {{ getActionStyle(log.action).label }}
                                </span>
                            </td>
                            <!-- Description -->
                            <td class="px-5 py-4 max-w-md">
                                <p class="line-clamp-2 text-slate-700 leading-relaxed font-sans">{{ log.description || '—' }}</p>
                            </td>
                            <!-- View Detail Button -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <button
                                    @click="openDetail(log)"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50"
                                >
                                    Chi tiết
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="flex items-center justify-between border-t border-slate-200 px-6 py-4 bg-slate-50/50">
                <span class="text-xs text-slate-500 font-sans">
                    Hiển thị <b>{{ logs.length }}</b> / <b>{{ totalLogs }}</b> bản ghi
                </span>
                <div class="flex items-center gap-1.5">
                    <button
                        @click="changePage(page - 1)"
                        :disabled="page === 1"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-white"
                    >
                        Trước
                    </button>
                    <span class="text-xs font-medium text-slate-600 font-sans">
                        Trang {{ page }} / {{ totalPages }}
                    </span>
                    <button
                        @click="changePage(page + 1)"
                        :disabled="page === totalPages"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-white"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </div>

        <!-- Detail Modal Dialog -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedLog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
                <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[85vh] overflow-hidden transition-all duration-300">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 p-5">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Chi tiết nhật ký hoạt động</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">ID: {{ selectedLog.id }}</p>
                        </div>
                        <button
                            @click="showDetailModal = false"
                            class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Overview Grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Người thực hiện</span>
                                <p class="text-xs font-bold text-slate-700 mt-1">{{ selectedLog.user?.full_name ?? 'Hệ thống tự động' }}</p>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ selectedLog.user?.phone ?? 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Thao tác & Thời gian</span>
                                <p class="text-xs font-bold text-slate-700 mt-1">
                                    <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold mr-1.5" :class="[getActionStyle(selectedLog.action).bg, getActionStyle(selectedLog.action).text]">
                                        {{ getActionStyle(selectedLog.action).label }}
                                    </span>
                                </p>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ selectedLog.created_at }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Kết nối (IP / Browser)</span>
                                <p class="text-xs font-bold text-slate-700 mt-1 font-mono">{{ selectedLog.ip_address ?? '—' }}</p>
                                <p class="text-[9px] text-slate-400 truncate mt-0.5" :title="selectedLog.user_agent ?? ''">{{ selectedLog.user_agent ?? '—' }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="rounded-xl border border-slate-150 bg-slate-50 p-4">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Nội dung hoạt động</span>
                            <p class="text-xs text-slate-700 leading-relaxed font-medium mt-1 font-sans">{{ selectedLog.description }}</p>
                        </div>

                        <!-- Associated Resource -->
                        <div v-if="selectedLog.model_type" class="rounded-xl border border-slate-200 p-4 space-y-2.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Đối tượng liên quan</span>
                            <div class="flex flex-wrap items-center gap-y-1.5 gap-x-6 text-xs text-slate-600">
                                <div>
                                    <span class="text-slate-400 font-medium">Model:</span>
                                    <code class="ml-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-mono">{{ selectedLog.model_type }}</code>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-medium">Model ID:</span>
                                    <code class="ml-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-mono">{{ selectedLog.model_id }}</code>
                                </div>
                            </div>
                        </div>

                        <!-- Values Comparison (Old vs New Diff) -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Old Values -->
                            <div class="flex flex-col h-[280px]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-400"></span> Dữ liệu cũ (Trước thay đổi)
                                </span>
                                <div class="flex-1 overflow-auto rounded-xl border border-red-100 bg-red-50/20 p-4 font-mono text-[10px] leading-relaxed text-red-950 whitespace-pre">
                                    {{ formatJson(selectedLog.old_values) }}
                                </div>
                            </div>
                            <!-- New Values -->
                            <div class="flex flex-col h-[280px]">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-green-400"></span> Dữ liệu mới (Sau thay đổi)
                                </span>
                                <div class="flex-1 overflow-auto rounded-xl border border-green-100 bg-green-50/20 p-4 font-mono text-[10px] leading-relaxed text-green-950 whitespace-pre">
                                    {{ formatJson(selectedLog.new_values) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="border-t border-slate-100 p-4 flex justify-end">
                        <button
                            @click="showDetailModal = false"
                            class="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow transition-all hover:bg-slate-800"
                        >
                            Đóng cửa sổ
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
