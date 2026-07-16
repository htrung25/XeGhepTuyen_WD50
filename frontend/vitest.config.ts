import { fileURLToPath } from 'node:url'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

// Standalone config so Vitest does not load the Laravel/Inertia/Wayfinder
// plugins from vite.config.ts (those need a running Laravel context).
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./resources/js/__tests__/setup.ts'],
    include: ['resources/js/**/*.spec.ts'],
    clearMocks: true,
  },
})
