<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { operatorApi } from '@/api/operator.api';

interface Vehicle {
    id: string;
    plate_number: string;
    vehicle_type: string;
    seat_count: number;
    manufacture_year: number | null;
    is_active: boolean;
    image_url?: string | null;
    current_driver?: { full_name: string; phone: string } | null;
}

interface Driver {
    id: string;
    full_name: string;
    phone: string;
    rating_avg: number | null;
    is_online: boolean;
    is_active: boolean;
    status?: string;
    status_label?: string;
    license_class?: string;
    license_expiry?: string;
    current_vehicle?: { plate_number: string; vehicle_type: string } | null;
}

const driverStatusMap: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700',
    verified: 'bg-green-50 text-green-700',
    suspended: 'bg-red-50 text-red-600',
    rejected: 'bg-gray-100 text-gray-600',
};

const tab = ref<'vehicles' | 'drivers'>('vehicles');
const vehicles = ref<Vehicle[]>([]);
const drivers = ref<Driver[]>([]);
const loading = ref(true);
const error = ref('');

const assignModal = ref(false);
const assignDriver = ref<Driver | null>(null);
const assignVehicleId = ref('');
const assignLoading = ref(false);

async function fetchData() {
    loading.value = true;
    error.value = '';
    const [vRes, dRes] = await Promise.all([
        operatorApi.getVehicles(),
        operatorApi.getDrivers(),
    ]);
    loading.value = false;
    if (vRes.error || dRes.error) {
        error.value = vRes.error ?? dRes.error ?? '';
        return;
    }
    vehicles.value = (vRes.data as any)?.data ?? vRes.data ?? [];
    drivers.value = (dRes.data as any)?.data ?? dRes.data ?? [];
}

function openAssign(driver: Driver) {
    assignDriver.value = driver;
    assignVehicleId.value = driver.current_vehicle?.plate_number ?? '';
    assignModal.value = true;
}

async function confirmAssign() {
    if (!assignDriver.value || !assignVehicleId.value) return;
    const v = vehicles.value.find((v) => v.id === assignVehicleId.value);
    if (!v) return;
    assignLoading.value = true;
    const { error: err } = await operatorApi.assignVehicle(
        assignDriver.value.id,
        v.id,
    );
    assignLoading.value = false;
    if (err) {
        alert(err);
        return;
    }
    assignModal.value = false;
    fetchData();
}

// ─── Thêm xe ──────────────────────────────────────────────────────────────
const vehicleTypes = [
    { value: 'sedan_4', label: 'Sedan (4 chỗ)', seats: 4 },
    { value: 'mpv_7', label: 'MPV (7 chỗ)', seats: 7 },
    { value: 'van_9', label: 'Van (9 chỗ)', seats: 9 },
    { value: 'minibus_16', label: 'Minibus (16 chỗ)', seats: 16 },
];
const amenityOptions = ['wifi', 'ac', 'usb', 'water', 'tv'];

const emptyVehicle = () => ({
    plate_number: '',
    vehicle_type: '',
    brand: '',
    model: '',
    year: new Date().getFullYear(),
    color: '',
    seat_count: 0,
    registration_expiry: '',
    amenities: [] as string[],
});
const addModal = ref(false);
const addLoading = ref(false);
const addError = ref('');
const newVehicle = ref(emptyVehicle());
const imageFile = ref<File | null>(null);
const imagePreview = ref('');

function openAdd() {
    newVehicle.value = emptyVehicle();
    imageFile.value = null;
    imagePreview.value = '';
    addError.value = '';
    addModal.value = true;
}

function onImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
        addError.value = 'Ảnh tối đa 10MB';
        return;
    }
    imageFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
}

// Chọn loại xe → tự điền số chỗ
function onTypeChange() {
    const t = vehicleTypes.find(
        (t) => t.value === newVehicle.value.vehicle_type,
    );
    if (t) newVehicle.value.seat_count = t.seats;
}

function toggleAmenity(a: string) {
    const i = newVehicle.value.amenities.indexOf(a);
    if (i >= 0) newVehicle.value.amenities.splice(i, 1);
    else newVehicle.value.amenities.push(a);
}

async function submitAdd() {
    const v = newVehicle.value;
    if (
        !v.plate_number.trim() ||
        !v.vehicle_type ||
        !v.brand.trim() ||
        !v.model.trim() ||
        !v.color.trim() ||
        !v.registration_expiry
    ) {
        addError.value = 'Vui lòng điền đầy đủ thông tin bắt buộc';
        return;
    }
    const fd = new FormData();
    fd.append('plate_number', v.plate_number.trim());
    fd.append('vehicle_type', v.vehicle_type);
    fd.append('brand', v.brand.trim());
    fd.append('model', v.model.trim());
    fd.append('year', String(v.year));
    fd.append('color', v.color.trim());
    fd.append('seat_count', String(v.seat_count));
    fd.append('registration_expiry', v.registration_expiry);
    v.amenities.forEach((a) => fd.append('amenities[]', a));
    if (imageFile.value) fd.append('image', imageFile.value);

    addLoading.value = true;
    addError.value = '';
    const { error: err } = await operatorApi.createVehicle(fd);
    addLoading.value = false;
    if (err) {
        addError.value = err;
        return;
    }
    addModal.value = false;
    fetchData();
}

// ─── Thêm tài xế ───────────────────────────────────────────────────────────
const emptyDriver = () => ({
    full_name: '',
    phone: '',
    email: '',
    license_number: '',
    license_class: '',
    license_expiry: '',
    id_card_number: '',
});
const addDriverModal = ref(false);
const addDriverLoading = ref(false);
const addDriverError = ref('');
const newDriver = ref(emptyDriver());
const driverFiles = ref<{
    id_card_front: File | null;
    id_card_back: File | null;
    license_front: File | null;
}>({
    id_card_front: null,
    id_card_back: null,
    license_front: null,
});
// Kết quả: đã thêm tài xế, đang chờ admin duyệt (mật khẩu cấp khi duyệt)
const driverAdded = ref<{ phone: string } | null>(null);

function openAddDriver() {
    newDriver.value = emptyDriver();
    driverFiles.value = {
        id_card_front: null,
        id_card_back: null,
        license_front: null,
    };
    driverAdded.value = null;
    addDriverError.value = '';
    addDriverModal.value = true;
}

function onDriverFile(
    field: 'id_card_front' | 'id_card_back' | 'license_front',
    e: Event,
) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        addDriverError.value = 'Ảnh tối đa 5MB';
        return;
    }
    driverFiles.value[field] = file;
}

async function submitAddDriver() {
    const d = newDriver.value;
    if (
        !d.full_name.trim() ||
        !d.phone.trim() ||
        !d.license_number.trim() ||
        !d.license_class ||
        !d.license_expiry ||
        !d.id_card_number.trim()
    ) {
        addDriverError.value = 'Vui lòng điền đầy đủ thông tin bắt buộc';
        return;
    }
    const fd = new FormData();
    fd.append('full_name', d.full_name.trim());
    fd.append('phone', d.phone.trim());
    if (d.email.trim()) fd.append('email', d.email.trim());
    fd.append('license_number', d.license_number.trim());
    fd.append('license_class', d.license_class);
    fd.append('license_expiry', d.license_expiry);
    fd.append('id_card_number', d.id_card_number.trim());
    if (driverFiles.value.id_card_front)
        fd.append('id_card_front', driverFiles.value.id_card_front);
    if (driverFiles.value.id_card_back)
        fd.append('id_card_back', driverFiles.value.id_card_back);
    if (driverFiles.value.license_front)
        fd.append('license_front', driverFiles.value.license_front);

    addDriverLoading.value = true;
    addDriverError.value = '';
    const { data, error: err } = await operatorApi.createDriver(fd);
    addDriverLoading.value = false;
    if (err) {
        addDriverError.value = err;
        return;
    }
    driverAdded.value = data ?? { phone: d.phone.trim() };
    fetchData();
}

// ─── Cấp lại mật khẩu tài xế ───────────────────────────────────────────────
const resetModal = ref(false);
const resetTarget = ref<Driver | null>(null);
const resetLoading = ref(false);
const resetResult = ref<{ phone: string; temp_password: string } | null>(null);
const resetCopied = ref(false);

function openResetDriver(d: Driver) {
    resetTarget.value = d;
    resetResult.value = null;
    resetCopied.value = false;
    resetModal.value = true;
}

async function confirmResetDriver() {
    if (!resetTarget.value) return;
    resetLoading.value = true;
    const { data, error: err } = await operatorApi.resetDriverPassword(
        resetTarget.value.id,
    );
    resetLoading.value = false;
    if (err) {
        addDriverError.value = err;
        resetModal.value = false;
        return;
    }
    resetResult.value = data;
}

async function copyResetPassword() {
    if (!resetResult.value) return;
    try {
        await navigator.clipboard.writeText(resetResult.value.temp_password);
        resetCopied.value = true;
        setTimeout(() => (resetCopied.value = false), 2000);
    } catch {
        /* clipboard không khả dụng */
    }
}

onMounted(fetchData);
</script>

<template>
    <div class="mx-auto max-w-6xl p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Xe & Tài xế</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ vehicles.length }} xe · {{ drivers.length }} tài xế
                </p>
            </div>
            <button
                v-if="tab === 'vehicles'"
                @click="openAdd"
                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Thêm xe
            </button>
            <button
                v-else
                @click="openAddDriver"
                class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Thêm tài xế
            </button>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex w-fit gap-1 rounded-xl bg-gray-100 p-1">
            <button
                v-for="t in [
                    { v: 'vehicles', l: `Xe (${vehicles.length})` },
                    { v: 'drivers', l: `Tài xế (${drivers.length})` },
                ]"
                :key="t.v"
                @click="tab = t.v as typeof tab.value"
                :class="[
                    'rounded-lg px-5 py-2 text-sm font-medium transition-colors',
                    tab === t.v
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                {{ t.l }}
            </button>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white p-16 text-center"
        >
            <div
                class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-2 border-amber-500 border-t-transparent"
            />
            <p class="text-sm text-gray-500">Đang tải...</p>
        </div>

        <div
            v-else-if="error"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center"
        >
            <p class="mb-4 text-sm text-red-500">{{ error }}</p>
            <button
                @click="fetchData"
                class="rounded-lg bg-amber-500 px-4 py-2 text-sm text-white"
            >
                Thử lại
            </button>
        </div>

        <!-- ─── Vehicles tab ─────────────────────────────────────────────── -->
        <div
            v-else-if="tab === 'vehicles'"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
            >
                <h2 class="font-semibold text-gray-800">Danh sách xe</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Biển số
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Loại xe
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Số chỗ
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Năm SX
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Tài xế hiện tại
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="vehicles.length === 0">
                            <td
                                colspan="6"
                                class="px-5 py-12 text-center text-sm text-gray-400"
                            >
                                Chưa có xe nào
                            </td>
                        </tr>
                        <tr
                            v-for="v in vehicles"
                            :key="v.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-100"
                                    >
                                        <img
                                            v-if="v.image_url"
                                            :src="v.image_url"
                                            alt="xe"
                                            class="h-full w-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-lg text-gray-300"
                                            >🚐</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono font-semibold text-gray-900"
                                        >{{ v.plate_number }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-700">
                                {{ v.vehicle_type }}
                            </td>
                            <td
                                class="px-5 py-3 text-center font-medium text-gray-800"
                            >
                                {{ v.seat_count }}
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">
                                {{ v.manufacture_year ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    v-if="v.current_driver"
                                    class="text-gray-800"
                                >
                                    {{ v.current_driver.full_name }}
                                    <span class="ml-1 text-xs text-gray-400">{{
                                        v.current_driver.phone
                                    }}</span>
                                </span>
                                <span v-else class="text-xs text-gray-400"
                                    >Chưa gán tài xế</span
                                >
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        v.is_active
                                            ? 'bg-green-50 text-green-700'
                                            : 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ v.is_active ? 'Hoạt động' : 'Ngừng' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── Drivers tab ─────────────────────────────────────────────── -->
        <div
            v-else
            class="overflow-hidden rounded-xl border border-gray-200 bg-white"
        >
            <div
                class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
            >
                <h2 class="font-semibold text-gray-800">Danh sách tài xế</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Tài xế
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase"
                            >
                                Xe được gán
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Đánh giá
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="drivers.length === 0">
                            <td
                                colspan="5"
                                class="px-5 py-12 text-center text-sm text-gray-400"
                            >
                                Chưa có tài xế nào
                            </td>
                        </tr>
                        <tr
                            v-for="d in drivers"
                            :key="d.id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <!-- Driver -->
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100"
                                    >
                                        <span
                                            class="text-sm font-semibold text-amber-700"
                                            >{{ d.full_name?.charAt(0) }}</span
                                        >
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ d.full_name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ d.phone }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <!-- Vehicle -->
                            <td class="px-5 py-3">
                                <span
                                    v-if="d.current_vehicle"
                                    class="font-mono text-gray-800"
                                >
                                    {{ d.current_vehicle.plate_number }}
                                    <span
                                        class="ml-1 font-sans text-xs text-gray-400"
                                        >{{
                                            d.current_vehicle.vehicle_type
                                        }}</span
                                    >
                                </span>
                                <span v-else class="text-xs text-gray-400"
                                    >Chưa có xe</span
                                >
                            </td>
                            <!-- Rating -->
                            <td class="px-5 py-3 text-center">
                                <div
                                    v-if="d.rating_avg"
                                    class="flex items-center justify-center gap-1"
                                >
                                    <svg
                                        class="h-3.5 w-3.5 fill-yellow-400 text-yellow-400"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-800"
                                        >{{ d.rating_avg.toFixed(1) }}</span
                                    >
                                </div>
                                <span v-else class="text-xs text-gray-400"
                                    >—</span
                                >
                            </td>
                            <!-- Status (trạng thái duyệt hồ sơ — đã bỏ chỉ báo online/offline "nhận khách") -->
                            <td class="px-5 py-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        driverStatusMap[d.status ?? ''] ??
                                            'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{
                                        d.status_label ??
                                        (d.is_active ? 'Hoạt động' : 'Ngừng')
                                    }}
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="px-5 py-3 text-center">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <button
                                        @click="openAssign(d)"
                                        class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                    >
                                        Gán xe
                                    </button>
                                    <button
                                        v-if="d.status === 'verified'"
                                        @click="openResetDriver(d)"
                                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-200"
                                        title="Cấp lại mật khẩu đăng nhập cho tài xế"
                                    >
                                        Cấp lại MK
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assign Vehicle Modal -->
        <Teleport to="body">
            <div
                v-if="assignModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="assignModal = false"
                />
                <div
                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-1 text-lg font-bold text-gray-900">
                        Gán xe cho tài xế
                    </h3>
                    <p class="mb-5 text-sm text-gray-500">
                        Tài xế: <strong>{{ assignDriver?.full_name }}</strong>
                    </p>
                    <div class="mb-5">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                            >Chọn xe</label
                        >
                        <select
                            v-model="assignVehicleId"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                        >
                            <option value="">-- Chọn xe --</option>
                            <option
                                v-for="v in vehicles"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.plate_number }} — {{ v.vehicle_type }} ({{
                                    v.seat_count
                                }}
                                chỗ)
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="assignModal = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Huỷ
                        </button>
                        <button
                            @click="confirmAssign"
                            :disabled="!assignVehicleId || assignLoading"
                            class="flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600 disabled:opacity-50"
                        >
                            <svg
                                v-if="assignLoading"
                                class="h-4 w-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>
                            Xác nhận gán
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Add Vehicle Modal -->
        <Teleport to="body">
            <div
                v-if="addModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="addModal = false"
                />
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
                >
                    <h3 class="mb-5 text-lg font-bold text-gray-900">
                        Thêm xe mới
                    </h3>

                    <div
                        v-if="addError"
                        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"
                    >
                        {{ addError }}
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Biển số
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="newVehicle.plate_number"
                                type="text"
                                placeholder="VD: 29A-12345"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Loại xe
                                <span class="text-red-500">*</span></label
                            >
                            <select
                                v-model="newVehicle.vehicle_type"
                                @change="onTypeChange"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="">-- Chọn loại --</option>
                                <option
                                    v-for="t in vehicleTypes"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Số chỗ
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model.number="newVehicle.seat_count"
                                type="number"
                                min="4"
                                max="50"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Hãng <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="newVehicle.brand"
                                type="text"
                                placeholder="Toyota"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Dòng xe
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="newVehicle.model"
                                type="text"
                                placeholder="Innova"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Năm SX
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model.number="newVehicle.year"
                                type="number"
                                min="2000"
                                max="2030"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Màu <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="newVehicle.color"
                                type="text"
                                placeholder="Trắng"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div class="col-span-2">
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Hạn đăng kiểm
                                <span class="text-red-500">*</span></label
                            >
                            <input
                                v-model="newVehicle.registration_expiry"
                                type="date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>
                        <div class="col-span-2">
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Ảnh xe</label
                            >
                            <div class="flex items-center gap-3">
                                <div
                                    v-if="imagePreview"
                                    class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200"
                                >
                                    <img
                                        :src="imagePreview"
                                        alt="preview"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <label class="flex-1 cursor-pointer">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png"
                                        class="hidden"
                                        @change="onImageChange"
                                    />
                                    <span
                                        class="flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-500 transition-colors hover:border-amber-400 hover:text-amber-600"
                                    >
                                        📷
                                        {{
                                            imageFile
                                                ? imageFile.name
                                                : 'Chọn ảnh (JPG/PNG, tối đa 10MB)'
                                        }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Tiện nghi</label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="a in amenityOptions"
                                    :key="a"
                                    type="button"
                                    @click="toggleAmenity(a)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                        newVehicle.amenities.includes(a)
                                            ? 'border-amber-500 bg-amber-500 text-white'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-amber-300',
                                    ]"
                                >
                                    {{ a.toUpperCase() }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="addModal = false"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Huỷ
                        </button>
                        <button
                            @click="submitAdd"
                            :disabled="addLoading"
                            class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600 disabled:opacity-50"
                        >
                            {{ addLoading ? 'Đang lưu...' : 'Thêm xe' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Add Driver Modal -->
        <Teleport to="body">
            <div
                v-if="addDriverModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="addDriverModal = false"
                />
                <div
                    class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
                >
                    <!-- Bước nhập hồ sơ -->
                    <template v-if="!driverAdded">
                        <h3 class="mb-1 text-lg font-bold text-gray-900">
                            Thêm tài xế mới
                        </h3>
                        <p class="mb-5 text-sm text-gray-500">
                            Hệ thống tạo tài khoản + mật khẩu tạm cho tài xế. Hồ
                            sơ sẽ chờ admin duyệt GPLX trước khi tài xế nhận
                            chuyến.
                        </p>

                        <div
                            v-if="addDriverError"
                            class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"
                        >
                            {{ addDriverError }}
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Họ tên
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="newDriver.full_name"
                                    type="text"
                                    placeholder="Nguyễn Văn A"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Số điện thoại
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="newDriver.phone"
                                    type="tel"
                                    placeholder="09xxxxxxxx"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Email</label
                                >
                                <input
                                    v-model="newDriver.email"
                                    type="email"
                                    placeholder="(không bắt buộc)"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Số GPLX
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="newDriver.license_number"
                                    type="text"
                                    placeholder="VD: 010123456789"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Hạng GPLX
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="newDriver.license_class"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                >
                                    <option value="">-- Chọn hạng --</option>
                                    <option
                                        v-for="c in ['B2', 'C', 'D', 'E']"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ c }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Hạn GPLX
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="newDriver.license_expiry"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                    >Số CMND/CCCD
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model="newDriver.id_card_number"
                                    type="text"
                                    placeholder="VD: 0010xxxxxxxx"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                            </div>

                            <div class="col-span-2">
                                <label
                                    class="mb-1.5 block text-sm font-medium text-gray-700"
                                    >Ảnh giấy tờ
                                    <span class="font-normal text-gray-400"
                                        >(không bắt buộc — admin cần để
                                        duyệt)</span
                                    ></label
                                >
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        v-for="f in [
                                            {
                                                key: 'id_card_front',
                                                label: 'CMND trước',
                                            },
                                            {
                                                key: 'id_card_back',
                                                label: 'CMND sau',
                                            },
                                            {
                                                key: 'license_front',
                                                label: 'GPLX',
                                            },
                                        ]"
                                        :key="f.key"
                                        class="cursor-pointer"
                                    >
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png"
                                            class="hidden"
                                            @change="
                                                onDriverFile(
                                                    f.key as
                                                        | 'id_card_front'
                                                        | 'id_card_back'
                                                        | 'license_front',
                                                    $event,
                                                )
                                            "
                                        />
                                        <span
                                            class="flex items-center justify-center rounded-lg border border-dashed border-gray-300 px-2 py-3 text-center text-xs text-gray-500 transition-colors hover:border-amber-400 hover:text-amber-600"
                                        >
                                            {{
                                                driverFiles[
                                                    f.key as
                                                        | 'id_card_front'
                                                        | 'id_card_back'
                                                        | 'license_front'
                                                ]
                                                    ? '✓ ' + f.label
                                                    : '📷 ' + f.label
                                            }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                @click="addDriverModal = false"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            >
                                Huỷ
                            </button>
                            <button
                                @click="submitAddDriver"
                                :disabled="addDriverLoading"
                                class="rounded-lg bg-amber-500 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-600 disabled:opacity-50"
                            >
                                {{
                                    addDriverLoading
                                        ? 'Đang tạo...'
                                        : 'Thêm tài xế'
                                }}
                            </button>
                        </div>
                    </template>

                    <!-- Bước kết quả: đã thêm, chờ admin duyệt (mật khẩu cấp khi duyệt) -->
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
                            <h3 class="text-lg font-bold text-gray-900">
                                Đã thêm tài xế
                            </h3>
                        </div>
                        <p class="mb-4 text-sm text-gray-500">
                            Hồ sơ
                            <span class="font-medium text-gray-700">{{
                                driverAdded.phone
                            }}</span>
                            đang chờ admin duyệt GPLX.
                        </p>

                        <div
                            class="mb-4 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
                        >
                            Sau khi admin <strong>duyệt</strong>, hệ thống sẽ
                            cấp <strong>mật khẩu đăng nhập</strong> và gửi SMS
                            cho tài xế. Nếu tài xế không nhận được, bạn bấm
                            <strong>"Cấp lại MK"</strong> ở danh sách tài xế để
                            lấy mật khẩu mới.
                        </div>

                        <button
                            @click="addDriverModal = false"
                            class="w-full rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                        >
                            Đóng
                        </button>
                    </template>
                </div>
            </div>
        </Teleport>

        <!-- ─── Modal cấp lại mật khẩu tài xế ─────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="resetModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div
                    class="absolute inset-0 bg-black/40"
                    @click="resetModal = false"
                />
                <div
                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                >
                    <!-- Bước xác nhận -->
                    <template v-if="!resetResult">
                        <h3 class="mb-1 text-lg font-bold text-gray-900">
                            Cấp lại mật khẩu tài xế
                        </h3>
                        <p class="mb-5 text-sm text-gray-500">
                            Tạo mật khẩu mới cho
                            <span class="font-medium text-gray-700">{{
                                resetTarget?.full_name
                            }}</span>
                            ({{ resetTarget?.phone }}) và gửi SMS. Mật khẩu cũ
                            sẽ không dùng được nữa.
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="resetModal = false"
                                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Hủy
                            </button>
                            <button
                                @click="confirmResetDriver"
                                :disabled="resetLoading"
                                class="flex-1 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-600 disabled:opacity-60"
                            >
                                {{
                                    resetLoading
                                        ? 'Đang xử lý...'
                                        : 'Cấp lại mật khẩu'
                                }}
                            </button>
                        </div>
                    </template>

                    <!-- Bước kết quả: mật khẩu mới -->
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
                            <h3 class="text-lg font-bold text-gray-900">
                                Đã cấp lại mật khẩu
                            </h3>
                        </div>
                        <p class="mb-4 text-sm text-gray-500">
                            Đã gửi SMS cho tài xế (nếu cấu hình).
                        </p>
                        <div
                            class="mb-4 space-y-2 rounded-xl bg-gray-50 p-4 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500"
                                    >Đăng nhập (SĐT):</span
                                >
                                <span
                                    class="font-mono font-medium text-gray-900"
                                    >{{ resetResult.phone }}</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Mật khẩu mới:</span>
                                <span
                                    class="font-mono text-base font-bold tracking-wider text-amber-700"
                                    >{{ resetResult.temp_password }}</span
                                >
                            </div>
                        </div>
                        <p class="mb-4 text-xs text-red-500">
                            Mật khẩu sẽ không hiển thị lại sau khi đóng.
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="copyResetPassword"
                                class="flex-1 rounded-lg border border-amber-300 px-4 py-2.5 text-sm font-medium text-amber-700 transition-colors hover:bg-amber-50"
                            >
                                {{
                                    resetCopied
                                        ? 'Đã sao chép ✓'
                                        : 'Sao chép mật khẩu'
                                }}
                            </button>
                            <button
                                @click="resetModal = false"
                                class="flex-1 rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-900"
                            >
                                Đóng
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>
