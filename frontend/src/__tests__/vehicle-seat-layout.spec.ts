import { describe, expect, it } from 'vitest';
import { VEHICLE_SEAT_ROWS } from '@/lib/vehicle-seat-layout';

const flatten = (vehicleType: string) => VEHICLE_SEAT_ROWS[vehicleType].flat();

describe('vehicle seat layouts', () => {
    it('dùng đúng sơ đồ riêng cho van 9 chỗ', () => {
        expect(VEHICLE_SEAT_ROWS.van_9).toEqual([
            ['A1', 'A2'],
            ['B1', 'B2'],
            ['B3', 'B4'],
            ['C1', 'C2', 'C3'],
        ]);
        expect(flatten('van_9')).toHaveLength(9);
    });

    it('giữ limousine 12 ở sơ đồ 12 ghế độc lập', () => {
        expect(VEHICLE_SEAT_ROWS.limousine_12).toEqual([
            ['A1', 'A2'],
            ['B1', 'B2'],
            ['C1', 'C2'],
            ['D1', 'D2'],
            ['E1', 'E2'],
            ['F1', 'F2'],
        ]);
        expect(flatten('limousine_12')).toHaveLength(12);
        expect(flatten('limousine_12')).not.toEqual(flatten('van_9'));
    });

    it.each([
        ['sedan_4', 4],
        ['mpv_7', 7],
        ['van_9', 9],
        ['limousine_12', 12],
        ['minibus_16', 16],
    ])('%s có đúng %i mã ghế duy nhất', (vehicleType, seatCount) => {
        const seats = flatten(vehicleType);
        expect(seats).toHaveLength(seatCount);
        expect(new Set(seats).size).toBe(seatCount);
    });
});
