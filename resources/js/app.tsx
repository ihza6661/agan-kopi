import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { router } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'POS';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx')
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);

        // Update CSRF token in meta tag and axios when page props change
        router.on('navigate', (event) => {
            const csrfToken = event.detail.page.props.csrf_token as string;
            if (csrfToken) {
                // Update meta tag
                const meta = document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
                if (meta) {
                    meta.content = csrfToken;
                }
                // Update axios default header
                if (window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
                }
            }
        });
    },
    progress: {
        color: '#4F46E5',
    },
});
