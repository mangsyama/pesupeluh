<script setup>
import { ref, watch, getCurrentInstance } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Search, Eye, Calendar, MapPin, User, Phone, ChevronLeft, ChevronRight, ArrowRight, Inbox, Clock, CheckCircle, Wrench, Play, AlertCircle } from '@lucide/vue';

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
// Set default tab to '' (Semua Tugas) if status not set
const currentTab = ref(props.filters.status || '');
const isFiltering = ref(false);

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
    isFiltering.value = true;
    router.post(route('reports-management.filters'), {
        search: searchQuery.value || undefined,
        status: currentTab.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu',     badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50' },
    ASSIGNED:           { label: 'Tugas Baru',   badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50' },
    IN_PROGRESS:        { label: 'Dikerjakan',   badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400 border border-violet-200/50' },
    PENDING:            { label: 'Tertunda',     badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200/50' },
    COMPLETED:          { label: 'Selesai',      badge: 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/20' },
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
        <!-- Unified Task Grid Card Wrapper -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
            <!-- Search & Custom Tab Controls (Combined Header - ALWAYS VISIBLE) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                <!-- Left: Tab Buttons -->
                <div class="flex flex-wrap items-center bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl w-full xl:w-fit gap-1">
                    <button
                        @click="setTab('')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === '' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <Inbox class="h-3.5 w-3.5" />
                        Semua Tugas
                    </button>
                    <button
                        @click="setTab('ASSIGNED')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'ASSIGNED' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <AlertCircle class="h-3.5 w-3.5" />
                        Tugas Baru
                    </button>
                    <button
                        @click="setTab('IN_PROGRESS,PENDING')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'IN_PROGRESS,PENDING' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <Clock class="h-3.5 w-3.5" />
                        Dalam Proses
                    </button>
                    <button
                        @click="setTab('COMPLETED,CANCEL')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'COMPLETED,CANCEL' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <CheckCircle class="h-3.5 w-3.5" />
                        Selesai
                    </button>
                </div>

                <!-- Right: Search Box -->
                <div class="relative w-full xl:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="__('pages.reports.history.search_placeholder')"
                        class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-855 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150 shadow-none"
                    />
                </div>
            </div>

            <!-- Table Rows & Cards -->
            <!-- Desktop View: Table -->
            <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                                        <th class="px-6 py-4">Pelapor</th>
                                        <th class="px-6 py-4">Kategori / Ruangan</th>
                                        <th class="px-6 py-4">Penjelasan Masalah</th>
                                        <th class="px-6 py-4 text-center">Prioritas</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                                    <tr v-if="!tickets.data || tickets.data.length === 0">
                                        <td colspan="7" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                                <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-sm font-medium">Tidak ada penugasan tiket di antrean ini</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr 
                                        v-for="ticket in tickets.data" 
                                        :key="'ticket-' + ticket.id" 
                                        :class="[
                                            'transition duration-150',
                                            ticket.priority === 'URGENT' 
                                                ? 'bg-rose-50/40 hover:bg-rose-50/70 dark:bg-rose-950/20 dark:hover:bg-rose-950/30' 
                                                : 'hover:bg-slate-50/30 dark:hover:bg-slate-800/10'
                                        ]"
                                    >
                                        <!-- ID & Date -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-950 dark:text-white text-xs">#{{ ticket.ticket_number }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(ticket.created_at) }}
                                            </div>
                                        </td>
                                        <!-- Reporter -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white text-xs">{{ ticket.reporter?.name ?? '-' }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                                                <Phone class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ ticket.reporter?.phone_number ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <!-- Category & Room -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-semibold text-emerald-700 dark:text-white text-xs">{{ ticket.category?.name ?? '-' }}</div>
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
                                        <!-- Priority -->
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span v-if="ticket.priority" :class="['w-28 inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getPriority(ticket.priority).badge]">
                                                {{ getPriority(ticket.priority).label }}
                                            </span>
                                            <span v-else class="text-slate-400">-</span>
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
                                                    :href="route('reports-management.show', ticket.uuid)"
                                                    :class="[
                                                        'min-w-[105px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 transition duration-150',
                                                        ticket.status === 'ASSIGNED'
                                                            ? 'bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold border-transparent'
                                                            : ticket.status === 'IN_PROGRESS'
                                                            ? 'bg-emerald-700 hover:bg-emerald-600 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold border-transparent'
                                                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700'
                                                    ]"
                                                >
                                                    <span>
                                                        {{ 
                                                            ticket.status === 'ASSIGNED' 
                                                                ? 'Kerjakan' 
                                                                : ticket.status === 'IN_PROGRESS' 
                                                                ? 'Update' 
                                                                : 'Detail' 
                                                        }}
                                                    </span>
                                                    <ArrowRight class="h-3.5 w-3.5 flex-shrink-0" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View: Grid Cards -->
                        <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10 border-t border-slate-100 dark:border-slate-800/60">
                            <div v-if="!tickets.data || tickets.data.length === 0" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-12 text-center rounded-2xl">
                                <svg class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-xs text-slate-400 font-medium">Tidak ada penugasan tiket di antrean ini</span>
                            </div>

                            <div
                                v-for="ticket in tickets.data"
                                :key="'mobile-ticket-' + ticket.id"
                                :class="[
                                    'rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between',
                                    ticket.priority === 'URGENT'
                                        ? 'bg-rose-50/40 dark:bg-rose-950/20 border-2 border-rose-300 dark:border-rose-800'
                                        : 'bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/65'
                                ]"
                            >
                                <div class="space-y-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="space-y-0.5">
                                            <span class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500">
                                                #{{ ticket.ticket_number }}
                                            </span>
                                            <h4 class="font-bold text-slate-955 dark:text-white text-xs line-clamp-1">
                                                {{ ticket.category?.name ?? '-' }}
                                            </h4>
                                        </div>
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border', getStatus(ticket.status).badge]">
                                            {{ getStatus(ticket.status).label }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 text-[11px] text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20 px-2.5 py-1 rounded-xl w-fit font-semibold">
                                        <MapPin class="h-3.5 w-3.5" />
                                        <span>{{ ticket.room?.name ?? '-' }}</span>
                                    </div>

                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ ticket.problem_description }}
                                    </p>

                                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/40">
                                        <span>Pelapor: <strong class="text-slate-700 dark:text-slate-300">{{ ticket.reporter?.name ?? '-' }}</strong></span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-slate-500">Prioritas:</span>
                                            <span :class="['px-1.5 py-0.2 rounded text-[8px] font-bold', getPriority(ticket.priority).badge]">
                                                {{ getPriority(ticket.priority).label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 dark:border-slate-800/60 pt-3 mt-4">
                                    <Link
                                        :href="route('reports-management.show', ticket.uuid)"
                                        :class="[
                                            'w-full py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all duration-150',
                                            ticket.status === 'ASSIGNED'
                                                ? 'bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold border-transparent'
                                                : ticket.status === 'IN_PROGRESS'
                                                ? 'bg-emerald-700 hover:bg-emerald-600 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold border-transparent'
                                                : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700'
                                        ]"
                                    >
                                        <span>
                                            {{ 
                                                ticket.status === 'ASSIGNED' 
                                                    ? 'Mulai Kerjakan' 
                                                    : ticket.status === 'IN_PROGRESS' 
                                                    ? 'Update Progres Kerjaan' 
                                                    : 'Lihat Detail Laporan' 
                                            }}
                                        </span>
                                        <ArrowRight class="h-3.5 w-3.5 flex-shrink-0" />
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
