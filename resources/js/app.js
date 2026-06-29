import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';
import { registerSW } from 'virtual:pwa-register';

// Register PWA Service Worker
if (typeof window !== 'undefined') {
    registerSW({ immediate: true });
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
                        const isToast = options.toast === true;
                        const isSuccess = options.icon === 'success';

                        const defaultSwalClasses = isToast ? {
                            popup: 'bg-white dark:bg-slate-900 border border-slate-100/80 dark:border-slate-800/80 rounded-2xl shadow-sm p-3 text-xs font-bold text-slate-800 dark:text-slate-200 font-sans'
                        } : {
                            popup: 'bg-white dark:bg-slate-900 dark:text-slate-200 border border-transparent dark:border-slate-850 rounded-2xl shadow-md font-sans',
                            title: 'text-slate-950 dark:text-white font-extrabold text-lg pt-5',
                            htmlContainer: 'text-slate-600 dark:text-slate-400 text-sm leading-relaxed',
                            confirmButton: 'h-10 px-5 text-xs font-bold rounded-xl text-white transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900',
                            cancelButton: 'h-10 px-5 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-350 transition-all duration-150 focus:outline-none'
                        };

                        const successDefaults = isSuccess ? {
                            timer: isToast ? 3000 : 2000,
                            showConfirmButton: false,
                            timerProgressBar: false
                        } : {};

                        const mergedOptions = {
                            ...successDefaults,
                            customClass: isToast ? {
                                popup: `${defaultSwalClasses.popup} ${options.customClass?.popup || ''}`
                            } : {
                                popup: `${defaultSwalClasses.popup} ${options.customClass?.popup || ''}`,
                                title: `${defaultSwalClasses.title} ${options.customClass?.title || ''}`,
                                htmlContainer: `${defaultSwalClasses.htmlContainer} ${options.customClass?.htmlContainer || ''}`,
                                confirmButton: `${defaultSwalClasses.confirmButton} ${options.customClass?.confirmButton || ''}`,
                                cancelButton: `${defaultSwalClasses.cancelButton} ${options.customClass?.cancelButton || ''}`
                            },
                            confirmButtonColor: isToast ? undefined : (options.confirmButtonColor || '#4f46e5'),
                            cancelButtonColor: isToast ? undefined : (options.cancelButtonColor || '#64748b'),
                            buttonsStyling: !isToast,
                            showClass: isToast ? {
                                popup: 'animate-swal-toast-show'
                            } : {
                                popup: 'animate-swal-show',
                                backdrop: 'swal2-backdrop-show'
                            },
                            hideClass: isToast ? {
                                popup: 'animate-swal-toast-hide'
                            } : {
                                popup: 'animate-swal-hide',
                                backdrop: 'swal2-backdrop-hide'
                            },
                            ...options
                        };
                        return Swal.fire(mergedOptions);
                    },
                    $toast(title, icon = 'success') {
                        return this.$swal({
                            title: title,
                            icon: icon,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: icon !== 'success'
                        });
                    }
                }
            });

        return vueApp.mount(el);
    },
    progress: false,
});
