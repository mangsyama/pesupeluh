<script setup>
import { ref, watch, getCurrentInstance } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Search, Eye, Calendar, User, MapPin, Phone, ChevronLeft, ChevronRight, Inbox, Clock, CheckCircle } from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
});

const searchQuery = ref(props.filters.search || '');
const currentTab = ref(props.filters.status || ''); // '' for Semua, or group string

// Debounce search
let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

const setTab = (tabValue) => {
    currentTab.value = tabValue;
    applyFilters();
};

const applyFilters = () => {
    const filterRoute = props.filters.personal ? route('reports.filters') : route('reports-management.filters');
    router.post(filterRoute, {
        search: searchQuery.value || undefined,
        status: currentTab.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const getDetailRoute = (ticketUuid) => {
    if (props.filters.personal) {
        return route('reports.show', ticketUuid);
    }
    return route('reports-management.show', ticketUuid);
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu',     badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50' },
    ASSIGNED:           { label: 'Ditugaskan',   badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50' },
    IN_PROGRESS:        { label: 'Dikerjakan',   badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400 border border-violet-200/50' },
    PENDING:            { label: 'Tertunda',     badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200/50' },
    COMPLETED:          { label: 'Selesai',      badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50' },
    CANCEL:             { label: 'Dibatalkan',   badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border border-slate-200' };

const priorityConfig = {
    URGENT:  { label: 'Urgent',  badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border-rose-200/50 dark:border-rose-900/50' },
    ROUTINE: { label: 'Routine', badge: 'bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400 border-sky-200/50 dark:border-sky-900/50' },
};

const getPriority = (priority) => priorityConfig[priority] ?? { label: '-', badge: 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900/40 dark:border-slate-800' };

const goToPage = (url) => {
    if (url) router.visit(url, { preserveState: true });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const date = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${date} - ${hours}.${minutes}`;
};
</script>

<template>
    <div class="w-full">
        <!-- Unified Table Card Wrapper -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
            <!-- Search & Custom Tab Controls (Combined Header) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                <!-- Left: Tab Buttons -->
                <div class="flex flex-wrap items-center bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl w-full xl:w-fit gap-1">
                    <button
                        @click="setTab('')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === '' ? 'bg-white dark:bg-slate-800 text-indigo-650 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <Inbox class="h-3.5 w-3.5" />
                        Semua
                    </button>
                    <button
                        @click="setTab('PENDING_VALIDATION,ASSIGNED,IN_PROGRESS,PENDING')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'PENDING_VALIDATION,ASSIGNED,IN_PROGRESS,PENDING' ? 'bg-white dark:bg-slate-800 text-indigo-650 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <Clock class="h-3.5 w-3.5" />
                        Laporan Aktif
                    </button>
                    <button
                        @click="setTab('COMPLETED,CANCEL')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'COMPLETED,CANCEL' ? 'bg-white dark:bg-slate-800 text-indigo-650 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <CheckCircle class="h-3.5 w-3.5" />
                        Riwayat Selesai
                    </button>
                </div>

                <!-- Right: Search Box -->
                <div class="relative w-full xl:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="__('pages.reports.history.search_placeholder')"
                        class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-855 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 shadow-none"
                    />
                </div>
            </div>

            <!-- Desktop View: Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                            <th v-if="!filters.personal" class="px-6 py-4">Pelapor</th>
                            <th class="px-6 py-4">Kategori / Ruangan</th>
                            <th class="px-6 py-4">Penjelasan Masalah</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                        <tr v-if="!tickets.data || tickets.data.length === 0">
                            <td :colspan="filters.personal ? 5 : 6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-400">
                                    <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm font-medium">Belum ada data laporan</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="ticket in tickets.data" :key="'ticket-' + ticket.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150">
                            <!-- ID / Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-955 dark:text-white text-xs">#{{ ticket.ticket_number }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-505 flex items-center gap-1 mt-0.5">
                                    <Calendar class="h-3.5 w-3.5" />
                                    {{ formatDate(ticket.created_at) }}
                                </div>
                            </td>
                            <!-- Reporter (Conditional) -->
                            <td v-if="!filters.personal" class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white text-xs">{{ ticket.reporter?.name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                                    <Phone class="h-3.5 w-3.5 text-slate-400" />
                                    <span>{{ ticket.reporter?.phone_number ?? '-' }}</span>
                                </div>
                            </td>
                            <!-- Category & Room -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-850 dark:text-slate-200 text-xs">{{ ticket.category?.name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                                    <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                    <span>{{ ticket.room?.name ?? '-' }}</span>
                                    <span v-if="ticket.room?.location_floor" class="opacity-75">({{ ticket.room.location_floor }})</span>
                                </div>
                            </td>
                            <!-- Desc -->
                            <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-400 break-words max-w-md">
                                {{ ticket.problem_description }}
                            </td>
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span :class="['w-28 inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getStatus(ticket.status).badge]">
                                    {{ getStatus(ticket.status).label }}
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex justify-center">
                                    <Link
                                        :href="getDetailRoute(ticket.uuid)"
                                        class="w-28 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition duration-150 border border-slate-200 dark:border-slate-700 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-750 dark:text-slate-200"
                                    >
                                        <Eye class="h-3.5 w-3.5" />
                                        <span>Detail</span>
                                        <ChevronRight class="h-3.5 w-3.5" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Modern Cards -->
            <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10 border-t border-slate-100 dark:border-slate-800/60">
                <div v-if="!tickets.data || tickets.data.length === 0" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-12 text-center rounded-2xl">
                    <svg class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-xs text-slate-400 font-medium">Belum ada data laporan</span>
                </div>

                <div
                    v-for="ticket in tickets.data"
                    :key="'mobile-ticket-' + ticket.id"
                    class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/60 rounded-2xl p-4 shadow-sm hover:shadow-md transition-all duration-200"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500">
                                #{{ ticket.ticket_number }}
                            </span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs mt-0.5">
                                {{ ticket.category?.name ?? '-' }}
                            </h4>
                        </div>
                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase', getStatus(ticket.status).badge]">
                            {{ getStatus(ticket.status).label }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-650 dark:text-slate-400 line-clamp-2 my-2.5">
                        {{ ticket.problem_description }}
                    </p>

                    <div class="border-t border-slate-100 dark:border-slate-800/50 pt-2.5 mt-2 flex justify-between items-center text-[10px] text-slate-400">
                        <div class="flex items-center gap-1">
                            <Calendar class="h-3 w-3" />
                            <span>{{ formatDate(ticket.created_at) }}</span>
                            <span class="mx-1">&bull;</span>
                            <span>{{ ticket.room?.name ?? '-' }}</span>
                        </div>
                        
                        <Link
                            :href="getDetailRoute(ticket.uuid)"
                            class="text-indigo-600 dark:text-indigo-405 font-bold flex items-center gap-0.5 hover:underline"
                        >
                            Detail
                            <ChevronRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.meta && tickets.meta.last_page > 1" class="flex items-center justify-between mt-4">
            <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">
                Menampilkan {{ tickets.meta.from ?? 0 }} – {{ tickets.meta.to ?? 0 }} dari {{ tickets.meta.total ?? 0 }} tiket
            </div>
            <div class="flex items-center gap-1">
                <button
                    @click="goToPage(tickets.links?.prev)"
                    :disabled="!tickets.links?.prev"
                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 px-3">
                    {{ tickets.meta.current_page }} / {{ tickets.meta.last_page }}
                </span>
                <button
                    @click="goToPage(tickets.links?.next)"
                    :disabled="!tickets.links?.next"
                    class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
