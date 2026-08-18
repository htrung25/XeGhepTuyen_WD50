import { describe, expect, it } from 'vitest';
import { formatPlaceLabel, formatRouteLabel } from '@/lib/route-label';

describe('route labels', () => {
    it('hiển thị huyện và tỉnh ở cả hai đầu tuyến', () => {
        expect(
            formatRouteLabel({
                origin_city: 'Hà Nội',
                origin_district: 'Quận Cầu Giấy',
                dest_city: 'Hải Phòng',
                dest_district: 'Quận Hồng Bàng',
            }),
        ).toBe('Quận Cầu Giấy, Hà Nội → Quận Hồng Bàng, Hải Phòng');
    });

    it('fallback về tỉnh khi dữ liệu cũ chưa có huyện', () => {
        expect(formatPlaceLabel('Hà Nội')).toBe('Hà Nội');
        expect(
            formatRouteLabel({
                origin_city: 'Hà Nội',
                dest_city: 'Hải Phòng',
            }),
        ).toBe('Hà Nội → Hải Phòng');
    });
});
