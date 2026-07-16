import { ref, onMounted, onUnmounted } from 'vue';
import { adminApi } from '@/api/admin.api';

export interface AdminNotification {
    id: string;
    title: string;
    body: string;
    data: { kind?: string; link?: string } | null;
    is_read: boolean;
    sent_at: string;
}

/**
 * Thông báo admin: tải danh sách + đếm chưa đọc, polling định kỳ (chưa có Reverb).
 */
export function useAdminNotifications(pollMs = 45000) {
    const items = ref<AdminNotification[]>([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;

    async function load() {
        loading.value = true;
        const { data, meta } = await adminApi.getNotifications();
        loading.value = false;
        items.value = (data as AdminNotification[]) ?? [];
        unreadCount.value =
            (meta as { unread_count?: number } | null)?.unread_count ?? 0;
    }

    async function markRead(n: AdminNotification) {
        if (n.is_read) return;
        n.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        await adminApi.markNotificationRead(n.id);
    }

    async function markAllRead() {
        unreadCount.value = 0;
        items.value.forEach((n) => (n.is_read = true));
        await adminApi.markAllNotificationsRead();
    }

    onMounted(() => {
        load();
        timer = setInterval(load, pollMs);
    });
    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { items, unreadCount, loading, load, markRead, markAllRead };
}
