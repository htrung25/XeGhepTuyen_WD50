import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface UserInfo {
    id: string;
    full_name: string;
    email: string;
    phone: string;
}

interface OperatorInfo {
    id: string;
    company_name: string;
    status: string;
    commission_rate: number;
}

export const useOperatorAuthStore = defineStore('operatorAuth', () => {
    const token = ref<string | null>(localStorage.getItem('operator_token'));
    const user = ref<UserInfo | null>(
        JSON.parse(localStorage.getItem('operator_user') ?? 'null'),
    );
    const operator = ref<OperatorInfo | null>(
        JSON.parse(localStorage.getItem('operator_info') ?? 'null'),
    );

    const isAuthenticated = computed(() => !!token.value);

    function setAuth(t: string, u: UserInfo, o: OperatorInfo) {
        token.value = t;
        user.value = u;
        operator.value = o;
        localStorage.setItem('operator_token', t);
        localStorage.setItem('operator_user', JSON.stringify(u));
        localStorage.setItem('operator_info', JSON.stringify(o));
    }

    function logout() {
        token.value = null;
        user.value = null;
        operator.value = null;
        localStorage.removeItem('operator_token');
        localStorage.removeItem('operator_user');
        localStorage.removeItem('operator_info');
    }

    return { token, user, operator, isAuthenticated, setAuth, logout };
});
