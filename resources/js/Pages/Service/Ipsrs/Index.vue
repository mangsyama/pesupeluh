<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { compressImage } from '@/Utils/imageCompressor';
import { 
    Wrench, 
    ClipboardList, 
    Gauge, 
    FilePlus,
    Send, 
    CheckCircle2, 
    Info,
    UploadCloud,
    X,
    Camera,
    Lock,
    Search,
    AlertTriangle,
    Clock
} from '@lucide/vue';

const props = defineProps({
    unit: {
        type: Object,
        default: () => ({})
    },
    rooms: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

// Features Navigation Tabs (Pelaporan, Kalibrasi, Usulan)
const features = [
    { id: 'pelaporan', name: 'Pelaporan Kerusakan', icon: ClipboardList, desc: 'Laporan perbaikan alat, sarana, dan prasarana' },
    { id: 'kalibrasi', name: 'Kalibrasi Alat', icon: Gauge, desc: 'Pengujian dan kalibrasi akurasi instrumen medis' },
    { id: 'usulan', name: 'Usulan Sarpras', icon: FilePlus, desc: 'Pengajuan pengadaan atau peremajaan fasilitas' },
];

const selectedFeatureId = ref('pelaporan');
const selectFeature = (id) => {
    selectedFeatureId.value = id;
};

// --- REPORTING FORM LOGIC ---

// Check if logged in user has an assigned room
const userAssignedRoom = computed(() => {
    const roomId = currentUser.value?.room_id;
    if (!roomId) return null;
    return props.rooms.find(r => r.id === roomId) || currentUser.value?.room || null;
});

// Category state & list
const selectedCategoryId = ref(null);
const categoriesList = computed(() => props.unit?.issue_categories || []);
const isCategoriesLoading = computed(() => !props.unit?.issue_categories);
const isRoomsLoading = computed(() => !props.rooms);

const categorySearchQuery = ref('');
const filteredCategoriesList = computed(() => {
    const list = categoriesList.value;
    const q = categorySearchQuery.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter(c => 
        (c.name && c.name.toLowerCase().includes(q)) || 
        (c.description && c.description.toLowerCase().includes(q))
    );
});

const activeCategory = computed(() => {
    return categoriesList.value.find(c => c.id === selectedCategoryId.value) || null;
});

// Form state
const form = useForm({
    room_id: '',
    category_id: '',
    problem_description: '',
    priority: 'ROUTINE',
    attachments: []
});

// Auto-select room if user has an assigned room
watchEffect(() => {
    if (userAssignedRoom.value) {
        form.room_id = userAssignedRoom.value.id;
    }
});

// Room formatting for SearchableSelect
const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

const formattedRooms = computed(() => {
    return (props.rooms || []).map(r => ({
        ...r,
        location_floor: formatRoomDetails(r)
    }));
});

// File Attachments Handling (Max 1 File)
const MAX_FILES = 1;
const attachmentPreviews = ref([]);
const dragOver = ref(false);
const fileInputRef = ref(null);
const cameraInputRef = ref(null);

const readFileAsDataURL = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
};

const processFiles = async (files) => {
    if (!files || files.length === 0) return;
    const file = files[0];
    const isImage = file.type.startsWith('image/');
    const isVideo = file.type.startsWith('video/');
    if (!isImage && !isVideo) return;

    let dataUrl;
    if (isImage) {
        try {
            dataUrl = await compressImage(file);
        } catch {
            dataUrl = await readFileAsDataURL(file);
        }
    } else {
        dataUrl = await readFileAsDataURL(file);
    }

    attachmentPreviews.value = [{
        name: file.name,
        size: file.size,
        type: isImage ? 'image' : 'video',
        preview: dataUrl,
        data: dataUrl
    }];

    syncFormAttachments();
};

const handleFileSelect = async (e) => {
    const files = Array.from(e.target.files || []);
    await processFiles(files);
    if (e.target) e.target.value = '';
};

const handleFileDrop = async (e) => {
    e.preventDefault();
    dragOver.value = false;
    const files = Array.from(e.dataTransfer.files || []).filter(f => f.type.startsWith('image/') || f.type.startsWith('video/'));
    await processFiles(files);
};

const syncFormAttachments = () => {
    form.attachments = attachmentPreviews.value.map(a => a.data);
};

const clearAllAttachments = () => {
    attachmentPreviews.value = [];
    form.attachments = [];
    if (fileInputRef.value) fileInputRef.value.value = '';
    if (cameraInputRef.value) cameraInputRef.value.value = '';
};

const selectCategory = (id) => {
    if (selectedCategoryId.value === id) {
        selectedCategoryId.value = null;
        form.category_id = '';
    } else {
        selectedCategoryId.value = id;
        form.category_id = id;
    }
};

const submitReport = () => {
    form.clearErrors('attachments');
    if (attachmentPreviews.value.length === 0) {
        form.setError('attachments', 'Lampiran foto / video wajib diunggah.');
        return;
    }

    form.post(route('services.tickets.store'), {
        onSuccess: () => {
            form.reset();
            selectedCategoryId.value = null;
            clearAllAttachments();
            if (userAssignedRoom.value) {
                form.room_id = userAssignedRoom.value.id;
            }
        }
    });
};
</script>

<template>
    <Head :title="'IPSRS - Layanan Pemeliharaan Sarpras & Alkes'" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                
                <!-- Premium Header Unit IPSRS -->
                <div class="hidden sm:flex bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Wrench class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                    {{ unit?.name || 'IPSRS' }}
                                </h2>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-emerald-50 text-emerald-700 dark:bg-white/10 dark:text-white">
                                    Penunjang Non-Medik
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                                {{ unit?.description || 'Instalasi Pemeliharaan Sarana Rumah Sakit - Layanan perbaikan fasilitas, sarana, prasarana, listrik, air, dan peralatan medis.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature Tabs (Pelaporan, Kalibrasi, Usulan) -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                            Pilih Jenis Layanan IPSRS
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                            Tentukan jenis pengajuan yang ingin Anda proses ke unit IPSRS.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div 
                            v-for="feat in features" 
                            :key="feat.id"
                            @click="selectFeature(feat.id)"
                            :class="[
                                'p-4 rounded-xl border text-left transition-all duration-200 select-none cursor-pointer flex items-center gap-3',
                                selectedFeatureId === feat.id
                                    ? 'border-emerald-500 dark:border-white bg-emerald-50 dark:bg-white/10'
                                    : 'border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 hover:bg-emerald-50/40 dark:hover:bg-white/5 hover:border-emerald-300 dark:hover:border-white/30'
                            ]"
                        >
                            <div 
                                :class="[
                                    'h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors duration-150',
                                    selectedFeatureId === feat.id
                                        ? 'bg-emerald-100 dark:bg-white/15 text-emerald-600 dark:text-white'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                <component :is="feat.icon" class="h-5 w-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div 
                                    :class="[
                                        'text-sm font-bold truncate transition-colors duration-150',
                                        selectedFeatureId === feat.id
                                            ? 'text-emerald-700 dark:text-white'
                                            : 'text-slate-800 dark:text-slate-200'
                                    ]"
                                >
                                    {{ feat.name }}
                                </div>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate mt-0.5">
                                    {{ feat.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 1: FORM PELAPORAN KERUSAKAN -->
                <div v-if="selectedFeatureId === 'pelaporan'" class="space-y-4">
                    <!-- Kategori Kerusakan Selector -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                    Pilih Kategori Kerusakan
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                    Pilih salah satu kategori kerusakan yang paling sesuai dengan kendala di ruangan Anda.
                                </p>
                            </div>
                            <!-- Search Kategori -->
                            <div v-if="categoriesList.length > 3" class="relative min-w-[220px]">
                                <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <input 
                                    v-model="categorySearchQuery"
                                    type="text"
                                    placeholder="Cari kategori kerusakan..."
                                    class="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150"
                                />
                            </div>
                        </div>

                        <!-- Skeleton Loading Categories -->
                        <div v-if="isCategoriesLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div 
                                v-for="n in 6" 
                                :key="'skel-cat-' + n"
                                class="p-4 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 flex items-start justify-between gap-3 min-h-[84px]"
                            >
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                    <div class="h-3 w-44 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                </div>
                                <div class="h-4 w-4 rounded-full bg-slate-200/80 dark:bg-slate-800 animate-pulse flex-shrink-0"></div>
                            </div>
                        </div>

                        <!-- Categories Grid -->
                        <div v-else-if="filteredCategoriesList.length > 0" class="max-h-[380px] overflow-y-auto pr-1 custom-scrollbar">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div 
                                    v-for="cat in filteredCategoriesList" 
                                    :key="cat.id"
                                    @click="selectCategory(cat.id)"
                                    :class="[
                                        'p-4 rounded-xl border text-left transition-all duration-200 select-none cursor-pointer relative group flex items-start justify-between gap-3 h-auto min-h-[84px]',
                                        selectedCategoryId === cat.id
                                            ? 'border-emerald-500 dark:border-white bg-emerald-50 dark:bg-white/10 text-emerald-950 dark:text-white'
                                            : 'border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 hover:bg-emerald-50/40 dark:hover:bg-white/5 hover:border-emerald-300 dark:hover:border-white/20'
                                    ]"
                                >
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold uppercase tracking-wide leading-tight group-hover:text-emerald-600 dark:group-hover:text-white transition-colors duration-150">
                                            {{ cat.name }}
                                        </h4>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-relaxed mt-1.5 whitespace-normal break-words">
                                            {{ cat.description || 'Tidak ada deskripsi kategori.' }}
                                        </p>
                                    </div>
                                    <div 
                                        :class="[
                                            'h-4 w-4 rounded-full border flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors duration-150',
                                            selectedCategoryId === cat.id
                                                ? 'border-emerald-500 dark:border-white bg-emerald-600 dark:bg-white text-white dark:text-slate-900'
                                                : 'border-slate-300 dark:border-slate-700 group-hover:border-emerald-400 dark:group-hover:border-white'
                                        ]"
                                    >
                                        <CheckCircle2 v-if="selectedCategoryId === cat.id" class="h-3 w-3 text-white" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                            {{ categorySearchQuery ? 'Kategori yang Anda cari tidak ditemukan.' : 'Belum ada kategori kerusakan untuk unit IPSRS.' }}
                        </div>
                    </div>

                    <!-- Reporting Form Box -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                                Formulir Pengajuan Laporan
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
                                Lengkapi rincian laporan di bawah ini untuk segera ditangani oleh teknisi IPSRS.
                            </p>
                        </div>

                        <!-- Notification when no category selected -->
                        <div 
                            v-if="!selectedCategoryId" 
                            class="p-4 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 dark:text-slate-400 text-xs flex gap-2.5 items-start"
                        >
                            <Info class="h-4 w-4 text-slate-400 flex-shrink-0 mt-0.5" />
                            <div class="leading-relaxed">
                                Silakan <strong>pilih salah satu kategori kerusakan</strong> pada daftar di atas terlebih dahulu untuk membuka formulir pelaporan.
                            </div>
                        </div>

                        <!-- Active Form Fields -->
                        <form v-else @submit.prevent="submitReport" class="space-y-4">
                            <!-- Readonly Category Display -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Kategori Terpilih
                                </label>
                                <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 flex items-center justify-between gap-4">
                                    <div class="text-xs font-bold text-slate-800 dark:text-white uppercase leading-none">
                                        {{ activeCategory?.name }}
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase shrink-0 border bg-emerald-50/90 dark:bg-white/10 text-emerald-700 dark:text-white border-emerald-200 dark:border-white/20">
                                        Pelaporan Kerusakan
                                    </span>
                                </div>
                            </div>

                            <!-- Room Picker -->
                            <div class="space-y-1.5 relative">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Lokasi Ruangan <span class="text-red-400">*</span>
                                </label>

                                <!-- Case 1: User HAS an assigned room (Locked) -->
                                <div v-if="userAssignedRoom" class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 flex items-center justify-between gap-4">
                                    <div>
                                        <div class="text-xs font-bold text-slate-800 dark:text-white uppercase leading-none">{{ userAssignedRoom.name }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1.5 leading-none">
                                            {{ formatRoomDetails(userAssignedRoom) || 'Lokasi Ruangan Penempatan Anda' }}
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-200/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border border-slate-300/40 dark:border-slate-700/50 shrink-0">
                                        <Lock class="h-3 w-3 text-slate-500" />
                                        Terkunci
                                    </span>
                                </div>

                                <!-- Case 2: User has NO assigned room (Searchable Select) -->
                                <div v-else>
                                    <div v-if="isRoomsLoading" class="h-[46px] w-full bg-slate-200/60 dark:bg-slate-800 rounded-xl animate-pulse"></div>
                                    <SearchableSelect
                                        v-else
                                        v-model="form.room_id"
                                        :options="formattedRooms"
                                        placeholder="Pilih lokasi ruangan..."
                                        search-placeholder="Cari nama ruangan, gedung, atau lantai..."
                                        not-found-text="Ruangan tidak ditemukan"
                                        value-key="id"
                                        label-key="name"
                                        subtitle-key="location_floor"
                                    />
                                </div>
                                <div v-if="form.errors.room_id" class="text-[10px] text-red-500 font-semibold">{{ form.errors.room_id }}</div>
                            </div>

                            <!-- Tingkat Urgensi Pelaporan -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Tingkat Urgensi Pelaporan <span class="text-red-400">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- ROUTINE -->
                                    <button
                                        type="button"
                                        @click="form.priority = 'ROUTINE'"
                                        :class="[
                                            'p-3.5 rounded-xl border transition-all duration-150 text-left select-none relative flex items-center justify-between gap-2 focus:outline-none outline-none shadow-none cursor-pointer',
                                            form.priority === 'ROUTINE'
                                                ? 'border-emerald-600 bg-emerald-600 text-white font-extrabold shadow-sm'
                                                : 'border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 hover:border-emerald-300 dark:hover:border-emerald-800'
                                        ]"
                                    >
                                        <div class="space-y-0.5 min-w-0">
                                            <div :class="['text-xs font-black uppercase tracking-wide truncate', form.priority === 'ROUTINE' ? 'text-white' : 'text-slate-800 dark:text-slate-200']">
                                                RUTIN
                                            </div>
                                            <div :class="['text-[10px] font-medium truncate', form.priority === 'ROUTINE' ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400']">
                                                Penanganan normal / biasa
                                            </div>
                                        </div>
                                        <Clock :class="['h-5 w-5 shrink-0 transition-colors', form.priority === 'ROUTINE' ? 'text-white' : 'text-emerald-600 dark:text-emerald-400']" />
                                    </button>

                                    <!-- URGENT -->
                                    <button
                                        type="button"
                                        @click="form.priority = 'URGENT'"
                                        :class="[
                                            'p-3.5 rounded-xl border transition-all duration-150 text-left select-none relative flex items-center justify-between gap-2 focus:outline-none outline-none shadow-none cursor-pointer',
                                            form.priority === 'URGENT'
                                                ? 'border-red-600 bg-red-600 text-white font-extrabold shadow-sm'
                                                : 'border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 hover:border-red-300 dark:hover:border-red-900/50'
                                        ]"
                                    >
                                        <div class="space-y-0.5 min-w-0">
                                            <div :class="['text-xs font-black uppercase tracking-wide truncate', form.priority === 'URGENT' ? 'text-white' : 'text-slate-800 dark:text-slate-200']">
                                                URGENT (MENDESAK)
                                            </div>
                                            <div :class="['text-[10px] font-medium truncate', form.priority === 'URGENT' ? 'text-red-100' : 'text-slate-500 dark:text-slate-400']">
                                                Prioritas penanganan cepat
                                            </div>
                                        </div>
                                        <AlertTriangle :class="['h-5 w-5 shrink-0 transition-colors', form.priority === 'URGENT' ? 'text-white' : 'text-red-600 dark:text-red-400']" />
                                    </button>
                                </div>

                                <!-- Priority Info Box -->
                                <div 
                                    v-if="form.priority === 'ROUTINE'" 
                                    class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs flex gap-3 items-start animate-fade-in"
                                >
                                    <Clock class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                                    <div class="leading-relaxed space-y-0.5">
                                        <strong class="font-extrabold block text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">
                                            Kategori Rutin
                                        </strong>
                                        <span>
                                            Untuk kerusakan fasilitas umum / non-kritikal yang tidak mengganggu pelayanan medis pasien secara langsung (contoh: AC kurang dingin, lampu redup, kran air menetes, engsel pintu rusak). Penanganan dilakukan sesuai antrean jam operasional.
                                        </span>
                                    </div>
                                </div>

                                <div 
                                    v-if="form.priority === 'URGENT'" 
                                    class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-800 dark:text-red-300 text-xs flex gap-3 items-start animate-fade-in"
                                >
                                    <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
                                    <div class="leading-relaxed space-y-0.5">
                                        <strong class="font-extrabold block text-red-700 dark:text-red-300 uppercase tracking-wide">
                                            Kategori Urgent (Mendesak)
                                        </strong>
                                        <span>
                                            Untuk kerusakan vital/kritikal yang mengganggu operasional pelayanan medis pasien atau berpotensi membahayakan keselamatan (contoh: kebocoran gas/air deras, mati listrik di ruang perawatan/ICU, konsleting/stopkontak vital alat medis). Penanganan diprioritaskan cepat.
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Problem Description -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Deskripsi Kendala / Kerusakan <span class="text-red-400">*</span>
                                </label>
                                <textarea 
                                    v-model="form.problem_description" 
                                    required
                                    rows="4"
                                    placeholder="Jelaskan secara detail kerusakan yang terjadi, kondisi alat/sarpras, atau letak spesifiknya..."
                                    class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150 leading-relaxed"
                                ></textarea>
                                <div v-if="form.errors.problem_description" class="text-[10px] text-red-500 font-semibold">{{ form.errors.problem_description }}</div>
                            </div>

                            <!-- Media Attachments (Max 1 File) -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Lampiran Foto / Video Bukti <span class="text-red-400">*</span>
                                    </label>
                                    <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">Maks. 1 Foto/Video</span>
                                </div>

                                <!-- Hidden Inputs -->
                                <input ref="fileInputRef" type="file" class="hidden" accept="image/*,video/*" @change="handleFileSelect" />
                                <input ref="cameraInputRef" type="file" class="hidden" accept="image/*" capture="environment" @change="handleFileSelect" />

                                <!-- Preview Card -->
                                <div v-if="attachmentPreviews.length > 0" class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 group shadow-sm transition-all duration-200">
                                    <div class="relative h-48 w-full flex items-center justify-center bg-black/40">
                                        <img v-if="attachmentPreviews[0].type === 'image'" :src="attachmentPreviews[0].preview" alt="Preview" class="w-full h-full object-cover" />
                                        <video v-else :src="attachmentPreviews[0].preview" controls class="w-full h-full object-cover"></video>

                                        <!-- Status Badge -->
                                        <div class="absolute top-3 left-3 flex items-center gap-1.5 bg-emerald-600/90 backdrop-blur-md text-white px-3 py-1 rounded-full text-[10px] font-extrabold shadow-sm">
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                            <span>Foto Bukti Terpilih</span>
                                        </div>

                                        <!-- Hover Overlay Actions -->
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                @click="fileInputRef?.click()"
                                                class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-900 rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <UploadCloud class="h-3.5 w-3.5 text-emerald-600" />
                                                <span>Ganti Foto</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="clearAllAttachments()"
                                                class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Card Footer Details -->
                                    <div class="p-3 bg-white dark:bg-slate-900 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 text-xs">
                                        <div class="flex items-center gap-2 min-w-0 pr-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ attachmentPreviews[0].name }}</span>
                                            <span class="text-[10px] font-semibold text-slate-400 shrink-0">({{ (attachmentPreviews[0].size / 1024).toFixed(0) }} KB)</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button
                                                type="button"
                                                @click="fileInputRef?.click()"
                                                class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer"
                                            >
                                                Ganti
                                            </button>
                                            <span class="text-slate-300 dark:text-slate-700">•</span>
                                            <button
                                                type="button"
                                                @click="clearAllAttachments()"
                                                class="text-[11px] font-bold text-rose-500 hover:underline cursor-pointer"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropzone Box when empty -->
                                <div v-else class="space-y-2">
                                    <div 
                                        @dragover.prevent="dragOver = true"
                                        @dragleave="dragOver = false"
                                        @drop="handleFileDrop"
                                        @click="fileInputRef?.click()"
                                        :class="[
                                            'border-2 border-dashed rounded-2xl p-5 text-center cursor-pointer transition flex flex-col items-center justify-center',
                                            dragOver 
                                                ? 'border-emerald-500 dark:border-white bg-emerald-50/20 dark:bg-white/10'
                                                : 'border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 dark:hover:border-white/30 bg-slate-50/50 dark:bg-slate-950/20'
                                        ]"
                                    >
                                        <div class="h-10 w-10 rounded-full flex items-center justify-center mb-2 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                                            <UploadCloud class="h-5 w-5" />
                                        </div>
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Unggah 1 Foto / Video Bukti</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Format PNG, JPG, WEBP, MP4 (Maks. 10MB)</p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="cameraInputRef?.click()"
                                        class="w-full h-10 rounded-xl border border-emerald-200 dark:border-white/20 text-emerald-600 dark:text-white hover:bg-emerald-50 dark:hover:bg-white/10 text-xs font-bold flex items-center justify-center gap-2 transition duration-150 cursor-pointer"
                                    >
                                        <Camera class="h-4 w-4" />
                                        Ambil Foto dari Kamera
                                    </button>
                                </div>

                                <div v-if="form.errors.attachments" class="text-[10px] text-red-500 font-semibold">{{ form.errors.attachments }}</div>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 transition duration-200 disabled:opacity-50 bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 cursor-pointer"
                            >
                                <Send class="h-4 w-4" />
                                <span>{{ form.processing ? 'Mengirim Laporan...' : 'Kirim Laporan ke IPSRS' }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: KALIBRASI ALAT -->
                <div v-else-if="selectedFeatureId === 'kalibrasi'">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4 shadow-sm min-h-[350px] animate-spa-fade-in">
                        <div class="h-16 w-16 rounded-full flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Gauge class="h-8 w-8 text-emerald-600 dark:text-white" />
                        </div>
                        <div class="space-y-1.5 max-w-md">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                Modul Kalibrasi Alat Medis & Sarpras
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                                Modul Kalibrasi & Inventaris Alat Medis IPSRS sedang dipersiapkan untuk integrasi data aset rumah sakit secara realtime.
                            </p>
                        </div>
                        <div class="pt-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wide uppercase bg-emerald-100/75 dark:bg-white/10 text-emerald-700 dark:text-white">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                Dalam Pengembangan
                            </span>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: USULAN SARPRAS -->
                <div v-else-if="selectedFeatureId === 'usulan'">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4 shadow-sm min-h-[350px] animate-spa-fade-in">
                        <div class="h-16 w-16 rounded-full flex items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <FilePlus class="h-8 w-8 text-emerald-600 dark:text-white" />
                        </div>
                        <div class="space-y-1.5 max-w-md">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                Modul Usulan Pengadaan & Peremajaan Sarpras
                            </h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                                Formulir Usulan Sarana & Prasarana IPSRS sedang disesuaikan dengan alur perencanaan anggaran dan administrasi rumah sakit.
                            </p>
                        </div>
                        <div class="pt-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wide uppercase bg-emerald-100/75 dark:bg-white/10 text-emerald-700 dark:text-white">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                Dalam Pengembangan
                            </span>
                        </div>
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

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 9999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}
</style>
