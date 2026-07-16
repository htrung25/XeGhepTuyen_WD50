import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/api/admin.api', () => ({
    adminApi: {
        getDashboard: vi.fn(),
        getOperators: vi.fn(),
        getDrivers: vi.fn(),
    },
}));

import { adminApi } from '@/api/admin.api';
import { useAdminStore } from '@/stores/admin.store';

const stats = {
    bookings_today: 5,
    revenue_today: 1_000_000,
    active_trips: 2,
    new_users_today: 3,
    pending_operators: 2,
    pending_drivers: 4,
};

describe('useAdminStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('fetchDashboard maps data.stats into state and toggles isLoading', async () => {
        vi.mocked(adminApi.getDashboard).mockResolvedValue({
            data: { stats },
            message: null,
            error: null,
        });
        const store = useAdminStore();

        const promise = store.fetchDashboard();
        expect(store.isLoading).toBe(true);
        await promise;

        expect(store.stats).toEqual(stats);
        expect(store.isLoading).toBe(false);
    });

    it('fetchPendingOperators requests status=pending and fills the list', async () => {
        const operators = [
            {
                id: 'o1',
                company_name: 'Bắc Hà',
                owner_name: 'A',
                status: 'pending',
                created_at: '',
            },
        ];
        vi.mocked(adminApi.getOperators).mockResolvedValue({
            data: operators,
            message: null,
            error: null,
        });
        const store = useAdminStore();

        await store.fetchPendingOperators();

        expect(adminApi.getOperators).toHaveBeenCalledWith({
            status: 'pending',
        });
        expect(store.pendingOperators).toEqual(operators);
    });

    it('decrement helpers reduce the pending counters without going below zero', async () => {
        vi.mocked(adminApi.getDashboard).mockResolvedValue({
            data: { stats: { ...stats, pending_operators: 1 } },
            message: null,
            error: null,
        });
        const store = useAdminStore();
        await store.fetchDashboard();

        store.decrementPendingOperators();
        expect(store.stats!.pending_operators).toBe(0);

        store.decrementPendingOperators(); // already 0 → stays clamped
        expect(store.stats!.pending_operators).toBe(0);
    });

    it('decrement is a no-op when stats have not loaded yet', () => {
        const store = useAdminStore();
        expect(() => store.decrementPendingDrivers()).not.toThrow();
        expect(store.stats).toBeNull();
    });
});
