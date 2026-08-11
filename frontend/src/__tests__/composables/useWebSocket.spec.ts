import { beforeEach, describe, expect, it, vi } from 'vitest';

const echoInstances: MockEcho[] = [];

class MockChannel {
    listeners: string[] = [];

    listen(event: string) {
        this.listeners.push(event);

        return this;
    }
}

class MockEcho {
    config: Record<string, unknown>;
    disconnected = false;
    joined: string[] = [];
    channels = new Map<string, MockChannel>();

    constructor(config: Record<string, unknown>) {
        this.config = config;
        echoInstances.push(this);
    }

    private(name: string) {
        return this.channelFor(`private-${name}`);
    }

    join(name: string) {
        this.joined.push(name);

        return this.channelFor(`presence-${name}`);
    }

    leave() {}

    leaveChannel() {}

    disconnect() {
        this.disconnected = true;
    }

    private channelFor(name: string) {
        const channel = this.channels.get(name) ?? new MockChannel();
        this.channels.set(name, channel);

        return channel;
    }
}

vi.mock('laravel-echo', () => ({ default: MockEcho }));
vi.mock('pusher-js', () => ({ default: class MockPusher {} }));

const storageValues = new Map<string, string>();
const memoryStorage: Storage = {
    get length() {
        return storageValues.size;
    },
    clear: () => storageValues.clear(),
    getItem: (key) => storageValues.get(key) ?? null,
    key: (index) => [...storageValues.keys()][index] ?? null,
    removeItem: (key) => storageValues.delete(key),
    setItem: (key, value) => storageValues.set(key, String(value)),
};

Object.defineProperty(globalThis, 'localStorage', {
    configurable: true,
    value: memoryStorage,
});

describe('useWebSocket', () => {
    beforeEach(() => {
        echoInstances.length = 0;
        localStorage.clear();
        window.history.replaceState({}, '', '/');
        vi.stubEnv('VITE_REVERB_APP_KEY', 'test-key');
        vi.stubEnv('VITE_REVERB_HOST', 'ws.example.test');
        vi.stubEnv('VITE_REVERB_PORT', '443');
        vi.stubEnv('VITE_REVERB_SCHEME', 'https');
        vi.resetModules();
    });

    it('đăng ký chuyến đi bằng presence channel', async () => {
        localStorage.setItem('customer_token', 'customer-token');
        const { useWebSocket } = await import('@/composables/useWebSocket');

        useWebSocket().watchTrip('trip-1', vi.fn());

        expect(echoInstances).toHaveLength(1);
        expect(echoInstances[0].joined).toEqual(['trips.trip-1']);
        expect(
            echoInstances[0].channels.get('presence-trips.trip-1')?.listeners,
        ).toContain('.driver.location.updated');
    });

    it('tạo lại kết nối khi bearer token thay đổi', async () => {
        localStorage.setItem('customer_token', 'first-token');
        const { useWebSocket } = await import('@/composables/useWebSocket');

        useWebSocket().watchTrip('trip-1', vi.fn());
        localStorage.setItem('customer_token', 'second-token');
        useWebSocket().watchTrip('trip-2', vi.fn());

        expect(echoInstances).toHaveLength(2);
        expect(echoInstances[0].disconnected).toBe(true);
        expect(echoInstances[1].config).toMatchObject({
            auth: {
                headers: { Authorization: 'Bearer second-token' },
            },
        });
        expect(window.Echo).toBe(echoInstances[1]);
    });

    it('nghe message mới trên feed support admin', async () => {
        window.history.replaceState({}, '', '/admin/support');
        localStorage.setItem('admin_token', 'admin-token');
        const { useWebSocket } = await import('@/composables/useWebSocket');

        useWebSocket().watchAdminSupport(vi.fn(), vi.fn(), vi.fn());

        expect(
            echoInstances[0].channels.get('private-admin.support')?.listeners,
        ).toEqual([
            '.support.ticket.created',
            '.support.ticket.updated',
            '.support.ticket.message.created',
        ]);
    });
});
