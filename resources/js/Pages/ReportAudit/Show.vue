<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, getCurrentInstance } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import { compressImage } from '@/Utils/imageCompressor';
import {
    ShieldCheck,
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
    Pause,
    Play,
    Check,
    Info,
    Trash2,
    RotateCcw,
    Edit3,
    Eye,
    ArrowLeft,
    AlertTriangle,
    Save,
    History,
    Upload,
    Plus,
    RefreshCw,
    Camera
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    ticket: {
        type: Object,
        default: () => null,
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    technicians: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const isEditMode = ref(false);
const showSlaInfoModal = ref(false);
const activeLightbox = ref(null);

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

// Date helper for flatpickr datetime inputs
const toDatetimeFormatted = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

// Options for Priority and Status Selects
const priorityOptions = [
    { id: 'ROUTINE', name: 'Rutin' },
    { id: 'URGENT', name: 'Urgent' },
];

const statusOptions = [
    { id: 'PENDING_VALIDATION', name: 'Menunggu Validasi' },
    { id: 'ASSIGNED', name: 'Ditugaskan' },
    { id: 'IN_PROGRESS', name: 'Dikerjakan' },
    { id: 'PENDING', name: 'Ditangguhkan' },
    { id: 'COMPLETED', name: 'Selesai' },
    { id: 'CANCEL', name: 'Dibatalkan' },
];

// Audit Edit Form
const editForm = useForm({
    reporter_id: props.ticket?.reporter_id || '',
    room_id: props.ticket?.room_id || '',
    category_id: props.ticket?.category_id || '',
    problem_description: props.ticket?.problem_description || '',
    priority: props.ticket?.priority || 'ROUTINE',
    status: props.ticket?.status || 'PENDING_VALIDATION',
    validated_by: props.ticket?.validated_by || 'SYSTEM',
    validated_at: toDatetimeFormatted(props.ticket?.validated_at),
    responded_at: toDatetimeFormatted(props.ticket?.responded_at),
    resolved_at: toDatetimeFormatted(props.ticket?.resolved_at),
    pending_reason: props.ticket?.pending_reason || '',
    paused_duration_seconds: props.ticket?.paused_duration_seconds || 0,
    completion_notes: props.ticket?.completion_notes || '',
    technician_ids: props.ticket?.assignments ? props.ticket.assignments.map(a => a.technician_id || a.technician?.id).filter(Boolean) : [],
    created_at: toDatetimeFormatted(props.ticket?.created_at),
});

// Flatpickr instances and refs for SLA date inputs
const fpCreatedAtRef = ref(null);
const fpValidatedAtRef = ref(null);
const fpRespondedAtRef = ref(null);
const fpResolvedAtRef = ref(null);

let fpCreatedAt = null;
let fpValidatedAt = null;
let fpRespondedAt = null;
let fpResolvedAt = null;

const initFlatpickrInstances = () => {
    destroyFlatpickrInstances();

    const commonConfig = {
        locale: Indonesian,
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'd F Y, H:i',
        altInputClass: 'w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none transition duration-150 cursor-pointer',
    };

    if (fpCreatedAtRef.value) {
        fpCreatedAt = flatpickr(fpCreatedAtRef.value, {
            ...commonConfig,
            defaultDate: editForm.created_at || null,
            onChange: (selectedDates, dateStr) => {
                editForm.created_at = dateStr;
            }
        });
    }

    if (fpValidatedAtRef.value) {
        fpValidatedAt = flatpickr(fpValidatedAtRef.value, {
            ...commonConfig,
            defaultDate: editForm.validated_at || null,
            onChange: (selectedDates, dateStr) => {
                editForm.validated_at = dateStr;
            }
        });
    }

    if (fpRespondedAtRef.value) {
        fpRespondedAt = flatpickr(fpRespondedAtRef.value, {
            ...commonConfig,
            defaultDate: editForm.responded_at || null,
            onChange: (selectedDates, dateStr) => {
                editForm.responded_at = dateStr;
            }
        });
    }

    if (fpResolvedAtRef.value) {
        fpResolvedAt = flatpickr(fpResolvedAtRef.value, {
            ...commonConfig,
            defaultDate: editForm.resolved_at || null,
            onChange: (selectedDates, dateStr) => {
                editForm.resolved_at = dateStr;
            }
        });
    }
};

const destroyFlatpickrInstances = () => {
    if (fpCreatedAt) { fpCreatedAt.destroy(); fpCreatedAt = null; }
    if (fpValidatedAt) { fpValidatedAt.destroy(); fpValidatedAt = null; }
    if (fpRespondedAt) { fpRespondedAt.destroy(); fpRespondedAt = null; }
    if (fpResolvedAt) { fpResolvedAt.destroy(); fpResolvedAt = null; }
};

watch(isEditMode, (val) => {
    if (val) {
        setTimeout(() => {
            initFlatpickrInstances();
        }, 50);
    } else {
        destroyFlatpickrInstances();
    }
});

onUnmounted(() => {
    destroyFlatpickrInstances();
});

const resetEditForm = () => {
    if (!props.ticket) return;
    editForm.reporter_id = props.ticket.reporter_id || '';
    editForm.room_id = props.ticket.room_id || '';
    editForm.category_id = props.ticket.category_id || '';
    editForm.problem_description = props.ticket.problem_description || '';
    editForm.priority = props.ticket.priority || 'ROUTINE';
    editForm.status = props.ticket.status || 'PENDING_VALIDATION';
    editForm.validated_by = props.ticket.validated_by || 'SYSTEM';
    editForm.validated_at = toDatetimeFormatted(props.ticket.validated_at);
    editForm.responded_at = toDatetimeFormatted(props.ticket.responded_at);
    editForm.resolved_at = toDatetimeFormatted(props.ticket.resolved_at);
    editForm.pending_reason = props.ticket.pending_reason || '';
    editForm.paused_duration_seconds = props.ticket.paused_duration_seconds || 0;
    editForm.completion_notes = props.ticket.completion_notes || '';
    editForm.technician_ids = props.ticket.assignments ? props.ticket.assignments.map(a => a.technician_id || a.technician?.id).filter(Boolean) : [];
    editForm.created_at = toDatetimeFormatted(props.ticket.created_at);
    destroyFlatpickrInstances();
    isEditMode.value = false;
};

// Select options
const roomOptions = computed(() => {
    return (props.rooms || []).map(r => ({
        id: r.id,
        name: r.name,
        location_floor: formatRoomDetails(r),
    }));
});

const categoryOptions = computed(() => {
    return (props.categories || []).map(c => ({
        id: c.id,
        name: `${c.name} (${c.supporting_unit?.name ?? c.supportingUnit?.name ?? 'Umum'})`,
    }));
});

const userOptions = computed(() => {
    return (props.users || []).map(u => ({
        id: u.id,
        name: `${u.name} ${u.nip ? ' - NIP: ' + u.nip : ''}`,
    }));
});

const validatorOptions = computed(() => {
    return [
        { id: 'SYSTEM', name: 'Sistem (Disposisi Otomatis)' },
        ...(props.users || []).map(u => ({
            id: u.id,
            name: `${u.name}${u.nip ? ' - NIP: ' + u.nip : ''}`,
        })),
    ];
});

// Searchable Technician Selection for Edit
const isTechDropdownOpen = ref(false);
const techSearchQuery = ref('');
const techDropdownRef = ref(null);

const handleClickOutsideTech = (event) => {
    if (isTechDropdownOpen.value && techDropdownRef.value && !techDropdownRef.value.contains(event.target)) {
        isTechDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideTech);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsideTech);
});

const filteredTechnicians = computed(() => {
    const q = techSearchQuery.value.trim().toLowerCase();
    if (!q) return props.technicians;
    return props.technicians.filter(t =>
        (t.name && t.name.toLowerCase().includes(q)) ||
        (t.nip && t.nip.toLowerCase().includes(q))
    );
});

const toggleTechDropdown = (e) => {
    e?.stopPropagation();
    isTechDropdownOpen.value = !isTechDropdownOpen.value;
    techSearchQuery.value = '';
};

const selectTechnician = (id) => {
    if (!editForm.technician_ids.includes(id)) {
        editForm.technician_ids.push(id);
    } else {
        editForm.technician_ids = editForm.technician_ids.filter(techId => techId !== id);
    }
};

const removeTechnician = (id) => {
    editForm.technician_ids = editForm.technician_ids.filter(techId => techId !== id);
};

const getTechnicianName = (id) => {
    const tech = props.technicians?.find(t => t.id === id);
    if (!tech) return '';
    const unitName = tech.supporting_unit ? ` (${tech.supporting_unit.name})` : '';
    return `${tech.name}${unitName}`;
};

// Live SLA Timers
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

// Lightbox
const openLightbox = (url) => {
    activeLightbox.value = url;
};
const closeLightbox = () => {
    activeLightbox.value = null;
};

// Attachments slot computed properties (classified by file prefix)
const reporterPhoto = computed(() => {
    return props.ticket?.attachments?.find(att => 
        att.file_path && !att.file_path.includes('ticket_arr_') && !att.file_path.includes('ticket_res_')
    ) || null;
});

const arrivalPhoto = computed(() => {
    return props.ticket?.attachments?.find(att => 
        att.file_path && att.file_path.includes('ticket_arr_')
    ) || null;
});

const completionPhoto = computed(() => {
    return props.ticket?.attachments?.find(att => 
        att.file_path && (att.file_path.includes('ticket_res_') || (!att.file_path.includes('ticket_arr_') && att.uploaded_by != props.ticket?.reporter_id))
    ) || null;
});

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

// 3-Slot Photo Management in Audit Edit Mode
const targetSlotType = ref(null);
const fileInputSlotRef = ref(null);
const isProcessingSlot = ref({
    reporter: false,
    arrival: false,
    completion: false,
});

const triggerSlotUpload = (slotType) => {
    targetSlotType.value = slotType;
    if (fileInputSlotRef.value) {
        fileInputSlotRef.value.value = '';
        fileInputSlotRef.value.click();
    }
};

const handleSlotFileChange = async (event) => {
    const file = event.target.files?.[0];
    const slotType = targetSlotType.value;
    if (!file || !slotType) return;

    isProcessingSlot.value[slotType] = true;

    try {
        let base64 = '';
        if (file.type.startsWith('image/')) {
            base64 = await compressImage(file, 1200, 1200, 0.75);
        } else {
            const reader = new FileReader();
            base64 = await new Promise((resolve) => {
                reader.onload = (e) => resolve(e.target.result);
                reader.readAsDataURL(file);
            });
        }

        router.post(route('reports-audit.update-slot', { uuid: props.ticket.uuid, slotType }), {
            attachment: base64,
        }, {
            preserveScroll: true,
            onFinish: () => {
                isProcessingSlot.value[slotType] = false;
                targetSlotType.value = null;
            }
        });
    } catch (err) {
        console.error(err);
        isProcessingSlot.value[slotType] = false;
        targetSlotType.value = null;
        proxy.$swal({
            title: 'Gagal Memproses Foto',
            text: 'Terjadi kesalahan saat memproses file foto.',
            icon: 'error'
        });
    }
};

const confirmDeleteSlot = (slotType, slotLabel) => {
    proxy.$swal({
        title: `Hapus ${slotLabel}?`,
        text: 'File foto ini akan dihapus secara permanen dari server penyimpanan dan database.',
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('reports-audit.delete-slot', { uuid: props.ticket.uuid, slotType }), {
                preserveScroll: true,
            });
        }
    });
};

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

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu Validasi', badge: 'bg-amber-50 text-amber-700 border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30' },
    ASSIGNED:           { label: 'Ditugaskan', badge: 'bg-blue-50 text-blue-700 border-blue-200/50 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/30' },
    IN_PROGRESS:        { label: 'Dikerjakan', badge: 'bg-violet-50 text-violet-700 border-violet-200/50 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/30' },
    PENDING:            { label: 'Ditangguhkan', badge: 'bg-orange-50 text-orange-700 border-orange-200/50 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/30' },
    COMPLETED:          { label: 'Selesai', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-white/10 dark:text-white dark:border-white/20' },
    CANCEL:             { label: 'Dibatalkan', badge: 'bg-rose-50 text-rose-700 border-rose-200/50 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/30' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600 border-slate-200' };

const priorityConfig = {
    URGENT:    { label: 'URGENT', badge: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50' },
    ROUTINE:   { label: 'RUTIN', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' },
};

const getPriority = (target) => {
    if (!target) return { label: '-', badge: '', isPending: true };
    const priority = typeof target === 'string' ? target : target.priority;
    const status = typeof target === 'object' ? target.status : (props.ticket?.status || null);

    if (status === 'PENDING_VALIDATION') {
        return { label: '-', badge: '', isPending: true };
    }
    return {
        ...(priorityConfig[priority] ?? { label: '-', badge: '' }),
        isPending: false
    };
};

// Actions: Submit Audit Edit, Delete, Restore
const submitAuditUpdate = () => {
    if (!props.ticket?.uuid || editForm.processing) return;
    proxy.$swal({
        title: 'Simpan Perubahan Audit?',
        text: `Semua perubahan data tiket #${props.ticket.ticket_number} akan diperbarui di basis data.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan Perubahan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            editForm.put(route('reports-audit.update', props.ticket.uuid), {
                onSuccess: () => {
                    isEditMode.value = false;
                }
            });
        }
    });
};

const confirmDelete = () => {
    if (!props.ticket) return;
    proxy.$swal({
        title: 'Hapus Laporan (Soft Delete)?',
        text: `Laporan #${props.ticket.ticket_number} akan di-soft delete. Anda dapat memulihkannya kapan saja.`,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Laporan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('reports-audit.destroy', props.ticket.uuid), {
                preserveScroll: true,
            });
        }
    });
};

const confirmRestore = () => {
    if (!props.ticket) return;
    proxy.$swal({
        title: 'Pulihkan Laporan?',
        text: `Laporan #${props.ticket.ticket_number} akan dipulihkan kembali ke antrean aktif.`,
        icon: 'question',
        iconColor: '#e11d48',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Pulihkan Laporan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('reports-audit.restore', props.ticket.uuid), {}, {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head :title="`Audit Laporan #${ticket?.ticket_number || ''}`" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 space-y-4">
            
            <!-- Soft Deleted Alert Banner -->
            <div 
                v-if="ticket?.deleted_at" 
                class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-rose-800 dark:text-rose-300 animate-spa-fade-in shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <Trash2 class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide">
                            Perhatian: Laporan Ini Berstatus Terhapus (Soft Delete)
                        </div>
                        <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">
                            Dihapus pada: {{ formatDateTime(ticket.deleted_at) }}. Data tetap tersimpan untuk keperluan audit.
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click="confirmRestore"
                    class="h-10 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white dark:bg-rose-600 dark:hover:bg-rose-500 dark:text-white transition flex items-center justify-center gap-2 shadow-sm shrink-0"
                >
                    <RotateCcw class="h-4 w-4" />
                    <span>Pulihkan Laporan (Restore)</span>
                </button>
            </div>

            <!-- Header Card -->
            <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white shrink-0">
                        <ShieldCheck class="h-6 w-6" />
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                #{{ ticket.ticket_number }}
                            </h2>
                            <span v-if="ticket.deleted_at" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50">
                                TERHAPUS (SOFT DELETE)
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed uppercase font-semibold">
                            {{ ticket.category?.supporting_unit?.name ?? ticket.category?.supportingUnit?.name ?? 'IPSRS' }} &bull; {{ ticket.category?.name ?? 'PELAPORAN' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Toggle Edit Mode Button -->
                    <button
                        type="button"
                        @click="isEditMode = !isEditMode"
                        :class="[
                            'h-10 px-4 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm',
                            isEditMode 
                                ? 'bg-slate-800 text-white hover:bg-slate-700 dark:bg-slate-800 dark:text-slate-200' 
                                : 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                        ]"
                    >
                        <component :is="isEditMode ? Eye : Edit3" class="h-4 w-4" />
                        <span>{{ isEditMode ? 'Lihat Tampilan Detail' : 'Edit Data Laporan (Audit)' }}</span>
                    </button>

                    <!-- Restore or Delete Action Button -->
                    <button
                        v-if="ticket.deleted_at"
                        type="button"
                        @click="confirmRestore"
                        class="h-10 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white dark:bg-rose-600 dark:hover:bg-rose-500 dark:text-white transition flex items-center gap-1.5 shadow-sm"
                    >
                        <RotateCcw class="h-4 w-4" />
                        <span>Pulihkan</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="confirmDelete"
                        class="h-10 px-4 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white dark:bg-rose-600 dark:hover:bg-rose-500 dark:text-white transition flex items-center gap-1.5 shadow-sm"
                    >
                        <Trash2 class="h-4 w-4" />
                        <span>Hapus (Soft)</span>
                    </button>
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Left Column: Edit Form OR Ticket Details -->
                <div class="lg:col-span-2 space-y-4">
                    
                    <!-- EDIT FORM PANEL (Audit Full Control) -->
                    <div v-if="isEditMode" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6 animate-spa-fade-in">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center">
                                    <Edit3 class="h-4 w-4" />
                                </div>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">
                                    Formulir Edit Data Tiket (Mode Audit)
                                </h3>
                            </div>
                            <span class="text-xs text-slate-400">Semua kolom dapat disesuaikan</span>
                        </div>

                        <form @submit.prevent="submitAuditUpdate" class="space-y-4">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Pelapor Selection -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Pelapor <span class="text-red-400">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="editForm.reporter_id"
                                        :options="userOptions"
                                        placeholder="Pilih Pelapor..."
                                        search-placeholder="Cari nama / NIP pelapor..."
                                        value-key="id"
                                        label-key="name"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.reporter_id" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.reporter_id }}</div>
                                </div>

                                <!-- Ruangan Selection -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Ruangan Lokasi <span class="text-red-400">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="editForm.room_id"
                                        :options="roomOptions"
                                        placeholder="Pilih Ruangan..."
                                        search-placeholder="Cari ruangan / gedung..."
                                        value-key="id"
                                        label-key="name"
                                        subtitle-key="location_floor"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.room_id" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.room_id }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Kategori Masalah Selection -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Kategori Permasalahan & Unit <span class="text-red-400">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="editForm.category_id"
                                        :options="categoryOptions"
                                        placeholder="Pilih Kategori..."
                                        search-placeholder="Cari kategori masalah..."
                                        value-key="id"
                                        label-key="name"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.category_id" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.category_id }}</div>
                                </div>

                                <!-- Prioritas Tiket Selection -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Prioritas Tiket <span class="text-red-400">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="editForm.priority"
                                        :options="priorityOptions"
                                        placeholder="Pilih Prioritas..."
                                        search-placeholder="Cari prioritas..."
                                        value-key="id"
                                        label-key="name"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.priority" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.priority }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Status Tiket Selection -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Status Tiket <span class="text-red-400">*</span>
                                    </label>
                                    <SearchableSelect
                                        v-model="editForm.status"
                                        :options="statusOptions"
                                        placeholder="Pilih Status..."
                                        search-placeholder="Cari status tiket..."
                                        value-key="id"
                                        label-key="name"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.status" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.status }}</div>
                                </div>

                                <!-- Validator / Petugas Disposisi Selection -->
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Validator / Petugas Disposisi
                                        </label>
                                        <span class="text-[10px] text-slate-400">Otomatis / Manual</span>
                                    </div>
                                    <SearchableSelect
                                        v-model="editForm.validated_by"
                                        :options="validatorOptions"
                                        placeholder="Pilih Validator / Sistem..."
                                        search-placeholder="Cari nama validator / petugas..."
                                        value-key="id"
                                        label-key="name"
                                        :absolute="false"
                                    />
                                    <div v-if="editForm.errors.validated_by" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.validated_by }}</div>
                                </div>
                            </div>

                            <!-- Penugasan Teknisi (Multi-select) -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Teknisi yang Ditugaskan
                                </label>
                                    <div class="relative" ref="techDropdownRef">
                                        <button 
                                            type="button"
                                            @click="toggleTechDropdown"
                                            class="w-full h-11 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                                        >
                                            <span v-if="editForm.technician_ids.length === 0" class="text-slate-400">Pilih Teknisi...</span>
                                            <span v-else class="font-bold text-slate-800 dark:text-white">{{ editForm.technician_ids.length }} Teknisi Dipilih</span>
                                            <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200', isTechDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                        </button>

                                        <div 
                                            v-if="isTechDropdownOpen" 
                                            class="absolute z-20 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-xl animate-spa-fade-in"
                                        >
                                            <div class="relative">
                                                <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                <input 
                                                    v-model="techSearchQuery"
                                                    type="text"
                                                    placeholder="Cari nama / NIP teknisi..."
                                                    class="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none"
                                                    @click.stop
                                                />
                                            </div>
                                            <div class="max-h-48 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                                <div v-if="filteredTechnicians.length === 0" class="p-3 text-center text-xs text-slate-400">
                                                    Teknisi tidak ditemukan
                                                </div>
                                                <button
                                                    v-for="tech in filteredTechnicians"
                                                    :key="tech.id"
                                                    type="button"
                                                    @click.stop="selectTechnician(tech.id)"
                                                    class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-white/10"
                                                    :class="editForm.technician_ids.includes(tech.id) ? 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300'"
                                                >
                                                    <div>
                                                        <div class="text-xs font-semibold">{{ tech.name }}</div>
                                                        <div class="text-[10px] text-slate-400 mt-0.5">NIP: {{ tech.nip }}{{ tech.supporting_unit ? ' • ' + tech.supporting_unit.name : '' }}</div>
                                                    </div>
                                                    <Check v-if="editForm.technician_ids.includes(tech.id)" class="h-3.5 w-3.5 text-emerald-600 dark:text-white shrink-0" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected Tech Chips -->
                                    <div v-if="editForm.technician_ids.length > 0" class="flex flex-wrap gap-1.5 p-3 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/30 dark:bg-slate-950/20 mt-2">
                                        <div 
                                            v-for="id in editForm.technician_ids" 
                                            :key="id"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 dark:bg-white/10 dark:text-white border border-emerald-200/50 dark:border-white/20"
                                        >
                                            <span>{{ getTechnicianName(id) }}</span>
                                            <button 
                                                type="button" 
                                                @click="removeTechnician(id)"
                                                class="text-emerald-500 hover:text-emerald-700 dark:text-white/70 dark:hover:text-white"
                                            >
                                                <X class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            <!-- Deskripsi Permasalahan -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Deskripsi Permasalahan <span class="text-red-400">*</span>
                                </label>
                                <textarea
                                    v-model="editForm.problem_description"
                                    rows="3"
                                    class="w-full p-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    placeholder="Tuliskan deskripsi kerusakan atau permohonan..."
                                ></textarea>
                                <div v-if="editForm.errors.problem_description" class="text-[10px] text-red-500 font-semibold">{{ editForm.errors.problem_description }}</div>
                            </div>

                            <!-- Alasan Pending / Catatan Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Alasan Penundaan (Pending)
                                    </label>
                                    <textarea
                                        v-model="editForm.pending_reason"
                                        rows="2"
                                        class="w-full p-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                        placeholder="Alasan penundaan bila ada..."
                                    ></textarea>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Catatan Penyelesaian / Alasan Batal
                                    </label>
                                    <textarea
                                        v-model="editForm.completion_notes"
                                        rows="2"
                                        class="w-full p-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                        placeholder="Catatan tindakan perbaikan / alasan pembatalan..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Audit Timestamp Controls (Flatpickr Calendars) -->
                            <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/30 space-y-3">
                                <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5 text-emerald-500" />
                                    <span>Penyesuaian Waktu & Durasi SLA (Opsional Audit)</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase">Dibuat Pada</label>
                                        <input
                                            ref="fpCreatedAtRef"
                                            type="text"
                                            placeholder="Pilih tanggal & jam..."
                                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none transition duration-150 cursor-pointer"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase">Divalidasi Pada</label>
                                        <input
                                            ref="fpValidatedAtRef"
                                            type="text"
                                            placeholder="Pilih tanggal & jam..."
                                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none transition duration-150 cursor-pointer"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase">Tiba di Lokasi</label>
                                        <input
                                            ref="fpRespondedAtRef"
                                            type="text"
                                            placeholder="Pilih tanggal & jam..."
                                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none transition duration-150 cursor-pointer"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase">Selesai Pada</label>
                                        <input
                                            ref="fpResolvedAtRef"
                                            type="text"
                                            placeholder="Pilih tanggal & jam..."
                                            class="w-full h-10 px-3 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs focus:outline-none transition duration-150 cursor-pointer"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Audit 3-Slot Lampiran Foto Management in Form -->
                            <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800 pb-3">
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                            <ImageIcon class="h-4 w-4 text-emerald-500" />
                                            <span>Lampiran Foto Tiket (Audit 3 Slot)</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">
                                            Kelola 3 foto utama tiket: Foto Laporan Pelapor, Bukti Hadir Teknisi, dan Bukti Penyelesaian (1 foto per slot).
                                        </p>
                                    </div>
                                </div>

                                <!-- 3 Slot Columns Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    
                                    <!-- Slot 1: Foto Laporan Pelapor -->
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 flex flex-col justify-between space-y-3 shadow-xs">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 uppercase tracking-wide">
                                                    1. Foto Pelapor
                                                </span>
                                                <span v-if="reporterPhoto" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                    <CheckCircle2 class="h-3 w-3" /> Ada Foto
                                                </span>
                                                <span v-else class="text-[10px] text-slate-400 italic">Kosong</span>
                                            </div>

                                            <!-- If Photo Exists -->
                                            <div v-if="reporterPhoto" class="relative aspect-video rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 group cursor-pointer" @click="openLightbox(reporterPhoto.file_path)">
                                                <video v-if="isVideo(reporterPhoto.file_path)" :src="reporterPhoto.file_path" class="w-full h-full object-cover"></video>
                                                <img v-else :src="reporterPhoto.file_path" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Pelapor" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <Eye class="h-5 w-5" />
                                                </div>
                                            </div>

                                            <!-- If Photo Empty (Clickable Upload Zone) -->
                                            <button
                                                v-else
                                                type="button"
                                                @click="triggerSlotUpload('reporter')"
                                                :disabled="isProcessingSlot.reporter"
                                                class="w-full aspect-video rounded-lg border-2 border-dashed border-slate-200 hover:border-emerald-500 dark:border-slate-800 dark:hover:border-emerald-400 bg-slate-50/50 hover:bg-emerald-50/30 dark:bg-slate-950/40 dark:hover:bg-emerald-950/20 transition-all flex flex-col items-center justify-center p-3 text-center cursor-pointer group disabled:opacity-50"
                                            >
                                                <Camera v-if="!isProcessingSlot.reporter" class="h-6 w-6 text-slate-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 transition-colors mb-1" />
                                                <RefreshCw v-else class="h-6 w-6 text-emerald-500 animate-spin mb-1" />
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                                                    {{ isProcessingSlot.reporter ? 'Mengunggah...' : 'Unggah Foto Pelapor' }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 mt-0.5">Klik untuk pilih 1 foto</span>
                                            </button>
                                        </div>

                                        <!-- Actions if Photo Exists -->
                                        <div v-if="reporterPhoto" class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                            <button
                                                type="button"
                                                @click="triggerSlotUpload('reporter')"
                                                :disabled="isProcessingSlot.reporter"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                            >
                                                <RefreshCw :class="['h-3 w-3', isProcessingSlot.reporter ? 'animate-spin text-emerald-500' : '']" />
                                                <span>{{ isProcessingSlot.reporter ? 'Mengganti...' : 'Ganti Foto' }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDeleteSlot('reporter', 'Foto Laporan Pelapor')"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition flex items-center justify-center gap-1.5 cursor-pointer"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Slot 2: Foto Teknisi Hadir -->
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 flex flex-col justify-between space-y-3 shadow-xs">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800 uppercase tracking-wide">
                                                    2. Foto Teknisi Hadir
                                                </span>
                                                <span v-if="arrivalPhoto" class="text-[10px] text-blue-600 dark:text-blue-400 font-bold flex items-center gap-1">
                                                    <CheckCircle2 class="h-3 w-3" /> Ada Foto
                                                </span>
                                                <span v-else class="text-[10px] text-slate-400 italic">Kosong</span>
                                            </div>

                                            <!-- If Photo Exists -->
                                            <div v-if="arrivalPhoto" class="relative aspect-video rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 group cursor-pointer" @click="openLightbox(arrivalPhoto.file_path)">
                                                <video v-if="isVideo(arrivalPhoto.file_path)" :src="arrivalPhoto.file_path" class="w-full h-full object-cover"></video>
                                                <img v-else :src="arrivalPhoto.file_path" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Teknisi Hadir" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <Eye class="h-5 w-5" />
                                                </div>
                                            </div>

                                            <!-- If Photo Empty (Clickable Upload Zone) -->
                                            <button
                                                v-else
                                                type="button"
                                                @click="triggerSlotUpload('arrival')"
                                                :disabled="isProcessingSlot.arrival"
                                                class="w-full aspect-video rounded-lg border-2 border-dashed border-slate-200 hover:border-blue-500 dark:border-slate-800 dark:hover:border-blue-400 bg-slate-50/50 hover:bg-blue-50/30 dark:bg-slate-950/40 dark:hover:bg-blue-950/20 transition-all flex flex-col items-center justify-center p-3 text-center cursor-pointer group disabled:opacity-50"
                                            >
                                                <Camera v-if="!isProcessingSlot.arrival" class="h-6 w-6 text-slate-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors mb-1" />
                                                <RefreshCw v-else class="h-6 w-6 text-blue-500 animate-spin mb-1" />
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                                    {{ isProcessingSlot.arrival ? 'Mengunggah...' : 'Unggah Foto Kehadiran' }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 mt-0.5">Klik untuk pilih 1 foto</span>
                                            </button>
                                        </div>

                                        <!-- Actions if Photo Exists -->
                                        <div v-if="arrivalPhoto" class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                            <button
                                                type="button"
                                                @click="triggerSlotUpload('arrival')"
                                                :disabled="isProcessingSlot.arrival"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                            >
                                                <RefreshCw :class="['h-3 w-3', isProcessingSlot.arrival ? 'animate-spin text-blue-500' : '']" />
                                                <span>{{ isProcessingSlot.arrival ? 'Mengganti...' : 'Ganti Foto' }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDeleteSlot('arrival', 'Foto Bukti Hadir Teknisi')"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition flex items-center justify-center gap-1.5 cursor-pointer"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Slot 3: Foto Penyelesaian -->
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3.5 flex flex-col justify-between space-y-3 shadow-xs">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800 uppercase tracking-wide">
                                                    3. Foto Penyelesaian
                                                </span>
                                                <span v-if="completionPhoto" class="text-[10px] text-teal-600 dark:text-teal-400 font-bold flex items-center gap-1">
                                                    <CheckCircle2 class="h-3 w-3" /> Ada Foto
                                                </span>
                                                <span v-else class="text-[10px] text-slate-400 italic">Kosong</span>
                                            </div>

                                            <!-- If Photo Exists -->
                                            <div v-if="completionPhoto" class="relative aspect-video rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 group cursor-pointer" @click="openLightbox(completionPhoto.file_path)">
                                                <video v-if="isVideo(completionPhoto.file_path)" :src="completionPhoto.file_path" class="w-full h-full object-cover"></video>
                                                <img v-else :src="completionPhoto.file_path" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Penyelesaian" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <Eye class="h-5 w-5" />
                                                </div>
                                            </div>

                                            <!-- If Photo Empty (Clickable Upload Zone) -->
                                            <button
                                                v-else
                                                type="button"
                                                @click="triggerSlotUpload('completion')"
                                                :disabled="isProcessingSlot.completion"
                                                class="w-full aspect-video rounded-lg border-2 border-dashed border-slate-200 hover:border-teal-500 dark:border-slate-800 dark:hover:border-teal-400 bg-slate-50/50 hover:bg-teal-50/30 dark:bg-slate-950/40 dark:hover:bg-teal-950/20 transition-all flex flex-col items-center justify-center p-3 text-center cursor-pointer group disabled:opacity-50"
                                            >
                                                <Camera v-if="!isProcessingSlot.completion" class="h-6 w-6 text-slate-400 group-hover:text-teal-500 dark:group-hover:text-teal-400 transition-colors mb-1" />
                                                <RefreshCw v-else class="h-6 w-6 text-teal-500 animate-spin mb-1" />
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-teal-600 dark:group-hover:text-teal-400">
                                                    {{ isProcessingSlot.completion ? 'Mengunggah...' : 'Unggah Foto Selesai' }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 mt-0.5">Klik untuk pilih 1 foto</span>
                                            </button>
                                        </div>

                                        <!-- Actions if Photo Exists -->
                                        <div v-if="completionPhoto" class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                            <button
                                                type="button"
                                                @click="triggerSlotUpload('completion')"
                                                :disabled="isProcessingSlot.completion"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                            >
                                                <RefreshCw :class="['h-3 w-3', isProcessingSlot.completion ? 'animate-spin text-teal-500' : '']" />
                                                <span>{{ isProcessingSlot.completion ? 'Mengganti...' : 'Ganti Foto' }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="confirmDeleteSlot('completion', 'Foto Bukti Penyelesaian')"
                                                class="h-8 px-2 rounded-lg text-[11px] font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition flex items-center justify-center gap-1.5 cursor-pointer"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Submit Form Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="resetEditForm"
                                    class="h-11 px-5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="h-11 px-6 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 transition flex items-center gap-2 shadow-sm disabled:opacity-50"
                                >
                                    <Save class="h-4 w-4" />
                                    <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Audit' }}</span>
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- VIEW MODE: SLA Metrics & Ticket Details (Identical to UnitHeadShow.vue) -->
                    <template v-else>
                        <!-- SLA Metrics Cards (Above Ticket Info in View Mode) -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Metrik Waktu & SLA
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
                                            Waktu Respon
                                        </div>
                                        <div class="text-[10px] font-medium leading-none">
                                            <span v-if="ticket.responded_at" class="text-slate-500 dark:text-slate-400">
                                                Teknisi telah tiba di lokasi
                                            </span>
                                            <span v-else-if="ticket.validated_at" class="text-emerald-600 dark:text-emerald-400 animate-pulse font-bold">
                                                Menghitung Waktu Respon...
                                            </span>
                                            <span v-else class="text-slate-400 dark:text-slate-500">
                                                Menunggu Validasi
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
                                            Total Waktu Tertunda
                                        </div>
                                        <div class="text-[10px] font-medium leading-none">
                                            <span v-if="ticket.status === 'PENDING'" class="text-orange-600 dark:text-orange-400 animate-pulse font-bold">
                                                Sedang Tertunda (Pending)
                                            </span>
                                            <span v-else class="text-slate-400 dark:text-slate-500">
                                                Akumulasi Penundaan
                                            </span>
                                        </div>
                                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 pt-0.5">
                                            {{ formatDuration(pausedDurationSeconds) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Resolution Time Card -->
                                <div class="p-3.5 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-950/20 flex items-center gap-3.5">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-100 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400 shrink-0">
                                        <CheckCircle2 class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 min-w-0 space-y-0.5">
                                        <div class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide leading-tight">
                                            Durasi Pengerjaan
                                        </div>
                                        <div class="text-[10px] font-medium leading-none">
                                            <span v-if="ticket.resolved_at" class="text-slate-500 dark:text-slate-400">
                                                Pekerjaan Selesai
                                            </span>
                                            <span v-else-if="ticket.status === 'PENDING'" class="text-orange-600 dark:text-orange-400 font-bold">
                                                Pekerjaan Ditunda
                                            </span>
                                            <span v-else-if="ticket.responded_at" class="text-emerald-600 dark:text-emerald-400 animate-pulse font-bold">
                                                Sedang Dikerjakan...
                                            </span>
                                            <span v-else class="text-slate-400 dark:text-slate-500">
                                                Menunggu Respon Teknisi
                                            </span>
                                        </div>
                                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 pt-0.5">
                                            {{ formatDuration(resolutionTimeSeconds) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Details Container -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Informasi Laporan
                            </h3>

                        <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                        Pelapor
                                    </div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-white uppercase leading-tight">
                                        {{ ticket.reporter?.name || '-' }}
                                    </div>
                                    <div v-if="ticket.reporter?.nip" class="text-[10px] text-slate-400">
                                        NIP: {{ ticket.reporter.nip }}
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
                                        Lokasi Ruangan
                                    </div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                        {{ ticket.room?.name || '-' }} <span v-if="formatRoomDetails(ticket.room)" class="text-slate-400 dark:text-slate-500">({{ formatRoomDetails(ticket.room) }})</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                        Kategori Permasalahan
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

                                <div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                        Prioritas Tiket
                                    </div>
                                    <div class="mt-0.5">
                                        <span v-if="!getPriority(ticket).isPending" :class="['px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide uppercase border', getPriority(ticket).badge]">
                                            {{ getPriority(ticket).label }}
                                        </span>
                                        <span v-else class="text-slate-400 font-bold text-xs">-</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                        Waktu Pelaporan
                                    </div>
                                    <div class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight">
                                        {{ formatDateTime(ticket.created_at) }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider mb-0.5">
                                        Status Audit Hapus
                                    </div>
                                    <div class="mt-0.5">
                                        <span v-if="ticket.deleted_at" class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-900/50">
                                            Terhapus: {{ formatDateTime(ticket.deleted_at) }}
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50 dark:bg-white/10 dark:text-white">
                                            Aktif di Sistem
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Problem Description -->
                        <div class="space-y-2">
                            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                Penjelasan Masalah
                            </span>
                            <div class="bg-slate-50/50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 text-slate-800 dark:text-slate-200 text-sm font-medium leading-relaxed whitespace-pre-line">
                                {{ ticket.problem_description }}
                            </div>
                        </div>

                        <!-- Reporter Attachments Media Grid (Clean View Mode) -->
                        <div class="space-y-3">
                            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                Lampiran Foto / Media Pelapor
                            </span>
                            <div v-if="reporterAttachments.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-2">
                                <div 
                                    v-for="att in reporterAttachments" 
                                    :key="att.id" 
                                    class="relative rounded-lg overflow-hidden border border-slate-100 dark:border-slate-800 aspect-square cursor-pointer bg-slate-50 dark:bg-slate-950/55 group shadow-sm"
                                    @click="openLightbox(att.file_path)"
                                >
                                    <video 
                                        v-if="isVideo(att.file_path)" 
                                        :src="att.file_path" 
                                        controls 
                                        class="w-full h-full object-cover"
                                    ></video>
                                    <img v-else :src="att.file_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" alt="Reporter attachment" />
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-400 dark:text-slate-500 italic p-3 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/20 dark:bg-slate-950/10">
                                Tidak ada foto/video lampiran pelapor
                            </div>
                        </div>

                        <!-- Validation Info & Assigned Techs -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                    Validator / Disposisi
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-800 dark:text-slate-200">
                                    <UserCheck class="h-4 w-4 text-slate-400 shrink-0" />
                                    <span class="text-xs font-semibold">{{ ticket.validator?.name || 'Sistem (Disposisi Otomatis)' }}</span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 tracking-wider">
                                    Teknisi yang Ditugaskan
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
                                        Belum ada teknisi ditugaskan
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    </template>

                </div>

                <!-- Right Column: Status Timelines -->
                <div class="space-y-4">
                    
                    <!-- Status Timeline Tracking -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Jejak Riwayat & Status
                            </h3>
                            <History class="h-4 w-4 text-slate-400" />
                        </div>

                        <div class="flow-root pt-1">
                            <ul>
                                <!-- Node 1: Created -->
                                <li>
                                    <div class="relative pb-6">
                                        <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', ticket.validated_at ? 'bg-emerald-500 dark:bg-white/20' : 'bg-slate-200 dark:bg-slate-800']" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-emerald-500 dark:bg-white flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white dark:text-slate-900">
                                                    <FileText class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    Laporan Dibuat
                                                </p>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                                    Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ ticket.reporter?.name }}</span>
                                                </p>
                                                <div class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
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
                                                    ticket.validated_at ? 'bg-emerald-500 dark:bg-white text-white dark:text-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500'
                                                ]">
                                                    <UserCheck class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                <p class="text-xs font-bold" :class="ticket.validated_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'">
                                                    Disposisi & Ditugaskan
                                                </p>
                                                <p v-if="ticket.validated_at" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                    Validator: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ ticket.validator?.name || 'Sistem' }}</span>
                                                    <br />
                                                    Teknisi: <span class="font-semibold text-slate-700 dark:text-slate-300">
                                                        {{ ticket.assignments?.map(a => a.technician?.name).filter(Boolean).join(', ') || '-' }}
                                                    </span>
                                                </p>
                                                <p v-else class="text-[10px] text-slate-400">
                                                    Menunggu disposisi
                                                </p>
                                                <div v-if="ticket.validated_at" class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
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
                                                    ticket.responded_at ? 'bg-emerald-500 dark:bg-white text-white dark:text-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500'
                                                ]">
                                                    <Activity class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                <p class="text-xs font-bold" :class="ticket.responded_at ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'">
                                                    Teknisi Tiba di Lokasi
                                                </p>
                                                <!-- Arrival Attachments (Clean View Mode) -->
                                                <div v-if="ticket.responded_at && arrivalAttachments.length > 0" class="grid grid-cols-3 gap-1.5 mt-2">
                                                    <div 
                                                        v-for="att in arrivalAttachments" 
                                                        :key="att.id" 
                                                        class="relative rounded-lg overflow-hidden border border-slate-100 dark:border-slate-800 aspect-square cursor-pointer"
                                                        @click="openLightbox(att.file_path)"
                                                    >
                                                        <img :src="att.file_path" class="w-full h-full object-cover" alt="Arrival proof" />
                                                    </div>
                                                </div>
                                                <div v-if="ticket.responded_at" class="text-[10px] font-medium text-slate-400 flex items-center gap-1 pt-0.5">
                                                    <Clock class="h-3 w-3 shrink-0" />
                                                    <span>{{ formatDateTime(ticket.responded_at) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Dynamic Pending Histories -->
                                <template v-if="pendingHistories.length > 0">
                                    <li v-for="hist in pendingHistories" :key="hist.id">
                                        <div class="relative pb-6">
                                            <span :class="['absolute top-4 left-4 -ml-px h-full w-0.5', hist.action === 'PAUSED' || hist.status === 'PENDING' ? 'bg-orange-300 dark:bg-orange-900/60' : 'bg-emerald-300 dark:bg-emerald-900/60']" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span v-if="hist.action === 'PAUSED' || hist.status === 'PENDING'" class="h-8 w-8 rounded-full bg-orange-500 flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white">
                                                        <Pause class="h-4 w-4" />
                                                    </span>
                                                    <span v-else class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white">
                                                        <Play class="h-4 w-4" />
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-0.5 space-y-1.5">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-xs font-bold text-orange-600 dark:text-orange-400">
                                                            {{ hist.action === 'PAUSED' || hist.status === 'PENDING' ? 'Pekerjaan Ditangguhkan' : 'Pekerjaan Dilanjutkan' }}
                                                        </p>
                                                        <span v-if="hist.duration_seconds > 0" class="text-[10px] font-semibold bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 px-2 py-0.5 rounded-full border border-orange-200 dark:border-orange-900/50 shrink-0">
                                                            {{ formatDuration(hist.duration_seconds) }}
                                                        </span>
                                                    </div>
                                                    <div v-if="hist.notes" class="p-2.5 rounded-xl bg-orange-50/70 dark:bg-orange-950/30 border border-orange-100 dark:border-orange-900/40 text-[11px] text-orange-950 dark:text-orange-200">
                                                        <p class="italic">"{{ hist.notes }}"</p>
                                                    </div>
                                                    <div class="text-[10px] font-medium text-slate-400 flex items-center gap-1">
                                                        <Clock class="h-3 w-3 shrink-0" />
                                                        <span>{{ formatDateTime(hist.created_at) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </template>

                                <!-- Node 4: Resolved / Cancelled -->
                                <li>
                                    <div class="relative pb-0">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900 text-white',
                                                    ticket.status === 'COMPLETED' ? 'bg-emerald-500 dark:bg-white dark:text-slate-900' : (ticket.status === 'CANCEL' ? 'bg-rose-500' : 'bg-slate-200 dark:bg-slate-800 text-slate-400')
                                                ]">
                                                    <CheckCircle2 v-if="ticket.status === 'COMPLETED'" class="h-4 w-4" />
                                                    <XCircle v-else-if="ticket.status === 'CANCEL'" class="h-4 w-4" />
                                                    <CheckCircle2 v-else class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-0.5 space-y-1">
                                                <p class="text-xs font-bold" :class="ticket.status === 'COMPLETED' || ticket.status === 'CANCEL' ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400'">
                                                    {{ ticket.status === 'CANCEL' ? 'Laporan Dibatalkan' : (ticket.status === 'COMPLETED' ? 'Laporan Selesai Dikerjakan' : 'Menunggu Penyelesaian') }}
                                                </p>
                                                <p v-if="ticket.status === 'COMPLETED' && completedBy" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                    Diselesaikan Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ completedBy }}</span>
                                                </p>
                                                <p v-else-if="ticket.status === 'CANCEL' && cancelledBy" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                    Dibatalkan Oleh: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ cancelledBy }}</span>
                                                </p>
                                                <p v-if="ticket.completion_notes" class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ ticket.status === 'CANCEL' ? 'Alasan Pembatalan:' : 'Catatan Tindakan:' }}</span> {{ ticket.completion_notes }}
                                                </p>
                                                <!-- Completion Attachments (Clean View Mode) -->
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
                                                <div v-if="ticket.resolved_at" class="text-[10px] font-medium text-slate-400 flex items-center gap-1 pt-0.5">
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

        <!-- Lightbox Fullscreen Modal -->
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
                    class="absolute top-2 right-2 h-9 w-9 rounded-full bg-black/60 hover:bg-black/80 flex items-center justify-center text-white transition"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
        </div>

        <!-- SLA Info Modal -->
        <Teleport to="body">
            <div v-if="showSlaInfoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm animate-spa-fade-in">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-white/10 dark:text-white flex items-center justify-center">
                                <Info class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                Penjelasan Metrik Waktu Penanganan
                            </h3>
                        </div>
                        <button type="button" @click="showSlaInfoModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="p-5 space-y-3.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 space-y-1">
                            <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                <Clock class="h-3.5 w-3.5 text-emerald-500" />
                                <span>Waktu Respon</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                                Dihitung sejak laporan <strong>didisposisikan/divalidasi</strong> hingga teknisi pertama kali menekan <strong>"Saya sudah di lokasi"</strong>.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 space-y-1">
                            <div class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                <Pause class="h-3.5 w-3.5 text-orange-500" />
                                <span>Total Waktu Tertunda</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                                Akumulasi durasi saat pengerjaan tiket di-pause/pending (misal: menunggu ketersediaan suku cadang atau bahan).
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 space-y-1">
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

        <!-- Hidden Single File Input for 3-Slot Photo Management -->
        <input 
            ref="fileInputSlotRef" 
            type="file" 
            accept="image/*" 
            class="hidden" 
            @change="handleSlotFileChange" 
        />
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

/* Flatpickr Custom Styling to match dark mode and application theme */
:deep(.flatpickr-calendar) {
    width: 100% !important;
    max-width: 290px !important;
    min-width: unset !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important;
    font-family: inherit !important;
    padding: 8px !important;
    z-index: 9999 !important;
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
:deep(.flatpickr-time) {
    border-top: 1px solid #e2e8f0 !important;
    margin-top: 6px !important;
    padding-top: 4px !important;
}
:deep(.dark .flatpickr-time) {
    border-top-color: #1e293b !important;
}
:deep(.flatpickr-time input) {
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #334155 !important;
}
:deep(.dark .flatpickr-time input) {
    color: #e2e8f0 !important;
}
</style>
