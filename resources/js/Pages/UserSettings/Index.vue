<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { User, Bell, Globe } from '@lucide/vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.auth?.page_permissions || []);
const hasAccess = (permKey) => permissions.value.includes(permKey);
const isAdmin = computed(() => [1, 2].includes(Number(user.value?.role_id)));

const systemNotifyEnabled = ref(user.value?.system_notify_enabled !== false);
const waNotifyEnabled = ref(user.value?.wa_notify_enabled !== false);
const defaultLang = ref(page.props.locale || 'id');
const isDark = ref(false);

const languageOptions = [
    { value: 'id', name: 'Bahasa Indonesia' },
    { value: 'en', name: 'English' },
];

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const saveNotificationSettings = () => {
    router.patch(route('profile.update-notifications'), {
        system_notify_enabled: systemNotifyEnabled.value,
        wa_notify_enabled: waNotifyEnabled.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Updated successfully
        }
    });
};

const toggleSystemNotify = () => {
    systemNotifyEnabled.value = !systemNotifyEnabled.value;
    saveNotificationSettings();
};

const toggleWaNotify = () => {
    waNotifyEnabled.value = !waNotifyEnabled.value;
    saveNotificationSettings();
};

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

const handleLangChange = (selected) => {
    const targetLang = typeof selected === 'object' ? selected.value : selected;
    if (targetLang) {
        router.visit(route('lang.switch', targetLang));
    }
};
</script>

<template>
    <Head :title="__('pages.settings.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Section 1: Profil Pengguna -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2.5 mb-4">
                        <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                            <User class="h-4 w-4 text-emerald-600 dark:text-white" />
                        </div>
                        <span>{{ __('pages.settings.profile_section') }}</span>
                    </h3>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('pages.settings.account_info') }}</label>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.edit_profile_desc') }}</p>
                        </div>
                        <Link 
                            :href="route('profile.edit')"
                            prefetch
                            class="inline-flex items-center justify-center gap-2 h-10 px-5 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-xs rounded-xl transition duration-150 flex-shrink-0 whitespace-nowrap w-full sm:w-auto shadow-sm"
                        >
                            {{ __('pages.settings.edit_profile') }}
                        </Link>
                    </div>
                </div>

                <!-- Section 2: Tampilan & Bahasa -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2.5 mb-4">
                        <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                            <Globe class="h-4 w-4 text-emerald-600 dark:text-white" />
                        </div>
                        <span>{{ __('pages.settings.appearance_lang') }}</span>
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
                                :class="isDark ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                    :class="isDark ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <!-- Dropdown Bahasa -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('pages.settings.primary_lang') }}</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">{{ __('pages.settings.primary_lang_desc') }}</p>
                            </div>
                            <SearchableSelect
                                v-model="defaultLang"
                                :options="languageOptions"
                                :searchable="false"
                                value-key="value"
                                label-key="name"
                                class="w-full sm:w-[210px] flex-shrink-0"
                                @change="handleLangChange"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Notifications -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-md font-bold text-slate-950 dark:text-white flex items-center gap-2.5 mb-4">
                        <div class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-white/10 flex items-center justify-center flex-shrink-0">
                            <Bell class="h-4 w-4 text-emerald-600 dark:text-white" />
                        </div>
                        <span>{{ __('pages.settings.notifications') }}</span>
                    </h3>
                    <div class="space-y-6">
                        <!-- Notifikasi Dalam Sistem -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">Notifikasi Dalam Sistem</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">Terima pemberitahuan lonceng di header, popup toast, dan riwayat notifikasi di dalam aplikasi.</p>
                            </div>
                            <button
                                @click="toggleSystemNotify"
                                type="button"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="systemNotifyEnabled ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                    :class="systemNotifyEnabled ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800"></div>

                        <!-- Notifikasi WhatsApp -->
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200">Notifikasi WhatsApp</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">Kirimkan rincian tiket, disposisi, dan pembaruan status pengerjaan secara otomatis ke WhatsApp Anda.</p>
                            </div>
                            <button
                                @click="toggleWaNotify"
                                type="button"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="waNotifyEnabled ? 'bg-emerald-600 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-slate-900 shadow ring-0 transition duration-200 ease-in-out"
                                    :class="waNotifyEnabled ? 'translate-x-4' : 'translate-x-0'"
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