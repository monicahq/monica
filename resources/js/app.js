require('./bootstrap');

import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import Antd from 'ant-design-vue';
import 'ant-design-vue/lib/popover/style/index.css';
import 'ant-design-vue/lib/dropdown/style/index.css';
import 'ant-design-vue/lib/date-picker/style/index.css';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => require(`./Pages/${name}.vue`),
  setup({ el, app, props, plugin }) {
    return createApp({ render: () => h(app, props) })
      .use(plugin)
      .use(Antd)
      .mixin({ methods: _.assign({ route }, require('./methods').default) })
      .component('inertia-link', Link)
      .mount(el);
  },
});

InertiaProgress.init({ color: '#4B5563' });
