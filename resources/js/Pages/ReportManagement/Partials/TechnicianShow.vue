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

const props = defineProps({
    ticket: {
        type: Object,
        required: true
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
const parsedDates = computed(() => {
    return {
        created: props.ticket.created_at ? new Date(props.ticket.created_at) : null,
        validated: props.ticket.validated_at ? new Date(props.ticket.validated_at) : null,
        responded: props.ticket.responded_at ? new Date(props.ticket.responded_at) : null,
        resolved: props.ticket.resolved_at ? new Date(props.ticket.resolved_at) : null,
        lastPaused: props.ticket.last_paused_at ? new Date(props.ticket.last_paused_at) : null,
    };
});

// SLA Timers Calculations (in Seconds)
const responseTimeSeconds = computed(() => {
    const dates = parsedDates.value;
    if (!dates.validated) return null;
    if (dates.responded) {
        return Math.max(0, Math.floor((dates.responded - dates.validated) / 1000));
    }
    return Math.max(0, Math.floor((now.value - dates.validated) / 1000));
});

const pausedDurationSeconds = computed(() => {
    const dates = parsedDates.value;
    let accumulated = props.ticket.paused_duration_seconds || 0;
    if (props.ticket.status === 'PENDING' && dates.lastPaused) {
        const elapsedSincePause = Math.floor((now.value - dates.lastPaused) / 1000);
        accumulated += Math.max(0, elapsedSincePause);
    }
    return accumulated;
});

const resolutionTimeSeconds = computed(() => {
    const dates = parsedDates.value;
    if (!dates.validated) return null;
    const pauseSecs = pausedDurationSeconds.value;

    if (dates.resolved) {
        const total = Math.floor((dates.resolved - dates.validated) / 1000);
        return Math.max(0, total - props.ticket.paused_duration_seconds);
    }
    if (props.ticket.status === 'PENDING' && dates.lastPaused) {
        const total = Math.floor((dates.lastPaused - dates.validated) / 1000);
        return Math.max(0, total - props.ticket.paused_duration_seconds);
    }
    const total = Math.floor((now.value - dates.validated) / 1000);
    return Math.max(0, total - pauseSecs);
});

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined || seconds < 0) return '-';
    if (seconds === 0) return '0s';
    
    const d = Math.floor(seconds / 86400);
    const h = Math.floor((seconds % 86400) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    const parts = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (s > 0 || parts.length === 0) parts.push(`${s}s`);

    return parts.join(' ');
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const respondTicket = () => {
    router.post(route('tickets.respond', props.ticket.uuid));
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
    const remaining = 5 - completePreviews.value.length;
    const toProcess = files.slice(0, remaining);

    for (const file of toProcess) {
        if (!file.type.startsWith('image/')) continue;
        
        let dataUrl;
        try {
            dataUrl = await compressImage(file);
        } catch {
            dataUrl = await readFileAsDataURL(file);
        }

        completePreviews.value.push({
            name: file.name,
            size: file.size,
            preview: dataUrl,
            data: dataUrl
        });
    }
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

const submitResolve = (status) => {
    resolveForm.resolution_status = status;
    resolveForm.post(route('tickets.resolve', props.ticket.uuid), {
        onSuccess: () => {
            showCompleteModal.value = false;
            showPendingModal.value = false;
            showCancelModal.value = false;
            resolveForm.reset();
        }
    });
};

const resumeTicket = () => {
    router.post(route('tickets.resume', props.ticket.uuid));
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
    return props.ticket.attachments?.filter(att => att.uploaded_by == props.ticket.reporter_id) || [];
});

const completionAttachments = computed(() => {
    return props.ticket.attachments?.filter(att => att.uploaded_by != props.ticket.reporter_id) || [];
});

const isVideo = (path) => {
    if (!path) return false;
    const lower = path.toLowerCase();
    return lower.endsWith('.mp4') || lower.endsWith('.mov') || lower.endsWith('.webm') || lower.endsWith('.ogg') || lower.endsWith('.3gp') || lower.endsWith('.avi');
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu Validasi', badge: 'bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30' },
    ASSIGNED:           { label: 'Ditugaskan',       badge: 'bg-blue-50 text-blue-700 border-blue-200/50 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/30' },
    IN_PROGRESS:        { label: 'Dikerjakan',       badge: 'bg-violet-50 text-violet-700 border-violet-200/50 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/30' },
    PENDING:            { label: 'Ditangguhkan',     badge: 'bg-orange-50 text-orange-700 border-orange-200/50 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/30' },
    COMPLETED:          { label: 'Selesai',          badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30' },
    CANCEL:             { label: 'Dibatalkan',       badge: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/30' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border-slate-200' };

const priorityConfig = {
    URGENT:  { label: 'URGENT (Mendesak)', badge: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50' },
    ROUTINE: { label: 'ROUTINE (Rutin)',   badge: 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50' },
};

const getPriority = (priority) => priorityConfig[priority] ?? { label: '-', badge: 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900/40 dark:border-slate-800' };

const contextLabel = computed(() => {
    if (props.personal) {
        return 'Laporan Saya';
    }
    const roleId = Number(user.value?.role_id);
    if (roleId === 5 || roleId === 1 || roleId === 2 || roleId === 3 || roleId === 4) {
        return 'Tugas Unit';
    } else if (roleId === 6) {
        return 'Tugas Saya';
    } else if (roleId === 7) {
        return 'Laporan Ruangan';
    }
    return null;
});
</script>

<template>
    <div class="py-4 px-4 sm:px-4 lg:px-4">
        <div class="w-full space-y-4">
            
            <!-- Ticket Profile Header Card -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                        <Wrench class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                                #{{ ticket.ticket_number }}
                            </h2>
                            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase border', getStatus(ticket.status).badge]">
                                {{ getStatus(ticket.status).label }}
                            </span>
                            <span v-if="ticket.priority" :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase border', getPriority(ticket.priority).badge]">
                                {{ getPriority(ticket.priority).label }}
                            </span>
                            <span v-if="contextLabel" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide uppercase border bg-slate-100/80 text-slate-700 dark:bg-slate-955/40 dark:text-slate-300 dark:border-slate-800">
                                {{ contextLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            {{ ticket.category?.unit_feature?.supporting_unit?.name }} &bull; {{ ticket.category?.unit_feature?.supporting_unit?.division?.name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Main Section Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left Column: Details & Execution Panels -->
                <div class="lg:col-span-2 space-y-4">
                    
                    <!-- Ticket Info Container -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                {{ __('pages.tickets.detail.ticket_info') }}
                            </h3>
                            <div class="h-0.5 bg-slate-50 dark:bg-slate-850 mt-2"></div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.reporter') }}
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                    <User class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <span class="text-xs font-semibold">{{ ticket.reporter?.name }}</span>
                                    <span v-if="ticket.reporter?.nip" class="text-[10px] text-slate-400 dark:text-slate-505">({{ ticket.reporter.nip }})</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.room_location') }}
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                    <MapPin class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <span class="text-xs font-semibold">{{ ticket.room?.name }}</span>
                                    <span v-if="ticket.room?.location_floor" class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">({{ ticket.room.location_floor }})</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.category_unit') }}
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                    <Activity class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <span class="text-xs font-semibold">{{ ticket.category?.name }}</span>
                                    <span class="text-[10px] text-indigo-500 dark:text-indigo-400 font-bold uppercase">[{{ ticket.category?.unit_feature?.name }}]</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.reported_at_label') }}
                                </span>
                                <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <Calendar class="h-4 w-4 text-slate-400" />
                                    <span class="text-xs font-semibold">{{ formatDateTime(ticket.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                {{ __('pages.tickets.detail.problem_desc') }}
                            </span>
                            <div class="bg-slate-50/50 dark:bg-slate-950/20 border border-slate-100 dark:border-slate-850 rounded-xl p-4 text-slate-700 dark:text-slate-300 text-xs leading-relaxed whitespace-pre-line">
                                {{ ticket.problem_description }}
                            </div>
                        </div>

                        <!-- Reporter Attachments Media Grid -->
                        <div class="space-y-3">
                            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-555 tracking-wider flex items-center gap-1.5">
                                <ImageIcon class="h-3.5 w-3.5" />
                                {{ __('pages.tickets.detail.attachments') }}
                            </span>
                            
                            <div v-if="reporterAttachments.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div 
                                    v-for="att in reporterAttachments" 
                                    :key="att.id" 
                                    class="relative rounded-xl overflow-hidden border border-slate-150 dark:border-slate-800 aspect-video bg-slate-50 dark:bg-slate-950/55 group shadow-sm"
                                >
                                    <video 
                                        v-if="isVideo(att.file_path)" 
                                        :src="att.file_path" 
                                        controls 
                                        class="w-full h-full object-cover"
                                    ></video>
                                    <div v-else class="w-full h-full relative cursor-pointer" @click="openLightbox(att.file_path)">
                                        <img :src="att.file_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" alt="Reporter photo" />
                                        <div class="absolute inset-0 bg-black/10 group-hover:bg-black/30 transition duration-150 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                            <Search class="h-6 w-6 text-white" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-400 dark:text-slate-500 italic p-3 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/20 dark:bg-slate-950/10">
                                {{ __('pages.tickets.detail.no_attachments') }}
                            </div>
                        </div>

                        <!-- Validation Info & Assigned Tech list -->
                        <div v-if="ticket.status !== 'PENDING_VALIDATION'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-850 pt-4">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.validator_label') }}
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                    <UserCheck class="h-4 w-4 text-slate-400 flex-shrink-0" />
                                    <span class="text-xs font-semibold">{{ ticket.validator?.name }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-550">({{ formatDateTime(ticket.validated_at) }})</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider">
                                    {{ __('pages.tickets.detail.assigned_techs') }}
                                </span>
                                <div class="flex flex-wrap gap-1.5 mt-0.5">
                                    <span 
                                        v-for="assign in ticket.assignments" 
                                        :key="assign.id" 
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-950/20 dark:text-indigo-400 dark:border-indigo-900/30"
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

                    <!-- Action Panel Card: Technician Execution Controls -->
                    <div v-if="ticket.status !== 'PENDING_VALIDATION' && ticket.status !== 'COMPLETED' && ticket.status !== 'CANCEL'" class="bg-white dark:bg-slate-900 border border-violet-100 dark:border-violet-900/50 rounded-2xl p-6 shadow-sm space-y-4 animate-spa-fade-in">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                {{ __('pages.tickets.detail.work_followup') }}
                            </h3>
                            <div class="h-0.5 bg-slate-50 dark:bg-slate-850 mt-2"></div>
                        </div>

                        <!-- Case 1: Assigned but not arrived yet -->
                        <div v-if="ticket.status === 'ASSIGNED'" class="space-y-3">
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                {{ __('pages.tickets.detail.arrive_instruction') }}
                            </p>
                            <button
                                @click="respondTicket"
                                class="w-full h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 transition duration-200"
                            >
                                <Clock class="h-4.5 w-4.5" />
                                <span>{{ __('pages.tickets.detail.btn_arrive') }}</span>
                            </button>
                        </div>

                        <!-- Case 2: In progress - Work updates -->
                        <div v-else-if="ticket.status === 'IN_PROGRESS'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button
                                @click="openCompleteModal"
                                class="h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 transition duration-200"
                            >
                                <CheckCircle2 class="h-4.5 w-4.5" />
                                <span>{{ __('pages.tickets.detail.btn_complete') }}</span>
                            </button>

                            <button
                                @click="openPendingModal"
                                class="h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-1.5 bg-orange-500 hover:bg-orange-450 transition duration-200"
                            >
                                <Pause class="h-4.5 w-4.5" />
                                <span>{{ __('pages.tickets.detail.btn_pending') }}</span>
                            </button>

                            <button
                                @click="openCancelModal"
                                class="h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-500 transition duration-200"
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
                                    class="h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 transition duration-200"
                                >
                                    <Play class="h-4.5 w-4.5" />
                                    <span>{{ __('pages.tickets.detail.btn_resume') }}</span>
                                </button>

                                <button
                                    @click="openCancelModal"
                                    class="h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-500 transition duration-200"
                                >
                                    <XCircle class="h-4.5 w-4.5" />
                                    <span>{{ __('pages.tickets.detail.btn_cancel') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: SLA Analytics & Progress Timelines -->
                <div class="space-y-4">
                    
                    <!-- SLA Metric Cards -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                {{ __('pages.tickets.detail.sla_metrics') }}
                            </h3>
                            <div class="h-0.5 bg-slate-50 dark:bg-slate-850 mt-2"></div>
                        </div>

                        <div class="space-y-3.5 pt-1">
                            <!-- Response Time Card -->
                            <div class="p-3 border border-slate-100 dark:border-slate-800 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4.5 w-4.5 text-indigo-500" />
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block leading-none">
                                            {{ __('pages.tickets.detail.response_time_sla') }}
                                        </span>
                                        <span v-if="ticket.responded_at" class="text-[9px] text-slate-450 mt-1 block">
                                            {{ __('pages.tickets.detail.responded_status_sla') }}
                                        </span>
                                        <span v-else-if="ticket.validated_at" class="text-[9px] text-indigo-500 animate-pulse font-bold mt-1 block">
                                            {{ __('pages.tickets.detail.running_status_sla') }}
                                        </span>
                                        <span v-else class="text-[9px] text-slate-450 mt-1 block">
                                            {{ __('pages.tickets.detail.awaiting_validate_sla') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm font-extrabold text-slate-955 dark:text-white">
                                    {{ formatDuration(responseTimeSeconds) }}
                                </span>
                            </div>

                            <!-- Paused Duration Card -->
                            <div class="p-3 border border-slate-100 dark:border-slate-800 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Pause class="h-4.5 w-4.5 text-orange-500" />
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block leading-none">
                                            {{ __('pages.tickets.detail.paused_duration') }}
                                        </span>
                                        <span v-if="ticket.status === 'PENDING'" class="text-[9px] text-orange-500 animate-pulse font-bold mt-1 block">
                                            {{ __('pages.tickets.detail.active_paused_sla') }}
                                        </span>
                                        <span v-else class="text-[9px] text-slate-455 mt-1 block">
                                            {{ __('pages.tickets.detail.total_pauses_sla') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm font-extrabold text-slate-955 dark:text-white">
                                    {{ formatDuration(pausedDurationSeconds) }}
                                </span>
                            </div>

                            <!-- Resolution Time Card -->
                            <div class="p-3 border border-slate-100 dark:border-slate-800 rounded-xl flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-4.5 w-4.5 text-emerald-500" />
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block leading-none">
                                            {{ __('pages.tickets.detail.resolution_time_sla') }}
                                        </span>
                                        <span v-if="ticket.resolved_at" class="text-[9px] text-slate-455 mt-1 block">
                                            {{ __('pages.tickets.detail.resolved_status_sla') }}
                                        </span>
                                        <span v-else-if="ticket.status === 'PENDING'" class="text-[9px] text-orange-500 font-bold mt-1 block">
                                            {{ __('pages.tickets.detail.paused_status_sla') }}
                                        </span>
                                        <span v-else-if="ticket.validated_at" class="text-[9px] text-emerald-500 animate-pulse font-bold mt-1 block">
                                            {{ __('pages.tickets.detail.running_status_sla') }}
                                        </span>
                                        <span v-else class="text-[9px] text-slate-455 mt-1 block">
                                            {{ __('pages.tickets.detail.awaiting_dispatch_sla') }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm font-extrabold text-slate-955 dark:text-white">
                                    {{ formatDuration(resolutionTimeSeconds) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Status Timeline Tracking -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                {{ __('pages.tickets.detail.timeline') }}
                            </h3>
                            <div class="h-0.5 bg-slate-50 dark:bg-slate-850 mt-2"></div>
                        </div>

                        <div class="flow-root pt-2">
                            <ul class="-mb-8">
                                
                                <!-- Node 1: Created -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-indigo-500 dark:bg-indigo-950" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white dark:ring-slate-900">
                                                    <FileText class="h-4 w-4 text-white" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-2">
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                        {{ __('pages.tickets.detail.created_status') }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-405 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                        {{ __('pages.tickets.detail.by_label') }}: <span class="font-semibold text-slate-700 dark:text-slate-350">{{ ticket.reporter?.name }}</span>
                                                    </p>
                                                </div>
                                                <div class="text-right text-[9px] whitespace-nowrap text-slate-400 mt-0.5">
                                                    {{ formatDateTime(ticket.created_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Node 2: Dispatched -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-indigo-500 dark:bg-indigo-950" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                    ticket.validated_at ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-800'
                                                ]">
                                                    <UserCheck class="h-4 w-4" :class="ticket.validated_at ? 'text-white' : 'text-slate-400 dark:text-slate-505'" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-2">
                                                <div>
                                                    <p class="text-xs font-bold" :class="ticket.validated_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-505'">
                                                        {{ __('pages.tickets.detail.assigned_status') }}
                                                    </p>
                                                    <p v-if="ticket.validated_at" class="text-[10px] text-slate-405 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                        {{ __('pages.tickets.detail.validator_label_timeline') }}: <span class="font-semibold text-slate-700 dark:text-slate-350">{{ ticket.validator?.name }}</span>
                                                        <br />
                                                        {{ __('pages.tickets.detail.technician_label_timeline') }}: <span class="font-semibold text-slate-700 dark:text-slate-350">
                                                            {{ ticket.assignments?.map(a => a.technician?.name).join(', ') }}
                                                        </span>
                                                    </p>
                                                    <p v-else class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">
                                                        {{ __('pages.tickets.detail.pending_validation_status') }}
                                                    </p>
                                                </div>
                                                <div v-if="ticket.validated_at" class="text-right text-[9px] whitespace-nowrap text-slate-400 mt-0.5">
                                                    {{ formatDateTime(ticket.validated_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Node 3: Arrived -->
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-indigo-500 dark:bg-indigo-950" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                    ticket.responded_at ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-800'
                                                ]">
                                                    <Activity class="h-4 w-4" :class="ticket.responded_at ? 'text-white' : 'text-slate-400 dark:text-slate-505'" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-2">
                                                <div>
                                                    <p class="text-xs font-bold" :class="ticket.responded_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-505'">
                                                        {{ __('pages.tickets.detail.in_progress_status') }}
                                                    </p>
                                                    <p v-if="ticket.responded_at" class="text-[10px] text-slate-405 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                        {{ __('pages.tickets.detail.arrived_detail_timeline') }}
                                                    </p>
                                                    <p v-else class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">
                                                        {{ __('pages.tickets.detail.waiting_arrival_timeline') }}
                                                    </p>
                                                </div>
                                                <div v-if="ticket.responded_at" class="text-right text-[9px] whitespace-nowrap text-slate-400 mt-0.5">
                                                    {{ formatDateTime(ticket.responded_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Node 4: Resolved -->
                                <li>
                                    <div class="relative pb-4">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900',
                                                    ticket.status === 'COMPLETED' ? 'bg-emerald-500' : (ticket.status === 'CANCEL' ? 'bg-rose-500' : 'bg-slate-200 dark:bg-slate-800')
                                                ]">
                                                    <CheckCircle2 v-if="ticket.status === 'COMPLETED'" class="h-4 w-4 text-white" />
                                                    <XCircle v-else-if="ticket.status === 'CANCEL'" class="h-4 w-4 text-white" />
                                                    <CheckCircle2 v-else class="h-4 w-4 text-slate-400 dark:text-slate-505" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between gap-2">
                                                <div>
                                                    <p class="text-xs font-bold" :class="ticket.status === 'COMPLETED' || ticket.status === 'CANCEL' ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-505'">
                                                        {{ ticket.status === 'CANCEL' ? __('pages.tickets.detail.cancel_status') : __('pages.tickets.detail.completed_status') }}
                                                    </p>
                                                    <p v-if="ticket.status === 'COMPLETED'" class="text-[10px] text-slate-405 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ __('pages.tickets.detail.action_taken_label') }}</span> {{ ticket.completion_notes }}
                                                    </p>
                                                    <p v-else-if="ticket.status === 'CANCEL'" class="text-[10px] text-slate-405 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                        <span class="font-bold text-rose-600 dark:text-rose-400">{{ __('pages.tickets.detail.cancel_reason_label_inline') }}</span> {{ ticket.completion_notes }}
                                                    </p>
                                                    <p v-else class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">
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
                                                </div>
                                                <div v-if="ticket.resolved_at" class="text-right text-[9px] whitespace-nowrap text-slate-400 mt-0.5">
                                                    {{ formatDateTime(ticket.resolved_at) }}
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

    <!-- Completion Modal -->
    <Modal :show="showCompleteModal" @close="showCompleteModal = false" maxWidth="lg">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                    {{ __('pages.tickets.detail.completion_modal_title') }}
                </h3>
                <button @click="showCompleteModal = false" class="text-slate-400 hover:text-slate-500">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submitResolve('COMPLETED')" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.tickets.detail.completion_notes_label') }} <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        v-model="resolveForm.notes" 
                        required
                        rows="4"
                        :placeholder="__('pages.tickets.detail.completion_notes_placeholder')"
                        class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                    ></textarea>
                    <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                </div>

                <!-- Upload Proof section -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.tickets.detail.upload_proof_label') }}
                    </label>

                    <!-- Hidden Inputs -->
                    <input ref="completeFileInput" type="file" class="hidden" accept="image/*" multiple @change="handleCompleteFileSelect" />
                    <input ref="completeCameraInput" type="file" class="hidden" accept="image/*" capture="environment" @change="handleCompleteFileSelect" />

                    <!-- Photo Previews Grid -->
                    <div v-if="completePreviews.length > 0" class="grid grid-cols-5 gap-2">
                        <div 
                            v-for="(p, idx) in completePreviews" 
                            :key="idx" 
                            class="relative rounded-xl overflow-hidden border border-slate-250 dark:border-slate-800 aspect-square bg-slate-50 dark:bg-slate-950"
                        >
                            <img :src="p.preview" class="w-full h-full object-cover cursor-pointer" @click="openLightbox(p.preview)" />
                            <button
                                type="button"
                                @click="removeCompleteAttachment(idx)"
                                class="absolute top-0.5 right-0.5 h-5 w-5 flex items-center justify-center rounded-md bg-black/60 hover:bg-black/80 text-white transition"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>
                    </div>

                    <!-- Dropzone uploader alternative buttons -->
                    <div v-if="completePreviews.length < 5" class="flex gap-2">
                        <button
                            type="button"
                            @click="completeFileInput?.click()"
                            class="flex-1 h-10 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-xs font-semibold flex items-center justify-center gap-1.5 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-655 dark:text-slate-450"
                        >
                            <UploadCloud class="h-4.5 w-4.5 text-slate-400" />
                            {{ __('pages.tickets.detail.upload_file') }}
                        </button>
                        <button
                            type="button"
                            @click="completeCameraInput?.click()"
                            class="flex-1 h-10 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-xs font-semibold flex items-center justify-center gap-1.5 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-655 dark:text-slate-450"
                        >
                            <Camera class="h-4.5 w-4.5 text-slate-400" />
                            {{ __('pages.tickets.detail.camera') }}
                        </button>
                    </div>
                    <div v-if="resolveForm.errors.attachments" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.attachments }}</div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <SecondaryButton type="button" @click="showCompleteModal = false">
                        {{ __('global.cancel') }}
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="resolveForm.processing || resolveForm.attachments.length === 0">
                        {{ __('pages.tickets.detail.submit_action') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Pending Modal -->
    <Modal :show="showPendingModal" @close="showPendingModal = false" maxWidth="lg">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                    {{ __('pages.tickets.detail.pending_modal_title') }}
                </h3>
                <button @click="showPendingModal = false" class="text-slate-400 hover:text-slate-500">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submitResolve('PENDING')" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.tickets.detail.pending_reason_label') }} <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        v-model="resolveForm.notes" 
                        required
                        rows="4"
                        :placeholder="__('pages.tickets.detail.pending_reason_placeholder')"
                        class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                    ></textarea>
                    <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <SecondaryButton type="button" @click="showPendingModal = false">
                        {{ __('global.cancel') }}
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="resolveForm.processing || !resolveForm.notes">
                        {{ __('pages.tickets.detail.submit_action') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Cancel Modal -->
    <Modal :show="showCancelModal" @close="showCancelModal = false" maxWidth="lg">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-extrabold text-rose-600 dark:text-rose-400 uppercase tracking-wider">
                    {{ __('pages.tickets.detail.cancel_modal_title') }}
                </h3>
                <button @click="showCancelModal = false" class="text-slate-400 hover:text-slate-500">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <form @submit.prevent="submitResolve('CANCEL')" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.tickets.detail.cancel_reason_label') }} <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        v-model="resolveForm.notes" 
                        required
                        rows="4"
                        :placeholder="__('pages.tickets.detail.cancel_reason_placeholder')"
                        class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                    ></textarea>
                    <div v-if="resolveForm.errors.notes" class="text-[10px] text-red-500 font-semibold">{{ resolveForm.errors.notes }}</div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <SecondaryButton type="button" @click="showCancelModal = false">
                        {{ __('global.cancel') }}
                    </SecondaryButton>
                    <DangerButton type="submit" :disabled="resolveForm.processing || !resolveForm.notes">
                        {{ __('pages.tickets.detail.submit_action') }}
                    </DangerButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
