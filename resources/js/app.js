import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js/src/js/vue.js';
import { i18nVue } from 'laravel-vue-i18n';
import Antd from 'ant-design-vue';
import 'ant-design-vue/lib/popover/style/index.css';
import 'ant-design-vue/lib/dropdown/style/index.css';
import 'ant-design-vue/lib/tooltip/style/index.css';
import 'v-calendar/dist/style.css';
import VCalendar from 'v-calendar';
import methods from './methods';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, app, props, plugin }) {
    return createApp({
      render: () => h(app, props),
    })
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(i18nVue, {
        resolve: (lang) => resolvePageComponent(`../../lang/${lang}.json`, import.meta.glob('../../lang/*.json')),
      })
      .use(Antd)
      .use(VCalendar)
      .mixin({ methods: Object.assign({ route }, methods) })
      .component('inertia-link', Link)
      .mount(el);
  },
});

InertiaProgress.init({ color: '#4B5563' });
