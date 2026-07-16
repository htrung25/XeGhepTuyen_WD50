import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface AdminRoleBrief {
    id: string;
    name: string;
    slug: string;
}

interface AdminUser {
    id: string;
    full_name: string;
    email: string;
    role: string;
    phone?: string | null;
    avatar_url?: string | null;
    admin_role?: AdminRoleBrief | null;
    is_super?: boolean;
    permissions?: string[];
}

export const useAdminAuthStore = defineStore('adminAuth', () => {
    const token = ref<string | null>(localStorage.getItem('admin_token'));
    const user = ref<AdminUser | null>(
        JSON.parse(localStorage.getItem('admin_user') ?? 'null'),
    );

    const isAuthenticated = computed(() => !!token.value);
    const isSuper = computed(() => !!user.value?.is_super);
    const permissions = computed<string[]>(() => user.value?.permissions ?? []);

    /** Kiểm tra quyền theo key (AdminPermission). Super admin luôn true. */
    function can(key: string): boolean {
        return isSuper.value || permissions.value.includes(key);
    }

    function setAuth(t: string, u: AdminUser) {
        token.value = t;
        user.value = u;
        localStorage.setItem('admin_token', t);
        localStorage.setItem('admin_user', JSON.stringify(u));
    }

    function updateUser(u: Partial<AdminUser>) {
        if (user.value) {
            user.value = { ...user.value, ...u };
            localStorage.setItem('admin_user', JSON.stringify(user.value));
        }
    }

    function logout() {
        token.value = null;
        user.value = null;
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
    }

    return {
        token,
        user,
        isAuthenticated,
        isSuper,
        permissions,
        can,
        setAuth,
        updateUser,
        logout,
    };
});
