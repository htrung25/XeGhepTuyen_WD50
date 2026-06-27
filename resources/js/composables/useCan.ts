import { useAdminAuthStore } from '@/stores/admin.auth.store';

/**
 * Kiểm tra quyền admin trong component: `const { can } = useCan()`.
 * Dùng `v-if="can('operators.review')"` để ẩn/hiện menu & nút thao tác.
 */
export function useCan() {
    const auth = useAdminAuthStore();

    const can = (key: string): boolean => auth.can(key);

    return { can };
}
