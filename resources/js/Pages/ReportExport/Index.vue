<script setup>
import { ref, computed, watch, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { FileText, Download, BarChart3, CheckCircle2, Clock, FileBarChart2, Filter, RotateCcw, Building, MapPin, UserCheck, Eye, ExternalLink, ClipboardList, ChevronDown, Check, Search, Calendar } from '@lucide/vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const { proxy } = getCurrentInstance();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total_month: 0, completed: 0, pending: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: '',
            unit_id: '',
            category_id: '',
            room_id: '',
            reporter_id: '',
        }),
    },
    supportingUnits: {
        type: Array,
        default: () => [],
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    reporters: {
        type: Array,
        default: () => [],
    },
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const formFilters = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    unit_id: props.filters.unit_id || '',
    category_id: props.filters.category_id || '',
    room_id: props.filters.room_id || '',
    reporter_id: props.filters.reporter_id || '',
});

// Flatpickr calendar refs and instances
const dateRangeRef = ref(null);
let fpRange = null;

// Custom Dropdowns State
const isUnitDropdownOpen = ref(false);
const isCategoryDropdownOpen = ref(false);
const isRoomDropdownOpen = ref(false);
const isReporterDropdownOpen = ref(false);

// Custom Dropdowns Search State
const unitSearchQuery = ref('');
const categorySearchQuery = ref('');
const roomSearchQuery = ref('');
const reporterSearchQuery = ref('');

const selectedUnitLabel = computed(() => {
    if (!formFilters.value.unit_id) return '-- Semua Unit --';
    const u = props.supportingUnits.find(item => String(item.id) === String(formFilters.value.unit_id));
    return u ? u.name : '-- Semua Unit --';
});

const selectedCategoryLabel = computed(() => {
    if (!formFilters.value.unit_id) return '-- Pilih Unit Terlebih Dahulu --';
    if (!formFilters.value.category_id) return '-- Semua Kategori --';
    const c = availableCategories.value.find(item => String(item.id) === String(formFilters.value.category_id));
    return c ? c.name : '-- Semua Kategori --';
});

const selectedRoomLabel = computed(() => {
    if (!formFilters.value.room_id) return '-- Semua Ruangan --';
    const r = props.rooms.find(item => String(item.id) === String(formFilters.value.room_id));
    return r ? `${r.name} (Lt. ${r.location_floor})` : '-- Semua Ruangan --';
});

const selectedReporterLabel = computed(() => {
    if (!formFilters.value.reporter_id) return '-- Semua Pelapor --';
    const rep = props.reporters.find(item => String(item.id) === String(formFilters.value.reporter_id));
    return rep ? rep.name : '-- Semua Pelapor --';
});

const closeAllDropdowns = () => {
    isUnitDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
    isReporterDropdownOpen.value = false;
    
    // Clear search queries
    unitSearchQuery.value = '';
    categorySearchQuery.value = '';
    roomSearchQuery.value = '';
    reporterSearchQuery.value = '';
};

const toggleUnitDropdown = (e) => {
    e?.stopPropagation();
    isUnitDropdownOpen.value = !isUnitDropdownOpen.value;
    isCategoryDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
    isReporterDropdownOpen.value = false;
    
    unitSearchQuery.value = '';
};

const selectUnit = (id) => {
    formFilters.value.unit_id = id;
    closeAllDropdowns();
};

const toggleCategoryDropdown = (e) => {
    if (!formFilters.value.unit_id) return;
    e?.stopPropagation();
    isCategoryDropdownOpen.value = !isCategoryDropdownOpen.value;
    isUnitDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
    isReporterDropdownOpen.value = false;
    
    categorySearchQuery.value = '';
};

const selectCategory = (id) => {
    formFilters.value.category_id = id;
    closeAllDropdowns();
};

const toggleRoomDropdown = (e) => {
    e?.stopPropagation();
    isRoomDropdownOpen.value = !isRoomDropdownOpen.value;
    isUnitDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isReporterDropdownOpen.value = false;
    
    roomSearchQuery.value = '';
};

const selectRoom = (id) => {
    formFilters.value.room_id = id;
    closeAllDropdowns();
};

const toggleReporterDropdown = (e) => {
    e?.stopPropagation();
    isReporterDropdownOpen.value = !isReporterDropdownOpen.value;
    isUnitDropdownOpen.value = false;
    isCategoryDropdownOpen.value = false;
    isRoomDropdownOpen.value = false;
    
    reporterSearchQuery.value = '';
};

const selectReporter = (id) => {
    formFilters.value.reporter_id = id;
    closeAllDropdowns();
};

// Computed filtered lists for search
const filteredSupportingUnits = computed(() => {
    const q = unitSearchQuery.value.toLowerCase().trim();
    if (!q) return props.supportingUnits;
    return props.supportingUnits.filter(u => u.name.toLowerCase().includes(q));
});

const filteredCategories = computed(() => {
    const list = availableCategories.value;
    const q = categorySearchQuery.value.toLowerCase().trim();
    if (!q) return list;
    return list.filter(c => c.name.toLowerCase().includes(q));
});

const filteredRooms = computed(() => {
    const q = roomSearchQuery.value.toLowerCase().trim();
    if (!q) return props.rooms;
    return props.rooms.filter(r => 
        r.name.toLowerCase().includes(q) || 
        (r.location_floor && r.location_floor.toLowerCase().includes(q))
    );
});

const filteredReporters = computed(() => {
    let list = props.reporters;
    if (formFilters.value.room_id) {
        list = list.filter(r => String(r.room_id) === String(formFilters.value.room_id));
    }
    const q = reporterSearchQuery.value.toLowerCase().trim();
    if (!q) return list;
    return list.filter(r => r.name.toLowerCase().includes(q));
});

// Dynamic category list based on selected unit_id
const availableCategories = computed(() => {
    if (!formFilters.value.unit_id) return [];
    
    const unit = props.supportingUnits.find(u => String(u.id) === String(formFilters.value.unit_id));
    if (!unit) return [];
    
    return unit.issue_categories || unit.issueCategories || [];
});

// Reset category if selected unit_id changes
watch(() => formFilters.value.unit_id, () => {
    formFilters.value.category_id = '';
});

// Reset reporter if selected room_id changes and reporter is not in that room
watch(() => formFilters.value.room_id, (newRoomId) => {
    if (newRoomId && formFilters.value.reporter_id) {
        const reporter = props.reporters.find(r => String(r.id) === String(formFilters.value.reporter_id));
        if (reporter && String(reporter.room_id) !== String(newRoomId)) {
            formFilters.value.reporter_id = '';
        }
    }
});

const isFiltering = ref(false);
const isLoading = computed(() => isFiltering.value || !props.tickets?.data);

watch(() => props.tickets, () => {
    isFiltering.value = false;
});

const applyFilters = () => {
    isFiltering.value = true;
    router.visit(route('reports.index'), {
        data: formFilters.value,
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            isFiltering.value = true;
        },
        onFinish: () => {
            isFiltering.value = false;
        }
    });
};

const resetFilters = () => {
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    
    const formatDate = (d) => {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    };

    const sDate = formatDate(startOfMonth);
    const eDate = formatDate(now);

    formFilters.value = {
        start_date: sDate,
        end_date: eDate,
        unit_id: '',
        category_id: '',
        room_id: '',
        reporter_id: '',
    };

    if (fpRange) fpRange.setDate([sDate, eDate]);

    applyFilters();
};

const exportPdf = () => {
    proxy.$toast(proxy.__('pages.reports.center.export_toast').replace('{format}', 'PDF'), 'success');
    window.open(route('reports.export.pdf', formFilters.value), '_blank');
};

const exportCsv = () => {
    proxy.$toast(proxy.__('pages.reports.center.export_toast').replace('{format}', 'CSV'), 'success');
    window.location.href = route('reports.export.csv', formFilters.value);
};

onMounted(() => {
    window.addEventListener('click', closeAllDropdowns);

    fpRange = flatpickr(dateRangeRef.value, {
        locale: {
            ...Indonesian,
            rangeSeparator: ' - '
        },
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd F Y',
        altInputClass: 'w-full h-10 px-4 text-center border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-0 focus:border-slate-200 dark:focus:border-slate-800 transition duration-150',
        defaultDate: [formFilters.value.start_date, formFilters.value.end_date],
        onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
                const formatDate = (d) => {
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                };
                formFilters.value.start_date = formatDate(selectedDates[0]);
                formFilters.value.end_date = formatDate(selectedDates[1]);
            }
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('click', closeAllDropdowns);
});

watch(() => props.filters, (newVal) => {
    if (fpRange && newVal.start_date && newVal.end_date) {
        fpRange.setDate([newVal.start_date, newVal.end_date]);
    }
}, { deep: true });
</script>

<template>
    <Head :title="__('pages.reports.center.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FileBarChart2 class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                                {{ __('pages.reports.center.title') }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                {{ __('pages.reports.center.description') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid (Row 2) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Total Laporan Bulan Ini -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ __('pages.reports.center.stat_total_month') }}</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ stats?.total_month ?? 0 }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                            <BarChart3 class="h-6 w-6 text-emerald-600 dark:text-white" />
                        </div>
                    </div>

                    <!-- Laporan Terverifikasi -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ __('pages.reports.center.stat_verified') }}</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ stats?.completed ?? 0 }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                            <CheckCircle2 class="h-6 w-6 text-emerald-600 dark:text-white" />
                        </div>
                    </div>

                    <!-- Menunggu Review -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ __('pages.reports.center.stat_pending') }}</span>
                            <div v-if="isLoading" class="h-8 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse mt-0.5"></div>
                            <div v-else class="text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ stats?.pending ?? 0 }}</div>
                        </div>
                        <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10">
                            <Clock class="h-6 w-6 text-emerald-600 dark:text-white" />
                        </div>
                    </div>
                </div>

                <!-- Split Grid Layout (Row 3: Filters & Exports) -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                    <!-- Left Column: Filters (8 cols) -->
                    <div class="xl:col-span-8">
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between h-full space-y-6">
                            <div class="space-y-4">
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                        Filter Pencarian Data
                                    </h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <!-- Tanggal Mulai - Selesai -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Tanggal Mulai - Selesai
                                        </label>
                                        <input 
                                            ref="dateRangeRef"
                                            type="text" 
                                            placeholder="Pilih Rentang Tanggal"
                                            class="w-full h-10 px-4 text-center border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none focus:ring-0 focus:border-slate-200 dark:focus:border-slate-800 transition duration-150"
                                        />
                                    </div>

                                    <!-- Unit Penunjang -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Unit Penunjang
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleUnitDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedUnitLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isUnitDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isUnitDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="unitSearchQuery"
                                                        type="text"
                                                        placeholder="Cari unit..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <div v-if="filteredSupportingUnits.length === 0" class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                                        Unit tidak ditemukan
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click.stop="selectUnit('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.unit_id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Unit --</span>
                                                        <Check v-if="!formFilters.unit_id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="unit in filteredSupportingUnits"
                                                        :key="unit.id"
                                                        type="button"
                                                        @click.stop="selectUnit(unit.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="String(formFilters.unit_id) === String(unit.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ unit.name }}</span>
                                                        <Check v-if="String(formFilters.unit_id) === String(unit.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kategori Permasalahan -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Kategori Permasalahan
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleCategoryDropdown"
                                                :disabled="!formFilters.unit_id"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedCategoryLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isCategoryDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isCategoryDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="categorySearchQuery"
                                                        type="text"
                                                        placeholder="Cari kategori..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <div v-if="filteredCategories.length === 0" class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                                        Kategori tidak ditemukan
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click.stop="selectCategory('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.category_id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Kategori --</span>
                                                        <Check v-if="!formFilters.category_id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="cat in filteredCategories"
                                                        :key="cat.id"
                                                        type="button"
                                                        @click.stop="selectCategory(cat.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="String(formFilters.category_id) === String(cat.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ cat.name }}</span>
                                                        <Check v-if="String(formFilters.category_id) === String(cat.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ruangan -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Ruangan
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleRoomDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedRoomLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isRoomDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isRoomDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="roomSearchQuery"
                                                        type="text"
                                                        placeholder="Cari ruangan atau lantai..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <div v-if="filteredRooms.length === 0" class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                                        Ruangan tidak ditemukan
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click.stop="selectRoom('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.room_id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Ruangan --</span>
                                                        <Check v-if="!formFilters.room_id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="room in filteredRooms"
                                                        :key="room.id"
                                                        type="button"
                                                        @click.stop="selectRoom(room.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="String(formFilters.room_id) === String(room.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ room.name }} (Lt. {{ room.location_floor }})</span>
                                                        <Check v-if="String(formFilters.room_id) === String(room.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Staf Pelapor -->
                                    <div class="space-y-1.5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center">
                                            Staf / Pelapor
                                        </label>
                                        <div class="relative">
                                            <button
                                                type="button"
                                                @click.stop="toggleReporterDropdown"
                                                class="w-full h-10 px-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-center focus:outline-none transition-all duration-150 text-center relative"
                                            >
                                                <span class="truncate font-medium text-slate-800 dark:text-slate-100 text-center">
                                                    {{ selectedReporterLabel }}
                                                </span>
                                                <ChevronDown :class="['absolute right-4 h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0', isReporterDropdownOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
                                            </button>

                                            <div
                                                v-if="isReporterDropdownOpen"
                                                class="absolute z-30 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-md"
                                            >
                                                <!-- Search Input -->
                                                <div class="relative">
                                                    <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                    <input 
                                                        v-model="reporterSearchQuery"
                                                        type="text"
                                                        placeholder="Cari staf pelapor..."
                                                        class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-0 focus:ring-offset-0"
                                                        @click.stop
                                                    />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                    <div v-if="filteredReporters.length === 0" class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                                                        Staf tidak ditemukan
                                                    </div>
                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click.stop="selectReporter('')"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="!formFilters.reporter_id ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">-- Semua Pelapor --</span>
                                                        <Check v-if="!formFilters.reporter_id" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                    <button
                                                        v-for="rep in filteredReporters"
                                                        :key="rep.id"
                                                        type="button"
                                                        @click.stop="selectReporter(rep.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                        :class="String(formFilters.reporter_id) === String(rep.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                    >
                                                        <span class="truncate">{{ rep.name }}</span>
                                                        <Check v-if="String(formFilters.reporter_id) === String(rep.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button 
                                    type="button"
                                    @click="resetFilters"
                                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    Reset Filter
                                </button>
                                
                                <button 
                                    type="button"
                                    @click="applyFilters"
                                    class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold transition shadow-sm shadow-emerald-500/10"
                                >
                                    <Filter class="h-3.5 w-3.5" />
                                    Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Export Actions (4 cols) -->
                    <div class="xl:col-span-4">
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between h-full min-h-[250px] space-y-6">
                            <div class="space-y-4">
                            <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Ekspor Laporan
                                </h3>
                            </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Unduh data laporan yang disaring ke format PDF resmi (layout cetak A4) atau CSV mentah (dapat diolah di Excel/Sheets).
                                </p>
                            </div>

                            <div class="space-y-3">
                                <!-- PDF Button -->
                                <button
                                    @click="exportPdf"
                                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-500 text-white font-extrabold text-xs rounded-xl transition duration-150 shadow-sm gap-2"
                                >
                                    <FileText class="h-4 w-4" />
                                    {{ __('pages.reports.center.pdf_btn') }}
                                </button>

                                <!-- CSV Button -->
                                <button
                                    @click="exportCsv"
                                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 font-extrabold text-xs rounded-xl transition duration-150 shadow-sm gap-2"
                                >
                                    <BarChart3 class="h-4 w-4" />
                                    {{ __('pages.reports.center.csv_btn') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Data Preview Table -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Preview Data Laporan
                            </h3>
                        </div>
                        <span class="text-xs text-slate-400 dark:text-slate-500">
                            Menampilkan {{ tickets.data?.length ?? 0 }} data dari total {{ tickets.total ?? 0 }} laporan terfilter
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800/80 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="px-6 py-4 whitespace-nowrap">No. Tiket</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Tanggal</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Pelapor</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Ruangan</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Unit Penunjang</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Kategori</th>
                                    <th class="px-6 py-4 whitespace-nowrap">Deskripsi Kerusakan</th>
                                    <th class="px-6 py-4 whitespace-nowrap text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                                <!-- Skeleton Loading Rows -->
                                <template v-if="isLoading">
                                    <tr v-for="n in 5" :key="'skel-t-' + n" class="align-middle">
                                        <td class="px-6 py-4"><div class="h-4 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4"><div class="h-4 w-48 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div></td>
                                        <td class="px-6 py-4 text-center"><div class="h-6 w-20 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse mx-auto"></div></td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-else-if="!tickets.data || tickets.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                        Tidak ada data laporan yang sesuai filter
                                    </td>
                                </tr>

                                <!-- Real Data Rows -->
                                <tr 
                                    v-else
                                    v-for="ticket in tickets.data" 
                                    :key="ticket.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20 text-slate-700 dark:text-slate-300"
                                >
                                    <td class="px-6 py-4 font-bold text-emerald-600 dark:text-white whitespace-nowrap">
                                        {{ ticket.ticket_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ ticket.created_at ? new Date(ticket.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold whitespace-normal break-words max-w-[150px]">
                                        {{ ticket.reporter?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal break-words max-w-[150px]">
                                        {{ ticket.room?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal break-words max-w-[150px] font-medium text-slate-800 dark:text-slate-200">
                                        {{ ticket.category?.supporting_unit?.name ?? ticket.category?.supportingUnit?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal break-words max-w-[150px] font-medium text-slate-800 dark:text-slate-200">
                                        {{ ticket.category?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal break-words max-w-xs">
                                        {{ ticket.problem_description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span 
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase border',
                                                ticket.status === 'COMPLETED' 
                                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white dark:border-white/20' 
                                                    : (ticket.status === 'PENDING_VALIDATION' || ticket.status === 'PENDING')
                                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border-amber-200 dark:border-amber-800'
                                                        : ticket.status === 'CANCEL'
                                                            ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                                                            : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800'
                                            ]"
                                        >
                                            {{ ticket.status === 'COMPLETED' ? 'Selesai' : (ticket.status === 'PENDING_VALIDATION' ? 'Menunggu' : (ticket.status === 'PENDING' ? 'Tertunda' : (ticket.status === 'CANCEL' ? 'Batal' : 'Progres'))) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!tickets.data || tickets.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 font-medium italic">
                                        Tidak ada data laporan yang cocok dengan filter yang dipilih.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div 
                        v-if="tickets.links && tickets.links.length > 3" 
                        class="p-6 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-center gap-1"
                    >
                        <Component 
                            :is="link.url ? Link : 'span'"
                            v-for="(link, idx) in tickets.links" 
                            :key="idx" 
                            :href="link.url"
                            :data="formFilters"
                            preserve-state
                            preserve-scroll
                            class="h-8 min-w-[32px] px-2.5 rounded-lg text-xs font-bold flex items-center justify-center transition"
                            :class="[
                                link.active 
                                    ? 'bg-emerald-600 dark:bg-white text-white dark:text-slate-900' 
                                    : link.url 
                                        ? 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' 
                                        : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
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

/* Flatpickr Custom Styling to be compact and match column width */
:deep(.flatpickr-calendar) {
    width: 100% !important;
    max-width: 280px !important;
    min-width: unset !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important;
    font-family: inherit !important;
    padding: 8px !important;
}
:deep(.dark .flatpickr-calendar) {
    background: #0f172a !important;
    border-color: #1e293b !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3) !important;
}
:deep(.flatpickr-months) {
    padding: 4px 0 !important;
}
:deep(.flatpickr-months .flatpickr-month) {
    background: transparent !important;
    color: inherit !important;
}
:deep(.flatpickr-current-month) {
    font-size: 110% !important;
    font-weight: 700 !important;
}
:deep(.flatpickr-current-month .flatpickr-monthDropdown-months) {
    background: transparent !important;
    color: inherit !important;
    font-weight: 700 !important;
}
:deep(.flatpickr-weekday) {
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #94a3b8 !important;
}
:deep(.flatpickr-days) {
    width: 100% !important;
    margin-top: 6px !important;
}
:deep(.dayContainer) {
    width: 100% !important;
    min-width: unset !important;
    max-width: unset !important;
}
:deep(.flatpickr-day) {
    font-size: 12px !important;
    max-width: unset !important;
    flex-basis: 14.28% !important;
    height: 32px !important;
    line-height: 32px !important;
    border-radius: 10px !important;
    color: #475569 !important;
}
:deep(.dark .flatpickr-day) {
    color: #cbd5e1 !important;
}
:deep(.flatpickr-day.today) {
    border-color: #10b981 !important;
    color: #10b981 !important;
    font-weight: 800 !important;
}
:deep(.flatpickr-day.selected) {
    background: #059669 !important;
    border-color: #059669 !important;
    color: #ffffff !important;
}
:deep(.flatpickr-day:hover) {
    background: #f1f5f9 !important;
}
:deep(.dark .flatpickr-day:hover) {
    background: #1e293b !important;
}
:deep(.flatpickr-day.prevMonthDay),
:deep(.flatpickr-day.nextMonthDay) {
    color: #cbd5e1 !important;
    opacity: 0.4 !important;
}
:deep(.dark .flatpickr-day.prevMonthDay),
:deep(.dark .flatpickr-day.nextMonthDay) {
    color: #475569 !important;
}

/* Force disable outline and box shadow rings on focus / active on all inputs and buttons */
:deep(input:focus),
:deep(input[type="text"]:focus),
:deep(input.flatpickr-input:focus),
:deep(button:focus),
:deep(input:active),
:deep(button:active),
:deep(input:focus-within),
:deep(button:focus-within),
:deep(input:focus-visible),
:deep(button:focus-visible) {
    outline: none !important;
    outline-width: 0 !important;
    box-shadow: none !important;
    --tw-shadow: none !important;
    --tw-shadow-colored: none !important;
    --tw-ring-shadow: none !important;
    --tw-ring-color: transparent !important;
    -webkit-tap-highlight-color: transparent !important;
}
</style>