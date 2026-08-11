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
        Echo?: Echo<'reverb'>;
    }
}

interface LocationUpdate {
    lat: number;
    lng: number;
    updated_at: string;
    eta_minutes: number | null;
}

let echoInstance: Echo<'reverb'> | null = null;
let echoToken: string | null = null;

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
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST ?? 'localhost';
    const port = import.meta.env.VITE_REVERB_PORT ?? 8080;
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
    const token = websocketToken();

    if (!key || !token) return null;
    if (echoInstance && echoToken === token) return echoInstance;

    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        window.Echo = undefined;
    }

    window.Pusher = Pusher;
    echoToken = token;

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
                Authorization: `Bearer ${token}`,
            },
        },
    });
    window.Echo = echoInstance;

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
        echo.join(`trips.${tripId}`).listen(
            '.driver.location.updated',
            onLocation,
        );
        subscriptions.push(() => echo.leave(`trips.${tripId}`));
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
        onMessage?: (event: SupportMessageCreatedEvent) => void,
    ) {
        const echo = getEcho();
        if (!echo) return;

        echo.private('admin.support')
            .listen('.support.ticket.created', onCreated)
            .listen('.support.ticket.updated', onUpdated);
        if (onMessage) {
            echo.private('admin.support').listen(
                '.support.ticket.message.created',
                onMessage,
            );
        }

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
