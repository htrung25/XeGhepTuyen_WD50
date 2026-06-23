<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { adminApi } from '@/api/admin.api';

interface Voucher {
    id: string;
    code: string;
    discount_type: 'percent' | 'fixed';
    discount_value: number;
    min_order: number;
    max_discount: number;
    used_count: number;
    usage_limit: number;
    valid_from: string;
    valid_until: string;
    operator_name: string | null;
    is_active: boolean;
}

const vouchers = ref<Voucher[]>([]);
const isLoading = ref(true);
const errorMsg = ref('');
const showModal = ref(false);
const editingVoucher = ref<Voucher | null>(null);
const saveLoading = ref(false);
const successMsg = ref('');

const defaultForm = () => ({
    code: '',
    discount_type: 'percent' as 'percent' | 'fixed',
    discount_value: 10,
    min_order: 50000,
    max_discount: 100000,
    usage_limit: 100,
    valid_from: '',
    valid_until: '',
    operator_id: '',
});

const form = ref(defaultForm());

const stats = computed(() => ({
    active: vouchers.value.filter((v) => v.is_active).length,
    used_today: vouchers.value.reduce((sum, v) => sum + v.used_count, 0),
    total: vouchers.value.length,
}));

function fmt(v: number) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(v);
}

async function loadVouchers() {
    isLoading.value = true;
    errorMsg.value = '';
    const { data, error } = await adminApi.getVouchers();
    if (error) {
        errorMsg.value = error;
        isLoading.value = false;
        return;
    }
    vouchers.value = data ?? [];
    isLoading.value = false;
}

function openCreate() {
    editingVoucher.value = null;
    form.value = defaultForm();
    showModal.value = true;
}

function openEdit(v: Voucher) {
    editingVoucher.value = v;
    form.value = {
        code: v.code,
        discount_type: v.discount_type,
        discount_value: v.discount_value,
        min_order: v.min_order,
        max_discount: v.max_discount,
        usage_limit: v.usage_limit,
        valid_from: v.valid_from.split('T')[0],
        valid_until: v.valid_until.split('T')[0],
        operator_id: '',
    };
    showModal.value = true;
}

async function saveVoucher() {
    if (!form.value.code.trim()) return;
    saveLoading.value = true;
    const payload = { ...form.value };
    const { error } = editingVoucher.value
        ? await adminApi.updateVoucher(editingVoucher.value.id, payload)
        : await adminApi.createVoucher(payload);
    saveLoading.value = false;
    if (error) {
        alert(error);
        return;
    }
    showModal.value = false;
    successMsg.value = editingVoucher.value
        ? 'Cập nhật voucher thành công'
        : 'Tạo voucher thành công';
    setTimeout(() => (successMsg.value = ''), 3000);
    await loadVouchers();
}

async function toggleVoucher(v: Voucher) {
    const { error } = await adminApi.toggleVoucher(v.id);
    if (error) {
        alert(error);
        return;
    }
    await loadVouchers();
}

async function deleteVoucher(v: Voucher) {
    if (v.used_count > 0) {
        alert('Không thể xóa voucher đã có lượt sử dụng');
        return;
    }
    if (!confirm(`Xác nhận xóa voucher ${v.code}?`)) return;
    const { error } = await adminApi.deleteVoucher(v.id);
    if (error) {
        alert(error);
        return;
    }
    await loadVouchers();
}

onMounted(loadVouchers);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Mã giảm giá</h1>
            <button
                @click="openCreate"
                class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Tạo voucher mới
            </button>
        </div>

        <!-- Success toast -->
        <div
            v-if="successMsg"
            class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
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
                    d="M5 13l4 4L19 7"
                />
            </svg>
            {{ successMsg }}
        </div>

        <!-- Stats row -->
        <div class="mb-6 grid grid-cols-3 gap-4">
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-xs text-gray-500">Voucher đang hoạt động</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">
                    {{ stats.active }}
                </p>
            </div>
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-xs text-gray-500">Tổng lượt đã dùng</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">
                    {{ stats.used_today }}
                </p>
            </div>
            <div
                class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <p class="text-xs text-gray-500">Tổng voucher</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">
                    {{ stats.total }}
                </p>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="isLoading"
            class="h-64 animate-pulse rounded-xl border border-slate-200 bg-white p-8"
        />

        <!-- Error -->
        <div
            v-else-if="errorMsg"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-red-700"
        >
            {{ errorMsg }}
            <button @click="loadVouchers" class="ml-auto text-sm underline">
                Thử lại
            </button>
        </div>

        <!-- Empty -->
        <div
            v-else-if="vouchers.length === 0"
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
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                />
            </svg>
            <p class="font-medium text-gray-500">Chưa có voucher nào</p>
            <button
                @click="openCreate"
                class="mt-3 rounded-lg bg-red-600 px-4 py-2 text-sm text-white transition-colors hover:bg-red-700"
            >
                Tạo voucher đầu tiên
            </button>
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
                                Mã
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Loại giảm
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Giá trị
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Đơn tối thiểu
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Đã dùng / Giới hạn
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Hiệu lực
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 uppercase"
                            >
                                Nhà xe
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
                        <tr
                            v-for="v in vouchers"
                            :key="v.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td
                                class="px-4 py-3 font-mono text-xs font-semibold text-gray-900"
                            >
                                {{ v.code }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        v.discount_type === 'percent'
                                            ? 'bg-purple-100 text-purple-700'
                                            : 'bg-blue-100 text-blue-700',
                                    ]"
                                >
                                    {{
                                        v.discount_type === 'percent'
                                            ? 'Giảm %'
                                            : 'Giảm tiền'
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right font-medium text-gray-900"
                            >
                                {{
                                    v.discount_type === 'percent'
                                        ? v.discount_value + '%'
                                        : fmt(v.discount_value)
                                }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                {{ fmt(v.min_order) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <span class="font-medium text-gray-900">{{
                                        v.used_count
                                    }}</span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-500">{{
                                        v.usage_limit
                                    }}</span>
                                </div>
                                <div
                                    class="mx-auto mt-1 h-1.5 w-16 rounded-full bg-gray-100"
                                >
                                    <div
                                        class="h-1.5 rounded-full bg-red-500"
                                        :style="{
                                            width:
                                                Math.min(
                                                    100,
                                                    Math.round(
                                                        (v.used_count /
                                                            v.usage_limit) *
                                                            100,
                                                    ),
                                                ) + '%',
                                        }"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                <span>{{
                                    new Date(v.valid_from).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}</span>
                                <span class="mx-1 text-gray-400">→</span>
                                <span>{{
                                    new Date(v.valid_until).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ v.operator_name ?? 'Toàn nền tảng' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="toggleVoucher(v)"
                                    :class="[
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                        v.is_active
                                            ? 'bg-green-500'
                                            : 'bg-gray-200',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            v.is_active
                                                ? 'translate-x-4'
                                                : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <button
                                        @click="openEdit(v)"
                                        class="rounded p-1.5 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteVoucher(v)"
                                        class="rounded p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
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
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit modal -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-5 text-lg font-semibold text-gray-900">
                    {{
                        editingVoucher ? 'Chỉnh sửa voucher' : 'Tạo voucher mới'
                    }}
                </h3>

                <div class="space-y-4">
                    <!-- Code -->
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Mã voucher
                            <span class="text-red-500">*</span></label
                        >
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="VD: SALE20"
                            :disabled="!!editingVoucher"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm uppercase focus:ring-2 focus:ring-red-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500"
                            @input="form.code = form.code.toUpperCase()"
                        />
                    </div>

                    <!-- Type + Value -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Loại giảm</label
                            >
                            <div class="flex gap-2">
                                <button
                                    @click="form.discount_type = 'percent'"
                                    :class="[
                                        'flex-1 rounded-lg border py-2 text-sm font-medium transition-colors',
                                        form.discount_type === 'percent'
                                            ? 'border-red-500 bg-red-50 text-red-700'
                                            : 'border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    %
                                </button>
                                <button
                                    @click="form.discount_type = 'fixed'"
                                    :class="[
                                        'flex-1 rounded-lg border py-2 text-sm font-medium transition-colors',
                                        form.discount_type === 'fixed'
                                            ? 'border-red-500 bg-red-50 text-red-700'
                                            : 'border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    VNĐ
                                </button>
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Giá trị
                                {{
                                    form.discount_type === 'percent'
                                        ? '(%)'
                                        : '(VNĐ)'
                                }}
                            </label>
                            <input
                                v-model.number="form.discount_value"
                                type="number"
                                :min="1"
                                :max="
                                    form.discount_type === 'percent'
                                        ? 100
                                        : undefined
                                "
                                :step="
                                    form.discount_type === 'percent' ? 1 : 10000
                                "
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Min order + Max discount -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Đơn tối thiểu (VNĐ)</label
                            >
                            <input
                                v-model.number="form.min_order"
                                type="number"
                                min="0"
                                step="10000"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Giảm tối đa (VNĐ)</label
                            >
                            <input
                                v-model.number="form.max_discount"
                                type="number"
                                min="0"
                                step="10000"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Usage limit -->
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Giới hạn sử dụng (lượt)</label
                        >
                        <input
                            v-model.number="form.usage_limit"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                        />
                    </div>

                    <!-- Date range -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Ngày bắt đầu</label
                            >
                            <input
                                v-model="form.valid_from"
                                type="date"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Ngày kết thúc</label
                            >
                            <input
                                v-model="form.valid_until"
                                type="date"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="showModal = false"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button
                        @click="saveVoucher"
                        :disabled="saveLoading || !form.code.trim()"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                    >
                        {{
                            saveLoading
                                ? 'Đang lưu...'
                                : editingVoucher
                                  ? 'Cập nhật'
                                  : 'Tạo voucher'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
