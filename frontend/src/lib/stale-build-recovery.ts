const STALE_BUILD_RELOAD_KEY = 'xeghep:stale-build-reload-at';
const RELOAD_GUARD_MS = 15_000;

type RecoveryEnvironment = {
    addEventListener: (type: string, listener: EventListener) => void;
    storage: Pick<Storage, 'getItem' | 'setItem'>;
    reload: () => void;
    now: () => number;
};

/**
 * Vite phát `vite:preloadError` khi một SPA đang mở cố tải lazy chunk thuộc
 * deployment cũ. Reload một lần để lấy index và asset manifest mới, đồng thời
 * dùng sessionStorage để tránh vòng lặp reload nếu deployment thật sự lỗi.
 */
export function installStaleBuildRecovery(
    environment: RecoveryEnvironment = {
        addEventListener: window.addEventListener.bind(window),
        storage: window.sessionStorage,
        reload: () => window.location.reload(),
        now: () => Date.now(),
    },
) {
    let reloadStarted = false;

    const handlePreloadError: EventListener = (event) => {
        event.preventDefault();

        if (reloadStarted) return;

        const now = environment.now();
        const lastReload = Number(
            environment.storage.getItem(STALE_BUILD_RELOAD_KEY) ?? 0,
        );

        if (now - lastReload < RELOAD_GUARD_MS) return;

        reloadStarted = true;
        environment.storage.setItem(STALE_BUILD_RELOAD_KEY, String(now));
        environment.reload();
    };

    environment.addEventListener('vite:preloadError', handlePreloadError);

    return handlePreloadError;
}
