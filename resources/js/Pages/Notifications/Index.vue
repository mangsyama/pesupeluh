<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NotificationItem from '@/Components/NotificationItem.vue';
import { 
    Bell, 
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
                    router.visit(notif.route);
                }
            }
        });
    } else {
        if (notif.route) {
            router.visit(notif.route);
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
                <!-- Header Panel (User Management Style) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Bell class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight flex items-center gap-2">
                                Semua Notifikasi
                                <span 
                                    v-if="unreadCount > 0" 
                                    class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-bold"
                                >
                                    {{ unreadCount }} Baru
                                </span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                Kelola dan lihat semua riwayat aktivitas, pendaftaran, dan laporan Anda.
                            </p>
                        </div>
                    </div>
                    
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-transparent bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 text-xs font-bold transition duration-150 w-full sm:w-auto self-start sm:self-center shadow-sm"
                    >
                        <Check class="h-3.5 w-3.5 text-white dark:text-slate-900" />
                        Tandai Semua Dibaca
                    </button>
                    <div v-else class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 dark:text-slate-500 py-2 self-start sm:self-center">
                        <CheckCheck class="h-4 w-4 text-emerald-500" />
                        Semua notifikasi telah dibaca
                    </div>
                </div>

                <!-- Notifications Main Card Section -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <!-- List body -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <div v-if="notificationsList.length === 0" class="py-16 text-center">
                            <Inbox class="h-12 w-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" />
                            <span class="text-xs text-slate-400 dark:text-slate-500 font-bold block">Tidak ada riwayat notifikasi</span>
                        </div>

                        <template v-else>
                            <NotificationItem
                                v-for="notif in notificationsList"
                                :key="'notif-page-' + notif.id"
                                :notification="notif"
                                variant="list"
                                @click="handleNotificationClick(notif)"
                            />
                        </template>
                    </div>

                    <!-- Footer / Action Area mimicking Settings Index box layout (only rendered if pagination is needed) -->
                    <div v-if="allNotifications.last_page > 1" class="px-2 py-4 border-t border-slate-100 dark:border-slate-800/60 mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <!-- Left space left intentionally blank -->
                        </div>

                        <!-- Pagination -->
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                                {{ allNotifications.from }}–{{ allNotifications.to }} dari {{ allNotifications.total }}
                            </span>
                            
                            <div class="flex items-center gap-1">
                                <button
                                    @click="goToPage(allNotifications.prev_page_url)"
                                    :disabled="!allNotifications.prev_page_url"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                    aria-label="Halaman sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button
                                    @click="goToPage(allNotifications.next_page_url)"
                                    :disabled="!allNotifications.next_page_url"
                                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
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
