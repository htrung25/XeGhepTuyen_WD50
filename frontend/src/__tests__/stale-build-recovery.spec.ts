import { describe, expect, it, vi } from 'vitest';
import { installStaleBuildRecovery } from '@/lib/stale-build-recovery';

function createEnvironment(lastReload?: string) {
    let listener: EventListener | undefined;
    const values = new Map<string, string>();
    if (lastReload) values.set('xeghep:stale-build-reload-at', lastReload);

    const reload = vi.fn();
    const environment = {
        addEventListener: vi.fn((type: string, callback: EventListener) => {
            if (type === 'vite:preloadError') listener = callback;
        }),
        storage: {
            getItem: (key: string) => values.get(key) ?? null,
            setItem: (key: string, value: string) => values.set(key, value),
        },
        reload,
        now: () => 20_000,
    };

    installStaleBuildRecovery(environment);

    return { listener: () => listener, reload, values };
}

describe('stale build recovery', () => {
    it('reloads once when Vite reports an unavailable lazy chunk', () => {
        const { listener, reload, values } = createEnvironment();
        const event = new Event('vite:preloadError', { cancelable: true });

        listener()?.(event);
        listener()?.(new Event('vite:preloadError', { cancelable: true }));

        expect(event.defaultPrevented).toBe(true);
        expect(reload).toHaveBeenCalledTimes(1);
        expect(values.get('xeghep:stale-build-reload-at')).toBe('20000');
    });

    it('does not enter a reload loop after a recent recovery attempt', () => {
        const { listener, reload } = createEnvironment('10000');

        listener()?.(new Event('vite:preloadError', { cancelable: true }));

        expect(reload).not.toHaveBeenCalled();
    });
});
