import '../css/app.css';
import './bootstrap';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
        registrations.forEach((registration) => registration.unregister());
    });
}

if (typeof window !== 'undefined' && 'caches' in window) {
    caches.keys().then((cacheNames) => {
        cacheNames.forEach((cacheName) => caches.delete(cacheName));
    });
}

if (typeof window !== 'undefined') {
    const appKey = import.meta.env.VITE_REVERB_APP_KEY || 'w60yiz2uk29hsgi3bxgg';
    if (import.meta.env.DEV) {
        console.log('[Echo] initializing, key:', appKey, 'host:', import.meta.env.VITE_REVERB_HOST, 'port:', import.meta.env.VITE_REVERB_PORT);
    }
    if (appKey) {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: appKey,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : (window.location.port ? Number(window.location.port) : 80),
            wssPort: import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : (window.location.port ? Number(window.location.port) : 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? (window.location.protocol === 'https:' ? 'https' : 'http')) === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': window.getCsrfToken?.(),
                },
            },
        });
    } else {
        console.warn('[Echo] key not found, skipping initialization');
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mixin({
                methods: {
                    __(key) {
                        const translations = this.$page.props.translations || {};
                        if (translations[key] !== undefined) {
                            return translations[key];
                        }
                        const parts = key.split('.');
                        let current = translations;
                        for (const part of parts) {
                            if (current && current[part] !== undefined) {
                                current = current[part];
                            } else {
                                return key;
                            }
                        }
                        return typeof current === 'string' ? current : key;
                    },
                    $swal(options) {
                        const opts = typeof options === 'string' ? { title: options } : options;
                        const isToast = opts.toast === true;

                        if (isToast) {
                            window.dispatchEvent(new CustomEvent('show-demo-toast', {
                                detail: {
                                    title: opts.title || 'Notifikasi',
                                    message: opts.text || '',
                                    type: opts.icon || 'success'
                                }
                            }));
                            return Promise.resolve({ isConfirmed: true });
                        }

                        return new Promise((resolve) => {
                            window.dispatchEvent(new CustomEvent('trigger-custom-alert', {
                                detail: {
                                    options: {
                                        title: opts.title || '',
                                        text: opts.text || '',
                                        icon: opts.icon || 'success',
                                        confirmText: opts.confirmButtonText || 'OK',
                                        cancelText: opts.showCancelButton ? (opts.cancelButtonText || 'Batal') : ''
                                    },
                                    callback: (result) => {
                                        resolve(result);
                                    }
                                }
                            }));
                        });
                    },
                    $toast(title, icon = 'success') {
                        window.dispatchEvent(new CustomEvent('show-demo-toast', {
                            detail: {
                                title: icon === 'success' ? 'Sukses' : icon === 'error' ? 'Gagal' : 'Notifikasi',
                                message: title,
                                type: icon
                            }
                        }));
                        return Promise.resolve({ isConfirmed: true });
                    }
                }
            });

        return vueApp.mount(el);
    },
    progress: false,
});
