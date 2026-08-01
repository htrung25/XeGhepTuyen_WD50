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
    it('sendOtp and verifyOtp resolve to customer auth endpoints', () => {
        customerApi.sendOtp({ phone: '0901234567' });
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/auth/send-otp', method: 'post' },
            { phone: '0901234567' },
        );

        customerApi.verifyOtp({ phone: '0901234567', otp: '123456' });
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/auth/verify-otp', method: 'post' },
            { phone: '0901234567', otp: '123456' },
        );
    });

    it('register forwards the one-time verification proof', () => {
        const payload = {
            full_name: 'Khách Test',
            phone: '0901234567',
            password: 'Customer@123',
            password_confirmation: 'Customer@123',
            verification_token: 'a'.repeat(64),
        };
        customerApi.register(payload);
        expect(apiClient.send).toHaveBeenCalledWith(
            { url: '/api/customer/auth/register', method: 'post' },
            payload,
        );
    });

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

    it('getTripSeats dùng binding PUBLIC khi khách chưa đăng nhập', () => {
        localStorage.removeItem('customer_token');
        customerApi.getTripSeats('trip-1');
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/public/trips/trip-1/seats',
            method: 'get',
        });
    });

    it('getTripSeats giữ binding CUSTOMER (authed) khi đã đăng nhập — nhận diện ghế do chính mình giữ', () => {
        localStorage.setItem('customer_token', 'tok-123');
        customerApi.getTripSeats('trip-1');
        localStorage.removeItem('customer_token');
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

    it('maps the complete customer support ticket workflow', () => {
        customerApi.getSupportTickets({ page: 2 });
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/customer/support/tickets?page=2',
            method: 'get',
        });

        customerApi.replySupportTicket('ticket-1', 'Nội dung phản hồi');
        expect(apiClient.send).toHaveBeenCalledWith(
            {
                url: '/api/customer/support/tickets/ticket-1/reply',
                method: 'post',
            },
            { body: 'Nội dung phản hồi' },
        );

        customerApi.closeSupportTicket('ticket-1');
        expect(apiClient.send).toHaveBeenCalledWith({
            url: '/api/customer/support/tickets/ticket-1/close',
            method: 'post',
        });
    });
});
