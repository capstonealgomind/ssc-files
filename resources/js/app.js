import { syncCsrfToken } from './bootstrap';
import '@unovis/ts/styles';
import 'leaflet/dist/leaflet.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Notifications from '@/Components/Notifications.vue';

router.on('success', (event) => {
    syncCsrfToken(event.detail.page.props.csrf_token);
});

createInertiaApp({
    title: (title) => title ? `${title} - SSCEVS` : 'SSCEVS',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        syncCsrfToken(props.initialPage.props.csrf_token);

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
