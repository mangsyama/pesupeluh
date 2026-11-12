<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Search, 
    CheckCircle2, 
    Clock, 
    AlertCircle, 
    ArrowRight, 
    Copy, 
    ShieldCheck, 
    RefreshCw,
    XCircle,
    ArrowLeft,
    Check,
    MapPin,
    Building2,
    Sparkles,
    UserCheck,
    Image as ImageIcon,
    Leaf,
    Maximize2,
    X,
    Wrench
} from '@lucide/vue';

const props = defineProps({
    initialTicket: {
        type: String,
        default: ''
    },
    report: {
        type: Object,
        default: null
    },
    searched: {
        type: Boolean,
        default: false
    },
    toiletCode: {
        type: String,
        default: ''
    }
});

const searchInput = ref(props.initialTicket || '');
const isSearching = ref(false);
const copied = ref(false);
const isKeyboardOpen = ref(false);
const isClearing = ref(false);
const searchInputRef = ref(null);

// Lightbox Preview State (Standar Sama Persis Dengan Modal Verifikasi)
const previewPhotoUrl = ref(null);
const previewPhotoTitle = ref('');
const previewDialogRef = ref(null);
let pushPhotoHistoryFlag = false;
let ignoreNextPopState = false;
let ignoreTimer = null;

const openPhotoPreview = (url, title = 'Foto Bukti') => {
    if (!url) return;
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title;
    if (typeof document !== 'undefined') {
        document.body.style.overflow = 'hidden';
    }
    try {
        window.history.pushState({ photoPreviewOpen: true }, '');
        pushPhotoHistoryFlag = true;
    } catch (e) {
        pushPhotoHistoryFlag = false;
    }
};

const closePhotoPreview = (shouldGoBack = true) => {
    if (!previewPhotoUrl.value) return;

    previewPhotoUrl.value = null;
    previewPhotoTitle.value = '';

    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }

    if (previewDialogRef.value && previewDialogRef.value.open) {
        try {
            previewDialogRef.value.close();
        } catch (e) {}
    }

    if (shouldGoBack && pushPhotoHistoryFlag && typeof window !== 'undefined') {
        pushPhotoHistoryFlag = false;
        ignoreNextPopState = true;
        clearTimeout(ignoreTimer);
        ignoreTimer = setTimeout(() => {
            ignoreNextPopState = false;
        }, 500);
        try {
            window.history.back();
        } catch (e) {
            ignoreNextPopState = false;
        }
    } else {
        pushPhotoHistoryFlag = false;
    }
};

watch(previewPhotoUrl, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            if (previewDialogRef.value && !previewDialogRef.value.open) {
                previewDialogRef.value.showModal();
            }
        }, 10);
    } else {
        if (previewDialogRef.value && previewDialogRef.value.open) {
            try {
                previewDialogRef.value.close();
            } catch (e) {}
        }
    }
});

// Intercept popstate (Browser Back)
const handleLightboxPopState = (e) => {
    if (ignoreNextPopState) {
        ignoreNextPopState = false;
        clearTimeout(ignoreTimer);
        return;
    }

    if (previewPhotoUrl.value) {
        closePhotoPreview(false);
    }
};

// Tangkap ESC pada capture phase
const handleLightboxKeyDown = (e) => {
    if (e.key === 'Escape' && previewPhotoUrl.value) {
        e.preventDefault();
        closePhotoPreview(true);
    }
};

const getUrlParam = (key) => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(key) || '';
    }
    return '';
};

const backToMenuUrl = computed(() => {
    const code = props.toiletCode 
        || getUrlParam('toilet') 
        || getUrlParam('code') 
        || (typeof sessionStorage !== 'undefined' ? sessionStorage.getItem('kesling_active_toilet') : '');
    if (code) {
        return route('public.sanitasi.create', { toilet: code });
    }
    return route('public.sanitasi.create');
});

const clearSearch = () => {
    searchInput.value = '';
    if (searchInputRef.value) {
        searchInputRef.value.focus();
    }
    if (props.searched || props.report) {
        isClearing.value = true;
        const code = props.toiletCode || getUrlParam('toilet') || getUrlParam('code');
        const query = code ? { toilet: code } : {};
        router.get(route('public.sanitasi.track'), query, {
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isClearing.value = false;
            }
        });
    }
};

const checkKeyboard = () => {
    if (window.visualViewport) {
        isKeyboardOpen.value = window.visualViewport.height < window.innerHeight - 150;
    }
};

const beforePhotos = computed(() => {
    const list = props.report?.evidence_before_list;
    if (Array.isArray(list) && list.length > 0) return list;
    return props.report?.evidence_before ? [props.report.evidence_before] : [];
});

const afterPhotos = computed(() => {
    const list = props.report?.evidence_after_list;
    if (Array.isArray(list) && list.length > 0) return list;
    return props.report?.evidence_after ? [props.report.evidence_after] : [];
});

onMounted(() => {
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', checkKeyboard);
    }
    if (typeof window !== 'undefined') {
        window.addEventListener('popstate', handleLightboxPopState, true);
        window.addEventListener('keydown', handleLightboxKeyDown, true);
    }
});

onUnmounted(() => {
    if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', checkKeyboard);
    }
    if (typeof window !== 'undefined') {
        window.removeEventListener('popstate', handleLightboxPopState, true);
        window.removeEventListener('keydown', handleLightboxKeyDown, true);
    }
    clearTimeout(ignoreTimer);
    if (previewDialogRef.value && previewDialogRef.value.open) {
        try {
            previewDialogRef.value.close();
        } catch (e) {}
    }
});

const handleSearch = () => {
    const trimmed = searchInput.value.trim().toUpperCase();
    if (!trimmed) return;
    searchInput.value = trimmed;
    
    isSearching.value = true;
    const code = props.toiletCode || getUrlParam('toilet') || getUrlParam('code');
    const query = { ticket: trimmed };
    if (code) {
        query.toilet = code;
    }
    router.get(route('public.sanitasi.track'), query, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isSearching.value = false;
        }
    });
};

const copyTicket = () => {
    if (!props.report?.ticket_number) return;
    navigator.clipboard.writeText(props.report.ticket_number);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

// Cek status laporan
const isCompleted = computed(() => {
    const s = props.report?.status?.toUpperCase() || '';
    return s === 'RESOLVED' || s === 'CLOSED' || s === 'VERIFIED' || s === 'SELESAI';
});

const isInProgress = computed(() => {
    const s = props.report?.status?.toUpperCase() || '';
    return s === 'IN_PROGRESS' || s === 'ASSIGNED' || s === 'DIKERJAKAN';
});

const isPending = computed(() => {
    const s = props.report?.status?.toUpperCase() || '';
    return !isCompleted.value && !isInProgress.value && !isRejected.value;
});

const isRejected = computed(() => {
    const s = props.report?.status?.toUpperCase() || '';
    return s === 'REJECTED' || s === 'DITOLAK';
});

// Format Lokasi Toilet
const formatLocation = computed(() => {
    if (!props.report) return '-';
    const point = props.report.location_point || props.report.room_name || 'Toilet Fasilitas';
    if (point.includes('(') && point.includes(')')) {
        return point;
    }
    const b = props.report.building_name;
    const f = props.report.floor;
    if (b && f) {
        return `${point} (${b} - ${f})`;
    }
    return point;
});

const statusBadge = computed(() => {
    if (!props.report) return { text: 'Menunggu', class: 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800/50' };
    if (isCompleted.value) {
        return { text: 'Selesai', class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50' };
    }
    if (isRejected.value) {
        return { text: 'Ditolak', class: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/50' };
    }
    if (isInProgress.value) {
        return { text: 'Dikerjakan', class: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/50' };
    }
    return { text: 'Menunggu', class: 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-800/50' };
});
</script>

<template>
    <Head title="Lacak Progres Aduan Sanitasi" />

    <div class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-slate-100/70 dark:bg-slate-950 p-0 sm:p-6 text-slate-900 dark:text-slate-100">
        <!-- Main Container Card -->
        <main class="w-full sm:max-w-xl mx-auto z-10 my-auto flex flex-col flex-1 sm:flex-initial py-0 sm:py-6">
            <div class="w-full flex-1 sm:flex-initial flex flex-col sm:rounded-2xl sm:border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                <!-- Card Body -->
                <div class="p-5 sm:p-8 space-y-6 pb-32 sm:pb-8">
                    <!-- Search Box Form -->
                    <form @submit.prevent="handleSearch" class="space-y-2">
                        <label for="ticket_search" class="block text-xs font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-300">
                            Masukkan Nomor Registrasi Aduan
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input
                                    ref="searchInputRef"
                                    id="ticket_search"
                                    v-model="searchInput"
                                    type="text"
                                    placeholder="Contoh: PS-20261102-001"
                                    class="h-12 w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pl-4 pr-10 text-xs sm:text-sm uppercase font-bold tracking-wider focus:border-emerald-500 dark:focus:border-emerald-400 focus:bg-white dark:focus:bg-slate-950 focus:outline-none focus:ring-0 transition"
                                />
                                <span v-if="searchInput" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-1" title="Hapus Pencarian">
                                    <XCircle class="h-4 w-4" />
                                </span>
                            </div>
                            <button
                                type="submit"
                                :disabled="!searchInput.trim() || isSearching"
                                class="h-12 flex items-center justify-center gap-2 px-5 sm:px-7 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs sm:text-sm transition cursor-pointer active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                            >
                                <RefreshCw v-if="isSearching" class="h-4 w-4 animate-spin" />
                                <Search v-else class="h-4 w-4" />
                                <span>Lacak</span>
                            </button>
                        </div>
                    </form>

                    <!-- STATE 1: Hasil Ditemukan -->
                    <div v-if="!isClearing && report" class="space-y-5 animate-spa-fade-in">
                        <!-- Top Summary Ticket Card (Container Rapi Senada) -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                            <div class="p-4 sm:p-5 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block">Nomor Registrasi</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-base sm:text-lg font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">
                                                {{ report.ticket_number }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="copyTicket"
                                                class="p-1 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition cursor-pointer"
                                                title="Salin Nomor Tiket"
                                            >
                                                <Copy class="h-3.5 w-3.5" />
                                            </button>
                                            <span v-if="copied" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">Disalin!</span>
                                        </div>
                                    </div>
                                    <span :class="['px-2.5 py-1 rounded-full text-[11px] font-extrabold border', statusBadge.class]">
                                        {{ statusBadge.text }}
                                    </span>
                                </div>

                                <!-- Hanya Lokasi Toilet & Waktu Aduan Saja (Tumpukan Atas-Bawah) -->
                                <div class="space-y-2 text-xs">
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight">
                                            Lokasi Toilet
                                        </span>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white mt-1 leading-snug break-words">
                                            {{ formatLocation }}
                                        </p>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight">
                                            Waktu Aduan
                                        </span>
                                        <p class="text-xs font-semibold text-slate-900 dark:text-white mt-1 leading-snug">
                                            {{ report.created_at || '-' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Kotak Uraian Kendala Pelapor -->
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight mb-1">
                                        Uraian Kendala
                                    </span>
                                    <p class="text-xs text-slate-700 dark:text-slate-300 italic leading-relaxed">
                                        "{{ report.problem_description }}"
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper / Resi Timeline: 4 Langkah Alur Progres Sesuai Permintaan -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                            <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                    Alur Progres Penanganan
                                </h4>
                            </div>

                            <div class="p-5 sm:p-6">
                                <div class="space-y-0.5">
                                    <!-- Step 1: Aduan Masuk -->
                                    <div class="flex gap-3.5">
                                        <div class="flex flex-col items-center shrink-0">
                                            <div class="h-6 w-6 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                                                <CheckCircle2 class="h-3.5 w-3.5 stroke-[2.5]" />
                                            </div>
                                            <div class="w-0.5 flex-1 min-h-[34px] my-1 bg-emerald-500"></div>
                                        </div>
                                        <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                            <p class="text-xs font-bold text-slate-900 dark:text-white">Aduan Masuk</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                Aduan telah berhasil diterima dan tercatat di sistem.
                                            </p>
                                            <span class="text-[10px] text-slate-400 mt-1 block">{{ report.created_at }}</span>
                                        </div>
                                    </div>

                                    <!-- Step 2: Menunggu Dikerjakan -->
                                    <div class="flex gap-3.5">
                                        <div class="flex flex-col items-center shrink-0">
                                            <div 
                                                :class="[
                                                    'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                    isPending 
                                                        ? 'bg-orange-500 text-white ring-4 ring-orange-100 dark:ring-orange-950/50' 
                                                        : 'bg-emerald-600 text-white'
                                                ]"
                                            >
                                                <CheckCircle2 v-if="!isPending" class="h-3.5 w-3.5 stroke-[2.5]" />
                                                <Clock v-else class="h-3.5 w-3.5 stroke-[2.5]" />
                                            </div>
                                            <div 
                                                class="w-0.5 flex-1 min-h-[34px] my-1 transition-colors"
                                                :class="isInProgress || isCompleted ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"
                                            ></div>
                                        </div>
                                        <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                            <p 
                                                class="text-xs font-bold"
                                                :class="isPending ? 'text-orange-600 dark:text-orange-400 font-extrabold' : 'text-slate-900 dark:text-white'"
                                            >
                                                Menunggu Dikerjakan
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                {{ isPending 
                                                    ? 'Aduan telah masuk antrean dan sedang menunggu penanganan petugas.' 
                                                    : 'Aduan telah diverifikasi dan siap ditindaklanjuti.' }}
                                            </p>
                                            <span v-if="report.created_at" class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1 block">
                                                {{ report.created_at }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Step 3: Dikerjakan -->
                                    <div class="flex gap-3.5">
                                        <div class="flex flex-col items-center shrink-0">
                                            <div 
                                                :class="[
                                                    'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                    isCompleted 
                                                        ? 'bg-emerald-600 text-white' 
                                                        : (isInProgress 
                                                            ? 'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-950/50 animate-pulse' 
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700')
                                                ]"
                                            >
                                                <CheckCircle2 v-if="isCompleted" class="h-3.5 w-3.5 stroke-[2.5]" />
                                                <Wrench v-else-if="isInProgress" class="h-3.5 w-3.5 stroke-[2.5]" />
                                                <Clock v-else class="h-3.5 w-3.5" />
                                            </div>
                                            <div 
                                                class="w-0.5 flex-1 min-h-[34px] my-1 transition-colors"
                                                :class="isCompleted ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"
                                            ></div>
                                        </div>
                                        <div class="pb-6 pt-0.5 min-w-0 flex-1">
                                            <p 
                                                class="text-xs font-bold"
                                                :class="isInProgress 
                                                    ? 'text-blue-600 dark:text-blue-400 font-extrabold' 
                                                    : (isCompleted ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500')"
                                            >
                                                {{ isInProgress ? 'Sedang Dikerjakan' : 'Dikerjakan' }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                {{ isCompleted 
                                                    ? 'Penanganan telah selesai dilakukan oleh petugas.' 
                                                    : (isInProgress 
                                                        ? 'Petugas sedang melakukan penanganan langsung di lokasi.' 
                                                        : 'Petugas akan segera menuju lokasi untuk melakukan penanganan.') }}
                                            </p>
                                            <span v-if="(isInProgress || isCompleted) && (report.started_at || report.resolved_at || report.created_at)" class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1 block">
                                                {{ report.started_at || report.resolved_at || report.created_at }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Step 4: Selesai -->
                                    <div class="flex gap-3.5">
                                        <div class="flex flex-col items-center shrink-0">
                                            <div 
                                                :class="[
                                                    'h-6 w-6 rounded-full flex items-center justify-center shrink-0 transition-colors shadow-sm',
                                                    isCompleted 
                                                        ? 'bg-emerald-600 text-white ring-4 ring-emerald-100 dark:ring-emerald-950/50' 
                                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700'
                                                ]"
                                            >
                                                <CheckCircle2 v-if="isCompleted" class="h-3.5 w-3.5 stroke-[2.5]" />
                                                <Clock v-else class="h-3.5 w-3.5" />
                                            </div>
                                        </div>
                                        <div class="pt-0.5 min-w-0 flex-1">
                                            <p 
                                                class="text-xs font-bold"
                                                :class="isCompleted ? 'text-emerald-600 dark:text-emerald-400 font-extrabold' : 'text-slate-400 dark:text-slate-500'"
                                            >
                                                Selesai
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                                {{ isCompleted 
                                                    ? 'Laporan telah selesai ditangani dan fasilitas siap digunakan kembali.' 
                                                    : 'Menunggu proses penanganan selesai dan verifikasi akhir.' }}
                                            </p>
                                            <span v-if="isCompleted && report.resolved_at" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1 block">
                                                Selesai pada: {{ report.resolved_at }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dokumentasi Penanganan Toilet (Container Desain Rapi Seragam dengan Resolusi Modal) -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                            <!-- Section Header -->
                            <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                    Dokumentasi Penanganan Toilet
                                </h4>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 sm:p-5 space-y-4">
                                <!-- Sub-bagian 1: Kondisi Awal Laporan -->
                                <div class="space-y-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight">
                                        Kondisi Awal Laporan
                                    </span>
                                    <div v-if="beforePhotos.length > 0" class="space-y-2">
                                        <div 
                                            v-for="(photoUrl, idx) in beforePhotos"
                                            :key="'track-before-' + idx"
                                            @click="openPhotoPreview(photoUrl, `Kondisi Awal Laporan ${beforePhotos.length > 1 ? '#' + (idx + 1) : ''} - ${report.ticket_number}`)"
                                            class="group flex items-center justify-between gap-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 hover:border-emerald-500/40 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 transition-all cursor-pointer select-none min-h-[52px]"
                                            title="Klik untuk memperbesar foto awal"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 shrink-0 relative">
                                                    <img 
                                                        :src="photoUrl" 
                                                        :alt="'Kondisi Awal ' + (idx + 1)" 
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                        loading="lazy" 
                                                    />
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors block leading-tight">
                                                        Foto Kondisi Awal {{ beforePhotos.length > 1 ? `#${idx + 1}` : '' }}
                                                    </span>
                                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block truncate">
                                                        Klik untuk memperbesar
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="h-7 w-7 rounded-lg bg-white dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600/60 text-slate-400 group-hover:text-emerald-600 group-hover:border-emerald-300 dark:group-hover:text-emerald-400 flex items-center justify-center shrink-0 transition-all shadow-2xs">
                                                <Maximize2 class="w-3.5 h-3.5" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 text-slate-400 min-h-[52px]">
                                        <div class="h-10 w-10 rounded-lg bg-slate-200/60 dark:bg-slate-700/60 flex items-center justify-center shrink-0 text-slate-400">
                                            <ImageIcon class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block leading-tight">Foto Kondisi Awal</span>
                                            <span class="text-[11px] text-slate-400 block mt-0.5">Tidak ada foto</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider Pemisah -->
                                <div class="border-t border-slate-100 dark:border-slate-800/80"></div>

                                <!-- Sub-bagian 2: Bukti Penyelesaian dari Petugas -->
                                <div class="space-y-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block leading-tight">
                                        Bukti Penyelesaian Petugas
                                    </span>
                                    <div v-if="afterPhotos.length > 0" class="space-y-2">
                                        <div 
                                            v-for="(photoUrl, idx) in afterPhotos"
                                            :key="'track-after-' + idx"
                                            @click="openPhotoPreview(photoUrl, `Bukti Penyelesaian Petugas ${afterPhotos.length > 1 ? '#' + (idx + 1) : ''} - ${report.ticket_number}`)"
                                            class="group flex items-center justify-between gap-3 p-2.5 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/20 border border-emerald-200/70 dark:border-emerald-900/50 hover:border-emerald-500/60 transition-all cursor-pointer select-none min-h-[52px]"
                                            title="Klik untuk memperbesar bukti penyelesaian"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="h-10 w-10 rounded-lg overflow-hidden bg-emerald-100 dark:bg-slate-700 border border-emerald-300/80 shrink-0 relative">
                                                    <img 
                                                        :src="photoUrl" 
                                                        :alt="'Bukti Penyelesaian ' + (idx + 1)" 
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                        loading="lazy" 
                                                    />
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors block leading-tight">
                                                        Foto Penyelesaian {{ afterPhotos.length > 1 ? `#${idx + 1}` : '' }}
                                                    </span>
                                                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 block truncate">
                                                        Klik untuk memperbesar
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="h-7 w-7 rounded-lg bg-white dark:bg-slate-700/60 border border-emerald-200 dark:border-emerald-700/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 transition-all shadow-2xs">
                                                <Maximize2 class="w-3.5 h-3.5" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 text-slate-400 min-h-[52px]">
                                        <div class="h-10 w-10 rounded-lg bg-slate-200/60 dark:bg-slate-700/60 flex items-center justify-center shrink-0 text-slate-400">
                                            <ImageIcon class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block leading-tight">Foto Penyelesaian</span>
                                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                                {{ isCompleted ? 'Tidak ada foto' : 'Menunggu pengerjaan selesai' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STATE 2: Laporan Tidak Ditemukan -->
                    <div v-else-if="!isClearing && searched" class="text-center py-8 space-y-3 animate-spa-fade-in">
                        <div class="h-14 w-14 rounded-full bg-rose-50 dark:bg-rose-950/50 text-rose-500 flex items-center justify-center mx-auto border border-rose-200 dark:border-rose-900/50">
                            <AlertCircle class="h-7 w-7" />
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Nomor Tiket Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                            Kami tidak menemukan aduan dengan nomor registrasi <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ initialTicket }}</strong>. Mohon periksa kembali nomor tiket yang tercetak di tanda terima Anda.
                        </p>
                    </div>

                    <!-- STATE 3: Panduan Awal (Belum Mencari) -->
                    <div v-else class="text-center py-8 space-y-3">
                        <div class="h-14 w-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto border border-emerald-500/20">
                            <Search class="h-7 w-7" />
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Pantau Status Penanganan Aduan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
                            Masukkan Nomor Registrasi untuk melihat progres penanganan aduan secara real-time.
                        </p>
                    </div>

                    <!-- Desktop Action (Inside Card - No Top Border) -->
                    <div class="hidden sm:flex items-center pt-4">
                        <Link
                            :href="backToMenuUrl"
                            class="w-full flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Menu Utama</span>
                        </Link>
                    </div>
                </div>

                <!-- Floating Bottom Navigation Bar (ONLY MOBILE, Auto-hides when keyboard opens) -->
                <div
                    v-show="!isKeyboardOpen"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 shadow-2xl transition-all duration-200"
                >
                    <div class="w-full">
                        <Link
                            :href="backToMenuUrl"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Menu Utama</span>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Lightbox Image Preview Modal (Close HANYA via Tombol X, ESC, atau Browser Back - Tanpa Slide/Swipe) -->
        <dialog
            ref="previewDialogRef"
            class="fixed inset-0 m-0 h-screen w-screen max-h-none max-w-none p-0 border-0 bg-transparent backdrop:bg-slate-950/90 backdrop:backdrop-blur-md outline-none focus:outline-none z-[9999] touch-none overscroll-none"
            @cancel.prevent.stop="closePhotoPreview(true)"
            @touchmove.prevent
        >
            <div 
                v-if="previewPhotoUrl" 
                class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md select-none touch-none overscroll-none"
                @touchmove.prevent
            >
                <!-- Tombol Close Lingkaran di Pojok Kanan Atas Layar (Selalu Terlihat Jelas) -->
                <button
                    @click.stop="closePhotoPreview(true)"
                    type="button"
                    class="fixed top-4 right-4 sm:top-6 sm:right-6 z-[10000] h-10 w-10 sm:h-11 sm:w-11 rounded-full bg-slate-900/80 hover:bg-rose-600 text-white border border-white/20 shadow-2xl flex items-center justify-center cursor-pointer transition-all duration-150 active:scale-95"
                    title="Tutup (ESC / Tombol X)"
                >
                    <X class="h-5 w-5 sm:h-6 sm:w-6 stroke-[2.5]" />
                </button>

                <div 
                    class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center justify-center select-none touch-none"
                    @dragstart.prevent
                    @touchmove.prevent
                >
                    <img
                        :src="previewPhotoUrl"
                        :alt="previewPhotoTitle || 'Foto Bukti'"
                        draggable="false"
                        class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border border-white/10 select-none pointer-events-auto touch-none"
                        style="user-select: none; -webkit-user-drag: none; touch-action: none;"
                    />
                </div>
            </div>
        </dialog>
    </div>
</template>
