<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { 
    ClipboardCheck, 
    CheckCircle2, 
    AlertCircle, 
    MapPin, 
    Check, 
    X, 
    Clock, 
    User, 
    Search, 
    ChevronDown, 
    ChevronLeft, 
    ChevronRight, 
    Camera, 
    Maximize2, 
    Table as TableIcon, 
    LayoutList,
    Edit3,
    Plus
} from '@lucide/vue';

const props = defineProps({
    toilets: {
        type: Array,
        default: () => []
    },
    inspections: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['openChecklistModal']);

// ==========================================
// DATE NAVIGATION & GROUPING STATE
// ==========================================
const formatDateIso = (d) => {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const todayIso = formatDateIso(new Date());

const getYesterdayIso = () => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return formatDateIso(d);
};

const selectedDate = ref(todayIso);
const datePickerInputRef = ref(null);

const triggerDatePicker = () => {
    if (datePickerInputRef.value && typeof datePickerInputRef.value.showPicker === 'function') {
        try {
            datePickerInputRef.value.showPicker();
        } catch (e) {}
    }
};

const setDateToday = () => {
    selectedDate.value = todayIso;
};

const setDateYesterday = () => {
    selectedDate.value = getYesterdayIso();
};

const prevDay = () => {
    const current = new Date(selectedDate.value);
    current.setDate(current.getDate() - 1);
    selectedDate.value = formatDateIso(current);
};

const nextDay = () => {
    const current = new Date(selectedDate.value);
    current.setDate(current.getDate() + 1);
    selectedDate.value = formatDateIso(current);
};

const formattedSelectedDate = computed(() => {
    try {
        const parts = selectedDate.value.split('-');
        if (parts.length === 3) {
            const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            return d.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
    } catch (e) {}
    return selectedDate.value;
});

const isToday = computed(() => selectedDate.value === todayIso);
const isYesterday = computed(() => selectedDate.value === getYesterdayIso());

// ==========================================
// VIEW MODE & RESPONSIVENESS
// ==========================================
const isMobile = () => typeof window !== 'undefined' && window.innerWidth < 768;
const viewMode = ref(isMobile() ? 'FEED' : 'TABLE');

// Search & Filter State
const searchQuery = ref('');
const selectedStatus = ref('ALL');
const selectedShift = ref('ALL');

// Pagination State
const currentPage = ref(1);
const perPage = ref(10);

let lastIsMobile = isMobile();
const handleResize = () => {
    const currentIsMobile = isMobile();
    if (currentIsMobile !== lastIsMobile) {
        lastIsMobile = currentIsMobile;
        viewMode.value = currentIsMobile ? 'FEED' : 'TABLE';
    }
};

// ==========================================
// FULLSCREEN LIGHTBOX PHOTO PREVIEW
// ==========================================
const previewPhotoUrl = ref(null);
const previewPhotoTitle = ref('');
let pushPhotoHistoryFlag = false;

const handlePopState = () => {
    if (previewPhotoUrl.value) {
        closePhotoPreview(false);
    }
};

const handleLightboxKeyDown = (e) => {
    if (e.key === 'Escape' && previewPhotoUrl.value) {
        e.preventDefault();
        closePhotoPreview();
    }
};

const openPhotoPreview = (url, title) => {
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title || 'Bukti Foto Patroli';
    if (typeof document !== 'undefined') {
        document.body.style.overflow = 'hidden';
    }
    try {
        window.history.pushState({ photoPreviewOpen: true }, '');
        pushPhotoHistoryFlag = true;
    } catch (e) {}
};

const closePhotoPreview = (shouldGoBack = true) => {
    if (!previewPhotoUrl.value) return;
    previewPhotoUrl.value = null;
    previewPhotoTitle.value = '';
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
    if (shouldGoBack && pushPhotoHistoryFlag && typeof window !== 'undefined' && window.history.state && window.history.state.photoPreviewOpen) {
        pushPhotoHistoryFlag = false;
        window.history.back();
    }
};

onMounted(() => {
    if (isMobile()) {
        viewMode.value = 'FEED';
    }
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', handleResize);
        window.addEventListener('keydown', handleLightboxKeyDown);
        window.addEventListener('popstate', handlePopState);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('keydown', handleLightboxKeyDown);
        window.removeEventListener('popstate', handlePopState);
        document.body.style.overflow = '';
    }
});

// Filter Options
const statusOptions = [
    { value: 'ALL', label: 'Semua Status' },
    { value: 'EXCELLENT', label: 'Sempurna' },
    { value: 'NEED_ATTENTION', label: 'Perlu Perhatian' },
    { value: 'PENDING', label: 'Menunggu' }
];

const shiftOptions = [
    { value: 'ALL', label: 'Semua Shift' },
    { value: 'PAGI', label: 'Shift Pagi' },
    { value: 'SIANG', label: 'Shift Siang' },
    { value: 'MALAM', label: 'Shift Malam' }
];

// ==========================================
// TOILETS WITH INSPECTIONS FOR SELECTED DATE
// ==========================================
const itemsForDate = computed(() => {
    const targetDate = selectedDate.value;
    const allToilets = props.toilets || [];
    const allInspections = props.inspections || [];

    // Inspections for selected date
    const dateInspections = allInspections.filter(i => {
        const iDate = i.inspection_date || (i.created_at ? i.created_at.substring(0, 10) : '');
        return iDate === targetDate;
    });

    if (allToilets.length > 0) {
        return allToilets.map(toilet => {
            const matched = dateInspections.find(i => {
                if (i.toilet_id != null) {
                    return String(i.toilet_id) === String(toilet.id);
                }
                if (i.toilet && i.toilet.id != null) {
                    return String(i.toilet.id) === String(toilet.id);
                }
                if (toilet.code && i.location && i.location.includes(toilet.code)) {
                    return true;
                }
                return false;
            });

            if (matched) {
                return {
                    id: matched.id,
                    toilet,
                    inspection: matched,
                    isChecked: true,
                    status: matched.status || 'EXCELLENT',
                    location: `${toilet.name} (${toilet.building || ''} - ${toilet.floor || ''})`.replace(/\(\s*-\s*\)/g, '').trim() || toilet.name,
                    rawLocation: toilet.name,
                    shift: matched.shift || 'Shift Siang',
                    checked_at: matched.checked_at || matched.created_at || '-',
                    inspector: matched.inspector || '-',
                    water_flush_ok: matched.water_flush_ok,
                    floor_mirror_clean: matched.floor_mirror_clean,
                    supplies_ready: matched.supplies_ready,
                    air_fragrance_ok: matched.air_fragrance_ok,
                    notes: matched.notes || '',
                    photo: matched.photo || null,
                    photo_list: Array.isArray(matched.photo_list) && matched.photo_list.length > 0 ? matched.photo_list : (matched.photo ? [matched.photo] : [])
                };
            }

            return {
                id: `pending-${toilet.id}`,
                toilet,
                inspection: null,
                isChecked: false,
                status: 'PENDING',
                location: `${toilet.name} (${toilet.building || ''} - ${toilet.floor || ''})`.replace(/\(\s*-\s*\)/g, '').trim() || toilet.name,
                rawLocation: toilet.name,
                shift: '-',
                checked_at: '-',
                inspector: '-',
                water_flush_ok: null,
                floor_mirror_clean: null,
                supplies_ready: null,
                air_fragrance_ok: null,
                notes: '',
                photo: null,
                photo_list: []
            };
        });
    }

    // Fallback if no toilets registered yet: show whatever inspections exist for this date
    return dateInspections.map(i => ({
        id: i.id,
        toilet: null,
        inspection: i,
        isChecked: true,
        status: i.status || 'EXCELLENT',
        location: i.location,
        rawLocation: i.location,
        shift: i.shift || 'Shift Siang',
        checked_at: i.checked_at || i.created_at || '-',
        inspector: i.inspector || '-',
        water_flush_ok: i.water_flush_ok,
        floor_mirror_clean: i.floor_mirror_clean,
        supplies_ready: i.supplies_ready,
        air_fragrance_ok: i.air_fragrance_ok,
        notes: i.notes || '',
        photo: i.photo || null,
        photo_list: Array.isArray(i.photo_list) && i.photo_list.length > 0 ? i.photo_list : (i.photo ? [i.photo] : [])
    }));
});

// Daily Progress & Statistics for Selected Date
const dateStats = computed(() => {
    const list = itemsForDate.value;
    const total = list.length;
    const checked = list.filter(i => i.isChecked).length;
    const pending = list.filter(i => !i.isChecked).length;
    const excellent = list.filter(i => i.status === 'EXCELLENT').length;
    const needAttention = list.filter(i => i.status === 'NEED_ATTENTION').length;
    const percent = total > 0 ? Math.round((checked / total) * 100) : 0;

    return {
        total,
        checked,
        pending,
        excellent,
        needAttention,
        percent
    };
});

// Filter Logic
const filteredInspections = computed(() => {
    return itemsForDate.value.filter(item => {
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q ||
            (item.location && item.location.toLowerCase().includes(q)) ||
            (item.inspector && item.inspector.toLowerCase().includes(q)) ||
            (item.notes && item.notes.toLowerCase().includes(q)) ||
            (item.shift && item.shift.toLowerCase().includes(q));

        let matchesStatus = true;
        if (selectedStatus.value !== 'ALL') {
            matchesStatus = item.status === selectedStatus.value;
        }

        let matchesShift = true;
        if (selectedShift.value !== 'ALL') {
            matchesShift = item.shift && item.shift.toUpperCase().includes(selectedShift.value);
        }

        return matchesSearch && matchesStatus && matchesShift;
    });
});

// Reset page on filter change
watch([searchQuery, selectedStatus, selectedShift, selectedDate], () => {
    currentPage.value = 1;
});

// Pagination Calculations
const totalPages = computed(() => Math.ceil(filteredInspections.value.length / perPage.value) || 1);

const paginatedInspections = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredInspections.value.slice(start, start + perPage.value);
});

const startItemIndex = computed(() => {
    if (filteredInspections.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredInspections.value.length);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const formatTime = (val) => {
    if (!val || val === '-') return '-';
    if (val.includes(' - ') && val.length > 10) return val;
    const clean = val.replace(/\s*WITA\s*/i, '').trim();
    if (/^\d{2}:\d{2}$/.test(clean)) {
        return `${clean} WITA`;
    }
    return val;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'EXCELLENT':
            return {
                label: 'Sempurna',
                icon: CheckCircle2,
                class: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'
            };
        case 'NEED_ATTENTION':
            return {
                label: 'Perlu Perhatian',
                icon: AlertCircle,
                class: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50'
            };
        default:
            return {
                label: 'Menunggu',
                icon: Clock,
                class: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
            };
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- 1. 3 SUMMARY STAT CARDS (Desain Orisinal Yang Sangat Disukai) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Card 1: Total Titik Patroli -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Titik Patroli</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ dateStats.total }}</div>
                    <span class="text-[11px] text-slate-400 block">Jadwal titik toilet aktif</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                    <ClipboardCheck class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>

            <!-- Card 2: Perlu Perhatian -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Perlu Perhatian</span>
                    <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-tight">{{ dateStats.needAttention }}</div>
                    <span class="text-[11px] text-slate-400 block">
                        Butuh tindak lanjut petugas
                    </span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-950/40">
                    <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
            </div>

            <!-- Card 3: Kondisi Sempurna -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Kondisi Sempurna</span>
                    <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">{{ dateStats.excellent }}</div>
                    <span class="text-[11px] text-slate-400 block">Memenuhi 4 pilar standar sanitasi</span>
                </div>
                <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-emerald-950/40">
                    <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
        </div>

        <!-- 2. UNIFIED CARD / TABLE WRAPPER (Desain Bersih & Elegan Orisinal) -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/60 rounded-2xl shadow-sm overflow-hidden mb-4">
            <!-- Search & Custom Filter Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 sm:p-5">
                <!-- Left: Search Box -->
                <div class="relative w-full sm:w-80 xl:w-96">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari lokasi toilet, pengawas, atau catatan..."
                        class="w-full h-10 pl-10 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150 shadow-none"
                    />
                </div>

                <!-- Right Controls: Status Filter, Shift Filter & View Switcher -->
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end flex-wrap">
                    <!-- Status Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedStatus"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none shadow-none"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
                    </div>

                    <!-- Shift Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-initial">
                        <select
                            v-model="selectedShift"
                            class="w-full sm:w-auto h-10 pl-3.5 pr-8 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-emerald-500 cursor-pointer appearance-none shadow-none"
                        >
                            <option v-for="opt in shiftOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
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

            <!-- Progress Ceklis & Pemilih Tanggal (Di dalam Wrapper, di Bawah Search & Filter) -->
            <div class="px-4 sm:px-5 pb-4 sm:pb-5">
                <div class="bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800/80 rounded-2xl p-4 sm:p-4.5 space-y-3">
                    <!-- Header: Quick Date Switcher & Date Label -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <!-- Stepper & Interactive Date Picker Widget (Full Width di Mobile, Auto di Desktop) -->
                        <div class="h-9 w-full sm:w-auto flex items-center bg-white dark:bg-slate-800 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-2xs overflow-hidden">
                            <!-- Prev Day -->
                            <button
                                @click="prevDay"
                                type="button"
                                title="Hari Sebelumnya"
                                class="h-full px-3 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/60 transition cursor-pointer flex items-center justify-center border-r border-slate-100 dark:border-slate-700/60 active:bg-slate-100 shrink-0"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <!-- Date Picker Trigger & Label -->
                            <div 
                                @click="triggerDatePicker"
                                class="relative h-full flex-1 sm:flex-initial flex items-center justify-center px-4 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition cursor-pointer select-none"
                                title="Klik untuk memilih tanggal"
                            >
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 pointer-events-none whitespace-nowrap">
                                    {{ formattedSelectedDate }}
                                </span>
                                <input
                                    ref="datePickerInputRef"
                                    type="date"
                                    v-model="selectedDate"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    title="Pilih tanggal"
                                />
                            </div>

                            <!-- Next Day -->
                            <button
                                @click="nextDay"
                                type="button"
                                title="Hari Berikutnya"
                                class="h-full px-3 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/60 transition cursor-pointer flex items-center justify-center border-l border-slate-100 dark:border-slate-700/60 active:bg-slate-100 shrink-0"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Progress Counter Pill (Responsive di Mobile) -->
                        <div class="flex items-center justify-between sm:justify-end gap-2 w-full sm:w-auto h-9">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                Progress Ceklis: <span class="text-emerald-600 dark:text-emerald-400 font-black">{{ dateStats.checked }}</span> dari {{ dateStats.total }} Titik
                            </span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                                {{ dateStats.percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-200/70 dark:bg-slate-700/60 h-2.5 rounded-full overflow-hidden">
                        <div 
                            class="bg-emerald-500 h-full rounded-full transition-all duration-500" 
                            :style="{ width: dateStats.percent + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- TIPE 1: VIEW TABEL STANDAR RS (SEPERTI ADUAN SANITASI) -->
            <div v-if="viewMode === 'TABLE'" class="px-4 sm:px-5 pb-4 sm:pb-5 bg-white dark:bg-slate-900">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-4 py-3.5 text-center w-12">NO</th>
                                <th class="px-5 py-3.5">WAKTU & PETUGAS</th>
                                <th class="px-5 py-3.5">LOKASI</th>
                                <th class="px-5 py-3.5 text-center">4 PILAR STANDAR SANITASI</th>
                                <th class="px-5 py-3.5 min-w-[220px]">CATATAN PATROLI</th>
                                <th class="px-5 py-3.5 text-center">STATUS</th>
                                <th class="px-5 py-3.5 text-center">FOTO BUKTI</th>
                                <th class="px-5 py-3.5 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300 bg-white dark:bg-slate-900">
                            <tr 
                                v-for="(item, idx) in paginatedInspections" 
                                :key="'patrol-table-' + item.id" 
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors duration-150"
                            >
                                <!-- No -->
                                <td class="px-4 py-4 whitespace-nowrap text-center text-xs font-semibold text-slate-400 dark:text-slate-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- Waktu & Petugas Inspeksi -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-xs">
                                        <Clock class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ formatTime(item.checked_at) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                                        <User class="h-3 w-3 text-slate-400 shrink-0" />
                                        <span>{{ item.inspector || '-' }}</span>
                                    </div>
                                </td>

                                <!-- Lokasi Inspeksi -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-xs">
                                        <MapPin class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                        <span>{{ item.location }}</span>
                                    </div>
                                </td>

                                <!-- 4 Pilar Standar Sanitasi Grid (Center) -->
                                <td class="px-5 py-4 text-center">
                                    <div class="grid grid-cols-2 gap-1.5 text-[10px] min-w-[410px] w-full max-w-[430px] mx-auto">
                                        <!-- Pilar 1: Ketersediaan Air -->
                                        <div class="min-w-0 flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                            <span class="text-slate-600 dark:text-slate-300 font-medium text-left leading-tight">Ketersediaan Air</span>
                                            <span v-if="item.water_flush_ok === true" class="h-4 w-4 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                                <Check class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else-if="item.water_flush_ok === false" class="h-4 w-4 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                                <X class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else class="text-[10px] text-slate-400 font-bold shrink-0">-</span>
                                        </div>

                                        <!-- Pilar 2: Kebersihan -->
                                        <div class="min-w-0 flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                            <span class="text-slate-600 dark:text-slate-300 font-medium text-left leading-tight">Kebersihan</span>
                                            <span v-if="item.floor_mirror_clean === true" class="h-4 w-4 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                                <Check class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else-if="item.floor_mirror_clean === false" class="h-4 w-4 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                                <X class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else class="text-[10px] text-slate-400 font-bold shrink-0">-</span>
                                        </div>

                                        <!-- Pilar 3: Ketersediaan Sabun & Tisu -->
                                        <div class="min-w-0 flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                            <span class="text-slate-600 dark:text-slate-300 font-medium text-left leading-tight">Ketersediaan Sabun & Tisu</span>
                                            <span v-if="item.supplies_ready === true" class="h-4 w-4 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                                <Check class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else-if="item.supplies_ready === false" class="h-4 w-4 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                                <X class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else class="text-[10px] text-slate-400 font-bold shrink-0">-</span>
                                        </div>

                                        <!-- Pilar 4: Keharuman & Sirkulasi Udara -->
                                        <div class="min-w-0 flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                            <span class="text-slate-600 dark:text-slate-300 font-medium text-left leading-tight">Keharuman & Sirkulasi Udara</span>
                                            <span v-if="item.air_fragrance_ok === true" class="h-4 w-4 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                                <Check class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else-if="item.air_fragrance_ok === false" class="h-4 w-4 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                                <X class="w-2.5 h-2.5 stroke-[2.5]" />
                                            </span>
                                            <span v-else class="text-[10px] text-slate-400 font-bold shrink-0">-</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Catatan Patroli -->
                                <td class="px-5 py-4 text-xs">
                                    <p v-if="item.notes" class="text-slate-700 dark:text-slate-300 italic font-medium leading-relaxed max-w-xs break-words">
                                        "{{ item.notes }}"
                                    </p>
                                    <span v-else class="text-slate-400 italic">Tidak ada catatan</span>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border inline-flex items-center gap-1.5',
                                        getStatusBadge(item.status).class
                                    ]">
                                        <component :is="getStatusBadge(item.status).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                        <span>{{ getStatusBadge(item.status).label }}</span>
                                    </span>
                                </td>

                                <!-- Foto Bukti (Mendukung Multi-file) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div v-if="item.photo_list && item.photo_list.length > 0" class="inline-flex items-center justify-center gap-1 flex-wrap max-w-[140px]">
                                        <button 
                                            v-for="(pUrl, pIdx) in item.photo_list"
                                            :key="'tbl-photo-' + pIdx"
                                            @click="openPhotoPreview(pUrl, `${item.location} (${formatTime(item.checked_at)})${item.photo_list.length > 1 ? ' #' + (pIdx + 1) : ''}`)"
                                            type="button"
                                            class="h-8 px-2 inline-flex items-center justify-center gap-1 rounded-xl bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer shadow-2xs"
                                            :title="`Lihat Foto Bukti Patroli ${item.photo_list.length > 1 ? '#' + (pIdx + 1) : ''}`"
                                        >
                                            <Camera class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                            <span v-if="item.photo_list.length > 1" class="text-[10px] font-bold">{{ pIdx + 1 }}</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 font-medium">
                                        -
                                    </span>
                                </td>

                                <!-- Aksi (Edit Ceklis atau Ceklis Sekarang) -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="item.isChecked"
                                            @click="emit('openChecklistModal', { toilet: item.toilet, inspection: item.inspection, date: selectedDate })"
                                            type="button"
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-extrabold inline-flex items-center justify-center gap-1.5 border transition-all duration-150 cursor-pointer active:scale-95 whitespace-nowrap bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700"
                                            title="Edit Data Ceklis Inspeksi"
                                        >
                                            <Edit3 class="w-3 h-3 shrink-0 stroke-[2.5] text-emerald-600 dark:text-emerald-400" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            v-else
                                            @click="emit('openChecklistModal', { toilet: item.toilet, date: selectedDate })"
                                            type="button"
                                            class="px-2.5 py-1 rounded-xl text-[10px] font-extrabold inline-flex items-center justify-center gap-1.5 border transition-all duration-150 cursor-pointer active:scale-95 whitespace-nowrap bg-emerald-600 hover:bg-emerald-500 text-white border-transparent shadow-xs"
                                            title="Input Ceklis Untuk Titik Toilet Ini"
                                        >
                                            <Check class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                            <span>Input Ceklis</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State row for Table -->
                            <tr v-if="filteredInspections.length === 0">
                                <td colspan="8" class="py-16 text-center text-slate-400 bg-white dark:bg-slate-900">
                                    <ClipboardCheck class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada data patroli ceklis yang sesuai pada tanggal {{ selectedDate }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Coba ubah tanggal, kata kunci pencarian, atau filter shift/status.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TIPE 2: VIEW KARTU / FEED DENGAN BG PUTIH BERSIH & SPACING SERAGAM -->
            <div v-else-if="viewMode === 'FEED'" class="px-4 sm:px-5 pb-4 sm:pb-5 bg-white dark:bg-slate-900">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="item in paginatedInspections" 
                        :key="'patrol-' + item.id"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs flex flex-col justify-between hover:border-emerald-500/40 transition-all overflow-hidden"
                    >
                        <!-- Card Header: Background Abu Netral & Status Badge -->
                        <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1 flex items-center gap-2">
                                <MapPin class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                                <h4 class="text-xs font-semibold text-slate-900 dark:text-white leading-tight truncate" :title="item.location">
                                    {{ item.location }}
                                </h4>
                            </div>
                            <span :class="[
                                'px-2.5 py-1 rounded-xl text-[10px] font-extrabold border shrink-0 inline-flex items-center gap-1.5',
                                getStatusBadge(item.status).class
                            ]">
                                <component :is="getStatusBadge(item.status).icon" class="w-3 h-3 shrink-0 stroke-[2.5]" />
                                <span>{{ getStatusBadge(item.status).label }}</span>
                            </span>
                        </div>

                        <!-- Card Body: 4 Pilar Sanitasi, Catatan, & Petugas -->
                        <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                            <div class="space-y-3.5">
                                <!-- 4 Pilar Sanitasi Checklist Mini Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    <!-- Pilar 1: Ketersediaan Air -->
                                    <div class="min-w-0 flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-slate-600 dark:text-slate-300 text-[11px] font-medium leading-tight">Ketersediaan Air</span>
                                        <span v-if="item.water_flush_ok === true" class="h-5 w-5 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                            <Check class="w-3 h-3" />
                                        </span>
                                        <span v-else-if="item.water_flush_ok === false" class="h-5 w-5 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                            <X class="w-3 h-3" />
                                        </span>
                                        <span v-else class="text-[11px] text-slate-400 font-bold shrink-0">-</span>
                                    </div>

                                    <!-- Pilar 2: Kebersihan -->
                                    <div class="min-w-0 flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-slate-600 dark:text-slate-300 text-[11px] font-medium leading-tight">Kebersihan</span>
                                        <span v-if="item.floor_mirror_clean === true" class="h-5 w-5 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                            <Check class="w-3 h-3" />
                                        </span>
                                        <span v-else-if="item.floor_mirror_clean === false" class="h-5 w-5 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                            <X class="w-3 h-3" />
                                        </span>
                                        <span v-else class="text-[11px] text-slate-400 font-bold shrink-0">-</span>
                                    </div>

                                    <!-- Pilar 3: Ketersediaan Sabun & Tisu -->
                                    <div class="min-w-0 flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-slate-600 dark:text-slate-300 text-[11px] font-medium leading-tight">Ketersediaan Sabun & Tisu</span>
                                        <span v-if="item.supplies_ready === true" class="h-5 w-5 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                            <Check class="w-3 h-3" />
                                        </span>
                                        <span v-else-if="item.supplies_ready === false" class="h-5 w-5 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                            <X class="w-3 h-3" />
                                        </span>
                                        <span v-else class="text-[11px] text-slate-400 font-bold shrink-0">-</span>
                                    </div>

                                    <!-- Pilar 4: Keharuman & Sirkulasi Udara -->
                                    <div class="min-w-0 flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-slate-600 dark:text-slate-300 text-[11px] font-medium leading-tight">Keharuman & Sirkulasi Udara</span>
                                        <span v-if="item.air_fragrance_ok === true" class="h-5 w-5 shrink-0 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 flex items-center justify-center">
                                            <Check class="w-3 h-3" />
                                        </span>
                                        <span v-else-if="item.air_fragrance_ok === false" class="h-5 w-5 shrink-0 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center">
                                            <X class="w-3 h-3" />
                                        </span>
                                        <span v-else class="text-[11px] text-slate-400 font-bold shrink-0">-</span>
                                    </div>
                                </div>

                                <!-- Catatan Temuan Patroli -->
                                <div v-if="item.notes" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 min-h-[52px] flex flex-col justify-center">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight mb-1">
                                        Catatan Patroli
                                    </span>
                                    <p class="text-xs text-slate-700 dark:text-slate-200 italic leading-relaxed">
                                        "{{ item.notes }}"
                                    </p>
                                </div>

                                <!-- Foto Bukti Patroli (Mendukung Multi-file) -->
                                <div v-if="item.photo_list && item.photo_list.length > 0" class="space-y-1.5">
                                    <div 
                                        v-for="(pUrl, pIdx) in item.photo_list"
                                        :key="'card-photo-' + pIdx"
                                        @click="openPhotoPreview(pUrl, `${item.location} (${formatTime(item.checked_at)})${item.photo_list.length > 1 ? ' #' + (pIdx + 1) : ''}`)"
                                        class="group flex items-center justify-between gap-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 hover:border-emerald-500/40 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 transition-all cursor-pointer select-none min-h-[52px]"
                                        title="Klik untuk memperbesar foto bukti patroli"
                                    >
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <!-- Thumbnail Preview Kecil -->
                                            <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shrink-0 relative">
                                                <img 
                                                    :src="pUrl" 
                                                    :alt="'Thumbnail Bukti ' + (pIdx + 1)" 
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                    loading="lazy"
                                                />
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors block leading-tight">
                                                    Foto Bukti Patroli {{ item.photo_list.length > 1 ? `#${pIdx + 1}` : '' }}
                                                </span>
                                                <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block truncate">
                                                    Klik untuk memperbesar foto
                                                </span>
                                            </div>
                                        </div>

                                        <div class="h-7 w-7 rounded-lg bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600/60 text-slate-400 group-hover:text-emerald-600 group-hover:border-emerald-300 dark:group-hover:text-emerald-400 flex items-center justify-center shrink-0 transition-all shadow-2xs">
                                            <Maximize2 class="w-3.5 h-3.5" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Row: Petugas & Waktu Inspeksi -->
                                <div class="flex items-center justify-between gap-3 text-xs pt-1">
                                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 min-w-0">
                                        <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                        <span class="truncate">Petugas: <strong class="text-slate-700 dark:text-slate-200 font-semibold">{{ item.inspector || '-' }}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[11px] text-slate-400 shrink-0">
                                        <Clock class="w-3 h-3 text-slate-400" />
                                        <span>{{ formatTime(item.checked_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom: Tombol Input Ceklis / Edit Full Width Seperti Aduan Sanitasi -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80">
                                <button
                                    v-if="item.isChecked"
                                    @click="emit('openChecklistModal', { toilet: item.toilet, inspection: item.inspection, date: selectedDate })"
                                    type="button"
                                    class="w-full h-10 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer border bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700"
                                >
                                    <Edit3 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                    <span>Edit</span>
                                </button>
                                <button
                                    v-else
                                    @click="emit('openChecklistModal', { toilet: item.toilet, date: selectedDate })"
                                    type="button"
                                    class="w-full h-10 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer border bg-emerald-600 hover:bg-emerald-500 text-white border-transparent shadow-xs"
                                >
                                    <Check class="w-4 h-4 stroke-[2.5]" />
                                    <span>Input Ceklis</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredInspections.length === 0" class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900">
                        <ClipboardCheck class="h-12 w-12 text-slate-200 dark:text-slate-700 mx-auto mb-2" />
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada data patroli ceklis yang sesuai pada tanggal {{ selectedDate }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Coba ubah tanggal, kata kunci pencarian, atau filter shift/status.</p>
                    </div>
                </div>
            </div>

            <!-- Table Footer / Interactive Pagination -->
            <div class="px-4 sm:px-5 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-white dark:bg-slate-900 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <!-- Left: Per-Page Selector -->
                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="text-[11px] font-medium">Tampilkan</span>
                    <select
                        v-model="perPage"
                        @change="currentPage = 1"
                        class="h-7 py-0 pl-2 pr-6 text-[11px] font-normal rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer transition"
                    >
                        <option :value="6">6</option>
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                    </select>
                    <span class="text-[11px] font-medium">data</span>
                </div>

                <!-- Right: Compact Range & Navigation Buttons -->
                <div class="flex items-center gap-3">
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {{ startItemIndex }}–{{ endItemIndex }} dari {{ filteredInspections.length }}
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

        <!-- Lightbox Image Preview Modal (Close HANYA via Tombol X, ESC, atau Browser Back) -->
        <Teleport to="body">
            <div 
                v-if="previewPhotoUrl" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md select-none"
            >
                <!-- Tombol Close Lingkaran di Pojok Kanan Atas Layar (Selalu Terlihat Jelas) -->
                <button
                    @click="closePhotoPreview"
                    type="button"
                    class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[60] h-10 w-10 sm:h-11 sm:w-11 rounded-full bg-slate-900/80 hover:bg-rose-600 text-white border border-white/20 shadow-2xl flex items-center justify-center cursor-pointer transition-all duration-150 active:scale-95"
                    title="Tutup (ESC / Tombol X)"
                >
                    <X class="h-5 w-5 sm:h-6 sm:w-6 stroke-[2.5]" />
                </button>

                <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center justify-center">
                    <img
                        :src="previewPhotoUrl"
                        alt="Foto Bukti Patroli"
                        class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border border-white/10"
                    />
                </div>
            </div>
        </Teleport>
    </div>
</template>
