import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  root: resolve(__dirname, 'resources'),
  base: '/wp-content/themes/culvers/',
  plugins: [tailwindcss()],
  build: {
    outDir: resolve(__dirname, '.'),
    emptyOutDir: false,
    manifest: false,
    cssCodeSplit: false,
    minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
    sourcemap: process.env.NODE_ENV === 'production' ? false : 'inline',
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/scripts/app.js'),
      },
      output: {
        format: 'iife',
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'css/app.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    hmr: {
      host: 'localhost',
    },
  },
});
