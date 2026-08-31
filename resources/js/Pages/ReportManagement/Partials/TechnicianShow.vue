<script setup>
import { ref, computed, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { compressImage } from '@/Utils/imageCompressor';
import {
    Calendar,
    User,
    MapPin,
    Wrench,
    Clock,
    Activity,
    FileText,
    Camera,
    UploadCloud,
    X,
    CheckCircle2,
    XCircle,
    Info,
    Play,
    Pause,
    Search,
    ImageIcon,
    UserCheck
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};
const showSlaInfoModal = ref(false);

const pendingHistories = computed(() => {
    const list = props.ticket?.histories ? props.ticket.histories.filter(h => h.action === 'PAUSED' || h.action === 'RESUMED' || h.status === 'PENDING') : [];
    
    if (props.ticket?.status === 'PENDING' && list.length === 0 && props.ticket?.pending_reason) {
        list.push({
            id: 'fallback-pending',
            action: 'PAUSED',
            status: 'PENDING',
            notes: props.ticket.pending_reason,
            created_at: props.ticket.last_paused_at || props.ticket.updated_at,
            user: null,
            duration_seconds: 0,
        });
    }
    
    return list;
});

const props = defineProps({
    ticket: {
        type: Object,
        default: () => null
    },
    personal: {
        type: Boolean,
        default: false
    }
});

// Live counter mechanism
const now = ref(new Date());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const user = computed(() => usePage().props.auth.user);

// Date Parsing
const parseDateSafe = (dateStr) => {
    if (!dateStr) return null;
    if (dateStr instanceof Date) return dateStr;
    const str = String(dateStr).trim();
    const normalized = str.includes(' ') && !str.includes('T') ? str.replace(' ', 'T') : str;
    const d = new Date(normalized);
    return isNaN(d.getTime()) ? null : d;
};

const parsedDates = computed(() => {
    return {
        created: parseDateSafe(props.ticket?.created_at),
        validated: parseDateSafe(props.ticket?.validated_at),
        responded: parseDateSafe(props.ticket?.responded_at),
        resolved: parseDateSafe(props.ticket?.resolved_at),
        lastPaused: parseDateSafe(props.ticket?.last_paused_at),
    };
});

// SLA Timers Calculations (in Seconds)
const responseTimeSeconds = computed(() => {
    const dates = parsedDates.value;
    const startTime = dates.validated || dates.created;
    if (!startTime) return null;
    if (dates.responded) {
        return Math.max(0, Math.floor((dates.responded.getTime() - startTime.getTime()) / 1000));
    }
    return Math.max(0, Math.floor((now.value.getTime() - startTime.getTime()) / 1000));
});

const pausedDurationSeconds = computed(() => {
    const dates = parsedDates.value;
    let accumulated = Number(props.ticket?.paused_duration_seconds || 0);
    if (props.ticket?.status === 'PENDING' && dates.lastPaused) {
        const elapsedSincePause = Math.floor((now.value.getTime() - dates.lastPaused.getTime()) / 1000);
        accumulated += Math.max(0, elapsedSincePause);
    }
    return accumulated;
});

const resolutionTimeSeconds = computed(() => {
    const dates = parsedDates.value;
    if (!dates.responded) return null;
    const pauseSecs = pausedDurationSeconds.value;

    if (dates.resolved) {
        const total = Math.floor((dates.resolved.getTime() - dates.responded.getTime()) / 1000);
        return Math.max(0, total - Number(props.ticket?.paused_duration_seconds || 0));
    }
    if (props.ticket?.status === 'PENDING' && dates.lastPaused) {
        const total = Math.floor((dates.lastPaused.getTime() - dates.responded.getTime()) / 1000);
        const prevPaused = Number(props.ticket?.paused_duration_seconds || 0);
        return Math.max(0, total - prevPaused);
    }
    const total = Math.floor((now.value.getTime() - dates.responded.getTime()) / 1000);
    return Math.max(0, total - pauseSecs);
});

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined || seconds < 0) return '00:00:00';
    
    const totalHours = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);

    const pad = (n) => String(n).padStart(2, '0');
    const baseFormatted = `${pad(totalHours)}:${pad(m)}:${pad(s)}`;

    const days = Math.floor(seconds / 86400);
    if (days > 0) {
        const remainingHours = Math.floor((seconds % 86400) / 3600);
        const dayStr = remainingHours > 0 ? `${days} Hari ${remainingHours} Jam` : `${days} Hari`;
        return `${baseFormatted} (${dayStr})`;
    }

    return baseFormatted;
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const datePart = d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${datePart}, ${hours}:${minutes} WITA`;
};

const arriveForm = useForm({
    attachments: []
});

const arrivePreviews = ref([]);
const arriveFileInput = ref(null);
const arriveCameraInput = ref(null);
const showArriveModal = ref(false);

const openArriveModal = () => {
    arriveForm.clearErrors();
    arrivePreviews.value = [];
    arriveForm.attachments = [];
    showArriveModal.value = true;
};

const handleArriveFileSelect = async (e) => {
    const files = Array.from(e.target.files || []);
    await processArriveFiles(files);
    if (e.target) e.target.value = '';
};

const processArriveFiles = async (files) => {
    if (!files || files.length === 0) return;
    const file = files[0];
    arriveForm.clearErrors('attachments');

    // Basic file type check
    if (file.type && !file.type.startsWith('image/') && !/\.(jpg|jpeg|png|webp|heic|heif|bmp)$/i.test(file.name)) {
        arriveForm.setError('attachments', 'File harus berupa gambar (JPG, PNG, WEBP, dsb).');
        return;
    }

    let dataUrl;
    try {
        dataUrl = await compressImage(file, 800, 800, 0.5);
    } catch (err) {
        console.warn('Compress image failed, falling back to raw reader:', err);
        dataUrl = await readFileAsDataURL(file);
    }

    // Protect against payloads larger than 3MB Base64 string length
    if (dataUrl && dataUrl.length > 3000000) {
        arriveForm.setError('attachments', 'Ukuran foto terlalu besar. Silakan gunakan foto lain.');
        return;
    }

    arrivePreviews.value = [{
        name: file.name,
        size: file.size,
        preview: dataUrl,
        data: dataUrl
    }];
    arriveForm.attachments = arrivePreviews.value.map(p => p.data);
};

const removeArriveAttachment = (idx) => {
    arrivePreviews.value.splice(idx, 1);
    arriveForm.attachments = arrivePreviews.value.map(p => p.data);
};

const isSubmittingArrive = ref(false);
const submitArrive = () => {
    if (!props.ticket?.uuid || isSubmittingArrive.value || arriveForm.processing) return;
    isSubmittingArrive.value = true;
    arriveForm.clearErrors();
    if (!arriveForm.attachments || arriveForm.attachments.length === 0) {
        arriveForm.setError('attachments', 'Wajib mengunggah minimal 1 foto bukti kedatangan di lokasi.');
        isSubmittingArrive.value = false;
        return;
    }

    arriveForm.post(`/tickets/${props.ticket.uuid}/respond`, {
        onSuccess: () => {
            showArriveModal.value = false;
            arriveForm.reset();
            arrivePreviews.value = [];
        },
        onFinish: () => {
            isSubmittingArrive.value = false;
        }
    });
};

const resolveForm = useForm({
    resolution_status: '',
    notes: '',
    attachments: []
});

const completePreviews = ref([]);
const completeFileInput = ref(null);
const completeCameraInput = ref(null);

const showCompleteModal = ref(false);
const showPendingModal = ref(false);
const showCancelModal = ref(false);

const openCompleteModal = () => {
    resolveForm.clearErrors();
    resolveForm.resolution_status = 'COMPLETED';
    resolveForm.notes = '';
    completePreviews.value = [];
    resolveForm.attachments = [];
    showCompleteModal.value = true;
};

const openPendingModal = () => {
    resolveForm.clearErrors();
    resolveForm.resolution_status = 'PENDING';
    resolveForm.notes = '';
    showPendingModal.value = true;
};

const openCancelModal = () => {
    resolveForm.clearErrors();
    resolveForm.resolution_status = 'CANCEL';
    resolveForm.notes = '';
    showCancelModal.value = true;
};

const handleCompleteFileSelect = async (e) => {
    const files = Array.from(e.target.files || []);
    await processCompleteFiles(files);
    if (e.target) e.target.value = '';
};

const processCompleteFiles = async (files) => {
    if (!files || files.length === 0) return;
    const file = files[0];
    resolveForm.clearErrors('attachments');

    if (file.type && !file.type.startsWith('image/') && !/\.(jpg|jpeg|png|webp|heic|heif|bmp)$/i.test(file.name)) {
        resolveForm.setError('attachments', 'File harus berupa gambar (JPG, PNG, WEBP, dsb).');
        return;
    }

    let dataUrl;
    try {
        dataUrl = await compressImage(file, 800, 800, 0.5);
    } catch (err) {
        console.warn('Compress image failed, falling back to raw reader:', err);
        dataUrl = await readFileAsDataURL(file);
    }

    if (dataUrl && dataUrl.length > 3000000) {
        resolveForm.setError('attachments', 'Ukuran foto terlalu besar. Silakan gunakan foto lain.');
        return;
    }

    completePreviews.value = [{
        name: file.name,
        size: file.size,
        preview: dataUrl,
        data: dataUrl
    }];
    resolveForm.attachments = completePreviews.value.map(p => p.data);
};

const removeCompleteAttachment = (idx) => {
    completePreviews.value.splice(idx, 1);
    resolveForm.attachments = completePreviews.value.map(p => p.data);
};

const readFileAsDataURL = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
};

const isSubmittingResolve = ref(false);
const submitResolve = (status) => {
    if (!props.ticket?.uuid || isSubmittingResolve.value || resolveForm.processing) return;
    isSubmittingResolve.value = true;
    resolveForm.clearErrors();
    resolveForm.resolution_status = status;

    if (status === 'COMPLETED' && (!resolveForm.attachments || resolveForm.attachments.length === 0)) {
        resolveForm.setError('attachments', 'Wajib mengunggah minimal 1 foto bukti hasil penanganan.');
        isSubmittingResolve.value = false;
        return;
    }

    resolveForm.post(`/tickets/${props.ticket.uuid}/resolve`, {
        onSuccess: () => {
            showCompleteModal.value = false;
            showPendingModal.value = false;
            showCancelModal.value = false;
            resolveForm.reset();
            completePreviews.value = [];
        },
        onFinish: () => {
            isSubmittingResolve.value = false;
        }
    });
};

const resumeTicket = () => {
    if (!props.ticket?.uuid) return;

    proxy.$swal({
        title: 'Konfirmasi Lanjutkan Pekerjaan',
        text: 'Apakah Anda yakin ingin melanjutkan kembali penanganan pekerjaan tiket ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('tickets.resume', props.ticket.uuid));
        }
    });
};

// Lightbox state
const activeLightbox = ref(null);
const openLightbox = (url) => {
    activeLightbox.value = url;
};
const closeLightbox = () => {
    activeLightbox.value = null;
};

// Group attachments
const reporterAttachments = computed(() => {
    return props.ticket?.attachments?.filter(att => 
        att.file_path && !att.file_path.includes('ticket_arr_') && !att.file_path.includes('ticket_res_')
    ) || [];
});

const arrivalAttachments = computed(() => {
    return props.ticket?.attachments?.filter(att => 
        att.file_path && att.file_path.includes('ticket_arr_')
    ) || [];
});

const completionAttachments = computed(() => {
    return props.ticket?.attachments?.filter(att => 
        att.file_path && (att.file_path.includes('ticket_res_') || (!att.file_path.includes('ticket_arr_') && att.uploaded_by != props.ticket?.reporter_id))
    ) || [];
});

const isVideo = (path) => {
    if (!path) return false;
    const lower = path.toLowerCase();
    return lower.endsWith('.mp4') || lower.endsWith('.mov') || lower.endsWith('.webm') || lower.endsWith('.ogg') || lower.endsWith('.3gp') || lower.endsWith('.avi');
};

const completedHistory = computed(() => {
    return props.ticket?.histories?.find(h => h.action === 'COMPLETED' || h.status === 'COMPLETED') || null;
});

const completedBy = computed(() => {
    if (completedHistory.value?.user?.name) {
        return completedHistory.value.user.name;
    }
    if (props.ticket?.assignments && props.ticket.assignments.length > 0) {
        return props.ticket.assignments.map(a => a.technician?.name).filter(Boolean).join(', ');
    }
    return null;
});

const cancelledHistory = computed(() => {
    return props.ticket?.histories?.find(h => h.action === 'CANCELLED' || h.status === 'CANCEL') || null;
});

const cancelledBy = computed(() => {
    if (cancelledHistory.value?.user?.name) {
        return cancelledHistory.value.user.name;
    }
    return null;
});

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu Validasi', badge: 'bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30' },
    ASSIGNED:           { label: 'Ditugaskan',       badge: 'bg-blue-50 text-blue-700 border-blue-200/50 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/30' },
    IN_PROGRESS:        { label: 'Dikerjakan',       badge: 'bg-violet-50 text-violet-700 border-violet-200/50 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/30' },
    PENDING:            { label: 'Ditangguhkan',     badge: 'bg-orange-50 text-orange-700 border-orange-200/50 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/30' },
    COMPLETED:          { label: 'Selesai',          badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-white/10 dark:text-white dark:border-white/20' },
    CANCEL:             { label: 'Dibatalkan',       badge: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/30' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border-slate-200' };

const priorityConfig = {
    URGENT:    { label: 'URGENT',  badge: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50' },
    ROUTINE:   { label: 'RUTIN',   badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' },
};

const getPriority = (target) => {
    if (!target) return { label: '-', badge: 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900/40 dark:border-slate-800' };

    const priority = typeof target === 'string' ? target : target.priority;
    const status = typeof target === 'object' ? target.status : (props.ticket?.status || null);

    if (status === 'PENDING_VALIDATION') {
        return { label: '-', badge: 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900/40 dark:border-slate-800' };
    }

    return priorityConfig[priority] ?? { label: '-', badge: 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900/40 dark:border-slate-800' };
};

const contextLabel = computed(() => {
    if (props.personal) {
        return 'Laporan Saya';
    }
    const roleId = Number(user.value?.role_id);
    if (roleId === 10) {
        return 'Tugas Saya';
    } else if (roleId === 9) {
        return 'Laporan Ruangan';
    } else if ([1, 2, 3, 4, 5, 6, 7, 8].includes(roleId)) {
        return 'Tugas Unit';
    }
    return null;
});

const canPerformAction = computed(() => {
    return props.ticket && 
        props.ticket.status !== 'PENDING_VALIDATION' && 
        props.ticket.status !== 'COMPLETED' && 
        props.ticket.status !== 'CANCEL';
});

// Smart Scroll Hide/Show for Mobile Floating Action Bar
const isFloatingBarVisible = ref(true);
let lastScrollY = 0;
const scrollThreshold = 8;

const handleScroll = () => {
    const currentScrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop;
    
    if (currentScrollY <= 40) {
        isFloatingBarVisible.value = true;
        lastScrollY = currentScrollY;
        return;
    }

    if (currentScrollY > lastScrollY + scrollThreshold) {
        // Scrolling down -> hide
        isFloatingBarVisible.value = false;
    } else if (currentScrollY < lastScrollY - scrollThreshold) {
        // Scrolling up -> show
        isFloatingBarVisible.value = true;
    }

    lastScrollY = currentScrollY;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="py-4 px-4 sm:px-4 lg:px-4" :class="canPerformAction ? 'pb-44 md:pb-4' : ''">
        <!-- Skeleton Loading view when ticket is fetching -->
        <div class="w-full space-y-4" v-if="!ticket">
            <!-- Ticket Profile Header Skeleton -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-slate-200/80 dark:bg-slate-800 animate-pulse flex-shrink-0"></div>
                    <div class="space-y-2">
                        <div class="h-6 w-36 bg-slate-200/80 dark:bg-slate-800 rounded-lg animate-pulse"></div>
                        <div class="h-4 w-48 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                    </div>
                </div>
            </div>

            <!-- Main Layout Grid Skeleton -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 space-y-4">
                    <!-- SLA Metrics Skeleton (Horizontal) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="h-5 w-36 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                            <div v-for="k in 3" :key="'skel-sla-' + k" class="h-16 bg-slate-100/80 dark:bg-slate-950/40 rounded-xl animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Ticket Info Skeleton -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="h-5 w-40 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="i in 4" :key="'skel-info-' + i" class="space-y-1.5">
                                    <div class="h-3 w-20 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    <div class="h-4 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="h-5 w-32 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                        <div class="h-10 w-full bg-slate-200/80 dark:bg-slate-800 rounded-xl animate-pulse"></div>
                        <div class="space-y-3 pt-2">
                            <div v-for="j in 4" :key="'skel-status-' + j" class="flex justify-between items-center">
                                <div class="h-3.5 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                <div class="h-4 w-16 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full space-y-4" v-else>
                    
                    <!-- Ticket Profile Header Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white shrink-0">
                                <Wrench class="h-6 w-6" />
                            </div>
                            <div class="space-y-0.5">
                                <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                    #{{ ticket.ticket_number }}
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed uppercase font-semibold">
                                    {{ ticket.category?.supporting_unit?.name ?? ticket.category?.supportingUnit?.name ?? 'IPSRS' }} &bull; {{ ticket.category?.name ?? 'PELAPORAN' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Section Layout Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        
                        <!-- Left Column: Details & Execution Panels -->
                        <div class="lg:col-span-2 space-y-4">
                            
                            <!-- SLA Metrics Container (Above Ticket Info) -->
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                        {{ __('pages.tickets.detail.sla_metrics') }}
                                    </h3>
                                    <button
                                        type="button"
                                        @click="showSlaInfoModal = true"
                                        class="h-6 w-6 rounded-full bg-slate-100 hover:bg-emerald-100 dark:bg-slate-800 dark:hover:bg-emerald-950/60 text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Informasi Penjelasan Metrik Waktu"
                                    >
                                        <Info class="h-3.5 w-3.5" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                                    <!-- Response Time Card -->
                                    <div class="p-3.5 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 flex items-center gap-3.5">
                                        <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white shrink-0">
                                            <Clock class="h-5 w-5" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-0.5">
                                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide leading-tight">
                                                {{ __('pages.tickets.detail.response_time_sla') }}
                                            </div>
                                            <div class="text-[10px] font-medium leading-none">
                                                <span v-if="ticket.responded_at" class="text-slate-500 dark:text-slate-400">
                                                    {{ __('pages.tickets.detail.responded_status_sla') }}
                                                </span>
                                                <span v-else-if="ticket.validated_at" class="text-emerald-600 dark:text-white animate-pulse font-bold">
                                                    {{ __('pages.tickets.detail.running_status_sla') }}
                                                </span>
                                                <span v-else class="text-slate-400 dark:text-slate-500">
                                                    {{ __('pages.tickets.detail.awaiting_validate_sla') }}
                                                </span>
                                            </div>
                                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 pt-0.5">
                                                {{ formatDuration(responseTimeSeconds) }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Paused Duration Card -->
                                    <div class="p-3.5 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 flex items-center gap-3.5">
                                        <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-950/50 border border-orange-100 dark:border-orange-900/50 text-orange-600 dark:text-orange-400 shrink-0">
                                            <Pause class="h-5 w-5" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-0.5">
                                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide leading-tight">
                                                {{ __('pages.tickets.detail.paused_duration') }}
                                            </div>
                                            <div class="text-[10px] font-medium leading-none">
                                                <span v-if="ticket.status === 'PENDING'" class="text-orange-600 dark:text-orange-400 animate-pulse font-bold">
                                                    {{ __('pages.tickets.detail.active_paused_sla') }}
                                                </span>
                                                <span v-else class="text-slate-400 dark:text-slate-500">
                                                    {{ __('pages.tickets.detail.total_pauses_sla') }}
                                                </span>
                                            </div>
                                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 pt-0.5">
                                                {{ formatDuration(pausedDurationSeconds) }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Resolution Time Card -->
                                    <div class="p-3.5 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 flex items-center gap-3.5">
                                        <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white shrink-0">
                                            <CheckCircle2 class="h-5 w-5" />
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-0.5">
                                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide leading-tight">
                                                {{ __('pages.tickets.detail.resolution_time_sla') }}
                                            </div>
                                            <div class="text-[10px] font-medium leading-none">
                                                <span v-if="ticket.resolved_at" class="text-slate-500 dark:text-slate-400">
                                                    {{ __('pages.tickets.detail.resolved_status_sla') }}
                                                </span>
                                                <span v-else-if="ticket.status === 'PENDING'" class="text-orange-600 dark:text-orange-400 font-bold">
                                                    {{ __('pages.tickets.detail.paused_status_sla') }}
                                                </span>
                                                <span v-else-if="ticket.responded_at" class="text-emerald-600 dark:text-white animate-pulse font-bold">
                                                    {{ __('pages.tickets.detail.running_status_sla') }}
                                                </span>
                                                <span v-else class="text-slate-400 dark:text-slate-500">
                                                    {{ __('pages.tickets.detail.awaiting_dispatch_sla') }}
                                                </span>
                                            </div>
                                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 pt-0.5">
                                                {{ formatDuration(resolutionTimeSeconds) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Info Container -->
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    {{ __('pages.tickets.detail.ticket_info') }}
                                </h3>

                                <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                {{ __('pages.tickets.detail.reporter') }}
                                            </div>
                                            <div class="text-sm font-bold text-slate-800 dark:text-white uppercase leading-tight">
                                                {{ ticket.reporter?.name || '-' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                Nomor HP Pelapor
                                            </div>
                                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                                {{ ticket.reporter?.phone_number || '-' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                {{ __('pages.tickets.detail.room_location') }}
                                            </div>
                                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                                {{ ticket.room?.name || '-' }} <span v-if="formatRoomDetails(ticket.room)" class="text-slate-400 dark:text-slate-500">({{ formatRoomDetails(ticket.room) }})</span>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                Kategori Kerusakan
                                            </div>
                                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                                {{ ticket.category?.name || '-' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                Status Tiket
                                            </div>
                                            <div class="mt-0.5">
                                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide uppercase border', getStatus(ticket.status).badge]">
                                                    {{ getStatus(ticket.status).label }}
                                                </span>
                                            </div>
                                        </div>

                                        <div v-if="ticket.priority">
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                Prioritas Tiket
                                            </div>
                                            <div class="mt-0.5">
                                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide uppercase border', getPriority(ticket).badge]">
                                                    {{ getPriority(ticket).label }}
                                                </span>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                                {{ __('pages.tickets.detail.reported_at_label') }}
                                            </div>
                                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight">
                                                {{ formatDateTime(ticket.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                        {{ __('pages.tickets.detail.problem_desc') }}
                                    </span>
                                    <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-slate-800 dark:text-slate-200 text-sm font-medium leading-relaxed whitespace-pre-line">
                                        {{ ticket.problem_description }}
                                    </div>
                                </div>

                                <!-- Reporter Attachments Media Grid -->
                                <div class="space-y-3">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                        {{ __('pages.tickets.detail.attachments') }}
                                    </span>
                                    
                                    <div v-if="reporterAttachments.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-2">
                                        <div 
                                            v-for="att in reporterAttachments" 
                                            :key="att.id" 
                                            class="relative rounded-lg overflow-hidden border border-slate-100 dark:border-slate-800 aspect-square cursor-pointer bg-slate-50 dark:bg-slate-950/55 group shadow-sm"
                                        >
                                            <video 
                                                v-if="isVideo(att.file_path)" 
                                                :src="att.file_path" 
                                                controls 
                                                class="w-full h-full object-cover"
                                            ></video>
                                            <div v-else class="w-full h-full relative cursor-pointer" @click="openLightbox(att.file_path)">
                                                <img :src="att.file_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" alt="Reporter photo" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-slate-400 dark:text-slate-500 italic p-3 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/20 dark:bg-slate-950/10">
                                        {{ __('pages.tickets.detail.no_attachments') }}
                                    </div>
                                </div>

                                <!-- Validation Info & Assigned Tech list -->
                                <div v-if="ticket.status !== 'PENDING_VALIDATION'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                                    <div class="space-y-1">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                            {{ __('pages.tickets.detail.validator_label') }}
                                        </span>
                                        <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                            <UserCheck class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                            <span class="text-xs font-semibold">{{ ticket.validator?.name || 'Sistem (Disposisi Otomatis)' }}</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                            {{ __('pages.tickets.detail.assigned_techs') }}
                                        </span>
                                        <div class="flex flex-wrap gap-1.5 mt-0.5">
                                            <span 
                                                v-for="assign in ticket.assignments" 
                                                :key="assign.id" 
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-white/10 dark:text-white dark:border-white/20"
                                            >
                                                <Wrench class="h-3 w-3" />
                                                {{ assign.technician?.name }}
                                            </span>
                                            <span v-if="!ticket.assignments || ticket.assignments.length === 0" class="text-xs text-slate-400 italic">
                                                {{ __('pages.tickets.detail.no_assigned_techs') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Action Panel Card: Technician Execution Controls (Desktop View) -->
                            <div v-if="canPerformAction" class="hidden md:block bg-white dark:bg-slate-900 border border-emerald-100 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 animate-spa-fade-in">
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                    {{ __('pages.tickets.detail.work_followup') }}
                                </h3>

                                <!-- Case 1: Assigned but not arrived yet -->
                                <div v-if="ticket.status === 'ASSIGNED'" class="space-y-3">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                        {{ __('pages.tickets.detail.arrive_instruction') }}
                                    </p>
                                    <button
                                        @click="openArriveModal"
                                        class="w-full h-11 text-xs font-bold rounded-xl text-white dark:text-slate-900 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                                    >
                                        <Clock class="h-4.5 w-4.5" />
                                        <span>{{ __('pages.tickets.detail.btn_arrive') }}</span>
                                    </button>
                                </div>

                                <!-- Case 2: In progress - Work updates -->
                                <div v-else-if="ticket.status === 'IN_PROGRESS'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <button
                                        @click="openCompleteModal"
                                        class="h-11 text-xs font-bold rounded-xl text-white dark:text-slate-900 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                                    >
                                        <CheckCircle2 class="h-4.5 w-4.5" />
                                        <span>{{ __('pages.tickets.detail.btn_complete') }}</span>
                                    </button>

                                    <button
                                        @click="openPendingModal"
                                        class="h-11 text-xs font-bold rounded-xl text-white flex items-center justify-center gap-1.5 bg-orange-500 hover:bg-orange-450 transition duration-200"
                                    >
                                        <Pause class="h-4.5 w-4.5" />
                                        <span>{{ __('pages.tickets.detail.btn_pending') }}</span>
                                    </button>

                                    <button
                                        @click="openCancelModal"
                                        class="h-11 text-xs font-bold rounded-xl text-white flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-500 transition duration-200"
                                    >
                                        <XCircle class="h-4.5 w-4.5" />
                                        <span>{{ __('pages.tickets.detail.btn_cancel') }}</span>
                                    </button>
                                </div>

                                <!-- Case 3: Paused / Pending -->
                                <div v-else-if="ticket.status === 'PENDING'" class="space-y-3">
                                    <div class="p-3 border border-orange-200/50 bg-orange-50/20 dark:border-orange-900/30 dark:bg-orange-950/10 rounded-xl text-xs text-orange-700 dark:text-orange-400 flex gap-2">
                                        <Info class="h-4.5 w-4.5 flex-shrink-0" />
                                        <div>
                                            <span class="font-bold">{{ __('pages.tickets.detail.paused_reason_label_inline') }}</span>
                                            <p class="mt-1 font-medium leading-relaxed">{{ ticket.pending_reason }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <button
                                            @click="resumeTicket"
                                            class="h-11 text-xs font-bold rounded-xl text-white dark:text-slate-900 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                                        >
                                            <Play class="h-4.5 w-4.5" />
                                            <span>{{ __('pages.tickets.detail.btn_resume') }}</span>
                                        </button>

                                        <button
                                            @click="openCancelModal"
                                            class="h-11 text-xs font-bold rounded-xl text-white flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-500 transition duration-200"
                                        >
                                            <XCircle class="h-4.5 w-4.5" />
                                            <span>{{ __('pages.tickets.detail.btn_cancel') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column: Progress Timelines -->
                        <div class="space-y-4">
                            
                            <!-- Ticket Status Timeline Tracking -->
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    {{ __('pages.tickets.detail.timeline') }}
                                </h3>

                                <div class="flow-root pt-1">
                                    <ul>
                                        
                                        <!-- Node 1: Created -->
                                        <li>
                                            <div class="relative pb-6">
                                                <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', ticket.validated_at ? 'bg-emerald-500 dark:bg-white/20' : 'bg-slate-200 dark:bg-slate-800']" aria-hidden="true"></span>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-emerald-500 dark:bg-white flex items-center justify-center ring-8 ring-white dark:ring-slate-900">
                                                            <FileText class="h-4 w-4 text-white dark:text-slate-900" />
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                            {{ __('pages.tickets.detail.created_status') }}
                                                        </p>
                                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            {{ __('pages.tickets.detail.by_label') }}: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ ticket.reporter?.name }}</span>
                                                        </p>
                                                        <div class="text-[10px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1">
                                                            <Clock class="h-3 w-3 shrink-0" />
                                                            <span>{{ formatDateTime(ticket.created_at) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>

                                        <!-- Node 2: Dispatched -->
                                        <li>
                                            <div class="relative pb-6">
                                                <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', ticket.responded_at ? 'bg-emerald-500 dark:bg-white/20' : 'bg-slate-200 dark:bg-slate-800']" aria-hidden="true"></span>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span :class="[
                                                            'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                            ticket.validated_at ? 'bg-emerald-500 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'
                                                        ]">
                                                            <UserCheck class="h-4 w-4" :class="ticket.validated_at ? 'text-white dark:text-slate-900' : 'text-slate-400 dark:text-slate-500'" />
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                        <p class="text-xs font-bold" :class="ticket.validated_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'">
                                                            {{ __('pages.tickets.detail.assigned_status') }}
                                                        </p>
                                                        <p v-if="ticket.validated_at" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            {{ __('pages.tickets.detail.validator_label_timeline') }}: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ ticket.validator?.name || 'Sistem (Disposisi Otomatis)' }}</span>
                                                            <br />
                                                            {{ __('pages.tickets.detail.technician_label_timeline') }}: <span class="font-semibold text-slate-700 dark:text-slate-300">
                                                                {{ ticket.assignments?.map(a => a.technician?.name).join(', ') }}
                                                            </span>
                                                        </p>
                                                        <p v-else class="text-[10px] text-slate-400 dark:text-slate-600">
                                                            {{ __('pages.tickets.detail.pending_validation_status') }}
                                                        </p>
                                                        <div v-if="ticket.validated_at" class="text-[10px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1">
                                                            <Clock class="h-3 w-3 shrink-0" />
                                                            <span>{{ formatDateTime(ticket.validated_at) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>

                                        <!-- Node 3: Arrived -->
                                        <li>
                                            <div class="relative pb-6">
                                                <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', ticket.responded_at ? 'bg-emerald-500 dark:bg-white/20' : 'bg-slate-200 dark:bg-slate-800']" aria-hidden="true"></span>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span :class="[
                                                            'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                            ticket.responded_at ? 'bg-emerald-500 dark:bg-white' : 'bg-slate-200 dark:bg-slate-800'
                                                        ]">
                                                            <Activity class="h-4 w-4" :class="ticket.responded_at ? 'text-white dark:text-slate-900' : 'text-slate-400 dark:text-slate-500'" />
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                        <p class="text-xs font-bold" :class="ticket.responded_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'">
                                                            {{ __('pages.tickets.detail.in_progress_status') }}
                                                        </p>
                                                        <p v-if="ticket.responded_at" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            {{ __('pages.tickets.detail.arrived_detail_timeline') }}
                                                        </p>
                                                        <p v-else class="text-[10px] text-slate-400 dark:text-slate-600">
                                                            {{ __('pages.tickets.detail.waiting_arrival_timeline') }}
                                                        </p>
                                                        <!-- Arrival Attachments Grid in Timeline -->
                                                        <div v-if="ticket.responded_at && arrivalAttachments.length > 0" class="grid grid-cols-3 gap-1.5 mt-2">
                                                            <div 
                                                                v-for="att in arrivalAttachments" 
                                                                :key="att.id" 
                                                                class="relative rounded-lg overflow-hidden border border-slate-100 dark:border-slate-800 aspect-square cursor-pointer"
                                                                @click="openLightbox(att.file_path)"
                                                            >
                                                                <img :src="att.file_path" class="w-full h-full object-cover" alt="Arrival photo proof" />
                                                            </div>
                                                        </div>
                                                        <div v-if="ticket.responded_at" class="text-[10px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 pt-0.5">
                                                            <Clock class="h-3 w-3 shrink-0" />
                                                            <span>{{ formatDateTime(ticket.responded_at) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>

                                        <!-- Dynamic Pending & Pause History Logs -->
                                        <template v-if="pendingHistories.length > 0">
                                            <li v-for="hist in pendingHistories" :key="hist.id">
                                                <div class="relative pb-6">
                                                    <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', hist.action === 'PAUSED' || hist.status === 'PENDING' ? 'bg-orange-300 dark:bg-orange-900/60' : 'bg-emerald-300 dark:bg-emerald-900/60']" aria-hidden="true"></span>
                                                    <div class="relative flex space-x-3">
                                                        <div>
                                                            <span v-if="hist.action === 'PAUSED' || hist.status === 'PENDING'" class="h-8 w-8 rounded-full bg-orange-500 flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white">
                                                                <Pause class="h-4 w-4" />
                                                            </span>
                                                            <span v-else-if="hist.action === 'RESUMED'" class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white">
                                                                <Play class="h-4 w-4" />
                                                            </span>
                                                        </div>
                                                        <div class="flex-1 min-w-0 pt-0.5 space-y-1.5">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <p class="text-xs font-bold">
                                                                    <span v-if="hist.action === 'PAUSED' || hist.status === 'PENDING'" class="text-orange-600 dark:text-orange-400">
                                                                        Pekerjaan Ditangguhkan (Pending)
                                                                    </span>
                                                                    <span v-else-if="hist.action === 'RESUMED'" class="text-emerald-600 dark:text-emerald-400">
                                                                        Pekerjaan Dilanjutkan Kembali
                                                                    </span>
                                                                </p>
                                                                <span v-if="hist.duration_seconds > 0" class="text-[10px] font-semibold bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 px-2 py-0.5 rounded-full border border-orange-200 dark:border-orange-900/50 shrink-0">
                                                                    Tertunda: {{ formatDuration(hist.duration_seconds) }}
                                                                </span>
                                                            </div>

                                                            <div v-if="hist.notes && (hist.action === 'PAUSED' || hist.status === 'PENDING')" class="p-2.5 rounded-xl bg-orange-50/70 dark:bg-orange-950/30 border border-orange-100 dark:border-orange-900/40 text-[11px] text-orange-950 dark:text-orange-200">
                                                                <div class="font-bold text-[10px] uppercase tracking-wider text-orange-700 dark:text-orange-400 mb-0.5">Alasan Penundaan:</div>
                                                                <p class="leading-normal italic">"{{ hist.notes }}"</p>
                                                            </div>

                                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                                Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ hist.user?.name || 'Petugas' }}</span>
                                                            </p>
                                                            <div class="text-[10px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1">
                                                                <Clock class="h-3 w-3 shrink-0" />
                                                                <span>{{ formatDateTime(hist.created_at) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </template>

                                        <!-- Node 4: Resolved -->
                                        <li>
                                            <div class="relative pb-0">
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span :class="[
                                                            'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                            ticket.status === 'COMPLETED' ? 'bg-emerald-500 dark:bg-white' : (ticket.status === 'CANCEL' ? 'bg-rose-500' : 'bg-slate-200 dark:bg-slate-800')
                                                        ]">
                                                            <CheckCircle2 v-if="ticket.status === 'COMPLETED'" class="h-4 w-4 text-white dark:text-slate-900" />
                                                            <XCircle v-else-if="ticket.status === 'CANCEL'" class="h-4 w-4 text-white" />
                                                            <CheckCircle2 v-else class="h-4 w-4 text-slate-400 dark:text-slate-500" />
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                        <p class="text-xs font-bold" :class="ticket.status === 'COMPLETED' || ticket.status === 'CANCEL' ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'">
                                                            {{ ticket.status === 'CANCEL' ? __('pages.tickets.detail.cancel_status') : __('pages.tickets.detail.completed_status') }}
                                                        </p>
                                                        <p v-if="ticket.status === 'COMPLETED' && completedBy" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            {{ __('pages.tickets.detail.by_label') }}: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ completedBy }}</span>
                                                        </p>
                                                        <p v-if="ticket.status === 'COMPLETED'" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            <span class="font-bold text-emerald-600 dark:text-white">{{ __('pages.tickets.detail.action_taken_label') }}</span> {{ ticket.completion_notes }}
                                                        </p>
                                                        <p v-else-if="ticket.status === 'CANCEL' && cancelledBy" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            Dibatalkan Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ cancelledBy }}</span>
                                                        </p>
                                                        <p v-if="ticket.status === 'CANCEL'" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                            <span class="font-bold text-rose-600 dark:text-rose-400">{{ __('pages.tickets.detail.cancel_reason_label_inline') }}</span> {{ ticket.completion_notes }}
                                                        </p>
                                                        <p v-else-if="ticket.status !== 'COMPLETED' && ticket.status !== 'CANCEL'" class="text-[10px] text-slate-400 dark:text-slate-600">
                                                            {{ __('pages.tickets.detail.waiting_resolution_timeline') }}
                                                        </p>

                                                        <!-- Completion Attachments Grid in Timeline -->
                                                        <div v-if="ticket.status === 'COMPLETED' && completionAttachments.length > 0" class="grid grid-cols-3 gap-1.5 mt-2">
                                                            <div 
                                                                v-for="att in completionAttachments" 
                                                                :key="att.id" 
                                                                class="relative rounded-lg overflow-hidden border border-slate-100 dark:border-slate-800 aspect-square cursor-pointer"
                                                                @click="openLightbox(att.file_path)"
                                                            >
                                                                <img :src="att.file_path" class="w-full h-full object-cover" alt="Completion photo proof" />
                                                            </div>
                                                        </div>
                                                        <div v-if="ticket.resolved_at" class="text-[10px] font-medium text-slate-400 dark:text-slate-500 flex items-center gap-1 pt-0.5">
                                                            <Clock class="h-3 w-3 shrink-0" />
                                                            <span>{{ formatDateTime(ticket.resolved_at) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>

                                    </ul>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
    </div>

    <!-- Floating Bottom Action Bar for Mobile Devices -->
    <div 
        v-if="canPerformAction" 
        :class="[
            'fixed bottom-5 sm:bottom-6 left-4 right-4 sm:left-6 sm:right-6 z-40 md:hidden max-w-md mx-auto p-3 sm:p-3.5 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-[0_12px_40px_rgba(0,0,0,0.2)] transition-all duration-300 transform',
            isFloatingBarVisible ? 'translate-y-0 opacity-100' : 'translate-y-32 opacity-0 pointer-events-none'
        ]"
    >
        <div class="space-y-2">
            <!-- Case 1: ASSIGNED -->
            <div v-if="ticket.status === 'ASSIGNED'">
                <button
                    @click="openArriveModal"
                    class="w-full h-12 text-xs font-extrabold rounded-2xl text-white dark:text-slate-900 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                >
                    <Clock class="h-4.5 w-4.5" />
                    <span>{{ __('pages.tickets.detail.btn_arrive') }}</span>
                </button>
            </div>

            <!-- Case 2: IN_PROGRESS -->
            <div v-else-if="ticket.status === 'IN_PROGRESS'" class="space-y-2">
                <button
                    @click="openCompleteModal"
                    class="w-full h-12 text-xs font-extrabold rounded-2xl text-white dark:text-slate-900 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                >
                    <CheckCircle2 class="h-4.5 w-4.5" />
                    <span>{{ __('pages.tickets.detail.btn_complete') }}</span>
                </button>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        @click="openPendingModal"
                        class="w-full h-12 text-xs font-extrabold rounded-2xl text-white flex items-center justify-center gap-1.5 bg-orange-500 hover:bg-orange-600 active:scale-[0.98] transition duration-200"
                    >
                        <Pause class="h-4 w-4" />
                        <span>Tangguhkan</span>
                    </button>
                    <button
                        @click="openCancelModal"
                        class="w-full h-12 text-xs font-extrabold rounded-2xl text-white flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 active:scale-[0.98] transition duration-200"
                    >
                        <XCircle class="h-4 w-4" />
                        <span>Batalkan</span>
                    </button>
                </div>
            </div>

            <!-- Case 3: PENDING -->
            <div v-else-if="ticket.status === 'PENDING'" class="space-y-2">
                <button
                    @click="resumeTicket"
                    class="w-full h-12 text-xs font-extrabold rounded-2xl text-white dark:text-slate-900 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] dark:bg-white dark:hover:bg-slate-200 transition duration-200"
                >
                    <Play class="h-4.5 w-4.5" />
                    <span>Lanjutkan Pekerjaan</span>
                </button>
                <button
                    @click="openCancelModal"
                    class="w-full h-12 text-xs font-extrabold rounded-2xl text-rose-600 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 hover:bg-rose-100 dark:hover:bg-rose-900/40 active:scale-[0.98] flex items-center justify-center gap-1.5 transition duration-200"
                >
                    <XCircle class="h-4 w-4" />
                    <span>Batalkan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modals for Technician Execution -->

    <!-- Modal 0: Arrive / Konfirmasi Kedatangan -->
    <Modal :show="showArriveModal" @close="showArriveModal = false" max-width="lg">
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <Clock class="h-5 w-5" />
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">
                        Konfirmasi Kedatangan di Lokasi
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Wajib unggah foto bukti bahwa Anda telah tiba di lokasi pekerjaan.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitArrive" class="space-y-4">
                <!-- Proof Photo Attachments (Single Photo) -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Foto Bukti Kedatangan <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">Maks. 1 Foto</span>
                    </div>

                    <input ref="arriveFileInput" type="file" accept="image/*" class="hidden" @change="handleArriveFileSelect" />
                    <input ref="arriveCameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="handleArriveFileSelect" />

                    <!-- Single Preview Card -->
                    <div v-if="arrivePreviews.length > 0" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 group shadow-sm transition-all duration-200">
                        <div class="relative h-44 w-full flex items-center justify-center bg-black/40">
                            <img :src="arrivePreviews[0].preview" alt="Arrive Preview" class="w-full h-full object-cover" />

                            <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-emerald-600/90 backdrop-blur-md text-white px-3 py-1 rounded-full text-[10px] font-extrabold shadow-sm">
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                <span>Foto Kedatangan Terpilih</span>
                            </div>

                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                                <button
                                    type="button"
                                    @click="arriveFileInput?.click()"
                                    class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-900 rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                >
                                    <UploadCloud class="h-3.5 w-3.5 text-emerald-600" />
                                    <span>Ganti Foto</span>
                                </button>
                                <button
                                    type="button"
                                    @click="removeArriveAttachment(0)"
                                    class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                >
                                    <X class="h-3.5 w-3.5" />
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        <div class="p-3 bg-white dark:bg-slate-900 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div class="flex items-center gap-2 min-w-0 pr-2">
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ arrivePreviews[0].name }}</span>
                                <span class="text-[10px] font-semibold text-slate-400 shrink-0">({{ (arrivePreviews[0].size / 1024).toFixed(0) }} KB)</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    @click="arriveFileInput?.click()"
                                    class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Ganti
                                </button>
                                <span class="text-slate-300 dark:text-slate-700">•</span>
                                <button
                                    type="button"
                                    @click="removeArriveAttachment(0)"
                                    class="text-[11px] font-bold text-rose-500 hover:underline"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Dropzone Box when empty -->
                    <div v-else class="space-y-2">
                        <div 
                            @click="arriveFileInput?.click()"
                            class="border-2 border-dashed rounded-2xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 dark:hover:border-white/30 bg-slate-50/50 dark:bg-slate-950/20"
                        >
                            <div class="h-9 w-9 rounded-full flex items-center justify-center mb-1.5 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                                <UploadCloud class="h-4.5 w-4.5" />
                            </div>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Unggah 1 Foto Bukti Kedatangan</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">JPG, PNG (Maks 5MB)</p>
                        </div>

                        <button
                            type="button"
                            @click="arriveCameraInput?.click()"
                            class="w-full h-10 rounded-xl border border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 text-xs font-bold flex items-center justify-center gap-2 transition duration-150"
                        >
                            <Camera class="h-4 w-4" />
                            Ambil Foto dari Kamera
                        </button>
                    </div>

                    <div v-if="arriveForm.errors.attachments" class="text-[10px] text-red-500 font-semibold">{{ arriveForm.errors.attachments }}</div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <SecondaryButton type="button" @click="showArriveModal = false">{{ __('Batal') }}</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="arriveForm.processing || arrivePreviews.length === 0" class="!bg-emerald-600 hover:!bg-emerald-500">
                        {{ arriveForm.processing ? __('Menyimpan...') : __('Konfirmasi Kedatangan') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Modal 1: Complete Work -->
    <Modal :show="showCompleteModal" @close="showCompleteModal = false" max-width="lg">
        <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
            <!-- Colored Sticky Header -->
            <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                <div class="flex items-center gap-3 pr-2">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <CheckCircle2 class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white leading-tight">
                            {{ __('pages.tickets.detail.complete_modal_title') }}
                        </h3>
                        <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                            {{ __('pages.tickets.detail.complete_modal_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitResolve('COMPLETED')" class="flex flex-col flex-1 justify-between min-h-0">
                <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                    <div class="p-3.5 border border-emerald-200/60 bg-emerald-50/40 dark:border-emerald-800/50 dark:bg-emerald-950/30 rounded-xl text-xs text-emerald-800 dark:text-emerald-300 flex gap-2.5">
                        <Info class="h-4.5 w-4.5 flex-shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                        <p class="leading-relaxed">
                            Pastikan Anda telah mengisi catatan penanganan dan melampirkan foto bukti hasil penanganan dengan jelas.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            {{ __('pages.tickets.detail.completion_notes_label') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="resolveForm.notes"
                            rows="6"
                            :placeholder="__('pages.tickets.detail.completion_notes_placeholder')"
                            class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none min-h-[140px]"
                        ></textarea>
                        <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                    </div>

                    <!-- Proof Photo Attachments (Single Photo) -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                {{ __('pages.tickets.detail.upload_proof_label') }} <span class="text-red-400">*</span>
                            </label>
                            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">Maks. 1 Foto</span>
                        </div>

                        <input ref="completeFileInput" type="file" accept="image/*" class="hidden" @change="handleCompleteFileSelect" />
                        <input ref="completeCameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="handleCompleteFileSelect" />

                        <!-- Single Preview Card -->
                        <div v-if="completePreviews.length > 0" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 group shadow-sm transition-all duration-200">
                            <div class="relative h-48 w-full flex items-center justify-center bg-black/40">
                                <img :src="completePreviews[0].preview" alt="Complete Preview" class="w-full h-full object-cover" />

                                <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-emerald-600/90 backdrop-blur-md text-white px-3 py-1 rounded-full text-[10px] font-extrabold shadow-sm">
                                    <CheckCircle2 class="h-3.5 w-3.5" />
                                    <span>Foto Selesai Penanganan Terpilih</span>
                                </div>

                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        @click="completeFileInput?.click()"
                                        class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-900 rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                    >
                                        <UploadCloud class="h-3.5 w-3.5 text-emerald-600" />
                                        <span>Ganti Foto</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeCompleteAttachment(0)"
                                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-3 bg-white dark:bg-slate-900 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 text-xs">
                                <div class="flex items-center gap-2 min-w-0 pr-2">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ completePreviews[0].name }}</span>
                                    <span class="text-[10px] font-semibold text-slate-400 shrink-0">({{ (completePreviews[0].size / 1024).toFixed(0) }} KB)</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button
                                        type="button"
                                        @click="completeFileInput?.click()"
                                        class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                                    >
                                        Ganti
                                    </button>
                                    <span class="text-slate-300 dark:text-slate-700">•</span>
                                    <button
                                        type="button"
                                        @click="removeCompleteAttachment(0)"
                                        class="text-[11px] font-bold text-rose-500 hover:underline"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone Box when empty -->
                        <div v-else class="space-y-2">
                            <div 
                                @click="completeFileInput?.click()"
                                class="border-2 border-dashed rounded-2xl p-5 text-center cursor-pointer transition flex flex-col items-center justify-center border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 dark:hover:border-white/30 bg-slate-50 dark:bg-slate-950/40"
                            >
                                <div class="h-10 w-10 rounded-full flex items-center justify-center mb-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                    <UploadCloud class="h-5 w-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Unggah 1 Foto Bukti Hasil Penanganan</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">JPG, PNG (Maks 5MB)</p>
                            </div>

                            <button
                                type="button"
                                @click="completeCameraInput?.click()"
                                class="w-full h-11 rounded-xl border border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 text-xs font-bold flex items-center justify-center gap-2 transition duration-150"
                            >
                                <Camera class="h-4.5 w-4.5" />
                                Ambil Foto dari Kamera
                            </button>
                        </div>

                        <div v-if="resolveForm.errors.attachments" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.attachments }}</div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                    <SecondaryButton type="button" @click="showCompleteModal = false" class="h-11 px-5">{{ __('Batal') }}</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="resolveForm.processing || !resolveForm.notes || !resolveForm.attachments || resolveForm.attachments.length === 0" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold">
                        {{ resolveForm.processing ? __('Menyimpan...') : __('Selesai Penanganan') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Modal 2: Pause / Pending -->
    <Modal :show="showPendingModal" @close="showPendingModal = false" max-width="lg">
        <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
            <!-- Colored Sticky Header -->
            <div class="bg-amber-500 dark:bg-amber-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-amber-400/30 dark:border-amber-800/50 shadow-sm">
                <div class="flex items-center gap-3 pr-2">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <Pause class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white leading-tight">
                            {{ __('pages.tickets.detail.pending_modal_title') }}
                        </h3>
                        <p class="text-xs text-amber-100/90 dark:text-amber-200/90 mt-0.5 font-medium">
                            {{ __('pages.tickets.detail.pending_modal_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitResolve('PENDING')" class="flex flex-col flex-1 justify-between min-h-0">
                <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                    <div class="p-3.5 border border-amber-200/60 bg-amber-50/40 dark:border-amber-800/50 dark:bg-amber-950/30 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex gap-2.5">
                        <Info class="h-4.5 w-4.5 flex-shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                        <p class="leading-relaxed">
                            Menangguhkan tugas akan menghentikan sementara perhitungan durasi Metrik Waktu Penanganan hingga Anda melanjutkan pekerjaan kembali.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            {{ __('pages.tickets.detail.pending_reason_label') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="resolveForm.notes"
                            rows="6"
                            :placeholder="__('pages.tickets.detail.pending_reason_placeholder')"
                            class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none min-h-[140px]"
                        ></textarea>
                        <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                    <SecondaryButton type="button" @click="showPendingModal = false" class="h-11 px-5">{{ __('Batal') }}</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="resolveForm.processing || !resolveForm.notes" class="h-11 px-6 !bg-amber-500 hover:!bg-amber-450 font-bold">
                        {{ resolveForm.processing ? __('Menangguhkan...') : __('Tangguhkan Tugas') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Modal 3: Cancel Ticket -->
    <Modal :show="showCancelModal" @close="showCancelModal = false" max-width="lg">
        <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
            <!-- Colored Sticky Header -->
            <div class="bg-rose-600 dark:bg-rose-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-rose-500/30 dark:border-rose-800/50 shadow-sm">
                <div class="flex items-center gap-3 pr-2">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <XCircle class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white leading-tight">
                            {{ __('pages.tickets.detail.cancel_modal_title') }}
                        </h3>
                        <p class="text-xs text-rose-100/90 dark:text-rose-200/90 mt-0.5 font-medium">
                            {{ __('pages.tickets.detail.cancel_modal_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitResolve('CANCEL')" class="flex flex-col flex-1 justify-between min-h-0">
                <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                    <div class="p-3.5 border border-rose-200/60 bg-rose-50/40 dark:border-rose-800/50 dark:bg-rose-950/30 rounded-xl text-xs text-rose-800 dark:text-rose-300 flex gap-2.5">
                        <Info class="h-4.5 w-4.5 flex-shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" />
                        <p class="leading-relaxed">
                            Perhatian: Pembatalan tiket ini akan menghentikan pengerjaan secara permanen. Pastikan alasan pembatalan telah diisi secara jelas.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            {{ __('pages.tickets.detail.cancel_reason_label') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="resolveForm.notes"
                            rows="6"
                            :placeholder="__('pages.tickets.detail.cancel_reason_placeholder')"
                            class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none min-h-[140px]"
                        ></textarea>
                        <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                    <SecondaryButton type="button" @click="showCancelModal = false" class="h-11 px-5">{{ __('Batal') }}</SecondaryButton>
                    <DangerButton type="submit" :disabled="resolveForm.processing || !resolveForm.notes" class="h-11 px-6 font-bold">
                        {{ resolveForm.processing ? __('Batalkan...') : __('Batalkan Tiket') }}
                    </DangerButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Lightbox Fullscreen Preview Portal Overlay -->
    <div 
        v-if="activeLightbox" 
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
        @click="closeLightbox"
    >
        <div class="relative max-w-4xl max-h-[85vh] overflow-hidden" @click.stop>
            <img 
                :src="activeLightbox" 
                class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl border border-white/10" 
                alt="Attachment Preview" 
            />
            <button 
                @click="closeLightbox" 
                class="absolute top-2 right-2 h-9 w-9 rounded-full bg-black/60 hover:bg-black/80 flex items-center justify-center text-white transition duration-150"
            >
                <X class="h-5 w-5" />
            </button>
        </div>
    </div>
    <!-- SLA Info Modal -->
    <Teleport to="body">
        <div v-if="showSlaInfoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm animate-spa-fade-in">
            <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <div class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-white/10 dark:text-white flex items-center justify-center">
                            <Info class="h-4 w-4" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Penjelasan Metrik Waktu Penanganan
                        </h3>
                    </div>
                    <button type="button" @click="showSlaInfoModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-5 space-y-3.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 space-y-1">
                        <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                            <Clock class="h-3.5 w-3.5 text-emerald-500" />
                            <span>Waktu Respon</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                            Dihitung sejak laporan <strong>didisposisikan/divalidasi</strong> hingga teknisi pertama kali menekan <strong>"Saya sudah di lokasi"</strong>.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 space-y-1">
                        <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                            <Pause class="h-3.5 w-3.5 text-orange-500" />
                            <span>Total Waktu Tertunda</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                            Akumulasi durasi saat pengerjaan tiket di-pause/pending (misal: menunggu ketersediaan suku cadang atau bahan).
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 space-y-1">
                        <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                            <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                            <span>Durasi Pengerjaan</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                            Durasi pengerjaan fisik murni (sejak teknisi tiba di lokasi hingga pekerjaan selesai), <strong>tidak termasuk waktu tertunda</strong>.
                        </p>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <button type="button" @click="showSlaInfoModal = false" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
