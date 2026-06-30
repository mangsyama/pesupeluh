<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Sun, Moon, Languages, Activity, ArrowLeft, CheckCircle2, AlertCircle, AlertTriangle, HelpCircle, X, Bell, Clock } from '@lucide/vue';

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const toasts = ref([]);

const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

const showNotificationToast = (normalized) => {
    const id = Date.now() + Math.random();
    toasts.value.push({
        id,
        title: normalized.title,
        message: normalized.message,
        type: normalized.type,
        route: normalized.route
    });

    // Auto-remove after 6 seconds
    setTimeout(() => {
        removeToast(id);
    }, 6000);
};

const customAlert = ref({
    show: false,
    title: '',
    text: '',
    icon: 'success', // success, error, warning, question
    confirmText: 'OK',
    cancelText: '',
    onConfirm: null,
    onCancel: null
});

const handleCustomAlert = (event) => {
    if (event.detail && event.detail.options) {
        const opts = event.detail.options;
        customAlert.value = {
            show: true,
            title: opts.title || '',
            text: opts.text || '',
            icon: opts.icon || 'success',
            confirmText: opts.confirmText || 'OK',
            cancelText: opts.cancelText || '',
            onConfirm: () => {
                customAlert.value.show = false;
                if (event.detail.callback) {
                    event.detail.callback({ isConfirmed: true });
                }
            },
            onCancel: () => {
                customAlert.value.show = false;
                if (event.detail.callback) {
                    event.detail.callback({ isConfirmed: false, isDismissed: true });
                }
            }
        };
    }
};

const handleDemoToast = (event) => {
    if (event.detail) {
        showNotificationToast(event.detail);
    }
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    window.addEventListener('show-demo-toast', handleDemoToast);
    window.addEventListener('trigger-custom-alert', handleCustomAlert);
});

onUnmounted(() => {
    window.removeEventListener('show-demo-toast', handleDemoToast);
    window.removeEventListener('trigger-custom-alert', handleCustomAlert);
});

// Translation helper
const __ = (key) => {
    const page = usePage();
    const translations = page.props.translations || {};
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
};
</script>

<template>
    <div class="min-h-screen md:h-screen grid grid-cols-1 md:grid-cols-2 bg-slate-50 dark:bg-slate-950 transition-colors duration-200 overflow-y-auto md:overflow-hidden">
        
        <!-- Left Column (Branding Hero, hidden on mobile) -->
        <section class="hidden md:flex relative overflow-hidden bg-cover bg-center bg-no-repeat flex-col justify-between p-12 lg:p-16 text-white select-none h-full w-full"
            style="background-image: linear-gradient(rgba(16, 185, 129, 0.55), rgba(4, 120, 87, 0.55)), url('/images/hospital-hero.jpg');">
            <!-- Put your hospital landing image at public/images/hospital-hero.jpg -->
            
            <!-- Abstract Geometric Lines (SalesSkip-style) -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-15">
                <div class="absolute -left-16 -bottom-16 w-[450px] h-[450px] border-2 border-white rounded-[60px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
                <div class="absolute -left-28 -bottom-28 w-[450px] h-[450px] border-2 border-white rounded-[80px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
                <div class="absolute -left-40 -bottom-40 w-[450px] h-[450px] border-2 border-white rounded-[100px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
            </div>

            <!-- Top Brand Logo (Rata kiri) -->
            <div class="relative z-10 flex items-center space-x-3 bg-white/10 shadow-sm backdrop-blur-md border border-white/20 rounded-2xl px-4 py-3 max-w-[24rem]">
                <div class="h-16 w-16 rounded-xl bg-emerald-600/0 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain filter brightness-0 invert" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold tracking-widest uppercase leading-none">PESU PELUH</span>
                    <span class="text-xs tracking-widest leading-tight text-slate-200 mt-1">
                        Pengendalian Terintegrasi <br/> Unit Penunjang Dalam Satu Sentuhan
                    </span>
                </div>
            </div>

            <!-- Hero Text (Rata kiri) -->
            <div class="relative z-10 my-auto py-12 text-left">
                <h2 class="text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight w-full max-w-xl">
                    {{ __('auth.login.hero_title') }}
                </h2>
                <p class="text-slate-200 text-base lg:text-lg leading-relaxed mt-6 max-w-xl lg:max-w-2xl">
                    {{ __('auth.login.hero_desc') }}
                </p>
            </div>

            <!-- Footer (Rata kiri) -->
            <div class="relative z-10 mt-auto text-xs text-slate-300/80 font-medium">
                &copy; 2026 PESU PELUH. {{ __('global.all_rights_reserved') }}
            </div>
        </section>

        <!-- Right Column (Form Panel) -->
        <main class="flex flex-col items-center px-6 py-12 md:py-16 relative bg-white dark:bg-slate-900 transition-colors duration-200 w-full h-full md:h-screen md:overflow-y-auto">
            
            <!-- Flow Header (Theme & Language Switchers + Mobile Logo / Back Button) -->
            <header class="w-full max-w-md flex items-center mb-8 shrink-0">
                <!-- Brand Logo for Mobile (Hidden on Desktop, shown only on Login page) -->
                <div v-if="route().current('login')" class="flex md:hidden items-center space-x-3">
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain hue-rotate-[280deg] saturate-[1.2] dark:hue-rotate-0 dark:saturate-100 dark:brightness-0 dark:invert" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold tracking-widest text-emerald-700 dark:text-emerald-400 uppercase leading-none">Pesu Peluh</span>
                        <span class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 mt-1 leading-tight tracking-normal max-w-[185px]">
                            Pengendalian Terintegrasi Unit Penunjang Dalam Satu Sentuhan
                        </span>
                    </div>
                </div>
                <!-- Back Button (Shown on Register and Forgot Password pages for both Mobile and Desktop) -->
                <div v-else class="flex items-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white uppercase tracking-widest transition duration-150 outline-none"
                    >
                        <ArrowLeft class="h-4.5 w-4.5 me-1.5 text-emerald-600 dark:text-emerald-400" />
                        {{ __('global.back') }}
                    </Link>
                </div>
                
                <div class="flex items-center space-x-2 ms-auto">
                    <!-- Theme Toggle -->
                    <button
                        @click="toggleTheme"
                        type="button"
                        class="rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 focus:outline-none transition duration-150"
                        :title="isDark ? __('global.dark_mode') : __('global.light_mode')"
                    >
                        <Moon v-if="isDark" class="h-5 w-5 text-emerald-400" />
                        <Sun v-else class="h-5 w-5 text-amber-500" />
                    </button>

                    <!-- Language Switcher (Inertia Link for SPA) -->
                    <Link
                        :href="route('lang.switch', $page.props.locale === 'id' ? 'en' : 'id')"
                        class="rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 focus:outline-none flex items-center space-x-1.5 transition duration-150"
                        :title="__('global.switch_language')"
                    >
                        <Languages class="h-5 w-5" />
                        <span class="text-xs font-bold uppercase tracking-wide">{{ $page.props.locale === 'id' ? 'ID' : 'EN' }}</span>
                    </Link>
                </div>
            </header>

            <!-- Form Card Wrapper -->
            <div class="w-full max-w-md my-auto pb-6 transition-all duration-200">
                <slot />
            </div>
        </main>

        <!-- Toast Floating Container -->
        <div class="fixed top-24 right-6 z-[9999] flex flex-col gap-3 w-80 max-w-[calc(100vw-3rem)] pointer-events-none">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 translate-x-4"
                enter-to-class="translate-y-0 opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 translate-x-4"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    @click="toast.route ? router.visit(toast.route) : null"
                    :class="[
                        'pointer-events-auto flex gap-3 p-4 rounded-2xl border shadow-lg cursor-pointer transition-all duration-200 hover:scale-[1.02]',
                        'bg-white/95 dark:bg-slate-900/95 backdrop-blur-md',
                        'border-slate-100 dark:border-slate-800/80',
                        toast.route ? 'hover:border-indigo-500/50 dark:hover:border-indigo-400/50' : ''
                    ]"
                >
                    <!-- Icon -->
                    <div :class="[
                        'h-9 w-9 rounded-xl flex items-center justify-center flex-shrink-0',
                        toast.type === 'ticket' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-500' :
                        toast.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                        toast.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                        'bg-violet-50 dark:bg-violet-950/40 text-violet-500'
                    ]">
                        <Bell v-if="toast.type === 'ticket'" class="h-4.5 w-4.5" />
                        <Clock v-else-if="toast.type === 'progress'" class="h-4.5 w-4.5" />
                        <CheckCircle2 v-else-if="toast.type === 'done'" class="h-4.5 w-4.5" />
                        <User v-else class="h-4.5 w-4.5" />
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-extrabold text-slate-900 dark:text-white leading-normal">{{ toast.title }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mt-1 line-clamp-3">{{ toast.message }}</p>
                    </div>
                    <!-- Close Button -->
                    <button @click.stop="removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 h-5 w-5 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0">
                        <X class="h-3 w-3" />
                    </button>
                </div>
            </TransitionGroup>
        </div>

        <!-- Custom Alert Modal (Glassmorphism Swal Alternative) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="customAlert.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <!-- Backdrop overlay -->
                    <div @click="customAlert.cancelText ? customAlert.onCancel() : customAlert.onConfirm()" class="fixed inset-0 bg-black/40 backdrop-blur-xs transition-opacity"></div>

                    <!-- Modal Card -->
                    <div class="relative bg-white/95 dark:bg-slate-900/95 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden p-7 flex flex-col items-center text-center transform transition-all duration-200 scale-100 backdrop-blur-md">
                        <!-- Status Icon -->
                        <div :class="[
                            'h-20 w-20 rounded-full flex items-center justify-center mb-5 flex-shrink-0',
                            customAlert.icon === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                            customAlert.icon === 'error' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' :
                            customAlert.icon === 'warning' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                            'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500'
                        ]">
                            <CheckCircle2 v-if="customAlert.icon === 'success'" class="h-10 w-10" />
                            <AlertCircle v-else-if="customAlert.icon === 'error'" class="h-10 w-10" />
                            <AlertTriangle v-else-if="customAlert.icon === 'warning'" class="h-10 w-10" />
                            <HelpCircle v-else class="h-10 w-10" />
                        </div>

                        <!-- Info Content -->
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight px-2">{{ customAlert.title }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 leading-relaxed px-1">{{ customAlert.text }}</p>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 w-full mt-6">
                            <button
                                v-if="customAlert.cancelText"
                                @click="customAlert.onCancel"
                                class="flex-1 h-11 text-sm font-bold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 transition duration-150 focus:outline-none"
                            >
                                {{ customAlert.cancelText }}
                            </button>
                            <button
                                v-if="customAlert.confirmText"
                                @click="customAlert.onConfirm"
                                :class="[
                                    'flex-1 h-11 text-sm font-bold rounded-xl text-white shadow-sm transition duration-150 focus:outline-none',
                                    customAlert.icon === 'success' ? 'bg-emerald-600 hover:bg-emerald-700' :
                                    customAlert.icon === 'error' ? 'bg-rose-600 hover:bg-rose-700' :
                                    customAlert.icon === 'warning' ? 'bg-amber-600 hover:bg-amber-700' :
                                    'bg-indigo-600 hover:bg-indigo-700'
                                ]"
                            >
                                {{ customAlert.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
