import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface DriverUser {
    id: string;
    full_name: string;
    phone: string;
    email: string | null;
    birth_date: string | null;
    avatar_url: string | null;
    rating_avg: number;
    total_trips: number;
    is_verified: boolean;
    must_change_password: boolean;
}

interface DriverInfo {
    id: string;
    status: string;
    license_number: string;
    license_expiry: string;
    operator: { company_name: string };
}

export const useDriverAuthStore = defineStore('driverAuth', () => {
    const token = ref<string | null>(localStorage.getItem('driver_token'));
    const user = ref<DriverUser | null>(
        JSON.parse(localStorage.getItem('driver_user') ?? 'null'),
    );
    const driver = ref<DriverInfo | null>(
        JSON.parse(localStorage.getItem('driver_info') ?? 'null'),
    );
    const isOnline = ref<boolean>(
        localStorage.getItem('driver_online') === 'true',
    );

    const isAuthenticated = computed(() => !!token.value);

    /** true = đang dùng mật khẩu tạm thời, bắt buộc đổi trước khi dùng app */
    const mustChangePassword = computed(() => !!user.value?.must_change_password);

    function setAuth(t: string, u: DriverUser, d: DriverInfo) {
        token.value = t;
        user.value = u;
        driver.value = d;
        localStorage.setItem('driver_token', t);
        localStorage.setItem('driver_user', JSON.stringify(u));
        localStorage.setItem('driver_info', JSON.stringify(d));
    }

    /** Gọi sau khi đổi mật khẩu thành công để xoá cờ bắt buộc */
    function clearMustChangePassword() {
        if (user.value) {
            user.value = { ...user.value, must_change_password: false };
            localStorage.setItem('driver_user', JSON.stringify(user.value));
        }
    }

    function setOnline(v: boolean) {
        isOnline.value = v;
        localStorage.setItem('driver_online', String(v));
    }

    function logout() {
        token.value = null;
        user.value = null;
        driver.value = null;
        isOnline.value = false;
        localStorage.removeItem('driver_token');
        localStorage.removeItem('driver_user');
        localStorage.removeItem('driver_info');
        localStorage.removeItem('driver_online');
    }

    return {
        token,
        user,
        driver,
        isOnline,
        isAuthenticated,
        mustChangePassword,
        setAuth,
        clearMustChangePassword,
        setOnline,
        logout,
    };
});
