import {
    login,
    me,
    logout,
    updateStatus,
    updateProfile,
    changePassword,
    uploadDocument,
} from '@/actions/App/Http/Controllers/Driver/AuthController';
import {
    checkin,
    absent,
} from '@/actions/App/Http/Controllers/Driver/CheckinController';
import {
    index as earningsIndex,
    transactions as earningsTransactions,
} from '@/actions/App/Http/Controllers/Driver/EarningController';
import { update as locationUpdate } from '@/actions/App/Http/Controllers/Driver/LocationController';
import {
    index as notificationsIndex,
    markRead as notificationMarkRead,
} from '@/actions/App/Http/Controllers/Driver/NotificationController';
import {
    index as tripsIndex,
    show as tripShow,
    passengers,
    start,
    complete,
    history,
} from '@/actions/App/Http/Controllers/Driver/TripController';
import { apiClient } from './client';

export const driverApi = {
    // ─── Auth ─────────────────────────────────────────────────────────────
    login: (data: { phone: string; password: string }) =>
        apiClient.send(login(), data),
    logout: () => apiClient.send(logout()),
    me: () => apiClient.send(me()),

    updateProfile: (data: { full_name?: string; email?: string; birth_date?: string | null }) =>
        apiClient.send(updateProfile(), data),
    changePassword: (data: {
        old_password: string;
        new_password: string;
        new_password_confirmation: string;
    }) => apiClient.send(changePassword(), data),
    uploadDocument: (type: string, file: File) => {
        const form = new FormData();
        form.append('type', type);
        form.append('file', file);

        return apiClient.sendForm(uploadDocument(), form);
    },

    // ─── Status ────────────────────────────────────────────────────────────
    setStatus: (data: { is_online: boolean }) =>
        apiClient.send(updateStatus(), data),

    // ─── Trips ─────────────────────────────────────────────────────────────
    getTrips: (params?: {
        date?: string;
        from_date?: string;
        to_date?: string;
        status?: string;
    }) => apiClient.send(tripsIndex({ query: params })),
    getTrip: (id: string) => apiClient.send(tripShow(id)),
    getPassengers: (tripId: string) => apiClient.send(passengers(tripId)),
    startTrip: (tripId: string) => apiClient.send(start(tripId)),
    completeTrip: (tripId: string) => apiClient.send(complete(tripId)),
    getTripHistory: (params?: { page?: number }) =>
        apiClient.send(history({ query: params })),

    // ─── Check-in ──────────────────────────────────────────────────────────
    checkin: (data: { qr_token: string; cash_collected?: boolean }) =>
        apiClient.send(checkin(), data),
    markAbsent: (data: { trip_id: string; booking_id: string }) =>
        apiClient.send(absent(), data),

    // ─── Location ──────────────────────────────────────────────────────────
    updateLocation: (data: {
        trip_id: string;
        lat: number;
        lng: number;
        speed?: number;
        heading?: number;
        accuracy?: number;
    }) => apiClient.send(locationUpdate(), data),

    // ─── Earnings (bảng kê chỉ-xem) ────────────────────────────────────────
    getEarnings: (params?: { period?: 'today' | 'week' | 'month' }) =>
        apiClient.send(earningsIndex({ query: params })),
    getTransactions: (params?: { page?: number }) =>
        apiClient.send(earningsTransactions({ query: params })),

    // ─── Notifications ─────────────────────────────────────────────────────
    getNotifications: (params?: { page?: number; unread_only?: boolean }) =>
        apiClient.send(notificationsIndex({ query: params })),
    markNotificationRead: (id: string) =>
        apiClient.send(notificationMarkRead(id)),
};
