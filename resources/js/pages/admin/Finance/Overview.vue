<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { adminApi } from '@/api/admin.api';

type TabKey = 'overview' | 'transactions' | 'commissions' | 'refunds';

interface FinanceSummary {
    total_revenue: number;
    platform_held: number;
    cash_collected: number;
    total_commission: number;
    pending_settlement: number;
    operator_debt: number;
    total_refunds: number;
}

interface Commission {
    id: string;
    operator_name: string;
    period: string;
    revenue: number;
    cash_gross: number;
    commission_rate: number;
    commission: number;
    cash_commission: number;
    net_amount: number;
    status: string;
    bank_info: string;
}

interface Transaction {
    id: string;
    type: string;
    amount: number;
    booking_code: string;
    customer: string;
    operator: string;
    created_at: string;
}

interface Refund {
    id: string;
    amount: number;
    refund_amount: number;
    method: string;
    refunded_at: string | null;
    created_at: string;
    booking?: {
        booking_code?: string;
        user?: { full_name?: string };
    } | null;
}

const activeTab = ref<TabKey>('overview');
const summary = ref<FinanceSummary | null>(null);
const commissions = ref<Commission[]>([]);
const transactions = ref<Transaction[]>([]);
const refunds = ref<Refund[]>([]);
const refundsLoading = ref(false);
const refundsLoaded = ref(false);
const isLoading = ref(true);
const errorMsg = ref('');

// Payout modal
const showPayoutModal = ref(false);
const selectedCommission = ref<Commission | null>(null);
const payoutLoading = ref(false);

const tabs: { key: TabKey; label: string }[] = [
    { key: 'overview', label: 'Tổng quan' },
    { key: 'transactions', label: 'Giao dịch' },
    { key: 'commissions', label: 'Quyết toán nhà xe' },
    { key: 'refunds', label: 'Hoàn tiền' },
];

const commissionStatusMap: Record<string, { label: string; class: string }> = {
    pending: {
        label: 'Chờ quyết toán',
        class: 'bg-yellow-100 text-yellow-700',
    },
    paid: { label: 'Đã tất toán', class: 'bg-green-100 text-green-700' },
    receivable: { label: 'Nhà xe nợ NT', class: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Đã hủy', class: 'bg-gray-100 text-gray-600' },
};

const transactionTypeMap: Record<string, { label: string; class: string }> = {
    booking: { label: 'Đặt vé', class: 'bg-blue-100 text-blue-700' },
    refund: { label: 'Hoàn tiền', class: 'bg-orange-100 text-orange-700' },
    topup: { label: 'Nạp tiền', class: 'bg-green-100 text-green-700' },
    withdraw: { label: 'Rút tiền', class: 'bg-red-100 text-red-700' },
};

const paymentMethodMap: Record<string, string> = {
    momo: 'MoMo',
    vnpay: 'VNPay',
    zalopay: 'ZaloPay',
    wallet: 'Ví XeGhep',
    cash: 'Tiền mặt',
};

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(v);
}

async function loadData() {
    isLoading.value = true;
    errorMsg.value = '';

    const [overviewRes, commissionsRes] = await Promise.all([
        adminApi.getFinanceOverview(),
        adminApi.getCommissions(),
    ]);

    if (overviewRes.error) {
        errorMsg.value = overviewRes.error;
        isLoading.value = false;
        return;
    }
    summary.value =
        (overviewRes.data as { summary?: FinanceSummary } | null)?.summary ??
        null;
    commissions.value = (commissionsRes.data as Commission[]) ?? [];
    isLoading.value = false;
}

async function loadTransactions() {
    const { data, error } = await adminApi.getFinanceTransactions();
    if (!error) transactions.value = (data as Transaction[]) ?? [];
}

async function loadRefunds() {
    refundsLoading.value = true;
    const { data, error } = await adminApi.getFinanceRefunds();
    refundsLoading.value = false;
    refundsLoaded.value = true;
    if (!error) refunds.value = (data as Refund[]) ?? [];
}

async function openPayout(c: Commission) {
    selectedCommission.value = c;
    showPayoutModal.value = true;
}

async function confirmPayout() {
    if (!selectedCommission.value) return;
    payoutLoading.value = true;
    const { error } = await adminApi.createPayout({
        commission_id: selectedCommission.value.id,
    });
    payoutLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    showPayoutModal.value = false;
    await loadData();
}

watch(activeTab, (tab) => {
    if (tab === 'transactions' && transactions.value.length === 0)
        loadTransactions();
    if (tab === 'refunds' && !refundsLoaded.value) loadRefunds();
});

onMounted(loadData);
</script>

<template>
    <div>
        <h1 class="mb-6 text-xl font-bold text-gray-900">
            Tài chính & Quyết toán
        </h1>

        <!-- Tabs -->
        <div class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
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
            <div class="grid grid-cols-3 gap-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="h-28 animate-pulse rounded-xl border border-slate-200 bg-white p-5"
                />
            </div>
        </div>

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            {{ errorMsg }}
            <button @click="loadData" class="ml-auto text-sm underline">
                Thử lại
            </button>
        </div>

        <template v-else>
            <!-- Overview tab -->
            <div v-if="activeTab === 'overview'">
                <!-- Summary cards -->
                <div class="mb-6 grid grid-cols-3 gap-4">
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >
                            Tổng doanh thu (GMV)
                        </p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ fmt(summary?.total_revenue ?? 0) }}
                        </p>
                        <div class="mt-2 space-y-0.5 text-xs">
                            <p class="text-gray-500">
                                Nền tảng giữ (online):
                                <span class="font-medium text-gray-700">{{
                                    fmt(summary?.platform_held ?? 0)
                                }}</span>
                            </p>
                            <p class="text-gray-500">
                                Tiền mặt (tài xế thu):
                                <span class="font-medium text-amber-600">{{
                                    fmt(summary?.cash_collected ?? 0)
                                }}</span>
                            </p>
                        </div>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >
                            Hoa hồng thu được
                        </p>
                        <p class="text-2xl font-bold text-red-600">
                            {{ fmt(summary?.total_commission ?? 0) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Trên toàn bộ vé (online + tiền mặt)
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase"
                        >
                            Chờ quyết toán
                        </p>
                        <p class="text-2xl font-bold text-orange-500">
                            {{ fmt(summary?.pending_settlement ?? 0) }}
                        </p>
                        <p
                            v-if="(summary?.operator_debt ?? 0) > 0"
                            class="mt-1 text-xs font-medium text-red-600"
                        >
                            Nhà xe nợ nền tảng:
                            {{ fmt(summary?.operator_debt ?? 0) }}
                        </p>
                        <p v-else class="mt-1 text-xs text-gray-400">
                            Cần thanh toán cho nhà xe
                        </p>
                    </div>
                </div>

                <!-- Bar chart placeholder (revenue by operator) -->
                <div
                    class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Doanh thu theo nhà xe
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="c in commissions.slice(0, 5)"
                            :key="c.id"
                            class="flex items-center gap-3"
                        >
                            <span class="w-40 truncate text-sm text-gray-600">{{
                                c.operator_name
                            }}</span>
                            <div class="h-2 flex-1 rounded-full bg-gray-100">
                                <div
                                    class="h-2 rounded-full bg-red-500 transition-all"
                                    :style="{
                                        width:
                                            commissions.length > 0
                                                ? Math.round(
                                                      (c.revenue /
                                                          Math.max(
                                                              ...commissions.map(
                                                                  (x) =>
                                                                      x.revenue,
                                                              ),
                                                          )) *
                                                          100,
                                                  ) + '%'
                                                : '0%',
                                    }"
                                />
                            </div>
                            <span
                                class="w-28 text-right text-sm font-medium text-gray-900"
                                >{{ fmt(c.revenue) }}</span
                            >
                        </div>
                        <div
                            v-if="commissions.length === 0"
                            class="py-4 text-center text-sm text-gray-400"
                        >
                            Chưa có dữ liệu
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions tab -->
            <div v-else-if="activeTab === 'transactions'">
                <div
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Mã GD
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Loại
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Số tiền
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Mã vé
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Khách hàng
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Thời gian
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="transactions.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-gray-400"
                                    >
                                        Không có giao dịch nào
                                    </td>
                                </tr>
                                <tr
                                    v-for="t in transactions"
                                    :key="t.id"
                                    class="transition-colors hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-gray-500"
                                    >
                                        {{ t.id.substring(0, 8) }}...
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                transactionTypeMap[t.type]
                                                    ?.class ??
                                                    'bg-gray-100 text-gray-600',
                                            ]"
                                        >
                                            {{
                                                transactionTypeMap[t.type]
                                                    ?.label ?? t.type
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium text-gray-900"
                                    >
                                        {{ fmt(t.amount) }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ t.booking_code }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ t.customer }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{
                                            new Date(
                                                t.created_at,
                                            ).toLocaleString('vi-VN')
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Commissions/Settlement tab -->
            <div v-else-if="activeTab === 'commissions'">
                <div
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Nhà xe
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Kỳ quyết toán
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Doanh thu
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Hoa hồng
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Số tiền CK
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Trạng thái
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Hành động
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="commissions.length === 0">
                                    <td
                                        colspan="7"
                                        class="px-4 py-12 text-center text-gray-400"
                                    >
                                        Không có dữ liệu quyết toán
                                    </td>
                                </tr>
                                <tr
                                    v-for="c in commissions"
                                    :key="c.id"
                                    class="transition-colors hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-medium text-gray-900"
                                    >
                                        {{ c.operator_name }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ c.period }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-gray-900"
                                    >
                                        {{ fmt(c.revenue) }}
                                        <span
                                            v-if="c.cash_gross > 0"
                                            class="block text-xs text-amber-600"
                                            >TM: {{ fmt(c.cash_gross) }}</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-red-600"
                                    >
                                        {{ fmt(c.commission) }}
                                        <span class="text-xs text-gray-400"
                                            >({{ c.commission_rate }}%)</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-semibold"
                                        :class="
                                            c.net_amount < 0
                                                ? 'text-red-600'
                                                : 'text-gray-900'
                                        "
                                    >
                                        {{ fmt(c.net_amount) }}
                                        <span
                                            v-if="c.net_amount < 0"
                                            class="block text-xs text-red-500"
                                            >nhà xe nợ NT</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                commissionStatusMap[c.status]
                                                    ?.class ??
                                                    'bg-gray-100 text-gray-600',
                                            ]"
                                        >
                                            {{
                                                commissionStatusMap[c.status]
                                                    ?.label ?? c.status
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="c.status === 'pending'"
                                            @click="openPayout(c)"
                                            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700"
                                        >
                                            Thanh toán
                                        </button>
                                        <span
                                            v-else
                                            class="text-xs text-gray-400"
                                            >—</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Refunds tab -->
            <div v-else-if="activeTab === 'refunds'">
                <!-- Loading -->
                <div v-if="refundsLoading" class="space-y-2">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="h-14 animate-pulse rounded-lg bg-gray-100"
                    />
                </div>

                <!-- Empty -->
                <div
                    v-else-if="refunds.length === 0"
                    class="rounded-xl border border-slate-200 bg-white p-12 text-center text-gray-400 shadow-sm"
                >
                    <svg
                        class="mx-auto mb-3 h-12 w-12"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                        />
                    </svg>
                    <p class="font-medium">Chưa có giao dịch hoàn tiền</p>
                    <p class="mt-1 text-sm">
                        Dữ liệu sẽ hiển thị sau khi có giao dịch hoàn tiền
                    </p>
                </div>

                <!-- Table -->
                <div
                    v-else
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Mã GD
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Mã vé
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Khách hàng
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Số tiền hoàn
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Phương thức
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                                    >
                                        Thời gian hoàn
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="r in refunds"
                                    :key="r.id"
                                    class="transition-colors hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-xs text-gray-500"
                                    >
                                        {{ r.id.substring(0, 8) }}...
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ r.booking?.booking_code ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ r.booking?.user?.full_name ?? '—' }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium text-orange-600"
                                    >
                                        {{ fmt(r.refund_amount || r.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            paymentMethodMap[r.method] ??
                                            r.method
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{
                                            r.refunded_at
                                                ? new Date(
                                                      r.refunded_at,
                                                  ).toLocaleString('vi-VN')
                                                : '—'
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Payout confirmation modal -->
    <Teleport to="body">
        <div
            v-if="showPayoutModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-1 text-lg font-semibold text-gray-900">
                    Xác nhận thanh toán quyết toán
                </h3>
                <div
                    v-if="selectedCommission"
                    class="my-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm"
                >
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nhà xe:</span>
                        <span class="font-medium text-gray-900">{{
                            selectedCommission.operator_name
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Kỳ quyết toán:</span>
                        <span class="font-medium text-gray-900">{{
                            selectedCommission.period
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Số tiền chuyển khoản:</span>
                        <span class="text-base font-bold text-red-600">{{
                            fmt(selectedCommission.net_amount)
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tài khoản nhận:</span>
                        <span class="font-mono text-xs text-gray-700">{{
                            selectedCommission.bank_info || 'Chưa có thông tin'
                        }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="showPayoutModal = false"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="confirmPayout"
                        :disabled="payoutLoading"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        {{
                            payoutLoading
                                ? 'Đang xử lý...'
                                : 'Xác nhận thanh toán'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
