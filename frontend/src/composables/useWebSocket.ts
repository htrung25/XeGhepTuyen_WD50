import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { onUnmounted } from 'vue';

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
        authEndpoint: '/api/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${
                    localStorage.getItem('admin_token') ??
                    localStorage.getItem('operator_token') ??
                    localStorage.getItem('driver_token') ??
                    localStorage.getItem('customer_token') ??
                    ''
                }`,
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

    onUnmounted(() => {
        subscriptions.forEach((fn) => fn());
    });

    return { watchAdminMonitor, watchTrip, watchUserNotifications };
}
