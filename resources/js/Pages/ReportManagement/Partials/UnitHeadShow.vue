<script setup>
import { ref, computed, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import { Head, Link, useForm, usePage, Deferred } from '@inertiajs/vue3';
import {
    Calendar,
    User,
    MapPin,
    Wrench,
    Clock,
    Activity,
    FileText,
    Search,
    X,
    CheckCircle2,
    XCircle,
    ImageIcon,
    UserCheck,
    Send,
    Sparkles,
    ChevronDown,
    Pause
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    ticket: {
        type: Object,
        default: () => null
    },
    technicians: {
        type: Array,
        default: () => []
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
        created: props.ticket?.created_at ? new Date(props.ticket.created_at) : null,
        validated: props.ticket?.validated_at ? new Date(props.ticket.validated_at) : null,
        responded: props.ticket?.responded_at ? new Date(props.ticket.responded_at) : null,
        resolved: props.ticket?.resolved_at ? new Date(props.ticket.resolved_at) : null,
        lastPaused: props.ticket?.last_paused_at ? new Date(props.ticket.last_paused_at) : null,
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
    let accumulated = props.ticket?.paused_duration_seconds || 0;
    if (props.ticket?.status === 'PENDING' && dates.lastPaused) {
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
        return Math.max(0, total - (props.ticket?.paused_duration_seconds || 0));
    }
    if (props.ticket?.status === 'PENDING' && dates.lastPaused) {
        const total = Math.floor((dates.lastPaused - dates.validated) / 1000);
        return Math.max(0, total - (props.ticket?.paused_duration_seconds || 0));
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

const assignForm = useForm({
    priority: 'ROUTINE',
    technician_ids: []
});

const submitAssign = () => {
    if (!props.ticket?.uuid) return;
    assignForm.post(route('tickets.assign', props.ticket.uuid), {
        onSuccess: () => {
            assignForm.reset();
        }
    });
};

const addTechnician = (event) => {
    const value = parseInt(event.target.value);
    if (value && !assignForm.technician_ids.includes(value)) {
        assignForm.technician_ids.push(value);
    }
    event.target.value = "";
};

const removeTechnician = (id) => {
    assignForm.technician_ids = assignForm.technician_ids.filter(techId => techId !== id);
};

const getTechnicianName = (id) => {
    const tech = props.technicians?.find(t => t.id === id);
    if (!tech) return '';
    const unitName = tech.supporting_unit ? ` (${tech.supporting_unit.name})` : '';
    return `${tech.name}${unitName}`;
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
    return props.ticket?.attachments?.filter(att => att.uploaded_by == props.ticket?.reporter_id) || [];
});

const completionAttachments = computed(() => {
    return props.ticket?.attachments?.filter(att => att.uploaded_by != props.ticket?.reporter_id) || [];
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
        <Deferred data="ticket">
            <template #fallback>
                <!-- Skeleton Loading for Unit Head Detail -->
                <div class="w-full space-y-4 animate-pulse">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4 w-full">
                            <div class="h-12 w-12 rounded-xl bg-slate-200 dark:bg-slate-800 flex-shrink-0"></div>
                            <div class="space-y-2 flex-1">
                                <div class="h-6 w-48 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="h-4 w-64 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="lg:col-span-2 space-y-4">
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <div class="h-4 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                    <div v-for="i in 4" :key="i" class="space-y-1">
                                        <div class="h-3 w-20 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                                        <div class="h-4 w-36 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                    </div>
                                </div>
                                <div class="h-24 w-full bg-slate-100 dark:bg-slate-800/40 rounded-xl"></div>
                            </div>
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <div class="h-4 w-40 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="h-10 w-full bg-slate-100 dark:bg-slate-800/60 rounded-xl"></div>
                                <div class="h-11 w-full bg-indigo-200 dark:bg-indigo-950/60 rounded-xl"></div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                                <div class="h-4 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div v-for="i in 3" :key="i" class="h-12 w-full bg-slate-100 dark:bg-slate-800/50 rounded-xl"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #default>
                <div class="w-full space-y-4" v-if="ticket">
                    
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
                        
                        <!-- Left Column: Details & Disposition Form -->
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
                                    <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-505 tracking-wider flex items-center gap-1.5">
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

                            <!-- Action Panel Card: Unit Head Validation & Assignments -->
                            <div v-if="ticket.status === 'PENDING_VALIDATION'" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 animate-spa-fade-in">
                                <div class="flex items-center gap-2">
                                    <Sparkles class="h-5 w-5 text-emerald-500" />
                                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                        {{ __('pages.tickets.detail.disposition_title') }}
                                    </h3>
                                </div>
                                <div class="h-0.5 bg-slate-50 dark:bg-slate-850"></div>

                                <form @submit.prevent="submitAssign" class="space-y-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            {{ __('pages.tickets.detail.select_priority') }}
                                        </label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <label 
                                                class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition select-none"
                                                :class="[
                                                    assignForm.priority === 'ROUTINE'
                                                        ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20 text-emerald-950 dark:text-white font-bold'
                                                        : 'border-slate-200 dark:border-slate-800 text-slate-650 dark:text-slate-355 hover:bg-slate-50 dark:hover:bg-slate-900'
                                                ]"
                                            >
                                                <span class="text-xs">{{ __('pages.tickets.detail.priority_routine') }}</span>
                                                <input type="radio" value="ROUTINE" v-model="assignForm.priority" class="hidden" />
                                                <CheckCircle2 v-if="assignForm.priority === 'ROUTINE'" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                            </label>

                                            <label 
                                                class="border rounded-xl p-3 flex items-center justify-between cursor-pointer transition select-none"
                                                :class="[
                                                    assignForm.priority === 'URGENT'
                                                        ? 'border-rose-500 bg-rose-50/20 dark:bg-rose-950/20 text-rose-950 dark:text-white font-bold'
                                                        : 'border-slate-200 dark:border-slate-800 text-slate-650 dark:text-slate-355 hover:bg-slate-50 dark:hover:bg-slate-900'
                                                ]"
                                            >
                                                <span class="text-xs">{{ __('pages.tickets.detail.priority_urgent') }}</span>
                                                <input type="radio" value="URGENT" v-model="assignForm.priority" class="hidden" />
                                                <CheckCircle2 v-if="assignForm.priority === 'URGENT'" class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                                            </label>
                                        </div>
                                        <div v-if="assignForm.errors.priority" class="text-[10px] text-red-500 font-semibold">{{ assignForm.errors.priority }}</div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            {{ __('pages.tickets.detail.select_technicians') }}
                                        </label>
                                        <div v-if="technicians.length > 0" class="space-y-3">
                                            <div class="relative">
                                                <select
                                                    @change="addTechnician($event)"
                                                    class="w-full h-11 pl-4 pr-10 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none appearance-none cursor-pointer"
                                                >
                                                    <option value="" disabled selected>{{ __('pages.tickets.detail.select_technicians') }}...</option>
                                                    <option 
                                                        v-for="tech in technicians" 
                                                        :key="tech.id" 
                                                        :value="tech.id"
                                                        :disabled="assignForm.technician_ids.includes(tech.id)"
                                                    >
                                                        {{ tech.name }} (NIP: {{ tech.nip }}) {{ tech.supporting_unit ? ' - ' + tech.supporting_unit.name : '' }}
                                                    </option>
                                                </select>
                                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                    <ChevronDown class="h-4.5 w-4.5 text-slate-400" />
                                                </div>
                                            </div>

                                            <div v-if="assignForm.technician_ids.length > 0" class="flex flex-wrap gap-1.5 p-3 border border-slate-100 dark:border-slate-800/60 rounded-xl bg-slate-50/20 dark:bg-slate-950/20">
                                                <div 
                                                    v-for="id in assignForm.technician_ids" 
                                                    :key="id"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-750 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/20"
                                                >
                                                    <span>{{ getTechnicianName(id) }}</span>
                                                    <button 
                                                        type="button" 
                                                        @click="removeTechnician(id)"
                                                        class="text-emerald-400 hover:text-emerald-600 dark:hover:text-emerald-200 focus:outline-none"
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-slate-450 dark:text-slate-500 italic p-3 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/20">
                                            {{ __('pages.tickets.detail.no_active_techs') }}
                                        </div>
                                        <div v-if="assignForm.errors.technician_ids" class="text-[10px] text-red-500 font-semibold">{{ assignForm.errors.technician_ids }}</div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="assignForm.processing || assignForm.technician_ids.length === 0"
                                        class="w-full h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 transition duration-200 disabled:opacity-50"
                                    >
                                        <Send class="h-4 w-4" />
                                        <span>{{ assignForm.processing ? __('Mengirim...') : __('pages.tickets.detail.btn_assign') }}</span>
                                    </button>
                                </form>
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
                                                <span v-else class="text-[9px] text-slate-455 mt-1 block">
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
                                                            <p v-if="ticket.validated_at" class="text-[10px] text-slate-400 dark:text-slate-505 mt-0.5 leading-relaxed">
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
                                                            <p v-if="ticket.status === 'COMPLETED'" class="text-[10px] text-slate-400 dark:text-slate-505 mt-0.5 leading-relaxed">
                                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ __('pages.tickets.detail.action_taken_label') }}</span> {{ ticket.completion_notes }}
                                                            </p>
                                                            <p v-else-if="ticket.status === 'CANCEL'" class="text-[10px] text-slate-400 dark:text-slate-505 mt-0.5 leading-relaxed">
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
            </template>
        </Deferred>
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
</template>
