import './bootstrap';
import '@unovis/ts/styles';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Notifications from '@/Components/Notifications.vue';

createInertiaApp({
    title: (title) => title ? `${title} - SSCEVS` : 'SSCEVS',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h('div', [
                h(App, props),
                h(Notifications),
            ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});
