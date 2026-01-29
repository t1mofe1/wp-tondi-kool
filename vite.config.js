import { defineConfig } from 'vite';
import mkcert from 'vite-plugin-mkcert';
import path from 'node:path';

const DEV_HOST = 'localhost';
const DEV_PORT = 5173;

export default defineConfig(({ mode }) => ({
  base: mode === 'development' ? '/' : './',
  server: {
    https: true,
    host: DEV_HOST,
    port: DEV_PORT,
    strictPort: true,
    origin: `https://${DEV_HOST}:${DEV_PORT}`,
    cors: true,
    hmr: { protocol: 'wss', host: DEV_HOST, port: DEV_PORT },
  },
  build: {
    manifest: true,
    emptyOutDir: true,
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: mode === 'development',
    rollupOptions: { input: { main: 'assets/js/main.js' } },
  },
  resolve: { alias: { '@': path.resolve(__dirname, 'assets') } },
  plugins: [
    mkcert(),
    {
      name: 'php-hmr',
      handleHotUpdate({ file, server }) {
        if (file.endsWith('.php')) {
          server.ws.send({ type: 'full-reload' });
        }
      },
    },
  ],
}));
