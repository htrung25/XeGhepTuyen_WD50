import { onMounted, onUnmounted, ref } from 'vue';
import { operatorApi } from '@/api/operator.api';

export interface OperatorNotification {
    id: string;
    title: string;
    body: string;
    data: { kind?: string; link?: string; trip_id?: string } | null;
    is_read: boolean;
    sent_at: string;
}

export function useOperatorNotifications(pollMs = 20000) {
    const items = ref<OperatorNotification[]>([]);
    const unreadCount = ref(0);
    const pendingCounts = ref<Record<string, number>>({});
    const loading = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;

    async function load() {
        loading.value = true;
        const [notifications, pending] = await Promise.all([
            operatorApi.getNotifications(),
            operatorApi.getPendingCounts(),
        ]);
        loading.value = false;

        if (!notifications.error) {
            items.value = (notifications.data as OperatorNotification[]) ?? [];
            unreadCount.value =
                (notifications.meta as { unread_count?: number } | null)
                    ?.unread_count ?? 0;
        }
        if (!pending.error) {
            pendingCounts.value =
                (pending.data as Record<string, number>) ?? {};
        }
    }

    async function markRead(notification: OperatorNotification) {
        if (notification.is_read) return;
        notification.is_read = true;
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        await operatorApi.markNotificationRead(notification.id);
    }

    async function markAllRead() {
        unreadCount.value = 0;
        items.value.forEach((notification) => {
            notification.is_read = true;
        });
        await operatorApi.markAllNotificationsRead();
    }

    onMounted(() => {
        load();
        timer = setInterval(load, pollMs);
    });
    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return {
        items,
        unreadCount,
        pendingCounts,
        loading,
        load,
        markRead,
        markAllRead,
    };
}
