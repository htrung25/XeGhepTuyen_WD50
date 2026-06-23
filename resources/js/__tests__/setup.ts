import { afterEach, vi } from 'vitest';

// Keep every test isolated: clear persisted auth tokens and restore mocks.
afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
});
