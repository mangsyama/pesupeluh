import '../css/app.css';
import './bootstrap';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';
import { registerSW } from 'virtual:pwa-register';

if (typeof window !== 'undefined') {
    if (import.meta.env.VITE_REVERB_APP_KEY) {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            },
        });
    }
}

// Register PWA Service Worker
if (typeof window !== 'undefined') {
    registerSW({
        immediate: true,
        onRegisteredSW(swUrl, registration) {
            console.debug('[PWA] Service worker registered:', swUrl, registration);
        },
        onRegisterError(error) {
            console.error('[PWA] Service worker registration failed:', error);
        },
        onNeedRefresh() {
            console.debug('[PWA] New service worker available, reloading page...');
            window.location.reload();
        },
        onOfflineReady() {
            console.debug('[PWA] Offline ready');
        },
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Page not found: ./Pages/${name}.vue`);
        }
        return page.default ?? page;
    },
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
