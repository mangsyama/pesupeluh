<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Bell, 
    Clock, 
    CheckCircle2, 
    User, 
    ChevronLeft, 
    ChevronRight, 
    Inbox, 
    Check,
    CheckCheck
} from '@lucide/vue';

const props = defineProps({
    allNotifications: {
        type: Object,
        default: () => ({ data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null }),
    }
});

const notificationsList = computed(() => props.allNotifications?.data ?? []);
const unreadCount = computed(() => notificationsList.value.filter(n => !n.read_at).length);

const handleNotificationClick = (notif) => {
    if (!notif.read_at) {
        router.post(route('notifications.markAsRead', notif.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                if (notif.route) {
                    window.location.href = notif.route;
                }
            }
        });
    } else {
        if (notif.route) {
            window.location.href = notif.route;
        }
    }
};

const markAllAsRead = () => {
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
    });
};

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveScroll: true, preserveState: true });
    }
};
</script>

<template>
    <Head title="Semua Notifikasi" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Notifications Main Card Section -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <!-- Title Header mimicking Service Index Header panel -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 dark:border-slate-800/60 pb-5 mb-5">
                        <div class="space-y-1">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight flex items-center gap-2">
                                Semua Notifikasi
                                <span 
                                    v-if="unreadCount > 0" 
                                    class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-bold"
                                >
                                    {{ unreadCount }} Baru
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-450 mt-1 max-w-xl leading-relaxed">
                                Kelola dan lihat semua riwayat aktivitas, pendaftaran, dan laporan Anda.
                            </p>
                        </div>
                        
                        <button
                            v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-emerald-250 dark:border-emerald-800 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400 text-xs font-bold transition duration-150 w-fit self-start sm:self-center"
                        >
                            <Check class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-455" />
                            Tandai Semua Dibaca
                        </button>
                        <div v-else class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 dark:text-slate-500 py-2 self-start sm:self-center">
                            <CheckCheck class="h-4 w-4 text-emerald-500" />
                            Semua notifikasi telah dibaca
                        </div>
                    </div>

                    <!-- List body -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <div v-if="notificationsList.length === 0" class="py-16 text-center">
                            <Inbox class="h-12 w-12 mx-auto text-slate-350 dark:text-slate-700 mb-3" />
                            <span class="text-xs text-slate-400 dark:text-slate-505 font-bold block">Tidak ada riwayat notifikasi</span>
                        </div>

                        <div
                            v-else
                            v-for="notif in notificationsList"
                            :key="'notif-page-' + notif.id"
                            @click="handleNotificationClick(notif)"
                            :class="[
                                'flex gap-4 p-4 hover:bg-slate-50/50 dark:hover:bg-slate-850/20 transition cursor-pointer relative rounded-xl my-1 first:mt-0 last:mb-0',
                                !notif.read_at ? 'bg-emerald-50/20 dark:bg-emerald-950/5' : ''
                            ]"
                        >
                            <!-- Read indicator border -->
                            <div 
                                v-if="!notif.read_at"
                                class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 dark:bg-emerald-600 rounded-r"
                            />

                            <!-- Icon column -->
                            <div :class="[
                                'h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5',
                                notif.priority === 'URGENT' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-500' :
                                notif.type === 'ticket' ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500' :
                                notif.type === 'progress' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-500' :
                                notif.type === 'done' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500' :
                                'bg-slate-50 dark:bg-slate-800 text-slate-500'
                            ]">
                                <Bell v-if="notif.type === 'ticket'" class="h-5 w-5" />
                                <Clock v-else-if="notif.type === 'progress'" class="h-5 w-5" />
                                <CheckCircle2 v-else-if="notif.type === 'done'" class="h-5 w-5" />
                                <User v-else class="h-5 w-5" />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <h3 :class="['text-xs sm:text-sm font-bold truncate', !notif.read_at ? 'text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300']">
                                            {{ notif.title }}
                                        </h3>
                                        <span 
                                            v-if="notif.priority === 'URGENT'" 
                                            class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase bg-rose-500 text-white flex-shrink-0 animate-pulse"
                                        >
                                            URGENT
                                        </span>
                                    </div>
                                    <span 
                                        v-if="!notif.read_at" 
                                        class="h-2.5 w-2.5 rounded-full bg-emerald-500 dark:bg-emerald-600 flex-shrink-0 mt-1"
                                    />
                                </div>
                                
                                <p class="text-xs text-slate-650 dark:text-slate-400 mt-1 leading-relaxed">
                                    {{ notif.message }}
                                </p>
                                
                                <div class="flex items-center gap-2 mt-2 text-[10px] text-slate-400 dark:text-slate-550 font-medium">
                                    <span>{{ notif.time }}</span>
                                    <span v-if="notif.created_at" class="opacity-50">&bull;</span>
                                    <span v-if="notif.created_at">
                                        {{ new Date(notif.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer / Action Area mimicking Settings Index box layout (only rendered if pagination is needed) -->
                    <div v-if="allNotifications.last_page > 1" class="px-2 py-4 border-t border-slate-100 dark:border-slate-800/60 mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <!-- Left space left intentionally blank -->
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] sm:text-xs font-medium text-slate-550 dark:text-slate-400">
                                {{ allNotifications.from }}–{{ allNotifications.to }} dari {{ allNotifications.total }}
                            </span>
                            
                            <div class="flex items-center gap-1">
                                <button
                                    @click="goToPage(allNotifications.prev_page_url)"
                                    :disabled="!allNotifications.prev_page_url"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-850 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                    aria-label="Halaman sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button
                                    @click="goToPage(allNotifications.next_page_url)"
                                    :disabled="!allNotifications.next_page_url"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-850 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                    aria-label="Halaman berikutnya"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
