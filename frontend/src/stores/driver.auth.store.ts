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

const TOKEN_KEY = 'driver_token';
const USER_KEY = 'driver_user';
const INFO_KEY = 'driver_info';
const ONLINE_KEY = 'driver_online';

export const useDriverAuthStore = defineStore('driverAuth', () => {
    const token = ref<string | null>(localStorage.getItem(TOKEN_KEY));
    const user = ref<DriverUser | null>(
        JSON.parse(localStorage.getItem(USER_KEY) ?? 'null'),
    );
    const driver = ref<DriverInfo | null>(
        JSON.parse(localStorage.getItem(INFO_KEY) ?? 'null'),
    );
    const isOnline = ref<boolean>(localStorage.getItem(ONLINE_KEY) === 'true');

    const isAuthenticated = computed(() => !!token.value);

    /** true = đang dùng mật khẩu tạm thời, bắt buộc đổi trước khi dùng app */
    const mustChangePassword = computed(
        () => !!user.value?.must_change_password,
    );

    function setAuth(t: string, u: DriverUser, d: DriverInfo) {
        token.value = t;
        user.value = u;
        driver.value = d;
        localStorage.setItem(TOKEN_KEY, t);
        localStorage.setItem(USER_KEY, JSON.stringify(u));
        localStorage.setItem(INFO_KEY, JSON.stringify(d));
    }

    /** Gọi sau khi đổi mật khẩu thành công để xoá cờ bắt buộc */
    function clearMustChangePassword() {
        if (user.value) {
            user.value = { ...user.value, must_change_password: false };
            localStorage.setItem(USER_KEY, JSON.stringify(user.value));
        }
    }

    function setOnline(v: boolean) {
        isOnline.value = v;
        localStorage.setItem(ONLINE_KEY, String(v));
    }

    function logout() {
        token.value = null;
        user.value = null;
        driver.value = null;
        isOnline.value = false;
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        localStorage.removeItem(INFO_KEY);
        localStorage.removeItem(ONLINE_KEY);
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
