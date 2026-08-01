import { afterEach, describe, expect, it, vi } from 'vitest';

import {
    buildNavigationWaypoints,
    fetchNavigationRoute,
} from '@/services/navigation-route.service';

describe('driver navigation route', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('xếp vị trí tài xế, điểm đón rồi điểm trả theo stop_order và loại trùng', () => {
        const waypoints = buildNavigationWaypoints(
            { lat: 21.0285, lng: 105.8542 },
            [
                {
                    checked_in: false,
                    booking_status: 'confirmed',
                    pickup_stop: {
                        stop_name: 'Giáp Bát',
                        lat: 20.9847,
                        lng: 105.8479,
                        stop_order: 2,
                    },
                    dropoff_stop: {
                        stop_name: 'Lạc Long',
                        lat: 20.8611,
                        lng: 106.6753,
                        stop_order: 5,
                    },
                },
                {
                    checked_in: false,
                    booking_status: 'confirmed',
                    pickup_stop: {
                        stop_name: 'Nước Ngầm',
                        lat: 20.9727,
                        lng: 105.8432,
                        stop_order: 1,
                    },
                    dropoff_stop: {
                        stop_name: 'Trung tâm Hải Phòng',
                        lat: 20.8529,
                        lng: 106.6877,
                        stop_order: 4,
                    },
                },
                {
                    checked_in: false,
                    booking_status: 'confirmed',
                    pickup_stop: {
                        stop_name: 'Nước Ngầm',
                        lat: 20.9727,
                        lng: 105.8432,
                        stop_order: 1,
                    },
                    dropoff_stop: {
                        stop_name: 'Trung tâm Hải Phòng',
                        lat: 20.8529,
                        lng: 106.6877,
                        stop_order: 4,
                    },
                },
            ],
        );

        expect(waypoints.map((point) => point.label)).toEqual([
            'Vị trí của bạn',
            'Nước Ngầm',
            'Giáp Bát',
            'Trung tâm Hải Phòng',
            'Lạc Long',
        ]);
    });

    it('không đưa điểm đón của khách đã check-in vào tuyến còn lại', () => {
        const waypoints = buildNavigationWaypoints({ lat: 21, lng: 105.8 }, [
            {
                checked_in: true,
                booking_status: 'checked_in',
                pickup_stop: {
                    stop_name: 'Đã đón',
                    lat: 21.01,
                    lng: 105.81,
                    stop_order: 1,
                },
                dropoff_stop: {
                    stop_name: 'Điểm trả',
                    lat: 20.85,
                    lng: 106.68,
                    stop_order: 4,
                },
            },
        ]);

        expect(waypoints.map((point) => point.label)).toEqual([
            'Vị trí của bạn',
            'Điểm trả',
        ]);
    });

    it('gọi Mapbox Directions và chuẩn hóa geometry, thời gian, quãng đường', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: vi.fn().mockResolvedValue({
                code: 'Ok',
                routes: [
                    {
                        geometry: {
                            type: 'LineString',
                            coordinates: [
                                [105.8, 21],
                                [106.68, 20.85],
                            ],
                        },
                        distance: 110_500,
                        duration: 7_200,
                        legs: [{ distance: 2_500, duration: 600 }],
                    },
                ],
            }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const result = await fetchNavigationRoute(
            [
                { lat: 21, lng: 105.8, label: 'Vị trí của bạn', order: 0 },
                { lat: 20.85, lng: 106.68, label: 'Điểm trả', order: 1 },
            ],
            'test-token',
        );

        expect(fetchMock).toHaveBeenCalledOnce();
        expect(fetchMock.mock.calls[0][0]).toContain(
            '/directions/v5/mapbox/driving-traffic/105.8,21;106.68,20.85',
        );
        expect(result).toEqual({
            coordinates: [
                [105.8, 21],
                [106.68, 20.85],
            ],
            distanceMeters: 110_500,
            durationSeconds: 7_200,
            nextStopDistanceMeters: 2_500,
            nextStopDurationSeconds: 600,
        });
    });
});
