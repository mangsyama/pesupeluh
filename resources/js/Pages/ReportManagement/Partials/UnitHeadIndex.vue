<script setup>
import { ref, watch, getCurrentInstance } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Search, Eye, Calendar, User, MapPin, Phone, ChevronLeft, ChevronRight, Inbox, Clock, CheckCircle, ShieldAlert, ArrowRight, Wrench } from '@lucide/vue';

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
    PENDING_VALIDATION: { label: 'Validasi', badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50' },
    ASSIGNED:           { label: 'Ditugaskan',       badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50' },
    IN_PROGRESS:        { label: 'Dikerjakan',       badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400 border border-violet-200/50' },
    PENDING:            { label: 'Tertunda',         badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200/50' },
    COMPLETED:          { label: 'Selesai',          badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50' },
    CANCEL:             { label: 'Dibatalkan',       badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50' },
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
                        @click="setTab('PENDING_VALIDATION')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'PENDING_VALIDATION' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <ShieldAlert class="h-3.5 w-3.5" />
                        Menunggu Validasi
                    </button>
                    <button
                        @click="setTab('ASSIGNED,IN_PROGRESS,PENDING')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'ASSIGNED,IN_PROGRESS,PENDING' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                    >
                        <Clock class="h-3.5 w-3.5" />
                        Sedang Berjalan
                    </button>
                    <button
                        @click="setTab('COMPLETED,CANCEL')"
                        :class="['flex-1 xl:flex-initial px-4 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'COMPLETED,CANCEL' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
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
                        class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-855 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                    />
                </div>
            </div>

            <!-- Table Rows & Cards -->
            <!-- Desktop View Table -->
            <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                                        <th class="px-6 py-4 text-center">Prioritas</th>
                                        <th class="px-6 py-4">Pelapor</th>
                                        <th class="px-6 py-4">Kategori / Ruangan</th>
                                        <th class="px-6 py-4">Penjelasan Masalah</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-855 dark:text-slate-300">
                                    <tr v-if="!tickets.data || tickets.data.length === 0">
                                        <td colspan="7" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                                <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-sm font-medium">Tidak ada data tiket di antrean ini</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="ticket in tickets.data" :key="'ticket-' + ticket.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                        <!-- ID / Date -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-950 dark:text-white text-xs">#{{ ticket.ticket_number }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(ticket.created_at) }}
                                            </div>
                                        </td>
                                        <!-- Priority -->
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span v-if="ticket.priority" :class="['w-28 inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getPriority(ticket.priority).badge]">
                                                {{ getPriority(ticket.priority).label }}
                                            </span>
                                            <span v-else class="text-slate-400">-</span>
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
                                            <div class="font-semibold text-emerald-700 dark:text-emerald-400 text-xs">{{ ticket.category?.name ?? '-' }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                                                <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ ticket.room?.name ?? '-' }}</span>
                                                <span v-if="ticket.room?.location_floor" class="opacity-75">({{ ticket.room.location_floor }})</span>
                                            </div>
                                        </td>
                                        <!-- Desc -->
                                        <td class="px-6 py-4 text-xs text-slate-650 dark:text-slate-400 break-words max-w-md">
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
                                                    :href="route('reports-management.show', ticket.uuid)"
                                                    :class="[
                                                        'w-28 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 border',
                                                        ticket.status === 'PENDING_VALIDATION' 
                                                            ? 'bg-emerald-600 hover:bg-emerald-500 text-white border-transparent' 
                                                            : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-750 dark:text-slate-200 border-slate-200 dark:border-slate-700'
                                                    ]"
                                                >
                                                    <span>{{ ticket.status === 'PENDING_VALIDATION' ? 'Disposisi' : 'Pantau' }}</span>
                                                    <ArrowRight class="h-3.5 w-3.5" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View (Optimized with Action Card Layout) -->
                        <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10 border-t border-slate-100 dark:border-slate-800/60">
                            <div v-if="!tickets.data || tickets.data.length === 0" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-12 text-center rounded-2xl">
                                <svg class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-xs text-slate-400 font-medium">Tidak ada data tiket di antrean ini</span>
                            </div>

                            <div
                                v-for="ticket in tickets.data"
                                :key="'mobile-ticket-' + ticket.id"
                                class="bg-white dark:bg-slate-900 border border-slate-155 dark:border-slate-850/60 rounded-2xl p-4 shadow-sm"
                            >
                                <div class="flex justify-between items-start mb-2.5">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-extrabold text-slate-450 dark:text-slate-400">#{{ ticket.ticket_number }}</span>
                                            <span v-if="ticket.priority" :class="['px-1.5 py-0.5 rounded text-[8px] font-bold', getPriority(ticket.priority).badge]">
                                                {{ getPriority(ticket.priority).label }}
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-xs mt-1">
                                            {{ ticket.category?.name ?? '-' }}
                                        </h4>
                                    </div>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold uppercase', getStatus(ticket.status).badge]">
                                        {{ getStatus(ticket.status).label }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-650 dark:text-slate-400 line-clamp-2 my-2 leading-relaxed">
                                    {{ ticket.problem_description }}
                                </p>

                                <div class="text-[10px] text-slate-400 space-y-1 my-3 bg-slate-50 dark:bg-slate-950/30 p-2 rounded-lg border border-slate-100 dark:border-slate-850/20">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">Pelapor:</span>
                                        <span>{{ ticket.reporter?.name ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-semibold">Ruangan:</span>
                                        <span>{{ ticket.room?.name ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-semibold">Tanggal:</span>
                                        <span>{{ formatDate(ticket.created_at) }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800/50">
                                    <Link
                                        :href="route('reports-management.show', ticket.uuid)"
                                        :class="[
                                            'w-full py-2 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1 transition-all duration-150 border',
                                            ticket.status === 'PENDING_VALIDATION'
                                                ? 'bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold border-transparent'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'
                                        ]"
                                    >
                                        <Wrench v-if="ticket.status === 'PENDING_VALIDATION'" class="h-3.5 w-3.5" />
                                        <span>{{ ticket.status === 'PENDING_VALIDATION' ? 'Lakukan Disposisi' : 'Detail Pemantauan' }}</span>
                                        <ArrowRight class="h-3.5 w-3.5" />
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
