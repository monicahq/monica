import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import basicSsl from '@vitejs/plugin-basic-ssl';
import i18n from 'laravel-vue-i18n/vite';
import tailwindcss from '@tailwindcss/vite';
import { sentryVitePlugin } from '@sentry/vite-plugin';

export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
  const env = loadEnv(mode, process.cwd(), '');
  return {
    plugins: [
      laravel({
        input: 'resources/js/app.js',
        ssr: 'resources/js/ssr.js',
        refresh: true,
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
      tailwindcss(),
      i18n(),
      basicSsl(),
      sentryVitePlugin({
        disable: !env.SENTRY_ORG || !env.SENTRY_PROJECT,
        org: env.SENTRY_ORG,
        project: env.SENTRY_PROJECT,
      }),
    ],
    server: {
      https: true,
      host: 'localhost',
    },
    ssr: {
      noExternal: ['@inertiajs/server'],
    },
    build: {
      sourcemap: env.VITE_PROD_SOURCE_MAPS,
    },
  };
});
