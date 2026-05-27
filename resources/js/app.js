import './bootstrap';
import '../css/app.css'

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { loadSiteSettings } from './settingsCache.js';

loadSiteSettings().then((data) => {
  const favicon = data?.favicon_icon || data?.favicon_url;
  if (!favicon) return;
  let link = document.querySelector("link[rel='icon']");
  if (!link) {
    link = document.createElement('link');
    link.rel = 'icon';
    document.head.appendChild(link);
  }
  link.href = favicon;
}).catch(() => {});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        const page = pages[`./Pages/${name}.vue`]

        if (!page) {
            throw new Error(`Не найдена страница: ./Pages/${name}.vue`)
        }

        return page
    },
    setup({el, App, props, plugin}) {
        createApp({render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    }
})