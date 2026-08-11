<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Search, Eye, Calendar, User, MapPin, Phone, ChevronLeft, ChevronRight, ArrowRight, Inbox, Clock, CheckCircle } from '@lucide/vue';

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
const isFiltering = ref(false);
const isLoading = computed(() => isFiltering.value || !props.tickets?.data);

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

// Debounce search
let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

const setTab = (tabValue) => {
    if (currentTab.value === tabValue) return;
    currentTab.value = tabValue;
    applyFilters();
};

watch(() => props.tickets, () => {
    isFiltering.value = false;
});

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (newFilters.search !== undefined) searchQuery.value = newFilters.search || '';
        if (newFilters.status !== undefined) currentTab.value = newFilters.status || '';
    }
});

const applyFilters = () => {
    isFiltering.value = true;
    const filterRoute = props.filters.personal ? route('reports.filters') : route('reports-management.filters');
    router.post(filterRoute, {
        search: searchQuery.value || undefined,
        status: currentTab.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onStart: () => {
            isFiltering.value = true;
        },
        onFinish: () => {
            isFiltering.value = false;
        }
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
    COMPLETED:          { label: 'Selesai',      badge: 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/20' },
    CANCEL:             { label: 'Dibatalkan',   badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border border-slate-200' };

const priorityConfig = {
    URGENT:    { label: 'URGENT',  badge: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50' },
    ROUTINE:   { label: 'RUTIN',   badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' },
};

const getPriority = (target) => {
    if (!target) return { label: '-', badge: '', isPending: true };

    const priority = typeof target === 'string' ? target : target?.priority;
    const status = typeof target === 'object' ? target?.status : null;

    if (status === 'PENDING_VALIDATION') {
        return { label: '-', badge: '', isPending: true };
    }

    if (priority && priorityConfig[priority]) {
        return { ...priorityConfig[priority], isPending: false };
    }

    return { label: '-', badge: '', isPending: true };
};

const totalCount = computed(() => props.tickets?.total ?? props.tickets?.meta?.total ?? 0);
const fromCount = computed(() => props.tickets?.from ?? props.tickets?.meta?.from ?? 0);
const toCount = computed(() => props.tickets?.to ?? props.tickets?.meta?.to ?? 0);
const lastPage = computed(() => props.tickets?.last_page ?? props.tickets?.meta?.last_page ?? 1);

const prevPageUrl = computed(() => {
    if (props.tickets?.prev_page_url) return props.tickets.prev_page_url;
    if (props.tickets?.links?.prev) return props.tickets.links.prev;
    if (Array.isArray(props.tickets?.links) && props.tickets.links.length > 0) {
        const prevLink = props.tickets.links[0];
        return prevLink && prevLink.url ? prevLink.url : null;
    }
    return null;
});

const nextPageUrl = computed(() => {
    if (props.tickets?.next_page_url) return props.tickets.next_page_url;
    if (props.tickets?.links?.next) return props.tickets.links.next;
    if (Array.isArray(props.tickets?.links) && props.tickets.links.length > 0) {
        const nextLink = props.tickets.links[props.tickets.links.length - 1];
        return nextLink && nextLink.url ? nextLink.url : null;
    }
    return null;
});

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
                        class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-855 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-white transition-all duration-150 shadow-none"
                    />
                </div>
            </div>

            <!-- Table Rows & Mobile List -->
            <!-- Desktop View: Table -->
            <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                        <th class="px-3 py-4 text-center w-12">NO</th>
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                                        <th v-if="!filters.personal" class="px-6 py-4">Pelapor</th>
                                        <th class="px-6 py-4">Kategori / Ruangan</th>
                                        <th class="px-6 py-4">Penjelasan Masalah</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                                    <!-- Skeleton Loading Rows -->
                                    <template v-if="isLoading">
                                        <tr v-for="n in 5" :key="'skel-t-' + n" class="align-middle">
                                            <td class="px-3 py-4 text-center">
                                                <div class="h-3 w-4 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mx-auto"></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                <div class="h-3 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-1.5"></div>
                                            </td>
                                            <td v-if="!filters.personal" class="px-6 py-4">
                                                <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                <div class="h-3 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-1.5"></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="h-4 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                <div class="h-3 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-1.5"></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="h-4 w-56 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="h-6 w-24 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="h-8 w-24 bg-slate-200/80 dark:bg-slate-800 rounded-xl animate-pulse mx-auto"></div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty State -->
                                    <tr v-else-if="!tickets.data || tickets.data.length === 0">
                                        <td :colspan="filters.personal ? 6 : 7" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                                <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-sm font-medium">Belum ada data laporan</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Real Data Rows -->
                                    <tr v-else v-for="(ticket, idx) in tickets.data" :key="'ticket-' + ticket.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                        <!-- No. -->
                                        <td class="px-3 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                            {{ (fromCount || 1) + idx }}
                                        </td>
                                        <!-- ID / Date -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white text-xs">#{{ ticket.ticket_number }}</div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
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
                                            <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ ticket.category?.name ?? '-' }}</div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                                                <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ ticket.room?.name ?? '-' }}</span>
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
                                                    class="min-w-[105px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 transition duration-150 border border-slate-200 dark:border-slate-700 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-750 dark:text-slate-200"
                                                >
                                                    <span>Pantau</span>
                                                    <ArrowRight class="h-3.5 w-3.5 flex-shrink-0" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View: Modern Cards -->
                        <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10 border-t border-slate-100 dark:border-slate-800/60">
                            <!-- Mobile Skeleton Cards -->
                            <template v-if="isLoading">
                                <div v-for="n in 3" :key="'skel-m-' + n" class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/60 rounded-2xl p-4 space-y-3 shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div class="space-y-1">
                                            <div class="h-3 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                            <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                        </div>
                                        <div class="h-5 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse"></div>
                                    </div>
                                    <div class="h-3 w-full bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    <div class="border-t border-slate-100 dark:border-slate-800/50 pt-2.5 flex justify-between items-center">
                                        <div class="h-3 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                        <div class="h-4 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="!tickets.data || tickets.data.length === 0">
                                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-12 text-center rounded-2xl">
                                    <svg class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-xs text-slate-400 font-medium">Belum ada data laporan</span>
                                </div>
                            </template>

                            <template v-else>
                                <div
                                    v-for="ticket in tickets.data"
                                    :key="'mobile-ticket-' + ticket.id"
                                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3 transition-all duration-150"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-xs text-slate-900 dark:text-white">#{{ ticket.ticket_number }}</span>
                                        <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', getStatus(ticket.status).badge]">
                                            {{ getStatus(ticket.status).label }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ ticket.problem_description }}
                                    </p>

                                    <div class="text-[11px] space-y-1.5 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                                        <div v-if="!filters.personal" class="flex justify-between items-center">
                                            <span class="font-medium text-slate-400 dark:text-slate-500">Pelapor:</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                                {{ ticket.reporter?.name ?? '-' }}
                                                <span v-if="ticket.reporter?.room?.name || ticket.reporter?.supporting_unit?.name" class="text-slate-400 font-normal text-[10px]">
                                                    ({{ ticket.reporter?.room?.name || ticket.reporter?.supporting_unit?.name }})
                                                </span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-slate-400 dark:text-slate-500">Kategori:</span>
                                            <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ ticket.category?.name ?? '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-slate-400 dark:text-slate-500">Ruangan:</span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ ticket.room?.name ?? '-' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-slate-400 dark:text-slate-500">Tanggal:</span>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ formatDate(ticket.created_at) }}</span>
                                        </div>
                                    </div>

                                    <div class="pt-0">
                                        <Link
                                            :href="getDetailRoute(ticket.uuid)"
                                            class="w-full py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-1.5 transition-all duration-150"
                                        >
                                            <span>Pantau Detail Laporan</span>
                                            <ArrowRight class="h-3.5 w-3.5 flex-shrink-0" />
                                        </Link>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Pagination (Notifications Index Style - Only visible when > 1 page) -->
                        <div v-if="lastPage > 1" class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2"></div>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                                    {{ fromCount }}–{{ toCount }} dari {{ totalCount }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="goToPage(prevPageUrl)"
                                        :disabled="!prevPageUrl"
                                        class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                        aria-label="Halaman sebelumnya"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="goToPage(nextPageUrl)"
                                        :disabled="!nextPageUrl"
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
