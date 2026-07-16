import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useDriverAuthStore } from '@/stores/driver.auth.store';

const user = {
    id: 'd1',
    full_name: 'Nguyễn Văn Tài',
    phone: '0923456789',
    email: null,
    avatar_url: null,
    rating_avg: 4.8,
    total_trips: 120,
    is_verified: true,
};

const driverInfo = {
    id: 'drv-1',
    status: 'verified',
    license_number: 'B2-123',
    license_expiry: '2028-12-31',
    operator: { company_name: 'Xe Ghép Bắc Hà' },
};

describe('useDriverAuthStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('setAuth persists token, user and driver info across all three keys', () => {
        const store = useDriverAuthStore();

        store.setAuth('d-tok', user, driverInfo);

        expect(store.isAuthenticated).toBe(true);
        expect(store.driver).toEqual(driverInfo);
        expect(localStorage.getItem('driver_token')).toBe('d-tok');
        expect(JSON.parse(localStorage.getItem('driver_user')!)).toEqual(user);
        expect(JSON.parse(localStorage.getItem('driver_info')!)).toEqual(
            driverInfo,
        );
    });

    it('setOnline persists the online flag as a string', () => {
        const store = useDriverAuthStore();

        store.setOnline(true);
        expect(store.isOnline).toBe(true);
        expect(localStorage.getItem('driver_online')).toBe('true');

        store.setOnline(false);
        expect(store.isOnline).toBe(false);
        expect(localStorage.getItem('driver_online')).toBe('false');
    });

    it('logout clears every persisted driver key including the online flag', () => {
        const store = useDriverAuthStore();
        store.setAuth('d-tok', user, driverInfo);
        store.setOnline(true);

        store.logout();

        expect(store.isAuthenticated).toBe(false);
        expect(store.driver).toBeNull();
        expect(store.isOnline).toBe(false);
        for (const key of [
            'driver_token',
            'driver_user',
            'driver_info',
            'driver_online',
        ]) {
            expect(localStorage.getItem(key)).toBeNull();
        }
    });

    it('reads isOnline=true back from localStorage on creation', () => {
        localStorage.setItem('driver_online', 'true');
        const store = useDriverAuthStore();
        expect(store.isOnline).toBe(true);
    });
});
