import { describe, expect, it, vi } from 'vitest';

vi.mock('@/api/client', () => ({
    apiClient: {
        send: vi.fn(() =>
            Promise.resolve({ data: null, message: null, error: null }),
        ),
        sendForm: vi.fn(() =>
            Promise.resolve({ data: null, message: null, error: null }),
        ),
    },
}));

import { apiClient } from '@/api/client';
import { customerApi } from '@/api/customer.api';

describe('customerApi → Wayfinder route contract', () => {
    it('login resolves to POST /api/customer/auth/login', () => {
        customerApi.login({ phone: '0901234567', password: 'secret' });
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/auth/login', method: 'post' },
            { phone: '0901234567', password: 'secret' },
        );
    });

    it('searchTrips resolves to the PUBLIC binding GET /api/public/trips', () => {
        customerApi.searchTrips({
            from_city: 'Hà Nội',
            to_city: 'Hải Phòng',
            date: '2026-06-21',
            passengers: 2,
        });
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/public/trips?from_city=H%C3%A0+N%E1%BB%99i&to_city=H%E1%BA%A3i+Ph%C3%B2ng&date=2026-06-21&passengers=2',
            method: 'get',
        });
    });

    it('getPublicTrip resolves to the PUBLIC binding GET /api/public/trips/{id}', () => {
        customerApi.getPublicTrip('trip-1');
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/public/trips/trip-1',
            method: 'get',
        });
    });

    it('getTripSeats keeps the CUSTOMER (authed) binding for the seat map', () => {
        customerApi.getTripSeats('trip-1');
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/customer/trips/trip-1/seats',
            method: 'get',
        });
    });

    it('createBooking resolves to POST /api/customer/bookings', () => {
        const payload = { trip_id: 't1' } as never;
        customerApi.createBooking(payload);
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/bookings', method: 'post' },
            payload,
        );
    });

    it('cancelBooking resolves to POST /api/customer/bookings/{id}/cancel with a reason', () => {
        customerApi.cancelBooking('b1', 'Đổi lịch');
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/bookings/b1/cancel', method: 'post' },
            { reason: 'Đổi lịch' },
        );
    });

    it('getWallet resolves to GET /api/customer/wallet', () => {
        customerApi.getWallet();
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/customer/wallet',
            method: 'get',
        });
    });

    it('markAllRead resolves to PUT /api/customer/notifications/read-all', () => {
        customerApi.markAllRead();
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/customer/notifications/read-all',
            method: 'put',
        });
    });

    it('submitPartnerApplication uploads multipart via sendForm to the public route', () => {
        const form = new FormData();
        customerApi.submitPartnerApplication(form);
        expect(apiClient.sendForm).toHaveBeenCalledWith(
            { url: '/api/public/partner-applications', method: 'post' },
            form,
        );
    });
});
