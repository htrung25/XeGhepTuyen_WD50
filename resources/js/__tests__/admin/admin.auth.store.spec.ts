import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useAdminAuthStore } from '@/stores/admin.auth.store';

const sampleUser = {
    id: 'u1',
    full_name: 'Quản trị',
    email: 'admin@xeghep.vn',
    role: 'admin',
};

describe('useAdminAuthStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('starts unauthenticated when localStorage is empty', () => {
        const store = useAdminAuthStore();
        expect(store.token).toBeNull();
        expect(store.user).toBeNull();
        expect(store.isAuthenticated).toBe(false);
    });

    it('setAuth stores the token + user in state and persists them', () => {
        const store = useAdminAuthStore();

        store.setAuth('tok-123', sampleUser);

        expect(store.isAuthenticated).toBe(true);
        expect(store.user).toEqual(sampleUser);
        expect(localStorage.getItem('admin_token')).toBe('tok-123');
        expect(JSON.parse(localStorage.getItem('admin_user')!)).toEqual(
            sampleUser,
        );
    });

    it('logout clears state and removes persisted keys', () => {
        const store = useAdminAuthStore();
        store.setAuth('tok-123', sampleUser);

        store.logout();

        expect(store.token).toBeNull();
        expect(store.user).toBeNull();
        expect(store.isAuthenticated).toBe(false);
        expect(localStorage.getItem('admin_token')).toBeNull();
        expect(localStorage.getItem('admin_user')).toBeNull();
    });

    it('rehydrates token + user from localStorage on creation', () => {
        localStorage.setItem('admin_token', 'persisted');
        localStorage.setItem('admin_user', JSON.stringify(sampleUser));

        const store = useAdminAuthStore();

        expect(store.isAuthenticated).toBe(true);
        expect(store.user).toEqual(sampleUser);
    });
});
