import { afterEach, vi } from 'vitest';

// Node 26 exposes an experimental global localStorage that is undefined
// without --localstorage-file. Prefer the jsdom storage used by component tests.
const storage =
    typeof window !== 'undefined' && window.localStorage
        ? window.localStorage
        : (() => {
              const values = new Map<string, string>();
              return {
                  getItem: (key: string) => values.get(key) ?? null,
                  setItem: (key: string, value: string) =>
                      values.set(key, String(value)),
                  removeItem: (key: string) => values.delete(key),
                  clear: () => values.clear(),
                  get length() {
                      return values.size;
                  },
                  key: (index: number) =>
                      Array.from(values.keys())[index] ?? null,
              } satisfies Storage;
          })();

if (typeof window !== 'undefined') {
    Object.defineProperty(globalThis, 'localStorage', {
        configurable: true,
        value: storage,
    });
}

// Keep every test isolated: clear persisted auth tokens and restore mocks.
afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
});
