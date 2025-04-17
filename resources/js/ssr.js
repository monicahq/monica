import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { sentry } from './sentry';

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    title: (title) => `${title} - ${page.props.appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ App, props, plugin }) {
      return createSSRApp({
        render: () => h(App, props),
        mounted() {
          this.$nextTick(() => {
            sentry.setContext(this);
          });
        },
      })
        .use(plugin)
        .use(ZiggyVue, {
          ...page.props.ziggy,
          location: new URL(page.props.ziggy.location),
        })
        .use(i18nVue, {
          resolve: (lang) => resolvePageComponent(`../../lang/${lang}.json`, import.meta.glob('../../lang/*.json')),
        })
        .use(sentry, page.props.sentry)
        .mixin({ methods: { route: window.route, ...methods } });
    },
  }),
);
