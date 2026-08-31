<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, getCurrentInstance } from 'vue';
import {
    ShieldCheck,
    Search,
    Inbox,
    ShieldAlert,
    Clock,
    CheckCircle,
    Trash2,
    RotateCcw,
    Calendar,
    Phone,
    MapPin,
    Eye,
    ChevronLeft,
    ChevronRight,
    AlertCircle,
    Activity,
    CheckCircle2,
    Layers,
    FileText,
    Wrench,
    ArrowRight
} from '@lucide/vue';

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
    stats: {
        type: Object,
        default: () => ({
            total_all: 0,
            total_active: 0,
            total_pending: 0,
            total_in_progress: 0,
            total_completed: 0,
            total_deleted: 0,
        }),
    },
});

const searchQuery = ref(props.filters.search || '');
const currentTab = ref(props.filters.status || '');
const isFiltering = ref(false);
const isLoading = computed(() => isFiltering.value || !props.tickets?.data);

// Debounce search input
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
    router.post(route('reports-audit.filters'), {
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

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Validasi', badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50' },
    ASSIGNED:           { label: 'Ditugaskan', badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50' },
    IN_PROGRESS:        { label: 'Dikerjakan', badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400 border border-violet-200/50' },
    PENDING:            { label: 'Tertunda', badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200/50' },
    COMPLETED:          { label: 'Selesai', badge: 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/20' },
    CANCEL:             { label: 'Dibatalkan', badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/50' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border border-slate-200' };

const priorityConfig = {
    URGENT:    { label: 'URGENT', badge: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50' },
    ROUTINE:   { label: 'RUTIN', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' },
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
    const date = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${date}, ${hours}:${minutes} WITA`;
};

// Actions: Soft Delete & Restore with SweetAlert2
const confirmDelete = (ticket) => {
    if (!ticket) return;
    proxy.$swal({
        title: 'Hapus Laporan (Soft Delete)?',
        text: `Laporan #${ticket.ticket_number} akan di-soft delete dan tetap dapat dilihat atau dipulihkan di tab Terhapus.`,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Laporan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('reports-audit.destroy', ticket.uuid), {
                preserveScroll: true,
            });
        }
    });
};

const confirmRestore = (ticket) => {
    if (!ticket) return;
    proxy.$swal({
        title: 'Pulihkan Laporan?',
        text: `Laporan #${ticket.ticket_number} akan dipulihkan kembali ke status aktif.`,
        icon: 'question',
        iconColor: '#e11d48',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Pulihkan Laporan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reports-audit.restore', ticket.uuid), {}, {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Audit Laporan (Report Audit)" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in space-y-4">
            <!-- Header Panel -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white shrink-0">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            Audit Laporan
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                            Pusat audit menyeluruh seluruh data tiket pelaporan sistem.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div 
                    @click="setTab('')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === '' 
                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-white dark:bg-white/10' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Total Semua</span>
                        <Inbox class="h-3.5 w-3.5" />
                    </div>
                    <div class="text-xl font-extrabold text-slate-900 dark:text-white">
                        {{ stats.total_all }}
                    </div>
                </div>

                <div 
                    @click="setTab('ACTIVE')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === 'ACTIVE' 
                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-white dark:bg-white/10' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Tiket Aktif</span>
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                    </div>
                    <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ stats.total_active }}
                    </div>
                </div>

                <div 
                    @click="setTab('PENDING_VALIDATION')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === 'PENDING_VALIDATION' 
                            ? 'bg-amber-500/10 border-amber-500/30 text-amber-800 dark:text-amber-300' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Validasi</span>
                        <ShieldAlert class="h-3.5 w-3.5 text-amber-500" />
                    </div>
                    <div class="text-xl font-extrabold text-amber-600 dark:text-amber-400">
                        {{ stats.total_pending }}
                    </div>
                </div>

                <div 
                    @click="setTab('ASSIGNED,IN_PROGRESS,PENDING')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === 'ASSIGNED,IN_PROGRESS,PENDING' 
                            ? 'bg-blue-500/10 border-blue-500/30 text-blue-800 dark:text-blue-300' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Berjalan</span>
                        <Clock class="h-3.5 w-3.5 text-blue-500" />
                    </div>
                    <div class="text-xl font-extrabold text-blue-600 dark:text-blue-400">
                        {{ stats.total_in_progress }}
                    </div>
                </div>

                <div 
                    @click="setTab('COMPLETED')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === 'COMPLETED' 
                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-emerald-300' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Selesai</span>
                        <CheckCircle class="h-3.5 w-3.5 text-emerald-500" />
                    </div>
                    <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ stats.total_completed }}
                    </div>
                </div>

                <div 
                    @click="setTab('DELETED')"
                    :class="[
                        'p-4 rounded-2xl border shadow-sm transition-all cursor-pointer select-none space-y-1',
                        currentTab === 'DELETED' 
                            ? 'bg-rose-500/10 border-rose-500/30 text-rose-800 dark:text-rose-300' 
                            : 'bg-white dark:bg-slate-900 border-white dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center justify-between">
                        <span>Terhapus (Soft)</span>
                        <Trash2 class="h-3.5 w-3.5 text-rose-500" />
                    </div>
                    <div class="text-xl font-extrabold text-rose-600 dark:text-rose-400">
                        {{ stats.total_deleted }}
                    </div>
                </div>
            </div>

            <!-- Unified Table Card Wrapper -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden">
                <!-- Search & Custom Tab Controls Header -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 p-5 border-b border-slate-100 dark:border-slate-800/60">
                    <!-- Left: Tab Buttons -->
                    <div class="flex flex-wrap items-center bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl w-full xl:w-fit gap-1">
                        <button
                            type="button"
                            @click="setTab('')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === '' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <Inbox class="h-3.5 w-3.5" />
                            <span>Semua Data</span>
                        </button>
                        <button
                            type="button"
                            @click="setTab('ACTIVE')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'ACTIVE' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            <span>Aktif</span>
                        </button>
                        <button
                            type="button"
                            @click="setTab('PENDING_VALIDATION')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'PENDING_VALIDATION' ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <ShieldAlert class="h-3.5 w-3.5" />
                            <span>Validasi</span>
                        </button>
                        <button
                            type="button"
                            @click="setTab('ASSIGNED,IN_PROGRESS,PENDING')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'ASSIGNED,IN_PROGRESS,PENDING' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <Clock class="h-3.5 w-3.5" />
                            <span>Berjalan</span>
                        </button>
                        <button
                            type="button"
                            @click="setTab('COMPLETED,CANCEL')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'COMPLETED,CANCEL' ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <CheckCircle class="h-3.5 w-3.5" />
                            <span>Selesai/Batal</span>
                        </button>
                        <button
                            type="button"
                            @click="setTab('DELETED')"
                            :class="['flex-1 xl:flex-initial px-3.5 py-2 text-xs font-bold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap', currentTab === 'DELETED' ? 'bg-white dark:bg-slate-800 text-rose-600 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200']"
                        >
                            <Trash2 class="h-3.5 w-3.5 text-rose-500" />
                            <span>Terhapus</span>
                        </button>
                    </div>

                    <!-- Right: Search Box -->
                    <div class="relative w-full xl:w-80">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari tiket, pelapor, ruangan, kategori..."
                            class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150"
                        />
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-3 py-4 text-center w-12">NO</th>
                                <th class="px-6 py-4">ID & Tanggal</th>
                                <th class="px-6 py-4">Pelapor</th>
                                <th class="px-6 py-4">Kategori & Ruangan</th>
                                <th class="px-6 py-4">Deskripsi Permasalahan</th>
                                <th class="px-6 py-4 text-center">Prioritas</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Audit Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <!-- Skeleton Loading -->
                            <template v-if="isLoading">
                                <tr v-for="n in 5" :key="'skel-t-' + n" class="align-middle">
                                    <td class="px-3 py-4 text-center">
                                        <div class="h-3 w-4 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mx-auto"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                        <div class="h-3 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-1.5"></div>
                                    </td>
                                    <td class="px-6 py-4">
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
                                        <div class="h-6 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="h-6 w-24 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="h-6 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="h-8 w-24 bg-slate-200/80 dark:bg-slate-800 rounded-xl animate-pulse mx-auto"></div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr v-else-if="!tickets.data || tickets.data.length === 0">
                                <td colspan="9" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <AlertCircle class="h-12 w-12 text-slate-300 dark:text-slate-700" />
                                        <span class="text-sm font-medium">Tidak ada data laporan yang cocok dengan kriteria audit</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr
                                v-else
                                v-for="(ticket, idx) in tickets.data"
                                :key="'ticket-' + ticket.id"
                                :class="[
                                    'hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors duration-150',
                                    ticket.deleted_at ? 'bg-rose-50/30 dark:bg-rose-950/15' : ''
                                ]"
                            >
                                <!-- No -->
                                <td class="px-3 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (fromCount || 1) + idx }}
                                </td>

                                <!-- ID / Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-extrabold text-slate-900 dark:text-white text-xs">
                                        #{{ ticket.ticket_number }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDate(ticket.created_at) }}
                                    </div>
                                </td>

                                <!-- Reporter -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ ticket.reporter?.name ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 flex items-center gap-1">
                                        <Phone class="h-3 w-3 text-slate-400" />
                                        <span>{{ ticket.reporter?.phone_number ?? '-' }}</span>
                                    </div>
                                </td>

                                <!-- Category & Room -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-emerald-700 dark:text-white text-xs">
                                        {{ ticket.category?.name ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 flex items-center gap-1">
                                        <MapPin class="h-3 w-3 text-slate-400" />
                                        <span>{{ ticket.room?.name ?? '-' }}</span>
                                    </div>
                                </td>

                                <!-- Problem Description -->
                                <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-400 break-words max-w-sm">
                                    <p class="line-clamp-2">{{ ticket.problem_description }}</p>
                                </td>

                                <!-- Priority -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="!getPriority(ticket).isPending" :class="['w-28 inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getPriority(ticket).badge]">
                                        {{ getPriority(ticket).label }}
                                    </span>
                                    <span v-else class="text-slate-400 font-bold text-xs">-</span>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span :class="['w-28 inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border', getStatus(ticket.status).badge]">
                                        {{ getStatus(ticket.status).label }}
                                    </span>
                                </td>

                                <!-- Audit State (Soft Delete Indicator) -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span 
                                        v-if="ticket.deleted_at" 
                                        class="w-28 inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-900/50"
                                        :title="'Dihapus pada: ' + formatDate(ticket.deleted_at)"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        <span>TERHAPUS</span>
                                    </span>
                                    <span 
                                        v-else 
                                        class="w-28 inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white border-emerald-100 dark:border-white/20"
                                    >
                                        <CheckCircle2 class="h-3 w-3 text-emerald-500" />
                                        <span>AKTIF</span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Audit Link Button -->
                                        <Link
                                            :href="route('reports-audit.show', ticket.uuid)"
                                            class="min-w-[105px] px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center justify-center gap-1.5 transition-all duration-150 border bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 border-transparent shadow-sm"
                                        >
                                            <span>Audit</span>
                                            <ArrowRight class="h-3.5 w-3.5 flex-shrink-0" />
                                        </Link>

                                        <!-- Restore Button (if soft deleted) -->
                                        <button
                                            v-if="ticket.deleted_at"
                                            type="button"
                                            @click="confirmRestore(ticket)"
                                            class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/50 transition cursor-pointer"
                                            title="Pulihkan Laporan (Restore)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </button>

                                        <!-- Soft Delete Button (if active) -->
                                        <button
                                            v-else
                                            type="button"
                                            @click="confirmDelete(ticket)"
                                            class="p-2 rounded-xl bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 border border-red-200/80 dark:border-red-900/50 transition cursor-pointer"
                                            title="Hapus Laporan (Soft Delete)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800/60 p-3 space-y-3">
                    <div
                        v-for="ticket in tickets.data"
                        :key="'mob-' + ticket.id"
                        :class="[
                            'p-4 rounded-2xl border space-y-3 shadow-sm',
                            ticket.deleted_at 
                                ? 'bg-rose-50/20 border-rose-200 dark:bg-rose-950/15 dark:border-rose-900/40' 
                                : 'bg-slate-50/50 dark:bg-slate-950/40 border-slate-100 dark:border-slate-800'
                        ]"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-extrabold text-xs text-slate-900 dark:text-white">
                                #{{ ticket.ticket_number }}
                            </span>
                            <span v-if="ticket.deleted_at" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-900/50">
                                TERHAPUS
                            </span>
                            <span v-else :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', getStatus(ticket.status).badge]">
                                {{ getStatus(ticket.status).label }}
                            </span>
                        </div>

                        <div class="text-xs space-y-1.5 text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-400">Pelapor:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ ticket.reporter?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-400">Kategori:</span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ ticket.category?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-400">Ruangan:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ ticket.room?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-400">Prioritas:</span>
                                <span v-if="!getPriority(ticket).isPending" :class="['px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border', getPriority(ticket).badge]">
                                    {{ getPriority(ticket).label }}
                                </span>
                                <span v-else class="text-slate-400 font-bold text-xs">-</span>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                            {{ ticket.problem_description }}
                        </p>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-800">
                            <span class="text-[11px] text-slate-400">{{ formatDate(ticket.created_at) }}</span>
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="route('reports-audit.show', ticket.uuid)"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500 transition shadow-sm inline-flex items-center gap-1"
                                >
                                    <span>Audit</span>
                                    <ArrowRight class="h-3 w-3" />
                                </Link>
                                <button
                                    v-if="ticket.deleted_at"
                                    type="button"
                                    @click="confirmRestore(ticket)"
                                    class="p-1.5 text-rose-600 rounded-xl hover:bg-rose-50 border border-rose-200 dark:border-rose-900/50 transition"
                                    title="Pulihkan Laporan"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click="confirmDelete(ticket)"
                                    class="p-1.5 text-rose-500 rounded-xl hover:bg-rose-50 border border-rose-200 dark:border-rose-900/50 transition"
                                    title="Hapus Laporan (Soft Delete)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                        Menampilkan <span class="font-bold text-slate-800 dark:text-slate-200">{{ fromCount }}</span> - <span class="font-bold text-slate-800 dark:text-slate-200">{{ toCount }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ totalCount }}</span> total laporan
                    </div>

                    <div class="flex items-center justify-center gap-1.5">
                        <button
                            type="button"
                            @click="goToPage(prevPageUrl)"
                            :disabled="!prevPageUrl"
                            class="h-9 px-3 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            <span>Sebelumnya</span>
                        </button>
                        <button
                            type="button"
                            @click="goToPage(nextPageUrl)"
                            :disabled="!nextPageUrl"
                            class="h-9 px-3 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1"
                        >
                            <span>Berikutnya</span>
                            <ChevronRight class="h-4 w-4" />
                        </button>
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
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
