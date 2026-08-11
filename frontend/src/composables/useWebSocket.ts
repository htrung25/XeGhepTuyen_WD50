import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { onUnmounted } from 'vue';
import { API_ORIGIN } from '@/api/client';
import type {
    SupportMessageCreatedEvent,
    SupportTicketCreatedEvent,
    SupportTicketUpdatedEvent,
} from '@/types/support';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

interface LocationUpdate {
    trip_id: string;
    driver_id: string;
    lat: number;
    lng: number;
    speed: number;
    heading: number;
    timestamp: string;
}

let echoInstance: Echo<'reverb'> | null = null;

function websocketToken(): string {
    const path = window.location.pathname;
    const portal = path.startsWith('/admin')
        ? 'admin'
        : path.startsWith('/operator')
          ? 'operator'
          : path.startsWith('/driver')
            ? 'driver'
            : 'customer';

    return localStorage.getItem(`${portal}_token`) ?? '';
}

function getEcho(): Echo<'reverb'> | null {
    if (echoInstance) return echoInstance;

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST ?? 'localhost';
    const port = import.meta.env.VITE_REVERB_PORT ?? 8080;
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    if (!key) return null;

    window.Pusher = Pusher;

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: Number(port),
        wssPort: Number(port),
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${API_ORIGIN}/api/broadcasting/auth`,
        auth: {
            headers: {
                Authorization: `Bearer ${websocketToken()}`,
            },
        },
    });

    return echoInstance;
}

export function useWebSocket() {
    const subscriptions: (() => void)[] = [];

    function watchAdminMonitor(onEvent: (data: unknown) => void) {
        const echo = getEcho();
        if (!echo) return;
        echo.private('admin.monitor').listen(
            '.driver.location.updated',
            onEvent,
        );
        subscriptions.push(() => echo.leaveChannel('private-admin.monitor'));
    }

    function watchTrip(
        tripId: string,
        onLocation: (data: LocationUpdate) => void,
    ) {
        const echo = getEcho();
        if (!echo) return;
        echo.channel(`trips.${tripId}`).listen(
            '.driver.location.updated',
            onLocation,
        );
        subscriptions.push(() => echo.leaveChannel(`trips.${tripId}`));
    }

    function watchUserNotifications(
        userId: string,
        onNotification: (data: unknown) => void,
    ) {
        const echo = getEcho();
        if (!echo) return;
        echo.private(`users.${userId}`).listen(
            '.notification.sent',
            onNotification,
        );
        subscriptions.push(() => echo.leaveChannel(`private-users.${userId}`));
    }

    function watchSupportTicket(
        ticketId: string,
        onMessage: (event: SupportMessageCreatedEvent) => void,
        onUpdated: (event: SupportTicketUpdatedEvent) => void,
        includeInternal = false,
    ) {
        const echo = getEcho();
        if (!echo) return;

        echo.private(`support.tickets.${ticketId}`)
            .listen('.support.ticket.message.created', onMessage)
            .listen('.support.ticket.updated', onUpdated);

        if (includeInternal) {
            echo.private(`admin.support.tickets.${ticketId}`).listen(
                '.support.ticket.message.created',
                onMessage,
            );
        }

        subscriptions.push(() => {
            echo.leave(`support.tickets.${ticketId}`);
            if (includeInternal) {
                echo.leave(`admin.support.tickets.${ticketId}`);
            }
        });
    }

    function watchAdminSupport(
        onCreated: (event: SupportTicketCreatedEvent) => void,
        onUpdated: (event: SupportTicketUpdatedEvent) => void,
    ) {
        const echo = getEcho();
        if (!echo) return;

        echo.private('admin.support')
            .listen('.support.ticket.created', onCreated)
            .listen('.support.ticket.updated', onUpdated);

        subscriptions.push(() => echo.leave('admin.support'));
    }

    onUnmounted(() => {
        subscriptions.forEach((fn) => fn());
    });

    return {
        watchAdminMonitor,
        watchTrip,
        watchUserNotifications,
        watchSupportTicket,
        watchAdminSupport,
    };
}
