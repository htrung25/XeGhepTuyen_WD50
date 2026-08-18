import { describe, expect, it } from 'vitest';
import { formatVehicleType } from '@/lib/vehicle-type';

describe('formatVehicleType', () => {
    it.each([
        ['sedan_4', 'Xe 4 chỗ (Sedan)'],
        ['mpv_7', 'Xe 7 chỗ (SUV/MPV)'],
        ['van_9', 'Xe 9 chỗ (Van/Limousine)'],
        ['limousine_12', 'Xe 12 chỗ (Limousine)'],
        ['minibus_16', 'Xe 16 chỗ (Minibus)'],
    ])('đổi mã BE %s thành nhãn %s', (code, label) => {
        expect(formatVehicleType(code)).toBe(label);
    });

    it('không hiển thị mã thô khi nhận loại xe không xác định', () => {
        expect(formatVehicleType('future_vehicle_code')).toBe(
            'Loại xe chưa xác định',
        );
    });
});
