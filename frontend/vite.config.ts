import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';
import type { Connect, Plugin, PreviewServer, ViteDevServer } from 'vite';

const root = fileURLToPath(new URL('.', import.meta.url));

// 4 SPA độc lập — URL prefix quyết định HTML entry được serve.
// Production trên Vercel dùng rewrites tương đương (xem vercel.json).
const spaEntries: Record<string, string> = {
    '/admin': '/admin.html',
    '/operator': '/operator.html',
    '/driver': '/driver.html',
};

// History-mode fallback cho dev/preview: request điều hướng (Accept: text/html)
// được rewrite về đúng HTML entry theo prefix, mặc định là customer (index.html).
function multiSpaFallback(): Plugin {
    const middleware: Connect.NextHandleFunction = (req, _res, next) => {
        const accept = req.headers.accept ?? '';
        const path = (req.url ?? '').split('?')[0];
        if (
            req.method === 'GET' &&
            accept.includes('text/html') &&
            !path.includes('.')
        ) {
            const entry = Object.entries(spaEntries).find(
                ([prefix]) => path === prefix || path.startsWith(`${prefix}/`),
            );
            req.url = entry ? entry[1] : '/index.html';
        }
        next();
    };
    return {
        name: 'multi-spa-fallback',
        configureServer(server: ViteDevServer) {
            server.middlewares.use(middleware);
        },
        configurePreviewServer(server: PreviewServer) {
            server.middlewares.use(middleware);
        },
    };
}

export default defineConfig({
    plugins: [multiSpaFallback(), tailwindcss(), vue()],
    resolve: {
        alias: {
            '@': resolve(root, 'src'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                customer: resolve(root, 'index.html'),
                driver: resolve(root, 'driver.html'),
                operator: resolve(root, 'operator.html'),
                admin: resolve(root, 'admin.html'),
            },
        },
    },
    server: {
        port: 5173,
    },
});
