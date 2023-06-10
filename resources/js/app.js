import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js/src/js/vue.js';
import { i18nVue } from 'laravel-vue-i18n';
import { sentry } from './sentry';
import methods from './methods';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';
const sentryConfigVal = typeof SentryConfig !== 'undefined' ? SentryConfig : {};

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  progress: {
    color: '#4B5563',
  },
  setup({ el, App, props, plugin }) {
    return createApp({
      render: () => h(App, props),
      mounted() {
        this.$nextTick(() => {
          sentry.setContext(this);
        });
      },
    })
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(i18nVue, {
        resolve: (lang) => resolvePageComponent(`../../lang/${lang}.json`, import.meta.glob('../../lang/*.json')),
      })
      .use(sentry, {
        ...sentryConfigVal,
        release: import.meta.env.VITE_SENTRY_RELEASE,
      })
      .mixin({ methods: Object.assign({ route }, methods) })
      .mount(el);
  },
});
