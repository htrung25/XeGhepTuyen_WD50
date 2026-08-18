export const VEHICLE_TYPE_OPTIONS = [
    { value: 'sedan_4', label: 'Xe 4 chỗ (Sedan)', seats: 4 },
    { value: 'mpv_7', label: 'Xe 7 chỗ (SUV/MPV)', seats: 7 },
    { value: 'van_9', label: 'Xe 9 chỗ (Van/Limousine)', seats: 9 },
    { value: 'limousine_12', label: 'Xe 12 chỗ (Limousine)', seats: 12 },
    { value: 'minibus_16', label: 'Xe 16 chỗ (Minibus)', seats: 16 },
] as const;

export function formatVehicleType(value?: string | null): string {
    if (!value) return 'Chưa xác định';

    return (
        VEHICLE_TYPE_OPTIONS.find((option) => option.value === value)?.label ??
        'Loại xe chưa xác định'
    );
}
