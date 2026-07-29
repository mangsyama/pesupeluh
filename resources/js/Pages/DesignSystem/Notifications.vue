<script setup>
import { ref, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NotificationItem from '@/Components/NotificationItem.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    Bell, 
    Send, 
    UserCheck, 
    CheckCircle, 
    CheckCircle2, 
    FileText, 
    Clock, 
    Wrench, 
    AlertTriangle, 
    AlertCircle, 
    UserPlus, 
    Activity,
    Layers,
    ArrowLeft,
    Sparkles,
    Check,
    Radio
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    catalog: {
        type: Object,
        default: () => ({})
    }
});

const isTriggering = ref({});

const triggerToastOnly = (item) => {
    let toastType = 'info';
    if (item.priority === 'urgent') toastType = 'error';
    else if (item.priority === 'high') toastType = 'warning';
    else if (item.type === 'ticket' || item.type === 'user') toastType = 'success';

    proxy.$toast(`${item.title}: ${item.message}`, toastType);
};

const triggerNotificationToDatabase = (item, itemKey) => {
    isTriggering.value[itemKey] = true;

    router.post(route('design-system.notifications.trigger'), {
        title: item.title,
        message: item.message,
        type: item.type,
        priority: item.priority,
        route: route('dashboard'),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isTriggering.value[itemKey] = false;
            // Also trigger toast so user sees immediate feedback
            let toastType = 'info';
            if (item.priority === 'urgent') toastType = 'error';
            else if (item.priority === 'high') toastType = 'warning';
            else if (item.type === 'ticket' || item.type === 'user') toastType = 'success';

            proxy.$toast(`[Terkirim ke Dropdown Header] ${item.title}`, toastType);
        },
        onError: () => {
            isTriggering.value[itemKey] = false;
            proxy.$toast('Gagal mengirimkan notifikasi ke database.', 'error');
        }
    });
};

const getRoleBadgeStyle = (roleKey) => {
    switch (roleKey) {
        case 'STAF_PELAPOR': return 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200 dark:border-sky-800';
        case 'KEPALA_RUANGAN': return 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
        case 'TEKNISI': return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'KEPALA_UNIT': return 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 border-teal-200 dark:border-teal-800';
        case 'ADMINISTRATOR': return 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    }
};

const getIconComponent = (iconName) => {
    switch (iconName) {
        case 'UserCheck': return UserCheck;
        case 'CheckCircle2': return CheckCircle2;
        case 'CheckCircle': return CheckCircle;
        case 'FileText': return FileText;
        case 'Clock': return Clock;
        case 'Wrench': return Wrench;
        case 'AlertTriangle': return AlertTriangle;
        case 'AlertCircle': return AlertCircle;
        case 'UserPlus': return UserPlus;
        case 'Activity': return Activity;
        default: return Bell;
    }
};
</script>

<template>
    <Head title="Pengujian Notifikasi Sistem - Design System" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                
                <!-- Premium Header Panel -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Link 
                                :href="route('design-system.index')" 
                                class="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition"
                                title="Kembali ke Design System"
                            >
                                <ArrowLeft class="h-5 w-5" />
                            </Link>
                            <div>
                                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight flex items-center gap-2">
                                    <Bell class="h-6 w-6 text-emerald-600 dark:text-white" />
                                    Pengujian & Pengelompokan Notifikasi
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                    Simulasi pemicu notifikasi berdasarkan role pengguna untuk menguji tampilan Toast Notifikasi dan Lonceng Header Dropdown.
                                </p>
                            </div>
                        </div>

                        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-900/60 p-3 rounded-xl flex items-center gap-3 shrink-0">
                            <Sparkles class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <div class="text-xs">
                                <span class="font-extrabold text-slate-800 dark:text-slate-200 block">Lonceng Dropdown Header:</span>
                                <span class="text-slate-500 dark:text-slate-400">Klik "Picu ke Dropdown & DB" untuk menambahkan notifikasi aktif real-time.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Grouped Notifications Grid -->
                <div class="space-y-6">
                    <div 
                        v-for="(group, roleKey) in catalog" 
                        :key="roleKey"
                        class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span :class="['px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider border', getRoleBadgeStyle(roleKey)]">
                                    {{ group.role_name }}
                                </span>
                            </div>
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                                {{ group.items.length }} Jenis Notifikasi
                            </span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div 
                                v-for="item in group.items" 
                                :key="item.key"
                                class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800/80 rounded-xl p-4 flex flex-col justify-between space-y-4 hover:border-emerald-500/40 transition duration-150"
                            >
                                <NotificationItem :notification="item" variant="list" />

                                <!-- Action buttons to test toast or database dropdown -->
                                <div class="flex items-center gap-2 pt-2 border-t border-slate-200/60 dark:border-slate-800/60">
                                    <button
                                        @click="triggerToastOnly(item)"
                                        type="button"
                                        class="flex-1 h-9 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition duration-150"
                                    >
                                        <Radio class="h-3.5 w-3.5 text-slate-500" />
                                        Test Toast Saja
                                    </button>

                                    <button
                                        @click="triggerNotificationToDatabase(item, item.key)"
                                        :disabled="isTriggering[item.key]"
                                        type="button"
                                        class="flex-1 h-9 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition duration-150 shadow-xs disabled:opacity-50"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        {{ isTriggering[item.key] ? 'Mengirim...' : 'Picu ke Dropdown & DB' }}
                                    </button>
                                </div>
                            </div>
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
