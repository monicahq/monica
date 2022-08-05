import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import basicSsl from '@vitejs/plugin-basic-ssl';
import i18n from 'laravel-vue-i18n/vite';

export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
  const env = loadEnv(mode, process.cwd(), '');
  return {
    plugins: [
      laravel({
        input: 'resources/js/app.js',
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
      i18n(),
      basicSsl(),
    ],
    server: {
      https: true,
      host: 'localhost',
    },
    build: {
      sourcemap: env.VITE_PROD_SOURCE_MAPS,
    },
  };
});
