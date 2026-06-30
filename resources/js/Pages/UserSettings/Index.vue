<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { User, Bell, Globe } from '@lucide/vue';

const page = usePage();
const notificationEnabled = ref(true);
const defaultLang = ref(page.props.locale || 'id');
const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
    // Dispatch custom event to sync with navbar dropdown
    window.dispatchEvent(new Event('theme-changed'));
};

const switchLang = (event) => {
    const targetLang = event.target.value;
    router.visit(route('lang.switch', targetLang));
};
</script>

<template>
    <Head :title="__('pages.settings.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Section 1: Profil Pengguna -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2 mb-4">
                        <User class="h-4 w-4 text-indigo-500" />
                        {{ __('pages.settings.profile_section') }}
                    </h3>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-1">
                        <div class="min-w-0 flex-1">
                            <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('pages.settings.account_info') }}</label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.edit_profile_desc') }}</p>
                        </div>
                        <Link 
                            :href="route('profile.edit')"
                            prefetch
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-semibold text-xs rounded-xl transition duration-150 flex-shrink-0 whitespace-nowrap self-start sm:self-center"
                        >
                            {{ __('pages.settings.edit_profile') }}
                        </Link>
                    </div>
                </div>

                <!-- Section 2: Tampilan & Bahasa -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2 mb-4">
                        <Globe class="h-4 w-4 text-indigo-500" />
                        {{ __('pages.settings.appearance_lang') }}
                    </h3>
                    <div class="space-y-5">
                        <!-- Toggle Theme -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('global.dark_mode') }}</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.dark_mode_desc') }}</p>
                            </div>
                            <button
                                @click="toggleTheme"
                                type="button"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="isDark ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-800'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="isDark ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <!-- Dropdown Bahasa -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-50 dark:border-slate-800/50">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('pages.settings.primary_lang') }}</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.primary_lang_desc') }}</p>
                            </div>
                            <select 
                                v-model="defaultLang"
                                @change="switchLang"
                                class="w-full sm:max-w-[180px] px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                            >
                                <option value="id">Bahasa Indonesia</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Notifications -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2 mb-4">
                        <Bell class="h-4 w-4 text-indigo-500" />
                        {{ __('pages.settings.notifications') }}
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('pages.settings.push_notifications') }}</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.push_desc') }}</p>
                            </div>
                            <button
                                @click="notificationEnabled = !notificationEnabled"
                                type="button"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="notificationEnabled ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-800'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="notificationEnabled ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes spa-fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>