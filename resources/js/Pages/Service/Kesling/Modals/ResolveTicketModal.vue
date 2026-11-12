<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    CheckCircle2, 
    Image as ImageIcon,
    X,
    FileText,
    Wrench,
    Clock,
    AlertCircle,
    AlertTriangle,
    MapPin,
    Phone,
    User,
    Check,
    Maximize2,
    Camera,
    Trash2,
    Info
} from '@lucide/vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    ticket: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'ticketUpdated']);

const localStatus = ref(props.ticket?.status || 'MENUNGGU');

watch(() => props.ticket?.status, (newStatus) => {
    if (newStatus) {
        localStatus.value = newStatus;
    }
}, { immediate: true });

// Status check helpers (Bereaksi langsung seketika status diupdate secara reaktif)
const currentStatus = computed(() => {
    return (localStatus.value || props.ticket?.status || 'MENUNGGU').toUpperCase();
});

const isResolved = computed(() => {
    const s = currentStatus.value;
    return s === 'SELESAI' || s === 'RESOLVED' || s === 'CLOSED';
});

const isInProgress = computed(() => {
    const s = currentStatus.value;
    return s === 'DIKERJAKAN' || s === 'IN_PROGRESS';
});

const isPending = computed(() => {
    return !isResolved.value && !isInProgress.value;
});

// Urgency badge helper
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

// Location formatter helper
const formatTicketLocation = (ticket) => {
    if (!ticket) return '-';
    const point = ticket.location_point || 'Toilet Fasilitas';
    if (point.includes('(') && point.includes(')')) {
        return point;
    }
    const b = ticket.location_building;
    const f = ticket.location_floor;
    if (b && f) {
        return `${point} (${b} - ${f})`;
    }
    return point;
};

// File upload & preview state for Evidence After
const afterPhotoPreview = ref(null);
const fileInputRef = ref(null);
const cameraInputRef = ref(null);
const isStartingProgress = ref(false);

const triggerCamera = () => {
    if (cameraInputRef.value) cameraInputRef.value.click();
};

const triggerGallery = () => {
    if (fileInputRef.value) fileInputRef.value.click();
};

// Lightbox Photo Preview (Menggunakan native dialog showModal agar tampil di atas modal verifikasi)
const previewPhotoUrl = ref(null);
const previewPhotoTitle = ref('');
const previewDialogRef = ref(null);
let pushPhotoHistoryFlag = false;
let ignoreNextPopState = false;
let ignoreTimer = null;

const openPhotoPreview = (url, title) => {
    if (!url) return;
    previewPhotoUrl.value = url;
    previewPhotoTitle.value = title || 'Lihat Foto Bukti';
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

watch(() => props.show, (newVal) => {
    if (!newVal) {
        closePhotoPreview(false);
    }
});

// Intercept popstate (Browser Back) agar menutup preview foto tanpa menutup modal utama
const handleLightboxPopState = (e) => {
    if (ignoreNextPopState) {
        ignoreNextPopState = false;
        clearTimeout(ignoreTimer);
        if (e) {
            e.__preventModalClose = true;
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
        return;
    }

    if (previewPhotoUrl.value) {
        if (e) {
            e.__preventModalClose = true;
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
        closePhotoPreview(false);
    }
};

// Tangkap ESC pada capture phase di document agar TIDAK menutup modal utama
const handleLightboxKeyDown = (e) => {
    if (e.key === 'Escape' && previewPhotoUrl.value) {
        e.__preventModalClose = true;
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        closePhotoPreview(true);
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleLightboxKeyDown, true);
        window.addEventListener('popstate', handleLightboxPopState, true);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleLightboxKeyDown, true);
        window.removeEventListener('popstate', handleLightboxPopState, true);
    }
    clearTimeout(ignoreTimer);
    if (previewDialogRef.value && previewDialogRef.value.open) {
        try {
            previewDialogRef.value.close();
        } catch (e) {}
    }
});

// Backward compatibility alias
const openZoomPhoto = openPhotoPreview;
const closeZoomPhoto = closePhotoPreview;

// Media helpers (support single string & multi-file array)
const beforePhotos = computed(() => {
    const list = props.ticket?.evidence_before_list;
    if (Array.isArray(list) && list.length > 0) return list;
    return props.ticket?.evidence_before ? [props.ticket.evidence_before] : [];
});

const afterPhotosList = computed(() => {
    const list = props.ticket?.evidence_after_list;
    if (Array.isArray(list) && list.length > 0) return list;
    return props.ticket?.evidence_after ? [props.ticket.evidence_after] : [];
});

// Form for Resolution (Multi-file upload)
const afterUploadFiles = ref([]);
const resolveForm = useForm({
    resolution_notes: '',
    evidence_after: '',
    evidence_after_files: [],
});

watch(() => props.ticket, (t) => {
    if (t) {
        resolveForm.resolution_notes = t.resolution_notes || '';
        resolveForm.evidence_after = t.evidence_after || '';
        resolveForm.evidence_after_files = [];
        afterUploadFiles.value = [];
        afterPhotoPreview.value = t.evidence_after || null;
    } else {
        resolveForm.reset();
        afterUploadFiles.value = [];
        afterPhotoPreview.value = null;
    }
}, { immediate: true });

// Handle multi-file selection
const handleFileChange = (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    const maxFiles = 5;
    const remainingSlots = maxFiles - afterUploadFiles.value.length;
    const filesToAdd = files.slice(0, Math.max(0, remainingSlots));

    filesToAdd.forEach((file) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            afterUploadFiles.value.push({
                file,
                name: file.name,
                previewUrl: event.target.result,
            });
            resolveForm.evidence_after_files = afterUploadFiles.value.map(item => item.file);
        };
        reader.readAsDataURL(file);
    });

    e.target.value = '';
};

const removeAfterPhoto = (index) => {
    if (typeof index === 'number') {
        afterUploadFiles.value.splice(index, 1);
    } else {
        afterUploadFiles.value = [];
    }
    resolveForm.evidence_after_files = afterUploadFiles.value.map(item => item.file);
    if (afterUploadFiles.value.length === 0) {
        resolveForm.evidence_after = '';
        afterPhotoPreview.value = null;
        if (fileInputRef.value) fileInputRef.value.value = '';
        if (cameraInputRef.value) cameraInputRef.value.value = '';
    }
};

const page = usePage();
const currentUserId = computed(() => Number(page.props.auth?.user?.id || 0));
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    return Number(user?.role_id) === 1 || user?.role?.name?.toUpperCase() === 'ADMINISTRATOR';
});

const isClaimedByOther = computed(() => {
    if (!isInProgress.value) return false;
    return !!props.ticket?.started_by && Number(props.ticket.started_by) !== currentUserId.value && !isAdmin.value;
});

// Flow 1: Start Working on Ticket (Ubah status ke DIKERJAKAN dengan atomic claim)
const startProgress = () => {
    if (!props.ticket || isStartingProgress.value) return;
    isStartingProgress.value = true;

    router.post(route('pansinar.tickets.start', props.ticket.id), {}, {
        preserveScroll: true,
        onSuccess: (resp) => {
            if (resp?.props?.flash?.error) {
                // Diklaim duluan oleh petugas lain
                localStatus.value = props.ticket?.status || 'DIKERJAKAN';
                return;
            }
            localStatus.value = 'DIKERJAKAN';
            if (props.ticket) {
                props.ticket.status = 'DIKERJAKAN';
                props.ticket.started_by = currentUserId.value;
                props.ticket.starter_name = page.props.auth?.user?.name || 'Anda';
            }
            emit('ticketUpdated', { id: props.ticket.id, status: 'DIKERJAKAN' });
        },
        onError: () => {
            if (props.ticket) {
                localStatus.value = props.ticket.status || 'MENUNGGU';
            }
        },
        onFinish: () => {
            isStartingProgress.value = false;
        }
    });
};

const isEmergency = computed(() => {
    return props.ticket?.urgency === 'DARURAT';
});

// Flow 2: Complete & Verify Ticket (Wajib ada catatan, foto opsional jika darurat)
const canResolve = computed(() => {
    const hasNotes = !!resolveForm.resolution_notes && resolveForm.resolution_notes.trim().length >= 5;
    if (isEmergency.value) {
        // Pada situasi darurat, foto bersifat opsional demi menjaga privasi & etika pasien
        return hasNotes;
    }
    const hasPhoto = afterUploadFiles.value.length > 0 || (!!resolveForm.evidence_after && resolveForm.evidence_after.trim() !== '');
    return hasPhoto && hasNotes;
});

const submitResolution = () => {
    if (!props.ticket || isResolved.value || !canResolve.value) return;

    resolveForm.evidence_after_files = afterUploadFiles.value.map(item => item.file);
    resolveForm.post(route('pansinar.tickets.resolve', props.ticket.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            localStatus.value = 'SELESAI';
            if (props.ticket) {
                props.ticket.status = 'SELESAI';
            }
            emit('ticketUpdated', { id: props.ticket.id, status: 'SELESAI' });
            resolveForm.reset();
            afterUploadFiles.value = [];
            afterPhotoPreview.value = null;
            emit('close');
        }
    });
};
</script>

<template>
    <Modal :show="show && !!ticket" @close="emit('close')" max-width="2xl">
        <div v-if="ticket" class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900 justify-between">
            
            <!-- 1. HEADER MODAL -->
            <div :class="[
                'text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-20 shrink-0 border-b shadow-sm transition-colors',
                isEmergency 
                    ? 'bg-rose-600 dark:bg-rose-950/90 border-rose-500/30 dark:border-rose-800/50' 
                    : 'bg-emerald-600 dark:bg-emerald-950/90 border-emerald-500/30 dark:border-emerald-800/50'
            ]">
                <div class="flex items-center gap-3 pr-2 min-w-0">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <CheckCircle2 v-if="isResolved" class="h-5 w-5 text-white" />
                        <AlertCircle v-else-if="isEmergency" class="h-5 w-5 text-white animate-pulse" />
                        <Wrench v-else-if="isInProgress" class="h-5 w-5 text-white" />
                        <AlertCircle v-else class="h-5 w-5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight truncate">
                                {{ isResolved ? 'Detail Penyelesaian Aduan' : (isEmergency ? (isInProgress ? 'Penanganan Bantuan Darurat' : 'Panggilan Bantuan Darurat') : (isInProgress ? 'Proses Pengerjaan Aduan' : 'Verifikasi Aduan Masuk')) }}
                            </h3>
                        </div>
                        <p :class="[
                            'text-[11px] font-medium leading-tight mt-0.5 truncate',
                            isEmergency ? 'text-rose-100/90 dark:text-rose-200' : 'text-emerald-100/90 dark:text-slate-300'
                        ]">
                            {{ ticket.ticket_number }} &bull; {{ ticket.location_point }}
                        </p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="emit('close')"
                    :class="[
                        'p-1 rounded-lg transition cursor-pointer shrink-0',
                        isEmergency ? 'text-rose-100 hover:text-white hover:bg-white/10' : 'text-emerald-100 hover:text-white hover:bg-white/10'
                    ]"
                    title="Tutup Modal"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- 2. BODY CONTENT (SCROLLABLE) -->
            <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-170px)] sm:max-h-[70vh] overscroll-contain touch-pan-y">
                
                <!-- STEPPER PROGRESS TRACKER (DENGAN BAR STEP SEPERTI ADUAN MASYARAKAT) -->
                <div class="bg-slate-50 dark:bg-slate-950/60 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                    <div class="grid grid-cols-3 relative">
                        <!-- Connecting Progress Line -->
                        <div class="absolute top-[18px] left-[16.66%] right-[16.66%] -translate-y-1/2 h-1 bg-slate-200 dark:bg-slate-800 z-0">
                            <div 
                                class="h-full bg-emerald-500 transition-all duration-300 rounded-full"
                                :style="{ width: isPending ? '0%' : (isInProgress ? '50%' : '100%') }"
                            ></div>
                        </div>

                        <!-- Step 1: Menunggu -->
                        <div class="flex flex-col items-center text-center z-10 relative">
                            <div 
                                :class="[
                                    'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-slate-50 dark:ring-slate-950',
                                    isPending 
                                        ? 'bg-orange-500 text-white border-orange-500 shadow-sm' 
                                        : 'bg-emerald-600 text-white border-emerald-600'
                                ]"
                            >
                                <Check v-if="!isPending" class="h-4 w-4 stroke-[3]" />
                                <span v-else>1</span>
                            </div>
                            <span :class="[
                                'text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide',
                                isPending ? 'text-orange-600 dark:text-orange-400' : 'text-slate-700 dark:text-slate-200'
                            ]">
                                Menunggu
                            </span>
                        </div>

                        <!-- Step 2: Dikerjakan -->
                        <div class="flex flex-col items-center text-center z-10 relative">
                            <div 
                                :class="[
                                    'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-slate-50 dark:ring-slate-950',
                                    isInProgress 
                                        ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
                                        : (isResolved ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700')
                                ]"
                            >
                                <Check v-if="isResolved" class="h-4 w-4 stroke-[3]" />
                                <Wrench v-else-if="isInProgress" class="h-3.5 w-3.5" />
                                <span v-else>2</span>
                            </div>
                            <span :class="[
                                'text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide',
                                isInProgress ? 'text-blue-600 dark:text-blue-400' : (isResolved ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500')
                            ]">
                                Dikerjakan
                            </span>
                        </div>

                        <!-- Step 3: Selesai -->
                        <div class="flex flex-col items-center text-center z-10 relative">
                            <div 
                                :class="[
                                    'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-slate-50 dark:ring-slate-950',
                                    isResolved 
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' 
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                ]"
                            >
                                <Check v-if="isResolved" class="h-4 w-4 stroke-[3]" />
                                <span v-else>3</span>
                            </div>
                            <span :class="[
                                'text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide',
                                isResolved ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'
                            ]">
                                Selesai
                            </span>
                        </div>
                    </div>
                </div>

                <!-- DETAIL ADUAN PELAPOR (DESAIN KARTU RAPI SEPERTI DI TIKET & CEKLIS HARIAN) -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                    <!-- Section Header -->
                    <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                Detail Aduan Pelapor
                            </h4>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <!-- Status Badge -->
                            <span :class="[
                                'px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold border',
                                isResolved 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400' 
                                    : (isInProgress 
                                        ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400' 
                                        : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400')
                            ]">
                                {{ ticket.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body: Grid Informasi Kartu Modular -->
                    <div class="p-4 sm:p-5 space-y-2.5">
                        
                        <!-- 1. Kotak Tingkat Urgensi -->
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

                        <!-- 2. Kotak Lokasi / Titik Toilet -->
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

                        <!-- 3. Grid 2 Kolom: Pelapor & Waktu Tanggal -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
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
                                <span class="block text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                    {{ ticket.reporter_phone && ticket.reporter_phone.trim() !== '-' && ticket.reporter_phone.trim() !== '' ? ticket.reporter_phone : '-' }}
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
                                <span v-if="ticket.created_at_relative" class="block text-[10px] text-slate-400 font-medium truncate mt-0.5">
                                    {{ ticket.created_at_relative }}
                                </span>
                            </div>
                        </div>

                        <!-- 4. Kotak Uraian Kendala -->
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 min-h-[52px] flex flex-col justify-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block leading-tight mb-1">
                                Uraian Kendala
                            </span>
                            <p class="text-xs text-slate-700 dark:text-slate-200 italic leading-relaxed">
                                "{{ ticket.problem_description }}"
                            </p>
                        </div>

                        <!-- 5. Foto Lampiran Aduan (Kondisi Awal / Before - Mendukung Multi-file) -->
                        <div v-if="beforePhotos.length > 0" class="space-y-1.5">
                            <div class="grid gap-2" :class="beforePhotos.length > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1'">
                                <div 
                                    v-for="(photoUrl, idx) in beforePhotos"
                                    :key="'before-' + idx"
                                    @click="openZoomPhoto(photoUrl, `Foto Bukti Kendala ${beforePhotos.length > 1 ? '#' + (idx + 1) : ''} - ${ticket.ticket_number}`)"
                                    class="group flex items-center justify-between gap-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 hover:border-emerald-500/40 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20 transition-all cursor-pointer select-none min-h-[52px]"
                                    title="Klik untuk memperbesar foto bukti aduan"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 border border-slate-200/80 dark:border-slate-700 shrink-0 relative">
                                            <img 
                                                :src="photoUrl" 
                                                :alt="'Thumbnail Bukti ' + (idx + 1)" 
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                                                loading="lazy"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors block leading-tight">
                                                Foto Bukti Kendala {{ beforePhotos.length > 1 ? `#${idx + 1}` : '' }}
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
                        </div>

                        <!-- Jika tidak ada foto lampiran awal -->
                        <div 
                            v-else 
                            class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50 text-slate-400 min-h-[52px]"
                        >
                            <div class="h-10 w-10 rounded-lg bg-slate-200/60 dark:bg-slate-700/60 flex items-center justify-center shrink-0 text-slate-400">
                                <ImageIcon class="w-4 h-4 text-slate-400" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block leading-tight">
                                    Foto Bukti Kendala
                                </span>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block truncate">
                                    Tidak ada lampiran foto saat pelaporan
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- STATE 2: KETIKA TIKET BERSTATUS 'DIKERJAKAN' (FORM INPUT FOTO AFTER & CATATAN SOLUSI) -->
                <div v-if="isInProgress" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                    <!-- Section Header (Desain Rapi Seragam Seperti Detail Aduan Pelapor) -->
                    <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                {{ isEmergency ? 'Form Laporan Pertolongan & Evakuasi Darurat' : 'Form Penyelesaian & Verifikasi Tiket' }}
                            </h4>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 sm:p-5 space-y-4">

                    <!-- Info Petugas Pengklaim (Anti Double Handling) -->
                    <div v-if="ticket.starter_name" class="p-3 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-900/50 flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-8 w-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <Wrench class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    Ditangani oleh: {{ ticket.starter_name }}
                                </p>
                                <p v-if="ticket.started_at_formatted" class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">
                                    Mulai dikerjakan: {{ ticket.started_at_formatted }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Lock Notice jika sedang ditangani petugas lain (Non-Admin) -->
                    <div v-if="isClaimedByOther" class="p-4 rounded-xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-center space-y-1.5">
                        <p class="text-xs font-extrabold text-amber-800 dark:text-amber-300">
                            Aduan Sedang Ditindaklanjuti Petugas Lain
                        </p>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400 leading-relaxed">
                            Tiket ini sudah diklaim oleh <strong>{{ ticket.starter_name }}</strong>. Form penyelesaian dikunci untuk mencegah penanganan ganda.
                        </p>
                    </div>

                    <template v-else>
                    <!-- 1. Upload Bukti Foto After (Hasil Perbaikan) -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                            {{ isEmergency ? 'Foto Situasi / Lokasi (Opsional)' : 'Foto Bukti Hasil Perbaikan (After)' }} <span v-if="!isEmergency" class="text-rose-500">*</span>
                        </label>

                        <!-- Hidden Inputs (always mounted) -->
                        <input ref="cameraInputRef" type="file" accept="image/*" capture="environment" @change="handleFileChange" class="hidden" />
                        <input ref="fileInputRef" type="file" accept="image/*" multiple @change="handleFileChange" class="hidden" />

                        <!-- Box Upload Pilihan Kamera vs Galeri (Sesuai Ceklis Harian) -->
                        <div v-if="afterUploadFiles.length === 0" 
                             class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5 text-center bg-white dark:bg-slate-900 space-y-3">
                            <div class="flex flex-col items-center gap-1">
                                <div :class="[
                                    'h-10 w-10 rounded-xl flex items-center justify-center',
                                    isEmergency ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400'
                                ]">
                                    <Camera class="w-5 h-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Ambil Foto atau Pilih dari Galeri
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    {{ isEmergency ? 'Opsional: Demi menjaga privasi & etika pasien, foto tidak diwajibkan' : 'Wajib sertakan foto hasil perbaikan sanitasi (bisa pilih beberapa foto, maks. 5)' }}
                                </p>
                            </div>

                            <div class="flex items-center justify-center gap-2 max-w-xs mx-auto pt-0.5">
                                <button
                                    type="button"
                                    @click="triggerCamera"
                                    :class="[
                                        'flex-1 flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl text-white text-xs font-bold transition cursor-pointer shadow-xs active:scale-95',
                                        isEmergency ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'
                                    ]"
                                >
                                    <Camera class="h-3.5 w-3.5 shrink-0" />
                                    <span>Buka Kamera</span>
                                </button>
                                <button
                                    type="button"
                                    @click="triggerGallery"
                                    class="flex-1 flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 active:scale-95 text-xs font-bold transition cursor-pointer"
                                >
                                    <ImageIcon class="h-3.5 w-3.5 shrink-0" />
                                    <span>Pilih Galeri</span>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Foto Terpilih (Multi-file Grid + Toolbar) -->
                        <div v-else class="space-y-2.5">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="(item, idx) in afterUploadFiles"
                                    :key="'after-upload-' + idx"
                                    class="relative aspect-square rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-800 border border-emerald-500/40 group cursor-pointer"
                                    @click="openZoomPhoto(item.previewUrl, `Foto Hasil Perbaikan #${idx + 1}`)"
                                >
                                    <img :src="item.previewUrl" :alt="item.name" class="w-full h-full object-cover" />
                                    <button
                                        type="button"
                                        @click.stop="removeAfterPhoto(idx)"
                                        class="absolute top-1.5 right-1.5 h-6 w-6 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white flex items-center justify-center shadow-sm cursor-pointer transition active:scale-90"
                                        title="Hapus Foto"
                                    >
                                        <X class="w-3.5 h-3.5 stroke-[2.5]" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-emerald-50/30 dark:bg-emerald-950/20 border border-emerald-500/40">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-9 w-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">
                                            {{ afterUploadFiles.length }} / 5 Foto Siap Diverifikasi
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                            {{ afterUploadFiles.length < 5 ? 'Masih bisa tambah foto lagi' : 'Batas maksimal 5 foto tercapai' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto shrink-0">
                                    <button
                                        v-if="afterUploadFiles.length < 5"
                                        type="button"
                                        @click="triggerCamera"
                                        :class="[
                                            'h-9 px-3 rounded-xl text-white text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap',
                                            isEmergency ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'
                                        ]"
                                        title="Tambah Foto Kamera"
                                    >
                                        <Camera class="h-3.5 w-3.5 shrink-0" />
                                        <span>Kamera</span>
                                    </button>
                                    <button
                                        v-if="afterUploadFiles.length < 5"
                                        type="button"
                                        @click="triggerGallery"
                                        class="h-9 px-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap"
                                        title="Tambah dari Galeri"
                                    >
                                        <ImageIcon class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Galeri</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeAfterPhoto()"
                                        class="col-span-2 sm:col-span-1 h-9 px-3 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap"
                                        title="Hapus Semua Foto"
                                    >
                                        <Trash2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Input Catatan Solusi / Tindakan -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 leading-tight">
                            {{ isEmergency ? 'Catatan Tindakan / Pertolongan Pasien' : 'Catatan Penanganan / Solusi' }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            v-model="resolveForm.resolution_notes"
                            rows="2"
                            required
                            :placeholder="isEmergency ? 'Tuliskan tindakan pertolongan atau evakuasi yang telah dilakukan...' : 'Tuliskan tindakan penanganan yang telah dilakukan...'"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 placeholder:text-slate-400"
                        ></textarea>
                        <div v-if="!canResolve" class="flex justify-end text-[10px] text-amber-600 dark:text-amber-400 font-medium">
                            <span>{{ isEmergency ? '* Harap lengkapi catatan tindakan (min. 5 karakter)' : '* Harap lengkapi foto & catatan solusi' }}</span>
                        </div>
                    </div>
                    </template>
                    </div>
                </div>

                <!-- STATE 3: KETIKA TIKET BERSTATUS 'SELESAI' (READONLY HASIL PENYELESAIAN) -->
                <div v-else-if="isResolved" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                    <!-- Section Header -->
                    <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                                Laporan Hasil Penyelesaian & Verifikasi
                            </h4>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 space-y-3">

                    <!-- Dokumentasi Hasil Penyelesaian (After saja, karena kondisi awal sudah tampil di atas) -->
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">
                            Dokumentasi Hasil Penyelesaian
                        </span>
                        <div v-if="afterPhotosList.length > 0" class="grid gap-2" :class="afterPhotosList.length > 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1'">
                            <div 
                                v-for="(photoUrl, idx) in afterPhotosList"
                                :key="'res-after-' + idx"
                                @click="openZoomPhoto(photoUrl, `Foto Hasil Penyelesaian ${afterPhotosList.length > 1 ? '#' + (idx + 1) : ''} - ${ticket.ticket_number}`)"
                                class="group flex items-center justify-between gap-3 p-2.5 rounded-xl bg-emerald-50/40 dark:bg-emerald-950/30 border border-emerald-500/40 hover:border-emerald-500 transition-all cursor-pointer select-none min-h-[52px]"
                                title="Klik untuk memperbesar foto hasil penyelesaian"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-10 w-10 rounded-lg overflow-hidden bg-emerald-100 dark:bg-slate-700 border border-emerald-300/80 shrink-0 relative">
                                        <img :src="photoUrl" :alt="'Hasil Penyelesaian ' + (idx + 1)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200" />
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 transition-colors block leading-tight">
                                            Bukti Penyelesaian {{ afterPhotosList.length > 1 ? `#${idx + 1}` : '' }}
                                        </span>
                                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 block truncate">
                                            Terverifikasi selesai
                                        </span>
                                    </div>
                                </div>
                                <div class="h-7 w-7 rounded-lg bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-700 text-emerald-600 flex items-center justify-center shrink-0">
                                    <Maximize2 class="w-3.5 h-3.5" />
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-800 text-slate-400 min-h-[52px]">
                            <div class="h-10 w-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 text-slate-400">
                                <ImageIcon class="w-4 h-4" />
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block leading-tight">Bukti Penyelesaian</span>
                                <span class="text-[11px] text-slate-400 block mt-0.5">Tidak ada foto dilampirkan</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tindakan Solusi -->
                    <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block mb-1">
                            Tindakan Penanganan yang Dilakukan
                        </span>
                        <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed font-medium">
                            {{ ticket.resolution_notes || 'Fasilitas telah diperiksa dan ditangani sesuai standar sanitasi rumah sakit.' }}
                        </p>
                    </div>
                    </div>
                </div>

            </div>

            <!-- 3. STICKY ACTION FOOTER -->
            <div class="sticky bottom-0 z-20 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-lg sm:shadow-none">
                
                <!-- KONDISI A: TIKET SUDAH SELESAI -->
                <div v-if="isResolved" class="flex justify-end">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="w-full sm:w-auto h-11 px-7 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-700 font-bold text-xs transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

                <!-- KONDISI B: TIKET BERSTATUS MENUNGGU (TOMBOL TINDAK LANJUTI & BATAL) -->
                <div v-else-if="isPending" class="w-full">
                    <!-- Mobile View: Tumpuk (Tindak Lanjuti di atas, Batal di bawah) -->
                    <div class="flex flex-col gap-2.5 w-full sm:hidden">
                        <button
                            type="button"
                            @click="startProgress"
                            :disabled="isStartingProgress"
                            :class="[
                                'flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl active:scale-[0.99] font-bold text-sm text-white transition shadow-sm disabled:opacity-60 disabled:cursor-not-allowed',
                                isEmergency ? 'bg-rose-600 hover:bg-rose-500' : 'bg-amber-600 hover:bg-amber-500'
                            ]"
                        >
                            <span v-if="isStartingProgress" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <AlertCircle v-else-if="isEmergency" class="h-4 w-4" />
                            <Wrench v-else class="h-4 w-4" />
                            <span>{{ isStartingProgress ? 'Memproses...' : (isEmergency ? 'Respon Panggilan Darurat' : 'Tindak Lanjuti') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="emit('close')"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Desktop View: Sejajar (Batal di kiri, Tindak Lanjuti di kanan) -->
                    <div class="hidden sm:flex items-center justify-end gap-3 w-full">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="h-11 px-5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="startProgress"
                            :disabled="isStartingProgress"
                            :class="[
                                'h-11 px-6 rounded-xl active:scale-95 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-2 shadow-sm disabled:opacity-60',
                                isEmergency ? 'bg-rose-600 hover:bg-rose-500' : 'bg-amber-600 hover:bg-amber-500'
                            ]"
                        >
                            <span v-if="isStartingProgress" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <AlertCircle v-else-if="isEmergency" class="w-4 h-4" />
                            <Wrench v-else class="w-4 h-4" />
                            <span>{{ isStartingProgress ? 'Memproses...' : (isEmergency ? 'Respon Panggilan Darurat' : 'Tindak Lanjuti') }}</span>
                        </button>
                    </div>
                </div>

                <!-- KONDISI C1: TIKET SEDANG DIKERJAKAN PETUGAS LAIN (READ-ONLY) -->
                <div v-else-if="isInProgress && isClaimedByOther" class="flex justify-end">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="w-full sm:w-auto h-11 px-7 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-700 font-bold text-xs transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

                <!-- KONDISI C2: TIKET SEDANG DIKERJAKAN (TOMBOL VERIFIKASI / SELESAIKAN) -->
                <div v-else-if="isInProgress" class="w-full">
                    <!-- Mobile View: Tumpuk (Tandai Selesai di atas, Batal di bawah) -->
                    <div class="flex flex-col gap-2.5 w-full sm:hidden">
                        <button
                            type="button"
                            @click="submitResolution"
                            :disabled="!canResolve || resolveForm.processing"
                            :class="[
                                'flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl font-bold text-sm transition shadow-sm',
                                canResolve && !resolveForm.processing 
                                    ? (isEmergency ? 'bg-rose-600 hover:bg-rose-500 active:scale-[0.99] text-white' : 'bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white')
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed opacity-80'
                            ]"
                        >
                            <span v-if="resolveForm.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <CheckCircle2 v-else class="h-4 w-4" />
                            <span>{{ resolveForm.processing ? 'Menyimpan...' : (isEmergency ? 'Selesaikan Penanganan Darurat' : 'Tandai Tiket Selesai') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="emit('close')"
                            :disabled="resolveForm.processing"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99]"
                        >
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Desktop View: Sejajar -->
                    <div class="hidden sm:flex items-center justify-end gap-3 w-full">
                        <button
                            type="button"
                            @click="emit('close')"
                            :disabled="resolveForm.processing"
                            class="h-11 px-5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitResolution"
                            :disabled="!canResolve || resolveForm.processing"
                            :class="[
                                'h-11 px-6 rounded-xl font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm',
                                canResolve && !resolveForm.processing 
                                    ? (isEmergency ? 'bg-rose-600 hover:bg-rose-500 text-white cursor-pointer active:scale-95' : 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer active:scale-95')
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed opacity-80'
                            ]"
                        >
                            <span v-if="resolveForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <CheckCircle2 v-else class="w-4 h-4" />
                            <span>{{ resolveForm.processing ? 'Menyimpan...' : (isEmergency ? 'Selesaikan Penanganan Darurat' : 'Tandai Tiket Selesai') }}</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </Modal>

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
</template>
