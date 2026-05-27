import './bootstrap';
import '../css/app.css'

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { loadSiteSettings } from './settingsCache.js';

function setFavicon(href) {
    if (!href) return;
    let link = document.querySelector("link[rel='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.href = href;
}

setFavicon('/favicon.ico');
loadSiteSettings()
    .then((settings) => {
        const icon = settings?.favicon_icon || settings?.favicon_url;
        if (icon) setFavicon(icon);
    })
    .catch(() => {});

router.on('success', () => {
    window.scrollTo({ top: 0, behavior: 'auto' });
});

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