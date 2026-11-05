<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { 
    Search, 
    AlertCircle, 
    CheckCircle2, 
    Clock, 
    Wrench,
    AlertTriangle, 
    Table as TableIcon, 
    LayoutList, 
    MapPin, 
    Phone, 
    ChevronDown, 
    ChevronLeft, 
    ChevronRight, 
    ArrowUpRight, 
    Camera, 
    Check, 
    FileText, 
    Ticket, 
    X,
    Eye,
    ShieldCheck,
    Info,
    User,
    Trash2
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    tickets: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['openResolveModal']);

// Filters & Search
const searchQuery = ref('');
const selectedUrgency = ref('ALL');
const selectedStatus = ref('ALL');

// View Mode (Default Table on desktop, Feed on mobile)
const isMobileView = () => typeof window !== 'undefined' && window.innerWidth < 768;
const viewMode = ref(isMobileView() ? 'FEED' : 'TABLE');

let lastIsMobileAduan = isMobileView();
const handleResizeAduan = () => {
    const currentIsMobile = isMobileView();
    if (currentIsMobile !== lastIsMobileAduan) {
        lastIsMobileAduan = currentIsMobile;
        viewMode.value = currentIsMobile ? 'FEED' : 'TABLE';
    }
};

onMounted(() => {
    if (isMobileView()) {
        viewMode.value = 'FEED';
    }
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResizeAduan);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResizeAduan);
    }
});

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

// Photo Preview Modal
const previewPhotoUrl = ref(null);
const previewPhotoTitle = ref('');

const openPhotoPreview = (url, title) => {
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title || 'Bukti Foto Kendala';
};

const closePhotoPreview = () => {
    previewPhotoUrl.value = null;
    previewPhotoTitle.value = '';
};

// Auth / Role Check: Hanya Administrator yang berhak menghapus aduan
const page = usePage();
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    return Number(user?.role_id) === 1 || user?.role?.name?.toUpperCase() === 'ADMINISTRATOR';
});

// Modal Delete State & Action (Khusus Administrator)
const showDeleteModal = ref(false);
const ticketToDelete = ref(null);
const isDeleting = ref(false);

const confirmDeleteTicket = (ticket) => {
    ticketToDelete.value = ticket;
    showDeleteModal.value = true;
};

const deleteTicketAction = () => {
    if (!ticketToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('pansinar.tickets.destroy', ticketToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            ticketToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

// Summary Stats
const stats = computed(() => {
    const list = props.tickets || [];
    return {
        total: list.length,
        pending: list.filter(t => t.status === 'MENUNGGU' || t.status === 'DIKERJAKAN' || t.status === 'OPEN' || t.status === 'IN_PROGRESS').length,
        resolved: list.filter(t => t.status === 'SELESAI' || t.status === 'RESOLVED' || t.status === 'CLOSED').length,
        darurat: list.filter(t => t.urgency === 'DARURAT' && (t.status === 'MENUNGGU' || t.status === 'DIKERJAKAN' || t.status === 'OPEN' || t.status === 'IN_PROGRESS')).length
    };
});

// Urgency & Status Options (Hanya Normal & Darurat, dan Menunggu, Dikerjakan, Selesai)
const urgencyOptions = [
    { value: 'ALL', label: 'Semua Urgensi' },
    { value: 'NORMAL', label: 'Normal' },
    { value: 'DARURAT', label: 'Darurat' }
];

const statusOptions = [
    { value: 'ALL', label: 'Semua Status' },
    { value: 'MENUNGGU', label: 'Menunggu' },
    { value: 'DIKERJAKAN', label: 'Dikerjakan' },
    { value: 'SELESAI', label: 'Selesai' }
];

// Filter Logic
const filteredTickets = computed(() => {
    return (props.tickets || []).filter(t => {
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q ||
            (t.ticket_number && t.ticket_number.toLowerCase().includes(q)) ||
            (t.location_point && t.location_point.toLowerCase().includes(q)) ||
            (t.location_building && t.location_building.toLowerCase().includes(q)) ||
            (t.category_name && t.category_name.toLowerCase().includes(q)) ||
            (t.problem_description && t.problem_description.toLowerCase().includes(q)) ||
            (t.reporter_name && t.reporter_name.toLowerCase().includes(q));

        const matchesUrgency = selectedUrgency.value === 'ALL' ||
            (selectedUrgency.value === 'NORMAL' && (t.urgency === 'NORMAL' || t.urgency === 'SEDANG' || t.urgency === 'TINGGI' || t.urgency === 'RENDAH')) ||
            (selectedUrgency.value === 'DARURAT' && t.urgency === 'DARURAT');

        const matchesStatus = selectedStatus.value === 'ALL' ||
            (selectedStatus.value === 'MENUNGGU' && (t.status === 'MENUNGGU' || t.status === 'OPEN')) ||
            (selectedStatus.value === 'DIKERJAKAN' && (t.status === 'DIKERJAKAN' || t.status === 'IN_PROGRESS')) ||
            (selectedStatus.value === 'SELESAI' && (t.status === 'SELESAI' || t.status === 'RESOLVED' || t.status === 'CLOSED'));

        return matchesSearch && matchesUrgency && matchesStatus;
    });
});

// Reset page on filter change
watch([searchQuery, selectedUrgency, selectedStatus], () => {
    currentPage.value = 1;
});

// Pagination Calculations
const totalPages = computed(() => Math.ceil(filteredTickets.value.length / perPage.value) || 1);

const paginatedTickets = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredTickets.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredTickets.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredTickets.value.length);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Badges Helper (Hanya Normal & Darurat - Keduanya Memiliki Ikon)
const getUrgencyBadge = (urgency) => {
    if (urgency === 'DARURAT') {
        return {
            label: 'Darurat',
            icon: AlertTriangle,
            class: 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
        };
    }
    return {
        label: 'Normal',
        icon: Info,
        class: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/50'
    };
};

// Status Helper (Hanya Menunggu, Dikerjakan, dan Selesai)
const getStatusBadge = (status) => {
    switch (status) {
        case 'MENUNGGU':
        case 'OPEN':
            return {
                label: 'Menunggu',
                icon: AlertCircle,
                class: 'bg-orange-50 text-orange-700 border-orange-200/80 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900/50'
            };
        case 'DIKERJAKAN':
        case 'IN_PROGRESS':
            return {
                label: 'Dikerjakan',
                icon: Wrench,
                class: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/50'
            };
        case 'SELESAI':
        case 'RESOLVED':
        case 'CLOSED':
        default:
            return {
                label: 'Selesai',
                icon: CheckCircle2,
                class: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'
            };
    }
};

// Format Lokasi Gabungan (misal: Toilet Umum Disabilitas (Gedung C - Lantai 1))
const formatTicketLocation = (ticket) => {
    const point = ticket.location_point || 'Toilet Fasilitas Sanitasi';
    if (point.includes('(') && point.includes(')')) {
        return point;
    }
    const building = ticket.location_building;
    const floor = ticket.location_floor;
    if (building && floor) {
        return `${point} (${building} - ${floor})`;
    } else if (building) {
        return `${point} (${building})`;
    } else if (floor) {
        return `${point} (${floor})`;
    }
    return point;
};

// Action Button Helper (Respon Darurat / Selesaikan Darurat jika DARURAT, Verifikasi saat Menunggu, Selesaikan saat Dikerjakan, Detail saat Selesai)
const getActionButtonText = (status, urgency) => {
    const s = status?.toUpperCase();
    if (s === 'DIKERJAKAN' || s === 'IN_PROGRESS') {
        return urgency === 'DARURAT' ? 'Selesaikan Darurat' : 'Selesaikan';
    }
    if (s === 'MENUNGGU' || s === 'OPEN') {
        return urgency === 'DARURAT' ? 'Respon Darurat' : 'Verifikasi';
    }
    return 'Detail';
};

const getActionButtonClass = (status, urgency) => {
    const s = status?.toUpperCase();
    if (urgency === 'DARURAT' && (s === 'MENUNGGU' || s === 'OPEN' || s === 'DIKERJAKAN' || s === 'IN_PROGRESS')) {
        return 'bg-rose-600 hover:bg-rose-500 text-white border-transparent shadow-xs animate-pulse';
    }
    if (s === 'DIKERJAKAN' || s === 'IN_PROGRESS') {
        return 'bg-blue-600 hover:bg-blue-500 text-white border-transparent shadow-xs';
    }
    if (s === 'MENUNGGU' || s === 'OPEN') {
        return 'bg-emerald-600 hover:bg-emerald-500 text-white border-transparent shadow-xs';
    }
    return 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700';
};
</script>

<template>
    <div class="space-y-4">
        <!-- 3 Summary Stat Cards (Kasi/Feed Style) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Card 1: Total Aduan -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Aduan</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.total }}</div>
                    <span class="text-[11px] text-slate-400 block">Semua tiket terdaftar</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                    <Ticket class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>

            <!-- Card 2: Perlu Penanganan -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Perlu Penanganan</span>
                    <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-tight">{{ stats.pending }}</div>
                    <span class="text-[11px] text-slate-400 block">
                        Menunggu tindakan
                    </span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                    <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
            </div>

            <!-- Card 3: Telah Selesai -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Telah Selesai</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ stats.resolved }}</div>
                    <span class="text-[11px] text-slate-400 block">Masalah telah terselesaikan</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                    <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
        </div>

        <!-- Unified Table Card Wrapper -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
            <!-- Search & Custom Filter Controls (Combined Header Tanpa Garis Pembatas ke Tabel) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 sm:p-5">
                <!-- Left: Search Box -->
                <div class="relative w-full sm:w-80 xl:w-96">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nomor tiket, lokasi, pelapor, atau uraian..."
                        class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                    />
                </div>

                <!-- Right Controls: Urgency Filter, Status Filter & View Mode Switcher -->
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end flex-wrap">
                    <!-- Urgency Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedUrgency"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none"
                        >
                            <option v-for="opt in urgencyOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedStatus"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-9 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none appearance-none transition-all duration-150"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>

                    <!-- Segmented View Mode Switcher -->
                    <div class="h-10 flex items-center bg-slate-100/80 dark:bg-slate-950/45 p-1 rounded-xl shrink-0 border border-slate-200/60 dark:border-slate-800/40">
                        <button
                            type="button"
                            @click="viewMode = 'TABLE'"
                            title="Tampilan Tabel Standar"
                            :class="[
                                'h-full flex items-center gap-1.5 px-3.5 rounded-lg text-xs transition cursor-pointer',
                                viewMode === 'TABLE' 
                                    ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm font-semibold' 
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'
                            ]"
                        >
                            <TableIcon class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Tabel</span>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'FEED'"
                            title="Tampilan Kartu"
                            :class="[
                                'h-full flex items-center gap-1.5 px-3.5 rounded-lg text-xs transition cursor-pointer',
                                viewMode === 'FEED' 
                                    ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-white shadow-sm font-semibold' 
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium'
                            ]"
                        >
                            <LayoutList class="h-3.5 w-3.5" />
                            <span class="hidden sm:inline">Kartu</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TIPE 1: VIEW TABEL NORMAL (STANDAR RESMI RS DALAM CONTAINER ROUNDED) -->
            <div v-if="viewMode === 'TABLE'" class="px-4 sm:px-5 pb-4 sm:pb-5 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-3.5 text-center w-12">NO</th>
                                <th class="px-5 py-3.5">NO. TIKET / WAKTU</th>
                                <th class="px-5 py-3.5">PELAPOR</th>
                                <th class="px-5 py-3.5">LOKASI / TOILET</th>
                                <th class="px-5 py-3.5 min-w-[280px]">PENJELASAN KENDALA</th>
                                <th class="px-5 py-3.5 text-center">URGENSI</th>
                                <th class="px-5 py-3.5 text-center">STATUS</th>
                                <th class="px-5 py-3.5 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300 bg-white dark:bg-slate-900">
                            <tr 
                                v-for="(ticket, idx) in paginatedTickets" 
                                :key="ticket.id" 
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- No -->
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- No Tiket & Waktu (Tanpa icon jam) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 dark:text-white text-xs block">
                                        {{ ticket.ticket_number }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                        {{ ticket.created_at_formatted || ticket.created_at }}
                                    </div>
                                </td>

                                <!-- Pelapor (Jika tidak ada nomor hp cukup strip -) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ ticket.reporter_name || 'Masyarakat (Anonim)' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-medium">
                                        <span v-if="ticket.reporter_phone && ticket.reporter_phone.trim() !== '-' && ticket.reporter_phone.trim() !== ''" class="flex items-center gap-1.5">
                                            <Phone class="h-3 w-3 text-slate-400 shrink-0" />
                                            <span>{{ ticket.reporter_phone }}</span>
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </div>
                                </td>

                                <!-- Lokasi / Ruangan -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ ticket.location_point }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                                        <MapPin class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ ticket.location_building }} • {{ ticket.location_floor }}</span>
                                    </div>
                                </td>

                                <!-- Penjelasan Masalah -->
                                <td class="px-5 py-4 text-xs">
                                    <p class="text-slate-700 dark:text-slate-300 font-medium leading-relaxed break-words max-w-lg">
                                        "{{ ticket.problem_description }}"
                                    </p>
                                </td>

                                <!-- Urgensi (Ukuran Badge Sesuai Standar Konsisten dengan Ikon) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border inline-flex items-center gap-1.5',
                                        getUrgencyBadge(ticket.urgency).class
                                    ]">
                                        <component :is="getUrgencyBadge(ticket.urgency).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                        <span>{{ getUrgencyBadge(ticket.urgency).label }}</span>
                                    </span>
                                </td>

                                <!-- Status (Ukuran Badge Sesuai Standar Konsisten) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border inline-flex items-center gap-1.5',
                                        getStatusBadge(ticket.status).class
                                    ]">
                                        <component :is="getStatusBadge(ticket.status).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                        <span>{{ getStatusBadge(ticket.status).label }}</span>
                                    </span>
                                </td>

                                <!-- Aksi (Tombol Verifikasi/Selesaikan/Detail Sesuai Status) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            @click="emit('openResolveModal', ticket)"
                                            type="button"
                                            :class="[
                                                'px-2.5 py-1 rounded-xl text-[10px] font-extrabold inline-flex items-center justify-center gap-1.5 border transition-all duration-150 cursor-pointer active:scale-95 whitespace-nowrap',
                                                getActionButtonClass(ticket.status, ticket.urgency)
                                            ]"
                                        >
                                            <span>{{ getActionButtonText(ticket.status, ticket.urgency) }}</span>
                                            <ArrowUpRight class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                        </button>

                                        <!-- Hapus Data (Khusus Administrator - Desain Seragam Manajemen Toilet) -->
                                        <button
                                            v-if="isAdmin"
                                            type="button"
                                            @click="confirmDeleteTicket(ticket)"
                                            class="h-8 w-8 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 dark:hover:bg-rose-950/40 dark:hover:border-rose-900/50 text-slate-400 flex items-center justify-center transition cursor-pointer shrink-0"
                                            title="Hapus Aduan Sanitasi"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State Table -->
                            <tr v-if="filteredTickets.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center bg-white dark:bg-slate-900">
                                    <div class="flex flex-col items-center text-slate-400">
                                        <Ticket class="h-12 w-12 text-slate-200 dark:text-slate-700 mb-2" />
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada data aduan sanitasi yang sesuai</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter status/kategori.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TIPE 2: VIEW KARTU / FEED (DESAIN SERAGAM DENGAN FORM INFORMASI RAPI) -->
            <div v-else class="px-4 sm:px-5 pb-4 sm:pb-5 bg-white dark:bg-slate-900">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="ticket in paginatedTickets"
                        :key="'feed-ticket-' + ticket.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs flex flex-col justify-between hover:border-emerald-500/40 transition-all overflow-hidden"
                    >
                        <!-- Card Header: Nomor Tiket di Kiri & Badge Status Saja di Kanan -->
                        <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1 flex items-center gap-2">
                                <Ticket class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                    {{ ticket.ticket_number }}
                                </h4>
                            </div>
                            <div class="shrink-0">
                                <span :class="[
                                    'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border shrink-0 inline-flex items-center gap-1.5',
                                    getStatusBadge(ticket.status).class
                                ]">
                                    <component :is="getStatusBadge(ticket.status).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                    <span>{{ getStatusBadge(ticket.status).label }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Card Body: Form Informasi Rapi (Lokasi, Urgensi, Pelapor, Waktu, & Uraian Kendala) -->
                        <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                            <div class="space-y-2.5">
                                <!-- Kotak Tingkat Urgensi (Keterangan Jelas Normal vs Darurat di Dalam Kartu) -->
                                <div :class="[
                                    'p-2.5 rounded-xl border flex items-center justify-between gap-3',
                                    ticket.urgency === 'DARURAT'
                                        ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200/80 dark:border-rose-900/50'
                                        : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200/50 dark:border-slate-700/50'
                                ]">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <component :is="getUrgencyBadge(ticket.urgency).icon" :class="[
                                                'w-3.5 h-3.5 shrink-0 stroke-[2.5]',
                                                ticket.urgency === 'DARURAT' ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400'
                                            ]" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                                                Tingkat Urgensi
                                            </span>
                                        </div>
                                        <p :class="[
                                            'text-xs font-semibold leading-snug break-words',
                                            ticket.urgency === 'DARURAT' ? 'text-rose-700 dark:text-rose-300' : 'text-slate-900 dark:text-white'
                                        ]">
                                            {{ ticket.urgency === 'DARURAT' ? 'Laporan Darurat (Penanganan Segera)' : 'Laporan Normal (Standar Operasional)' }}
                                        </p>
                                    </div>
                                    <span :class="[
                                        'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border shrink-0 inline-flex items-center gap-1.5',
                                        getUrgencyBadge(ticket.urgency).class
                                    ]">
                                        <component :is="getUrgencyBadge(ticket.urgency).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                        <span>{{ getUrgencyBadge(ticket.urgency).label }}</span>
                                    </span>
                                </div>

                                <!-- Kotak Lokasi / Titik Toilet -->
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <MapPin class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                                            Lokasi
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-900 dark:text-white leading-snug break-words">
                                        {{ formatTicketLocation(ticket) }}
                                    </p>
                                </div>

                                <!-- Grid 2 Kolom: Pelapor & Waktu Tanggal -->
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <!-- Kotak Pelapor -->
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 flex flex-col justify-center">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <User class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                                                Pelapor
                                            </span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white truncate" :title="ticket.reporter_name">
                                            {{ ticket.reporter_name || 'Masyarakat (Anonim)' }}
                                        </p>
                                        <span v-if="ticket.reporter_phone && ticket.reporter_phone.trim() !== '-'" class="block text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                            {{ ticket.reporter_phone }}
                                        </span>
                                    </div>

                                    <!-- Kotak Waktu & Tanggal -->
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 flex flex-col justify-center">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <Clock class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                                                Waktu Aduan
                                            </span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                            {{ ticket.created_at_formatted || ticket.created_at }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Kotak Uraian Kendala -->
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 min-h-[52px] flex flex-col justify-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight mb-1">
                                        Uraian Kendala
                                    </span>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 italic leading-relaxed">
                                        "{{ ticket.problem_description }}"
                                    </p>
                                </div>
                            </div>

                            <!-- Bottom: Tombol Verifikasi/Selesaikan/Detail Sesuai Status & Tombol Hapus Khusus Admin -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
                                <button
                                    @click="emit('openResolveModal', ticket)"
                                    type="button"
                                    :class="[
                                        'flex-1 h-10 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer border',
                                        getActionButtonClass(ticket.status, ticket.urgency)
                                    ]"
                                >
                                    <span>{{ getActionButtonText(ticket.status, ticket.urgency) }}</span>
                                    <ArrowUpRight class="h-4 w-4" />
                                </button>

                                <button
                                    v-if="isAdmin"
                                    type="button"
                                    @click="confirmDeleteTicket(ticket)"
                                    class="h-10 w-10 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 dark:hover:bg-rose-950/40 dark:hover:border-rose-900/50 text-slate-400 flex items-center justify-center transition cursor-pointer shrink-0"
                                    title="Hapus Aduan Sanitasi"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredTickets.length === 0" class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900">
                        <Ticket class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada data aduan sanitasi yang sesuai</p>
                        <p class="text-xs text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter status/kategori.</p>
                    </div>
                </div>
            </div>

            <!-- Table Footer / Interactive Pagination (Sesuai Kasi/Feed.vue) -->
            <div class="px-4 sm:px-5 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-white dark:bg-slate-900 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- Left: Per-Page Selector -->
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="text-[11px] font-medium">Tampilkan</span>
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="h-7 py-0 pl-2 pr-6 text-[11px] font-normal rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer transition"
                    >
                        <option :value="5">5</option>
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span class="text-[11px] font-medium">data</span>
                </div>

                <!-- Right: Compact Range & Navigation Buttons -->
                <div class="flex items-center gap-3">
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredTickets.length }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft class="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="h-7 w-7 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150 cursor-pointer"
                            aria-label="Halaman berikutnya"
                        >
                            <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox / Photo Preview Modal -->
        <Modal :show="!!previewPhotoUrl" @close="closePhotoPreview" max-width="lg">
            <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate pr-4">
                        {{ previewPhotoTitle }}
                    </h3>
                    <button 
                        @click="closePhotoPreview" 
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4 bg-slate-950 flex items-center justify-center max-h-[70vh] overflow-hidden">
                    <img 
                        :src="previewPhotoUrl" 
                        alt="Bukti Aduan" 
                        class="max-w-full max-h-[65vh] object-contain rounded-lg"
                    />
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 text-right">
                    <button
                        type="button"
                        @click="closePhotoPreview"
                        class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600 transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
        <!-- Modal Konfirmasi Hapus Aduan (Khusus Administrator) -->
        <Modal :show="showDeleteModal && !!ticketToDelete" @close="showDeleteModal = false" max-width="md">
            <div v-if="ticketToDelete" class="p-6 bg-white dark:bg-slate-900 rounded-2xl">
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="h-12 w-12 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                            Hapus Aduan Sanitasi?
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Data aduan yang dihapus tidak dapat dipulihkan kembali.
                        </p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs mb-5 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-slate-900 dark:text-white">{{ ticketToDelete.ticket_number }}</span>
                        <span :class="[
                            'px-2 py-0.5 rounded-lg text-[10px] font-extrabold border',
                            ticketToDelete.urgency === 'DARURAT' 
                                ? 'bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800' 
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                        ]">
                            {{ ticketToDelete.urgency === 'DARURAT' ? 'Darurat' : 'Normal' }}
                        </span>
                    </div>
                    <div class="text-slate-600 dark:text-slate-400 font-medium">
                        {{ ticketToDelete.location_point }} ({{ ticketToDelete.location_building }} • {{ ticketToDelete.location_floor }})
                    </div>
                    <div class="text-slate-500 dark:text-slate-400 italic text-[11px] line-clamp-2 mt-1">
                        "{{ ticketToDelete.problem_description }}"
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <SecondaryButton @click="showDeleteModal = false" :disabled="isDeleting">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteTicketAction" :disabled="isDeleting" class="font-bold flex items-center gap-1.5">
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Aduan' }}</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
