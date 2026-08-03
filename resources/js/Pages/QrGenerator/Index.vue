<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import Swal from 'sweetalert2';
import { 
    QrCode, 
    Download, 
    Copy, 
    Wrench, 
    Stethoscope, 
    ShieldCheck, 
    Sparkles, 
    Check,
    Globe,
    Layers,
    RefreshCw,
    Sliders,
    Link as LinkIcon,
    Palette
} from '@lucide/vue';

const props = defineProps({
    baseUrl: {
        type: String,
        default: ''
    },
    units: {
        type: Array,
        default: () => []
    }
});

// Dynamic base origin
const currentOrigin = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return props.baseUrl || 'http://localhost';
});

// Form states
const customPath = ref('/services/units/ipsrs');
const qrColor = ref('#059669'); // Emerald 600 default
const includeLogo = ref(true);
const qrCanvasRef = ref(null);
const isGenerating = ref(false);
const copied = ref(false);

// Presets list
const presets = computed(() => [
    {
        id: 'ipsrs',
        title: 'IPSRS (Fasilitas & Sarpras)',
        path: '/services/units/ipsrs',
        icon: Wrench,
        badge: 'IPSRS'
    },
    {
        id: 'medik',
        title: 'Layanan Penunjang Medik',
        path: '/services/medik',
        icon: Stethoscope,
        badge: 'MEDIK'
    },
    {
        id: 'non-medik',
        title: 'Layanan Penunjang Non-Medik',
        path: '/services/non-medik',
        icon: ShieldCheck,
        badge: 'NON-MEDIK'
    },
    ...props.units
        .filter(u => !['ipsrs'].includes(u.slug))
        .map(u => ({
            id: u.slug,
            title: u.name,
            path: `/services/units/${u.slug}`,
            icon: Layers,
            badge: u.type
        }))
]);

// Dynamic selectedPreset matching current customPath
const selectedPreset = computed(() => {
    const matched = presets.value.find(p => p.path === customPath.value);
    return matched ? matched.id : null;
});

// Final Target Full URL
const fullTargetUrl = computed(() => {
    let path = customPath.value || '';
    if (!path.startsWith('/')) {
        path = '/' + path;
    }
    return `${currentOrigin.value}${path}`;
});

const applyPreset = (preset) => {
    customPath.value = preset.path;
    generateQR();
};

const resetConfig = () => {
    const defaultPreset = presets.value[0];
    if (defaultPreset) {
        applyPreset(defaultPreset);
    }
};

const colorPresets = [
    { name: 'Emerald', hex: '#059669' },
    { name: 'Black', hex: '#000000' },
    { name: 'Navy', hex: '#1e3a8a' },
    { name: 'Slate', hex: '#0f172a' },
    { name: 'Purple', hex: '#7e22ce' },
    { name: 'Crimson', hex: '#991b1b' },
];

const isCustomColor = computed(() => {
    return !colorPresets.some(c => c.hex.toLowerCase() === qrColor.value.toLowerCase());
});

const generateQR = async () => {
    if (!qrCanvasRef.value) return;
    isGenerating.value = true;

    try {
        const canvas = qrCanvasRef.value;
        const ctx = canvas.getContext('2d');

        // 1. Generate Ultra Crisp High-Res QR Code (1200px resolution)
        await QRCode.toCanvas(canvas, fullTargetUrl.value, {
            width: 1200,
            margin: 2,
            color: {
                dark: qrColor.value,
                light: '#FFFFFF'
            },
            errorCorrectionLevel: 'H'
        });

        // 2. Draw Center Logo Overlay if enabled
        if (includeLogo.value) {
            const logoImg = new Image();
            logoImg.crossOrigin = 'Anonymous';
            logoImg.src = '/images/logo-sidebar.png';

            await new Promise((resolve) => {
                logoImg.onload = () => {
                    const canvasWidth = canvas.width;
                    const logoSize = canvasWidth * 0.22; // 22% of QR width
                    const logoX = (canvasWidth - logoSize) / 2;
                    const logoY = (canvasWidth - logoSize) / 2;

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    // Offscreen canvas to tint white logo with selected qrColor
                    const tintCanvas = document.createElement('canvas');
                    tintCanvas.width = logoImg.width || 400;
                    tintCanvas.height = logoImg.height || 400;
                    const tintCtx = tintCanvas.getContext('2d');
                    tintCtx.imageSmoothingEnabled = true;
                    tintCtx.imageSmoothingQuality = 'high';

                    tintCtx.drawImage(logoImg, 0, 0, tintCanvas.width, tintCanvas.height);
                    tintCtx.globalCompositeOperation = 'source-in';
                    tintCtx.fillStyle = qrColor.value;
                    tintCtx.fillRect(0, 0, tintCanvas.width, tintCanvas.height);

                    // Background circle for logo with high-res shadow radius
                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(canvasWidth / 2, canvasWidth / 2, (logoSize / 2) + 12, 0, 2 * Math.PI);
                    ctx.fillStyle = '#FFFFFF';
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                    ctx.shadowBlur = 20;
                    ctx.fill();
                    ctx.restore();

                    // Draw Tinted High-Res Logo Image inside
                    ctx.drawImage(tintCanvas, logoX, logoY, logoSize, logoSize);
                    resolve();
                };
                logoImg.onerror = () => {
                    resolve();
                };
            });
        }
    } catch (err) {
        console.error('Failed to generate QR Code:', err);
    } finally {
        isGenerating.value = false;
    }
};

const downloadQR = () => {
    if (!qrCanvasRef.value) return;
    const canvas = qrCanvasRef.value;
    const dataUrl = canvas.toDataURL('image/png');
    const key = selectedPreset.value ? selectedPreset.value.toUpperCase() : 'CUSTOM';
    const filename = `QR_PESUPELUH_${key}_${Date.now()}.png`;

    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'QR Code Berhasil Diunduh!',
        showConfirmButton: false,
        timer: 2000
    });
};

const copyUrl = () => {
    navigator.clipboard.writeText(fullTargetUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Tautan disalin ke clipboard!',
        showConfirmButton: false,
        timer: 2000
    });
};

watch([customPath, qrColor, includeLogo], () => {
    generateQR();
});

onMounted(() => {
    generateQR();
});
</script>

<template>
    <Head title="Generator QR Code" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">

                <!-- Header Panel (Matching WA Gateway Panel standard) -->
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <QrCode class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                                Generator QR Code
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-2xl leading-relaxed">
                                Buat dan unduh QR Code dinamis bercetak logo PESU PELUH untuk unit layanan RS
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full xl:w-auto">
                        <!-- Domain Origin Badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 h-10 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-semibold bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 flex-1 xl:flex-initial justify-center sm:justify-start">
                            <Globe class="h-4 w-4 text-emerald-500 shrink-0" />
                            <span class="truncate font-bold text-emerald-600 dark:text-white">{{ currentOrigin }}</span>
                        </div>

                        <!-- Reset / Refresh Button -->
                        <button 
                            @click="resetConfig" 
                            class="h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer flex-1 xl:flex-initial"
                        >
                            <RefreshCw class="h-4 w-4" />
                            <span>Reset Form</span>
                        </button>
                    </div>
                </div>

                <!-- Main Content Grid (12 Columns layout matching WA Gateway) -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                    
                    <!-- Left Panel: Configurations & Presets (7 Cols) -->
                    <div class="xl:col-span-7 space-y-4">

                        <!-- Card 1: Presets Selector -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    1. Pilih Preset Unit Layanan
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Pilih salah satu pintasan unit untuk langsung memasukkan URL target</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    v-for="preset in presets"
                                    :key="preset.id"
                                    type="button"
                                    @click="applyPreset(preset)"
                                    :class="[
                                        'group p-3.5 rounded-xl border text-left transition duration-200 flex items-start gap-3.5 relative cursor-pointer overflow-hidden',
                                        selectedPreset === preset.id
                                            ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-200 shadow-xs'
                                            : 'border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 hover:border-emerald-200 dark:hover:border-emerald-800/50 text-slate-700 dark:text-slate-300'
                                    ]"
                                >
                                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 border border-emerald-100 dark:border-white/20 text-emerald-600 dark:text-white flex items-center justify-center flex-shrink-0">
                                        <component :is="preset.icon" class="h-5 w-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-extrabold text-slate-900 dark:text-white truncate flex items-center justify-between">
                                            <span>{{ preset.title }}</span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ preset.path }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Card 2: Target URL Input -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    2. Target URL QR Code
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Masukkan path halaman yang ingin dibuka saat QR Code di-scan</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Path Target URL <span class="text-red-400">*</span>
                                </label>

                                <div class="flex flex-col sm:flex-row rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden focus-within:border-slate-400 dark:focus-within:border-slate-600 transition shadow-2xs">
                                    <div class="bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 px-3.5 py-2.5 sm:py-0 flex items-center text-xs font-bold shrink-0 select-none border-b sm:border-b-0 sm:border-r border-slate-200 dark:border-slate-800 justify-start">
                                        <span>{{ currentOrigin }}</span>
                                    </div>
                                    <input 
                                        v-model="customPath"
                                        type="text" 
                                        placeholder="/services/units/ipsrs"
                                        class="flex-1 h-11 px-3.5 bg-transparent text-slate-800 dark:text-slate-200 text-xs font-bold border-none outline-none focus:outline-none focus:ring-0"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Appearance Controls -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    3. Kustomisasi Desain QR
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Atur skema warna kode QR dan opsi penyematan logo resmi di tengah</p>
                            </div>

                            <div class="space-y-4">
                                <!-- Color Selection Swatches + Custom Picker -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pilihan Warna Kode QR</label>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            v-for="color in colorPresets"
                                            :key="color.hex"
                                            type="button"
                                            @click="qrColor = color.hex"
                                            :class="[
                                                'h-9 px-3 rounded-xl border text-xs font-bold flex items-center gap-2 transition cursor-pointer',
                                                qrColor.toLowerCase() === color.hex.toLowerCase()
                                                    ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-200 shadow-xs'
                                                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                                            ]"
                                        >
                                            <span class="h-3.5 w-3.5 rounded-full shadow-xs shrink-0" :style="{ backgroundColor: color.hex }"></span>
                                            <span>{{ color.name }}</span>
                                        </button>

                                        <!-- Custom Color Picker Button -->
                                        <label 
                                            :class="[
                                                'h-9 px-3 rounded-xl border text-xs font-bold flex items-center gap-2 transition cursor-pointer relative select-none',
                                                isCustomColor
                                                    ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-950 dark:text-emerald-200 shadow-xs'
                                                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                                            ]"
                                        >
                                            <Palette class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                                            <span>Custom Warna: <strong class="font-bold uppercase">{{ qrColor }}</strong></span>
                                            <input 
                                                v-model="qrColor"
                                                type="color" 
                                                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                                            />
                                        </label>
                                    </div>
                                </div>

                                <!-- Center Logo Toggle Card -->
                                <div 
                                    @click="includeLogo = !includeLogo"
                                    :class="[
                                        'p-4 rounded-xl border transition cursor-pointer flex items-center justify-between gap-4 select-none',
                                        includeLogo 
                                            ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' 
                                            : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950'
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <div :class="[
                                            'h-10 w-10 rounded-xl flex items-center justify-center shrink-0 transition p-2',
                                            includeLogo ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                                        ]">
                                            <img src="/images/logo-sidebar.png" alt="Logo" class="h-full w-full object-contain brightness-0 invert" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-extrabold text-slate-900 dark:text-white">Tampilkan Logo PESU PELUH di Tengah</h4>
                                            <p class="hidden sm:block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Sematkan logo resmi rumah sakit dengan warna yang selaras</p>
                                        </div>
                                    </div>

                                    <!-- Switch Toggle Button -->
                                    <div :class="[
                                        'w-11 h-6 rounded-full transition-colors p-0.5 shrink-0 flex items-center',
                                        includeLogo ? 'bg-emerald-600 justify-end' : 'bg-slate-300 dark:bg-slate-700 justify-start'
                                    ]">
                                        <div class="w-5 h-5 rounded-full bg-white shadow-xs"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Panel: QR Code Display & Actions (5 Cols) -->
                    <div class="xl:col-span-5 flex flex-col">
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between h-full space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white">
                                    Preview QR Code & Unduh
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Tampilan gambar QR Code siap simpan & pakai</p>
                            </div>

                            <!-- QR Frame (Focused pure on QR Code) -->
                            <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 text-center shadow-xs flex-1 flex flex-col items-center justify-center min-w-0 overflow-hidden">
                                <div class="p-3 sm:p-3.5 bg-white rounded-2xl shadow-md border border-slate-100 flex items-center justify-center aspect-square w-full max-w-[240px] sm:max-w-[280px] min-w-0 overflow-hidden">
                                    <canvas ref="qrCanvasRef" class="w-full h-full max-w-full max-h-full object-contain block"></canvas>
                                </div>
                            </div>

                            <!-- Target URL Badge Container -->
                            <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center gap-2 overflow-hidden shadow-2xs">
                                <Globe class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">{{ fullTargetUrl }}</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-2 pt-1">
                                <button
                                    type="button"
                                    @click="downloadQR"
                                    class="w-full h-10 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-900 text-xs font-bold rounded-xl shadow-sm flex items-center justify-center gap-2 transition cursor-pointer"
                                >
                                    <Download class="h-4 w-4" />
                                    <span>Download Gambar QR</span>
                                </button>

                                <button
                                    type="button"
                                    @click="copyUrl"
                                    class="w-full h-9 px-3 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                                >
                                    <Check v-if="copied" class="h-3.5 w-3.5 text-emerald-500" />
                                    <Copy v-else class="h-3.5 w-3.5 text-slate-400" />
                                    <span>{{ copied ? 'Tersalin!' : 'Salin URL Target' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
