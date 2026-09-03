import { describe, expect, it } from 'vitest';
import {
    boundaryBounds,
    boundaryCenter,
    isInsideBoundary,
} from '@/lib/location-geometry';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const square: ServiceAreaBoundary = {
    type: 'Polygon',
    coordinates: [
        [
            [105, 20],
            [107, 20],
            [107, 22],
            [105, 22],
            [105, 20],
        ],
    ],
};

describe('location geometry', () => {
    it('chấp nhận điểm trong vùng và từ chối điểm ngoài vùng', () => {
        expect(isInsideBoundary(square, 106, 21)).toBe(true);
        expect(isInsideBoundary(square, 108, 21)).toBe(false);
    });

    it('tôn trọng phần rỗng bên trong polygon', () => {
        const withHole: ServiceAreaBoundary = {
            type: 'Polygon',
            coordinates: [
                square.coordinates[0],
                [
                    [105.5, 20.5],
                    [106.5, 20.5],
                    [106.5, 21.5],
                    [105.5, 21.5],
                    [105.5, 20.5],
                ],
            ],
        };

        expect(isInsideBoundary(withHole, 106, 21)).toBe(false);
        expect(isInsideBoundary(withHole, 105.25, 20.25)).toBe(true);
    });

    it('hỗ trợ MultiPolygon và không giới hạn khi tuyến chưa có boundary', () => {
        const multiPolygon: ServiceAreaBoundary = {
            type: 'MultiPolygon',
            coordinates: [
                square.coordinates,
                [
                    [
                        [108, 20],
                        [109, 20],
                        [109, 21],
                        [108, 21],
                        [108, 20],
                    ],
                ],
            ],
        };

        expect(isInsideBoundary(multiPolygon, 108.5, 20.5)).toBe(true);
        expect(isInsideBoundary(null, 500, 500)).toBe(true);
    });

    it('tính tâm và bounds để bias tìm kiếm, hiển thị vùng phục vụ', () => {
        expect(boundaryCenter(square)).toEqual([106, 21]);
        expect(boundaryBounds(square)).toEqual([
            [105, 20],
            [107, 22],
        ]);
    });
});
