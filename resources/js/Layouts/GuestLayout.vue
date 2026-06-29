<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sun, Moon, Languages, Activity, ArrowLeft } from '@lucide/vue';

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

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
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
            style="background-image: linear-gradient(rgba(37, 99, 235, 0.55), rgba(79, 70, 229, 0.55)), url('/images/hospital-hero.jpg');">
            <!-- Put your hospital landing image at public/images/hospital-hero.jpg -->
            
            <!-- Abstract Geometric Lines (SalesSkip-style) -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-15">
                <div class="absolute -left-16 -bottom-16 w-[450px] h-[450px] border-2 border-white rounded-[60px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
                <div class="absolute -left-28 -bottom-28 w-[450px] h-[450px] border-2 border-white rounded-[80px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
                <div class="absolute -left-40 -bottom-40 w-[450px] h-[450px] border-2 border-white rounded-[100px] rotate-[15deg] transition-transform duration-1000 hover:scale-105"></div>
            </div>

            <!-- Top Brand Logo (Rata kiri) -->
            <div class="relative z-10 flex items-center space-x-3 bg-white/10 shadow-sm backdrop-blur-md border border-white/20 rounded-2xl px-4 py-3 max-w-[24rem]">
                <div class="h-16 w-16 rounded-xl bg-indigo-600/0 flex items-center justify-center flex-shrink-0 overflow-hidden">
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
                        <img src="/images/logo-sidebar.png" alt="PESU PELUH" class="h-full w-full object-contain dark:filter dark:brightness-0 dark:invert" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold tracking-widest text-slate-800 dark:text-slate-100 uppercase leading-none">Pesu Peluh</span>
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
                        <ArrowLeft class="h-4.5 w-4.5 me-1.5 text-indigo-600 dark:text-indigo-400" />
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
                        <Moon v-if="isDark" class="h-5 w-5 text-indigo-400" />
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

    </div>
</template>
