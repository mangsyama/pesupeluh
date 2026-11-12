<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { 
    Send, 
    MessageSquare,
    MessageCircle,
    Building2, 
    Sparkles, 
    Check,
    CheckCircle2,
    Copy,
    ArrowRight,
    ArrowLeft,
    FileText,
    RefreshCw,
    Paperclip,
    UploadCloud,
    Shield,
    X,
    User,
    Phone,
    Award,
    Camera,
    Image as ImageIcon,
    Video as VideoIcon,
    Trash2,
    AlertCircle,
    Search,
    Megaphone,
    BellRing,
    PhoneCall,
    Radio,
    ShieldAlert,
    Siren,
    MapPin,
    Clock,
    AlertTriangle,
    Leaf,
    Info,
    ShieldCheck
} from '@lucide/vue';

const props = defineProps({
    toilets: {
        type: Array,
        default: () => []
    },
    toiletData: {
        type: Object,
        default: () => null
    },
    roomId: {
        type: [String, Number],
        default: ''
    },
    initialRoomId: {
        type: [String, Number],
        default: ''
    },
    targetObject: {
        type: String,
        default: ''
    },
    initialTarget: {
        type: String,
        default: ''
    },
    targetLocation: {
        type: String,
        default: ''
    },
    reportMode: {
        type: String,
        default: ''
    },
    initialStep: {
        type: [Number, String],
        default: null
    },
    rooms: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    }
});

// Resolve initial target and room from props or client URL search params
const getInitialParam = (key) => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(key) || '';
    }
    return '';
};

// Cek apakah lokasi toilet sudah otomatis terkunci via scan QR
const isToiletPreset = computed(() => {
    return !!(props.toiletData?.id || props.toiletData?.code || props.targetObject);
});

// List toilet yang tersedia dari Manajemen Toilet (SanitationToilet)
const availableToilets = computed(() => {
    if (props.toilets && props.toilets.length > 0) {
        return props.toilets.map(t => ({
            id: t.id,
            name: t.name,
            building: t.building || '',
            floor: t.floor || '',
            code: t.code || '',
            display: `${t.name} - ${t.building || ''}${t.floor ? ' (' + t.floor + ')' : ''}`
        }));
    }
    if (props.rooms && props.rooms.length > 0) {
        return props.rooms.map(r => ({
            id: r.id,
            name: r.name,
            building: r.building_name || '',
            floor: r.location_floor || '',
            code: r.code || '',
            display: `${r.name} - ${r.building_name || ''}${r.location_floor ? ' (' + r.location_floor + ')' : ''}`
        }));
    }
    return [];
});

// ID toilet terpilih (jika via scan QR langsung terisi, jika URL kosong user memilih lewat dropdown)
const selectedToiletId = ref(props.toiletData?.id ? String(props.toiletData.id) : '');

const selectedToilet = computed(() => {
    if (props.toiletData?.id) return props.toiletData;
    if (selectedToiletId.value) {
        return availableToilets.value.find(t => String(t.id) === String(selectedToiletId.value)) || null;
    }
    return null;
});

const displayTarget = computed(() => {
    if (props.toiletData?.name) return props.toiletData.name;
    if (props.targetObject) return props.targetObject;
    if (selectedToilet.value?.name) return selectedToilet.value.name;
    return '';
});

const displayLocation = computed(() => {
    if (props.targetLocation) return props.targetLocation;
    if (props.toiletData?.building) {
        return `${props.toiletData.building}${props.toiletData.floor ? ' - ' + props.toiletData.floor : ''}`;
    }
    if (selectedToilet.value) {
        const b = selectedToilet.value.building || '';
        const f = selectedToilet.value.floor || '';
        return b && f ? `${b} - ${f}` : (b || f);
    }
    return '';
});

const currentReportLocation = computed(() => {
    if (displayTarget.value && displayLocation.value) {
        return `${displayTarget.value} (${displayLocation.value})`;
    }
    if (displayTarget.value) return displayTarget.value;
    if (displayLocation.value) return displayLocation.value;
    return 'Area Sanitasi & Toilet Rumah Sakit';
});

const trackUrl = computed(() => {
    const code = props.toiletData?.code || props.toiletData?.id || getInitialParam('toilet') || getInitialParam('code');
    if (code) {
        return route('public.sanitasi.track', { toilet: code });
    }
    return route('public.sanitasi.track');
});

const determineInitialStep = () => {
    if (props.initialStep !== null && props.initialStep !== undefined && props.initialStep !== '') {
        return Number(props.initialStep);
    }
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('step')) {
            return Number(urlParams.get('step'));
        }
    }
    return 0;
};

const currentStep = ref(determineInitialStep());
const isSubmitting = ref(false);
const copied = ref(false);
const generatedReportId = ref('');
const isKeyboardOpen = ref(false);
const uploadError = ref('');
const isTransitioning = ref(false);
const isCompressing = ref(false);

const fileInput = ref(null);
const cameraInput = ref(null);
const targetObjectRef = ref(null);
const isiLaporanRef = ref(null);
const reporterNameRef = ref(null);
const reporterPhoneRef = ref(null);
const uploadedAttachments = ref([]);
const hasAttachments = computed(() => uploadedAttachments.value.length > 0);

const checkKeyboard = () => {
    if (window.visualViewport) {
        isKeyboardOpen.value = window.visualViewport.height < window.innerHeight - 150;
    }
};

onMounted(() => {
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', checkKeyboard);
    }
    const tCode = props.toiletData?.code || getInitialParam('toilet') || getInitialParam('code');
    if (tCode && typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem('kesling_active_toilet', tCode);
    }
    if (props.toiletData?.id) {
        selectedToiletId.value = String(props.toiletData.id);
    }
});

onUnmounted(() => {
    if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', checkKeyboard);
    }
});

const autoResizeIsiLaporan = (e) => {
    const el = e?.target || isiLaporanRef.value;
    if (el) {
        el.style.height = 'auto';
        el.style.height = Math.max(el.scrollHeight, 108) + 'px';
    }
};

const isiLaporanPlaceholder = 'Tuliskan detail keluhan sanitasi, kloset mampet, air mati, sabun/tisu habis, atau fasilitas rusak...';

const onIsiLaporanKeydown = (e) => {
    if (e.key === 'Enter') {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            if (form.value.isi_laporan.trim().length >= 5) {
                goToStep2();
            }
            return;
        }

        const isTouch = typeof window !== 'undefined' && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
        if (isTouch && !e.shiftKey && form.value.isi_laporan.trim().length >= 5) {
            e.preventDefault();
            goToStep2();
        }
    }
};

const handleReporterNameEnter = () => {
    reporterPhoneRef.value?.focus();
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'instant' });
    const cardElement = document.querySelector('main');
    if (cardElement) cardElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const form = ref({
    toilet_id: props.toiletData?.id ? String(props.toiletData.id) : '',
    target_object: '',
    isi_laporan: '',
    urgency: 'NORMAL',
    uploaded_files: [],
    reporter_name: '',
    reporter_phone: ''
});

const openFileInput = () => {
    if (isTransitioning.value || isCompressing.value) return;
    uploadError.value = '';
    fileInput.value?.click();
};

const triggerCamera = () => {
    if (isTransitioning.value || isCompressing.value) return;
    uploadError.value = '';
    cameraInput.value?.click();
};

const onPhoneInput = (e) => {
    const raw = e.target.value || '';
    const digitsOnly = raw.replace(/\D/g, '').slice(0, 15);
    form.value.reporter_phone = digitsOnly;
    if (e.target.value !== digitsOnly) {
        e.target.value = digitsOnly;
    }
};

const onPhoneKeyPress = (e) => {
    if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key) && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
    }
};

const compressImage = (file, maxWidth = 1920, maxHeight = 1920, quality = 0.82) => {
    return new Promise((resolve) => {
        if (!file.type.startsWith('image/') || file.type === 'image/gif' || file.type === 'image/svg+xml') {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (!blob || blob.size >= file.size) {
                            return resolve(file);
                        }
                        const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file);
        };
        reader.onerror = () => resolve(file);
    });
};

const onFileSelected = async (event) => {
    uploadError.value = '';
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    const maxFiles = 5;
    const remainingSlots = maxFiles - uploadedAttachments.value.length;
    if (remainingSlots <= 0) {
        uploadError.value = `Maksimal ${maxFiles} file lampiran yang dapat diunggah.`;
        event.target.value = '';
        return;
    }

    const filesToProcess = files.slice(0, remainingSlots);
    if (files.length > remainingSlots) {
        uploadError.value = `Hanya ${remainingSlots} file lagi yang dapat ditambahkan (maksimal ${maxFiles} file).`;
    }

    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'avi', 'webm'];
    isCompressing.value = true;

    try {
        for (const originalFile of filesToProcess) {
            const fileExtension = originalFile.name.split('.').pop()?.toLowerCase() || '';

            if (!allowedExtensions.includes(fileExtension) || (!originalFile.type.startsWith('image/') && !originalFile.type.startsWith('video/'))) {
                uploadError.value = 'Format file tidak didukung! Hanya file Foto (JPG, JPEG, PNG, WEBP) atau Video (MP4, MOV) yang diizinkan.';
                continue;
            }

            if (originalFile.size > 10 * 1024 * 1024) {
                uploadError.value = `Ukuran file "${originalFile.name}" terlalu besar. Maksimal 10MB per file!`;
                continue;
            }

            let finalFile = originalFile;
            if (originalFile.type.startsWith('image/')) {
                finalFile = await compressImage(originalFile);
            }

            const isImage = finalFile.type.startsWith('image/');
            const isVideo = finalFile.type.startsWith('video/');
            const previewUrl = URL.createObjectURL(finalFile);
            const sizeInMB = (finalFile.size / (1024 * 1024)).toFixed(1);
            const sizeFormatted = sizeInMB < 0.1 ? Math.round(finalFile.size / 1024) + ' KB' : sizeInMB + ' MB';

            uploadedAttachments.value.push({
                file: finalFile,
                name: finalFile.name,
                sizeFormatted,
                isImage,
                isVideo,
                previewUrl
            });
        }

        form.value.uploaded_files = uploadedAttachments.value;
    } catch (e) {
        console.error('Gagal memproses media:', e);
    } finally {
        isCompressing.value = false;
        event.target.value = '';
    }
};

const removeAttachment = (index) => {
    uploadError.value = '';
    if (typeof index === 'number') {
        const item = uploadedAttachments.value[index];
        if (item?.previewUrl) {
            URL.revokeObjectURL(item.previewUrl);
        }
        uploadedAttachments.value.splice(index, 1);
    } else {
        uploadedAttachments.value.forEach((item) => {
            if (item?.previewUrl) URL.revokeObjectURL(item.previewUrl);
        });
        uploadedAttachments.value = [];
    }
    form.value.uploaded_files = uploadedAttachments.value;
    if (fileInput.value) fileInput.value.value = '';
    if (cameraInput.value) cameraInput.value.value = '';
};

const goToStep0 = () => {
    if (isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 0;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const goToStep1 = () => {
    if (isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 1;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const goToStep2 = () => {
    if (!isToiletPreset.value && availableToilets.value.length > 0 && !selectedToiletId.value) {
        uploadError.value = 'Silakan pilih lokasi toilet terlebih dahulu.';
        return;
    }
    if (!form.value.isi_laporan.trim() || form.value.isi_laporan.trim().length < 5 || isTransitioning.value) return;
    isTransitioning.value = true;
    currentStep.value = 2;
    scrollToTop();
    setTimeout(() => {
        isTransitioning.value = false;
    }, 350);
};

const submitReport = async () => {
    if (!isToiletPreset.value && availableToilets.value.length > 0 && !selectedToiletId.value) {
        alert('Silakan pilih lokasi toilet terlebih dahulu.');
        return;
    }
    if (!form.value.isi_laporan.trim() || form.value.isi_laporan.trim().length < 5 || isSubmitting.value) return;
    
    isSubmitting.value = true;
    
    try {
        const formData = new FormData();
        const activeToiletId = selectedToiletId.value || props.toiletData?.id || '';
        if (activeToiletId) {
            formData.append('toilet_id', activeToiletId);
        }
        formData.append('location_point', currentReportLocation.value);
        formData.append('target_object', currentReportLocation.value);
        formData.append('problem_description', form.value.isi_laporan);
        formData.append('urgency', form.value.urgency || 'NORMAL');
        formData.append('reporter_name', form.value.reporter_name || '');
        formData.append('reporter_phone', form.value.reporter_phone || '');
        formData.append('is_anonymous', form.value.reporter_name ? '0' : '1');
        
        uploadedAttachments.value.forEach((item) => {
            if (item?.file) {
                formData.append('attachments[]', item.file);
            }
        });

        const response = await fetch('/services/kesling/aduan-sanitasi', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const contentType = response.headers.get('content-type') || '';
        let data = null;
        if (contentType.includes('application/json')) {
            data = await response.json();
        }

        if (data && (data.success || response.ok) && data.ticket_number) {
            generatedReportId.value = data.ticket_number;
            currentStep.value = 3;
            scrollToTop();
            return;
        }

        if (!response.ok) {
            alert('Gagal mengirim formulir: ' + (data?.message || response.statusText || 'Terjadi kesalahan pada server.'));
            return;
        }

        alert('Gagal mengirim formulir: ' + (data?.message || 'Silakan periksa kembali kelengkapan data Anda.'));
    } catch (e) {
        console.error('Gagal mengirim laporan:', e);
        alert('Gagal mengirim formulir: Terjadi gangguan (' + (e?.message || 'Silakan coba lagi') + ').');
    } finally {
        isSubmitting.value = false;
    }
};

const resetForm = () => {
    uploadedAttachments.value.forEach((item) => {
        if (item?.previewUrl) {
            URL.revokeObjectURL(item.previewUrl);
        }
    });
    uploadedAttachments.value = [];
    if (fileInput.value) fileInput.value.value = '';
    if (cameraInput.value) cameraInput.value.value = '';
    form.value = {
        toilet_id: props.toiletData?.id ? String(props.toiletData.id) : '',
        target_object: '',
        isi_laporan: '',
        urgency: 'NORMAL',
        uploaded_files: [],
        reporter_name: '',
        reporter_phone: ''
    };
    if (!props.toiletData?.id) {
        selectedToiletId.value = '';
    }
    generatedReportId.value = '';
    currentStep.value = 0;
};

const copyReceipt = () => {
    navigator.clipboard.writeText(generatedReportId.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

// Emergency Panic Logic & Front 3-Second Hold
const showPanicModal = ref(false);
const isPanicTriggering = ref(false);
const panicHoldProgress = ref(0);
const panicTriggered = ref(false);
const panicEmergencyTicket = ref(null);
const showEmergencyContactsModal = ref(false);
let panicInterval = null;

const emergencyContacts = [
    {
        name: 'Dangkayani',
        phone: '083850392949',
        waNumber: '6283850392949',
    },
    {
        name: 'Gusti Made Kartika',
        phone: '085792909781',
        waNumber: '6285792909781',
    },
    {
        name: 'Sukiman',
        phone: '081338608050',
        waNumber: '6281338608050',
    },
];

const getEmergencyWaUrl = (waNumber) => {
    const loc = currentReportLocation.value || 'Lokasi Toilet RS';
    const ticket = panicEmergencyTicket.value ? `\nNo. Tiket: ${panicEmergencyTicket.value}` : '';
    const message = `*PANGGILAN BANTUAN DARURAT TOILET*\nLokasi: ${loc}${ticket}\nMohon bantuan dan pertolongan segera di lokasi ini.`;
    return `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;
};

const frontPanicHoldProgress = ref(0);
const isFrontHolding = ref(false);
const panicHoldWarning = ref(false);
const panicWarningMessage = ref('Harap tekan & tahan selama 3 detik penuh!');
const showPanicInfo = ref(false);
let frontPanicInterval = null;
let holdStartTime = ref(0);
let warningTimeout = null;

const startFrontPanicHold = () => {
    if (panicTriggered.value || isPanicTriggering.value) return;

    panicWarningMessage.value = 'Harap tekan & tahan selama 3 detik penuh!';
    panicHoldWarning.value = false;
    isFrontHolding.value = true;
    frontPanicHoldProgress.value = 0;
    holdStartTime.value = Date.now();
    
    const totalDuration = 3000; // 3 seconds
    const intervalMs = 30;
    
    frontPanicInterval = setInterval(() => {
        const elapsed = Date.now() - holdStartTime.value;
        const progress = Math.min(100, (elapsed / totalDuration) * 100);
        frontPanicHoldProgress.value = progress;
        
        if (progress >= 100) {
            clearInterval(frontPanicInterval);
            isFrontHolding.value = false;
            if (typeof navigator !== 'undefined' && navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            }
            triggerEmergencyAlert();
            showPanicModal.value = true;
        }
    }, intervalMs);
};

const cancelFrontPanicHold = () => {
    if (frontPanicInterval) {
        clearInterval(frontPanicInterval);
    }
    const elapsed = Date.now() - holdStartTime.value;
    const wasHolding = isFrontHolding.value;
    isFrontHolding.value = false;
    
    // Gak boleh bisa di-klik sekali: harus ditahan 3 detik penuh!
    if (wasHolding && elapsed < 2800 && !panicTriggered.value && !isPanicTriggering.value) {
        panicHoldWarning.value = true;
        if (warningTimeout) clearTimeout(warningTimeout);
        warningTimeout = setTimeout(() => {
            panicHoldWarning.value = false;
        }, 3000);
    }
    
    frontPanicHoldProgress.value = 0;
};

const openPanicModal = () => {
    panicTriggered.value = false;
    panicHoldProgress.value = 0;
    showPanicModal.value = true;
};

const closePanicModal = () => {
    cancelPanicHold();
    showPanicModal.value = false;
    panicTriggered.value = false;
    showEmergencyContactsModal.value = false;
    frontPanicHoldProgress.value = 0;
};

const startPanicHold = () => {
    if (panicTriggered.value || isPanicTriggering.value) return;
    panicHoldProgress.value = 0;
    const stepDuration = 30;
    const totalDuration = 3000; // 3 seconds
    const increment = (stepDuration / totalDuration) * 100;
    
    panicInterval = setInterval(() => {
        panicHoldProgress.value += increment;
        if (panicHoldProgress.value >= 100) {
            clearInterval(panicInterval);
            triggerEmergencyAlert();
        }
    }, stepDuration);
};

const cancelPanicHold = () => {
    if (panicInterval) clearInterval(panicInterval);
    if (!panicTriggered.value) {
        panicHoldProgress.value = 0;
    }
};

const triggerEmergencyAlert = async () => {
    if (isPanicTriggering.value) return;
    isPanicTriggering.value = true;
    try {
        const activeToiletId = selectedToiletId.value || props.toiletData?.id || '';
        const locName = currentReportLocation.value;

        const formData = new FormData();
        if (activeToiletId) {
            formData.append('toilet_id', activeToiletId);
        }
        formData.append('location_point', locName);
        formData.append('target_object', locName);
        formData.append('problem_description', '🚨 PANGGILAN BANTUAN DARURAT: Pengguna/pasien membutuhkan bantuan dan pertolongan segera di lokasi toilet ini!');
        formData.append('urgency', 'DARURAT');
        formData.append('reporter_name', 'Tombol Bantuan Darurat Toilet');
        formData.append('reporter_phone', '-');
        formData.append('is_anonymous', '0');

        const response = await fetch('/services/kesling/aduan-sanitasi', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        const data = await response.json();
        panicEmergencyTicket.value = data?.ticket_number || ('PS-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-EMG');
        panicTriggered.value = true;
    } catch (e) {
        panicEmergencyTicket.value = 'PS-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-EMG';
        panicTriggered.value = true;
    } finally {
        isPanicTriggering.value = false;
    }
};
</script>

<template>
    <Head title="Aduan Masyarakat" />

    <div 
        class="min-h-screen flex flex-col justify-between sm:justify-center items-center relative overflow-x-hidden font-sans bg-slate-100/70 dark:bg-slate-950 p-0 sm:p-6 text-slate-900 dark:text-slate-100"
    >
        <!-- Main Form Card Container (Full-screen on Mobile, Card Modal on Desktop) -->
        <main class="w-full sm:max-w-xl mx-auto z-10 my-auto flex flex-col flex-1 sm:flex-initial py-0 sm:py-4">
            <!-- Solid Container Card (Without Shadow) -->
            <div class="w-full flex-1 sm:flex-initial flex flex-col sm:rounded-2xl sm:border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <!-- Card Body -->
                <div class="flex-1 p-5 sm:p-8 bg-white dark:bg-slate-900 pb-44 sm:pb-8 sm:rounded-2xl" :class="{ 'pointer-events-none': isTransitioning }">
                    <!-- 3 Step Progress Bar Indicator (Visible in Form Steps 1-3) -->
                    <div v-if="currentStep > 0" class="mb-6 sm:mb-8 px-1 sm:px-2">
                        <div class="grid grid-cols-3 relative">
                            <!-- Connecting Progress Line -->
                            <div class="absolute top-[18px] left-[16.66%] right-[16.66%] -translate-y-1/2 h-1 bg-slate-100 dark:bg-slate-800 z-0">
                                <div 
                                    class="h-full bg-emerald-500 transition-all duration-300 rounded-full"
                                    :style="{ width: currentStep === 1 ? '0%' : currentStep === 2 ? '50%' : '100%' }"
                                ></div>
                            </div>

                            <!-- Step 1 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-white dark:ring-slate-900',
                                        currentStep >= 1 ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <Check v-if="currentStep > 1" class="h-4 w-4 stroke-[3]" />
                                    <span v-else>1</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Detail</span>
                            </div>

                            <!-- Step 2 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-white dark:ring-slate-900',
                                        currentStep >= 2 ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <Check v-if="currentStep > 2" class="h-4 w-4 stroke-[3]" />
                                    <span v-else>2</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Identitas</span>
                            </div>

                            <!-- Step 3 Node -->
                            <div class="flex flex-col items-center text-center z-10 relative">
                                <div 
                                    :class="[
                                        'h-9 w-9 rounded-full flex items-center justify-center font-extrabold text-xs transition-all duration-300 border-2 ring-4 ring-white dark:ring-slate-900',
                                        currentStep === 3 ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <Check v-if="currentStep === 3" class="h-4 w-4 stroke-[3]" />
                                    <span v-else>3</span>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-extrabold mt-2 uppercase tracking-wide text-slate-700 dark:text-slate-200">Selesai</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 0: Portal Mini (Pilihan Buat Laporan, Cek Progres, atau Tombol Darurat) -->
                    <div v-if="currentStep === 0" class="space-y-6 animate-spa-fade-in py-1">
                        <!-- Welcome Header Banner -->
                        <div class="bg-gradient-to-b from-slate-50 to-slate-100/50 dark:from-slate-950 dark:to-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 text-center space-y-2">
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <Leaf class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                    <span>Layanan Sanitasi Lingkungan & Toilet</span>
                                </span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                Selamat Datang di <span class="text-emerald-600 dark:text-emerald-400">PAN-SINAR</span>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                                Sampaikan aduan kebersihan fasilitas atau pantau progres laporan Anda.
                            </p>
                        </div>

                        <!-- Banner Peringatan Toilet Nonaktif -->
                        <div 
                            v-if="props.toiletData?.status === 'NONAKTIF'" 
                            class="mb-4 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-3"
                        >
                            <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div class="space-y-0.5 text-left">
                                <span class="font-bold block">Status Toilet: Nonaktif (Dalam Pemeliharaan)</span>
                                <span class="text-[11px] text-amber-700/90 dark:text-amber-300/80">Titik toilet ini sedang dalam masa perbaikan/nonaktif. Aduan yang Anda kirim tetap akan diteruskan ke tim sanitasi.</span>
                            </div>
                        </div>

                        <!-- 3 Main Portal Cards -->
                        <div class="grid grid-cols-1 gap-3.5">
                            <!-- Card 1: Buat Laporan Baru -->
                            <div 
                                @click="goToStep1"
                                class="group p-5 rounded-2xl border border-emerald-500/40 hover:border-emerald-500 bg-gradient-to-r from-emerald-50/50 to-white dark:from-emerald-950/30 dark:to-slate-900 transition-colors duration-200 cursor-pointer flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="h-12 w-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                        <FileText class="h-6 w-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                            Buat Aduan Sanitasi Baru
                                        </h3>
                                        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Sampaikan keluhan Anda
                                        </p>
                                    </div>
                                </div>
                                <div class="h-9 w-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                                    <ArrowRight class="h-4 w-4" />
                                </div>
                            </div>

                            <!-- Card 2: Cek Progres Laporan -->
                            <Link 
                                :href="trackUrl"
                                class="group p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-teal-500 bg-gradient-to-r from-slate-50/70 to-white dark:from-slate-800/40 dark:to-slate-900 transition-colors duration-200 cursor-pointer flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="h-12 w-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center shrink-0">
                                        <Search class="h-6 w-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                                            Cek Progres Aduan
                                        </h3>
                                        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Pantau status aduan Anda
                                        </p>
                                    </div>
                                </div>
                                <div class="h-9 w-9 rounded-xl bg-teal-100 dark:bg-teal-950/80 text-teal-700 dark:text-teal-300 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform">
                                    <ArrowRight class="h-4 w-4" />
                                </div>
                            </Link>
                        </div>

                        <!-- Dedicated Circular Panic Button Section -->
                        <div class="mt-4 p-5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/25 border border-rose-200/80 dark:border-rose-900/50 flex flex-col items-center justify-center text-center select-none relative">
                            <!-- Location Name Centered with MapPin Icon (Satu Baris, Text [10px]) -->
                            <div class="w-full flex items-center justify-center gap-1.5 text-center mb-3 text-[10px] sm:text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                <MapPin class="h-3 w-3 text-rose-600 dark:text-rose-500 shrink-0" />
                                <span class="truncate">{{ currentReportLocation }}</span>
                            </div>

                            <!-- Circular Progress & Button Box (Strict Fixed Dimensions: 128px x 128px, Perfectly Centered) -->
                            <div class="relative w-32 h-32 flex items-center justify-center my-1 mx-auto">
                                <!-- Outer Radar Ping when holding -->
                                <div 
                                    v-if="isFrontHolding"
                                    class="absolute inset-0 rounded-full bg-rose-600/30 animate-ping pointer-events-none"
                                ></div>

                                <!-- SVG Radial Progress Ring (128x128) -->
                                <svg class="w-32 h-32 absolute inset-0 -rotate-90 pointer-events-none" viewBox="0 0 128 128">
                                    <!-- Background Track -->
                                    <circle
                                        cx="64"
                                        cy="64"
                                        r="58"
                                        stroke="currentColor"
                                        stroke-width="4"
                                        fill="transparent"
                                        class="text-rose-200/80 dark:text-rose-950/80"
                                    />
                                    <!-- Active Progress Arc -->
                                    <circle
                                        cx="64"
                                        cy="64"
                                        r="58"
                                        stroke="currentColor"
                                        stroke-width="5"
                                        stroke-linecap="round"
                                        fill="transparent"
                                        class="text-rose-600 dark:text-rose-500 transition-all duration-75"
                                        :stroke-dasharray="364.42"
                                        :stroke-dashoffset="364.42 - (frontPanicHoldProgress / 100) * 364.42"
                                    />
                                </svg>

                                <!-- Circular Button (96x96) centered with 10px spacing from the ring -->
                                <button
                                    type="button"
                                    @mousedown="startFrontPanicHold"
                                    @mouseup="cancelFrontPanicHold"
                                    @mouseleave="cancelFrontPanicHold"
                                    @touchstart.prevent="startFrontPanicHold"
                                    @touchend="cancelFrontPanicHold"
                                    @touchcancel="cancelFrontPanicHold"
                                    :class="[
                                        'w-24 h-24 rounded-full flex flex-col items-center justify-center transition-all duration-150 cursor-pointer shadow-lg active:scale-95 focus:outline-none select-none z-10',
                                        isFrontHolding 
                                            ? 'bg-rose-700 text-white shadow-rose-600/50 scale-95 ring-4 ring-rose-400/60' 
                                            : 'bg-gradient-to-br from-rose-500 via-rose-600 to-red-700 text-white hover:shadow-xl hover:shadow-rose-600/40 hover:scale-[1.02]'
                                    ]"
                                    title="Tahan 3 detik untuk Panggilan Darurat"
                                >
                                    <BellRing :class="['h-6 w-6 text-white transition-transform mb-1', isFrontHolding ? 'scale-110 animate-bounce' : 'animate-pulse']" />
                                    <template v-if="isFrontHolding">
                                        <span class="text-base font-black text-white leading-none">
                                            {{ Math.ceil((3000 - ((Date.now() - holdStartTime) || 0)) / 1000) }}
                                        </span>
                                        <span class="text-[9px] font-bold uppercase tracking-tight mt-0.5 text-amber-300">
                                            Tahan...
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-[10px] font-black text-white leading-tight uppercase tracking-wider text-center px-1">
                                            TOMBOL<br />DARURAT
                                        </span>
                                    </template>
                                </button>
                            </div>

                            <!-- Peringatan jika hanya diklik sekali (Warning Popup/Banner) -->
                            <div 
                                v-if="panicHoldWarning" 
                                class="mt-2.5 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 text-xs font-bold animate-spa-fade-in"
                            >
                                <AlertTriangle class="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0 stroke-[2.2]" />
                                <span>{{ panicWarningMessage }}</span>
                            </div>

                            <!-- Text Info under Circular Button -->
                            <div v-else class="mt-2 text-center space-y-0.5 max-w-xs">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                    {{ isFrontHolding ? 'Tetap tahan untuk memancarkan sinyal darurat...' : 'Tekan & tahan tombol bulat 3 detik untuk memanggil respon cepat petugas.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 1: Detail Kendala & Upload Bukti Foto -->
                    <div v-if="currentStep === 1" class="space-y-4 animate-spa-fade-in">
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-4 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Langkah 1 dari 2
                                </span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                Detail Kendala & Bukti Foto
                            </h2>
                            <!-- Keterangan lokasi otomatis jika terkunci via scan QR -->
                            <div v-if="isToiletPreset" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-[10px] sm:text-[11px] text-emerald-800 dark:text-emerald-300 font-semibold max-w-full">
                                <MapPin class="h-3 w-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span class="truncate">{{ currentReportLocation }}</span>
                            </div>
                        </div>

                        <!-- Dropdown Pilihan Toilet jika dibuka via URL umum (bukan Scan QR) -->
                        <div v-if="!isToiletPreset" class="space-y-1.5 text-left animate-spa-fade-in">
                            <InputLabel value="Pilih Lokasi Toilet / Titik Sanitasi *" />
                            <select
                                id="step1_select_toilet"
                                v-model="selectedToiletId"
                                required
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-200 h-10 py-2 px-3 text-xs font-normal focus:border-emerald-500 dark:focus:border-emerald-400 focus:bg-white dark:focus:bg-slate-950 focus:outline-none transition cursor-pointer"
                            >
                                <option value="" disabled>-- Pilih Lokasi Toilet yang Terkendala --</option>
                                <option v-for="t in availableToilets" :key="t.id" :value="String(t.id)">
                                    {{ t.display }}
                                </option>
                            </select>
                            <div v-if="!selectedToiletId && availableToilets.length > 0" class="text-[11px] text-amber-600 dark:text-amber-400 font-normal px-1">
                                Silakan pilih lokasi toilet terlebih dahulu.
                            </div>
                            <div v-if="availableToilets.length === 0" class="text-[11px] text-amber-600 dark:text-amber-400 font-normal px-1">
                                Belum ada data titik toilet di Manajemen Toilet.
                            </div>
                        </div>

                        <!-- Detail Teks Laporan (Tanpa deskripsi subtitle) -->
                        <div class="space-y-1.5">
                            <InputLabel value="Uraian Kendala / Aduan *" />
                            <textarea
                                id="isi_laporan"
                                ref="isiLaporanRef"
                                v-model="form.isi_laporan"
                                rows="4"
                                maxlength="3000"
                                required
                                enterkeyhint="next"
                                @keydown="onIsiLaporanKeydown"
                                @input="autoResizeIsiLaporan"
                                :placeholder="isiLaporanPlaceholder"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 text-xs sm:text-sm focus:border-emerald-500 dark:focus:border-emerald-400 focus:bg-white dark:focus:bg-slate-950 focus:outline-none focus:ring-0 focus:shadow-none transition duration-150 leading-relaxed resize-none"
                                style="field-sizing: content; min-height: 108px;"
                            ></textarea>
                            <div 
                                v-if="form.isi_laporan.length > 0 && form.isi_laporan.trim().length < 5" 
                                class="text-[11px] text-amber-600 dark:text-amber-400 font-medium px-1 animate-spa-fade-in"
                            >
                                Minimal 5 karakter (kurang {{ 5 - form.isi_laporan.trim().length }} karakter lagi)
                            </div>
                        </div>

                        <!-- Upload Bukti Foto / Video (Opsional) -->
                        <div class="space-y-2.5">
                            <InputLabel value="Lampirkan Bukti Foto / Video (Opsional)" />

                            <!-- Inline Upload Error Alert Banner -->
                            <div 
                                v-if="uploadError" 
                                class="flex items-center justify-between gap-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 text-xs animate-spa-fade-in"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <AlertCircle class="h-4 w-4 shrink-0 text-rose-500" />
                                    <span class="font-medium text-[11px] sm:text-xs leading-tight">{{ uploadError }}</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click.stop="uploadError = ''" 
                                    class="p-1 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-400 hover:text-rose-600 transition cursor-pointer shrink-0"
                                    title="Tutup Peringatan"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Kamera HP Langsung -->
                            <input 
                                ref="cameraInput" 
                                type="file" 
                                accept="image/*" 
                                capture="environment" 
                                class="hidden" 
                                @change="onFileSelected" 
                            />

                            <!-- Galeri / File Penyimpanan HP (Multiple) -->
                            <input 
                                ref="fileInput" 
                                type="file" 
                                accept="image/*,video/*" 
                                multiple
                                class="hidden" 
                                @change="onFileSelected" 
                            />

                            <!-- Space Upload Area -->
                            <div 
                                class="relative w-full rounded-2xl overflow-hidden border transition-all duration-200"
                                :class="hasAttachments ? 'border-slate-300 dark:border-slate-700 bg-slate-100/60 dark:bg-slate-900 shadow-none' : 'border-2 border-dashed border-slate-300 hover:border-emerald-500 dark:border-slate-700 dark:hover:border-emerald-500 bg-slate-50 hover:bg-emerald-50/30 dark:bg-slate-950 dark:hover:bg-emerald-950/20 cursor-pointer'"
                                @click.stop="!hasAttachments && !isTransitioning && openFileInput()"
                            >
                                <!-- State 1: Mengoptimalkan Media -->
                                <div v-if="isCompressing" class="flex flex-col items-center justify-center p-8 text-center select-none">
                                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent mb-2.5"></div>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        Mengoptimalkan Ukuran Media...
                                    </p>
                                </div>

                                <!-- State 2: Kosong (Siap Klik) -->
                                <div v-else-if="!hasAttachments" class="flex flex-col items-center justify-center p-5 sm:p-7 text-center select-none">
                                    <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2.5">
                                        <Camera class="h-6 w-6" />
                                    </div>
                                    <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100">
                                        Ambil Foto atau Pilih dari Galeri
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 mb-3.5">
                                        Bisa pilih beberapa foto/video sekaligus (Maks. 5 file)
                                    </p>

                                    <!-- Tombol Pilihan: Kamera vs Galeri (Tanpa Shadow) -->
                                    <div class="flex flex-wrap items-center justify-center gap-2.5 w-full max-w-xs">
                                        <button
                                            type="button"
                                            @click.stop="triggerCamera"
                                            class="flex-1 min-w-[125px] flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold transition cursor-pointer"
                                        >
                                            <Camera class="h-4 w-4 shrink-0" />
                                            <span>Buka Kamera</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="openFileInput"
                                            class="flex-1 min-w-[125px] flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 active:scale-95 text-slate-700 dark:text-slate-200 text-xs font-bold transition cursor-pointer"
                                        >
                                            <ImageIcon class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            <span>Pilih Galeri</span>
                                        </button>
                                    </div>

                                    <span class="inline-block text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-3 px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800/80">
                                        Khusus Foto / Video (Maks. 5 File, 10MB/file)
                                    </span>
                                </div>

                                <!-- State 3: Grid Media Terlampir (Multi-file) -->
                                <div v-else class="p-3 grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    <div
                                        v-for="(att, idx) in uploadedAttachments"
                                        :key="idx"
                                        class="relative aspect-square rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 group"
                                    >
                                        <img
                                            v-if="att.isImage"
                                            :src="att.previewUrl"
                                            :alt="att.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <video
                                            v-else-if="att.isVideo"
                                            :src="att.previewUrl"
                                            controls
                                            class="w-full h-full object-cover"
                                        ></video>

                                        <span class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 rounded-md bg-slate-900/75 text-white text-[10px] font-bold">
                                            {{ att.sizeFormatted }}
                                        </span>

                                        <button
                                            type="button"
                                            @click.stop="removeAttachment(idx)"
                                            :disabled="isTransitioning"
                                            class="absolute top-1.5 right-1.5 h-6 w-6 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white flex items-center justify-center shadow-sm cursor-pointer transition active:scale-90"
                                            title="Hapus file ini"
                                        >
                                            <X class="h-3.5 w-3.5 stroke-[2.5]" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Keterangan & Aksi Tambah / Hapus Semua File Terlampir -->
                            <div 
                                v-if="hasAttachments && !isCompressing" 
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 animate-spa-fade-in"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-9 w-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">
                                            {{ uploadedAttachments.length }} / 5 Media Terlampir
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                            {{ uploadedAttachments.length < 5 ? 'Masih bisa tambah foto atau video lagi' : 'Batas maksimal 5 file tercapai' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto shrink-0">
                                    <button
                                        v-if="uploadedAttachments.length < 5"
                                        type="button"
                                        @click.stop="triggerCamera"
                                        :disabled="isTransitioning"
                                        class="h-9 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 disabled:opacity-50 whitespace-nowrap"
                                        title="Tambah Foto Kamera"
                                    >
                                        <Camera class="h-3.5 w-3.5 shrink-0" />
                                        <span>Kamera</span>
                                    </button>
                                    <button
                                        v-if="uploadedAttachments.length < 5"
                                        type="button"
                                        @click.stop="openFileInput"
                                        :disabled="isTransitioning"
                                        class="h-9 px-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 disabled:opacity-50 whitespace-nowrap"
                                        title="Tambah dari Galeri"
                                    >
                                        <ImageIcon class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Galeri</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click.stop="removeAttachment()"
                                        :disabled="isTransitioning"
                                        class="col-span-2 sm:col-span-1 h-9 px-3 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 disabled:opacity-50 whitespace-nowrap"
                                        title="Hapus Semua Lampiran"
                                    >
                                        <Trash2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Step 1 Actions (Inside Card) -->
                        <div class="hidden sm:flex items-center gap-3 pt-5">
                            <button
                                type="button"
                                @click.stop.prevent="goToStep0"
                                :disabled="isTransitioning"
                                class="flex h-11 px-6 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50 shrink-0"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Menu Utama</span>
                            </button>
                            <button
                                type="button"
                                @click.stop.prevent="goToStep2"
                                :disabled="(!isToiletPreset && availableToilets.length > 0 && !selectedToiletId) || !form.isi_laporan.trim() || form.isi_laporan.trim().length < 5 || isTransitioning"
                                class="flex-1 flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>Lanjut ke Data Pelapor</span>
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: Identitas Pelapor -->
                    <div v-else-if="currentStep === 2" class="space-y-4 animate-spa-fade-in">
                        <div class="bg-slate-50 dark:bg-slate-950 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 mb-5 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Langkah 2 dari 2
                                </span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                Identitas Pelapor
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Dapat diisi atau dikosongkan jika ingin menyampaikan aduan secara anonim.
                            </p>
                        </div>

                        <!-- Input Nama Pelapor (Tanpa deskripsi subtitle) -->
                        <div class="space-y-1.5">
                            <InputLabel value="Nama Lengkap (Opsional / Anonim)" />
                            <TextInput
                                id="reporter_name"
                                ref="reporterNameRef"
                                type="text"
                                class="block w-full"
                                v-model="form.reporter_name"
                                enterkeyhint="next"
                                @keydown.enter.prevent="handleReporterNameEnter"
                                placeholder="Contoh: Budi Santoso (atau biarkan kosong)"
                            />
                        </div>

                        <!-- Input No. HP / WA Pelapor (Tanpa deskripsi subtitle) -->
                        <div class="space-y-1.5">
                            <InputLabel value="No. WhatsApp / Telepon (Opsional)" />
                            <TextInput
                                id="reporter_phone"
                                ref="reporterPhoneRef"
                                type="tel"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="15"
                                class="block w-full"
                                v-model="form.reporter_phone"
                                @input="onPhoneInput"
                                @keypress="onPhoneKeyPress"
                                enterkeyhint="send"
                                @keydown.enter.prevent="submitReport"
                                placeholder="Contoh: 081234567890 (atau biarkan kosong)"
                            />
                        </div>

                        <!-- Desktop Step 2 Actions (Inside Card) -->
                        <div class="hidden sm:flex items-center gap-3 pt-5">
                            <button
                                type="button"
                                @click.stop.prevent="goToStep1"
                                :disabled="isSubmitting || isTransitioning"
                                class="flex h-11 px-6 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50 shrink-0"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali ke Detail</span>
                            </button>
                            <button
                                type="button"
                                @click.stop.prevent="submitReport"
                                :disabled="isSubmitting || isTransitioning"
                                class="flex-1 flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <span
                                    v-if="isSubmitting"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                ></span>
                                <template v-else>
                                    <Send class="h-4 w-4" />
                                    <span>Kirim Aduan Sekarang</span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: Laporan Berhasil & Struk Registrasi Digital -->
                    <div v-else-if="currentStep === 3" class="space-y-5 text-center animate-spa-fade-in py-2">
                        <!-- Success Icon Badge -->
                        <div class="h-16 w-16 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full border border-emerald-500/20 dark:border-emerald-500/30 flex items-center justify-center mx-auto shadow-none">
                            <CheckCircle2 class="h-8 w-8" />
                        </div>

                        <div class="mb-4">
                            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Aduan Terkirim!</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                Terima kasih. Aduan Anda telah dicatat oleh sistem PAN-SINAR dan diteruskan langsung ke tim cleaning service & pengawas sanitasi.
                            </p>
                        </div>

                        <!-- Receipt Code Box -->
                        <div class="bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Nomor Registrasi Aduan</span>
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xl font-sans font-extrabold text-emerald-600 dark:text-emerald-400 tracking-wider">{{ generatedReportId }}</span>
                                <button
                                    @click="copyReceipt"
                                    class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer focus:outline-none"
                                    title="Salin Kode Aduan"
                                >
                                    <Copy class="h-4 w-4" />
                                </button>
                            </div>
                            <span v-if="copied" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-1">Kode berhasil disalin!</span>
                        </div>

                        <!-- Warning Catat / Simpan Nomor Registrasi -->
                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 flex items-start gap-3 text-left">
                            <AlertCircle class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div class="text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                                <strong class="text-amber-900 dark:text-amber-200 font-bold block mb-0.5">Catatan Penting:</strong>
                                Jika Anda ingin melacak progres penanganan aduan ini, pastikan untuk <strong>menyimpan atau mencatat Nomor Registrasi</strong> di atas sebelum meninggalkan halaman ini.
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="button"
                                @click="resetForm"
                                class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition cursor-pointer active:scale-[0.99] shadow-sm"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                <span>Kembali ke Halaman Pertama</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Floating Bottom Navigation Bar for Steps 1 & 2 (ONLY MOBILE, Auto-hides when keyboard opens) -->
                <div
                    v-if="currentStep > 0 && currentStep < 3"
                    v-show="!isKeyboardOpen"
                    class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 shadow-2xl transition-all duration-200"
                    :class="{ 'pointer-events-none': isTransitioning }"
                >
                    <!-- STEP 1 Action (Stacked) -->
                    <div v-if="currentStep === 1" class="flex flex-col gap-2.5 w-full">
                        <button
                            type="button"
                            @click.stop.prevent="goToStep2"
                            :disabled="(!isToiletPreset && availableToilets.length > 0 && !selectedToiletId) || !form.isi_laporan.trim() || form.isi_laporan.trim().length < 5 || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span>Lanjut ke Data Pelapor</span>
                            <ArrowRight class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            @click.stop.prevent="goToStep0"
                            :disabled="isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Menu Utama</span>
                        </button>
                    </div>

                    <!-- STEP 2 Action (Stacked) -->
                    <div v-else-if="currentStep === 2" class="flex flex-col gap-2.5 w-full">
                        <button
                            type="button"
                            @click.stop.prevent="submitReport"
                            :disabled="isSubmitting || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <span
                                v-if="isSubmitting"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <template v-else>
                                <Send class="h-4 w-4" />
                                <span>Kirim Aduan Sekarang</span>
                            </template>
                        </button>
                        <button
                            type="button"
                            @click.stop.prevent="goToStep1"
                            :disabled="isSubmitting || isTransitioning"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Kembali ke Detail & Bukti</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- FULL SCREEN EMERGENCY SUCCESS VIEW (Matching Reference Screenshot) -->
        <div 
            v-if="panicTriggered" 
            class="fixed inset-0 z-50 bg-white dark:bg-slate-950 flex flex-col justify-between items-center p-6 sm:p-10 animate-spa-fade-in overflow-y-auto"
        >
            <!-- Spacer to maintain balance -->
            <div class="w-full max-w-md h-2"></div>

            <!-- Center Content (Concentric Soft Green Halo with Solid Green Checkmark + Title + Description) -->
            <div class="w-full max-w-sm mx-auto my-auto py-6 text-center flex flex-col items-center">
                <!-- Concentric Soft Green Halo with Solid Green Checkmark -->
                <div class="relative flex items-center justify-center mb-6">
                    <!-- Outer soft green halo ring -->
                    <div class="h-36 w-36 rounded-full bg-emerald-500/10 dark:bg-emerald-500/15 flex items-center justify-center animate-pulse">
                        <!-- Inner soft green halo ring -->
                        <div class="h-28 w-28 rounded-full bg-emerald-500/20 dark:bg-emerald-500/25 flex items-center justify-center">
                            <!-- Solid Green Checkmark -->
                            <Check class="h-14 w-14 text-emerald-500 dark:text-emerald-400 stroke-[3.5]" />
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Sinyal Darurat Terkirim
                </h2>

                <!-- Description -->
                <p class="mt-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-xs mx-auto font-medium">
                    Panggilan darurat telah diterima. Petugas terkait segera menuju ke lokasi Anda.
                </p>
            </div>

            <!-- Bottom Action Buttons (Standard Form Button Size h-12 rounded-xl) -->
            <div class="w-full max-w-md mx-auto flex flex-col gap-2.5 pb-2">
                <button 
                    type="button"
                    @click="showEmergencyContactsModal = true"
                    class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-rose-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-rose-500 active:scale-[0.99] focus:outline-none"
                >
                    <MessageCircle class="w-4 h-4" />
                    <span>Hubungi Petugas via WhatsApp</span>
                </button>
                <button 
                    type="button"
                    @click="closePanicModal"
                    class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Kembali ke Menu Utama</span>
                </button>
            </div>
        </div>

        <!-- MODAL / SHEET PILIHAN KONTAK WHATSAPP PETUGAS DARURAT -->
        <div
            v-if="panicTriggered && showEmergencyContactsModal"
            @click.self="showEmergencyContactsModal = false"
            class="fixed inset-0 z-[60] bg-slate-950/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4 animate-spa-fade-in"
        >
            <div class="w-full sm:max-w-md bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="px-5 py-4 bg-rose-600 text-white flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                            <PhoneCall class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm sm:text-base font-extrabold leading-tight truncate">
                                Hubungi Petugas Darurat
                            </h3>
                            <p class="text-[11px] text-rose-100 mt-0.5 truncate">
                                Pilih nomor untuk chat atau telepon via WhatsApp
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showEmergencyContactsModal = false"
                        class="p-1.5 rounded-lg text-rose-100 hover:text-white hover:bg-white/15 transition cursor-pointer shrink-0"
                        title="Tutup"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Contact List -->
                <div class="p-4 sm:p-5 space-y-2.5">
                    <a
                        v-for="contact in emergencyContacts"
                        :key="contact.waNumber"
                        :href="getEmergencyWaUrl(contact.waNumber)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group flex items-center justify-between gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 hover:border-emerald-500/60 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30 transition-all duration-150 active:scale-[0.99]"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="h-11 w-11 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                <MessageCircle class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-extrabold text-slate-900 dark:text-white truncate">
                                    {{ contact.name }}
                                </div>
                                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                    <Phone class="h-3 w-3 shrink-0" />
                                    <span>{{ contact.phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-[11px] font-bold shadow-xs group-hover:bg-emerald-500 transition">
                            <span>WhatsApp</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </div>
                    </a>
                </div>

                <!-- Footer -->
                <div class="px-5 sm:px-6 pb-8 sm:pb-6 pt-1">
                    <button
                        type="button"
                        @click="showEmergencyContactsModal = false"
                        class="flex h-12 w-full cursor-pointer items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
