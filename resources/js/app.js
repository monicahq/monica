import './bootstrap';
import '../css/app.css';

import { createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { i18nVue } from 'laravel-vue-i18n';
import { sentry } from './sentry';
import methods from './methods';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Monica';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  progress: {
    color: '#4B5563',
  },
  setup({ el, App, props, plugin }) {
    return createSSRApp({
      mounted() {
        this.$nextTick().then(() => {
          sentry.setContext(this);
        });
      },
      render: () => h(App, props),
    })
      .use(plugin)
      .use(ZiggyVue, window.Ziggy)
      .use(i18nVue, {
        resolve: (lang) => resolvePageComponent(`../../lang/${lang}.json`, import.meta.glob('../../lang/*.json')),
      })
      .use(sentry, props.initialPage.props.sentry)
      .mixin({ methods: { route: window.route, ...methods } })
      .mount(el);
  },
});
