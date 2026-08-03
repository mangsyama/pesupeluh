<script setup>
import { ref, computed, watchEffect, onMounted, onUnmounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { compressImage } from '@/Utils/imageCompressor';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { 
    Send, 
    CheckCircle2, 
    Info,
    UploadCloud,
    X,
    Camera,
    Video,
    Lock,
    Building2,
    ChevronDown,
    Search,
    Check,
    Flame,
    AlertTriangle,
    Clock,
    Zap
} from '@lucide/vue';

const props = defineProps({
    unit: {
        type: Object,
        required: true
    },
    activeFeature: {
        type: Object,
        required: true
    },
    rooms: {
        type: Array,
        required: true
    },
    isMedik: {
        type: Boolean,
        required: true
    }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || null);

// Check if logged in user has an assigned room
const userAssignedRoom = computed(() => {
    const roomId = currentUser.value?.room_id;
    if (!roomId) return null;
    return props.rooms.find(r => r.id === roomId) || currentUser.value?.room || null;
});

// Selected category ID
const selectedCategoryId = ref(null);

const categoriesList = computed(() => props.unit?.issue_categories || props.activeFeature?.feature_categories || []);
const isCategoriesLoading = computed(() => !props.unit?.issue_categories && !props.activeFeature?.feature_categories);
const isRoomsLoading = computed(() => !props.rooms);

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

// Searchable dropdown state for users without an assigned room
const isRoomDropdownOpen = ref(false);
const roomSearchQuery = ref('');
const roomDropdownRef = ref(null);

const handleClickOutsideRoomDropdown = (event) => {
    if (isRoomDropdownOpen.value && roomDropdownRef.value && !roomDropdownRef.value.contains(event.target)) {
        isRoomDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideRoomDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsideRoomDropdown);
});

const formatRoomDetails = (room) => {
    if (!room) return '';
    const b = room.building_name ? (/^gedung/i.test(room.building_name.trim()) ? room.building_name.trim() : `Gedung ${room.building_name.trim()}`) : null;
    const f = room.location_floor ? (/^lantai/i.test(room.location_floor.trim()) || /^lt\./i.test(room.location_floor.trim()) ? room.location_floor.trim() : `Lantai ${room.location_floor.trim()}`) : null;
    return [b, f].filter(Boolean).join(' - ');
};

const formattedRooms = computed(() => {
    return (props.rooms || []).map(r => {
        return {
            ...r,
            location_floor: formatRoomDetails(r)
        };
    });
});

const filteredRooms = computed(() => {
    const q = roomSearchQuery.value.trim().toLowerCase();
    if (!q) return props.rooms;
    return props.rooms.filter(r => 
        (r.name && r.name.toLowerCase().includes(q)) || 
        (r.building_name && r.building_name.toLowerCase().includes(q)) ||
        (r.location_floor && r.location_floor.toLowerCase().includes(q))
    );
});

const selectedRoomLabel = computed(() => {
    if (!form.room_id) return '';
    const selected = props.rooms.find(r => r.id === form.room_id);
    if (!selected) return '';
    const details = formatRoomDetails(selected);
    return details ? `${selected.name} (${details})` : selected.name;
});

const toggleRoomDropdown = (event) => {
    event?.stopPropagation();
    isRoomDropdownOpen.value = !isRoomDropdownOpen.value;
    if (isRoomDropdownOpen.value) {
        roomSearchQuery.value = '';
    }
};

const selectRoom = (roomId) => {
    form.room_id = roomId;
    isRoomDropdownOpen.value = false;
};

// Multi-file attachment state
const MAX_FILES = 5;
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

const processFiles = async (files) => {
    const remaining = MAX_FILES - attachmentPreviews.value.length;
    const toProcess = files.slice(0, remaining);
    
    for (const file of toProcess) {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        if (!isImage && !isVideo) continue;
        
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
        
        attachmentPreviews.value.push({
            name: file.name,
            size: file.size,
            type: isImage ? 'image' : 'video',
            preview: dataUrl,
            data: dataUrl
        });
    }
    
    syncFormAttachments();
};

const syncFormAttachments = () => {
    form.attachments = attachmentPreviews.value.map(a => a.data);
};

const removeAttachment = (index) => {
    attachmentPreviews.value.splice(index, 1);
    syncFormAttachments();
};

const clearAllAttachments = () => {
    attachmentPreviews.value = [];
    form.attachments = [];
    if (fileInputRef.value) fileInputRef.value.value = '';
    if (cameraInputRef.value) cameraInputRef.value.value = '';
};

const canAddMore = computed(() => attachmentPreviews.value.length < MAX_FILES);

const selectCategory = (id) => {
    if (selectedCategoryId.value === id) {
        // Toggle uncheck if clicked again
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
    <div class="space-y-4">
        <!-- Categories Grid -->
        <div 
            v-if="activeFeature" 
            class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4"
        >
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">
                    {{ __('pages.services.kategori_di_bawah') }} {{ activeFeature.name }}
                </h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.kategori_di_bawah_desc') }}</p>
            </div>

            <!-- Skeleton Loading Categories -->
            <div v-if="isCategoriesLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div 
                    v-for="n in 6" 
                    :key="'skel-rep-cat-' + n"
                    class="p-4 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 flex items-start justify-between gap-3 min-h-[84px]"
                >
                    <div class="flex-1 space-y-2">
                        <div class="h-4 w-28 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                        <div class="h-3 w-44 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                    </div>
                    <div class="h-4 w-4 rounded-full bg-slate-200/80 dark:bg-slate-800 animate-pulse flex-shrink-0"></div>
                </div>
            </div>

            <!-- Categories Grid Data -->
            <div v-else-if="categoriesList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div 
                    v-for="cat in categoriesList" 
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
                        <p class="text-[10px] text-slate-400 dark:text-slate-505 leading-relaxed mt-1.5 whitespace-normal break-words">
                            {{ cat.description || __('Tidak ada deskripsi kategori.') }}
                        </p>
                    </div>
                    <!-- Checkbox style indicator -->
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

            <div v-else class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                {{ __('pages.services.kategori_empty') }}
            </div>
        </div>

        <!-- Reporting Form -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">{{ __('pages.services.form_title') }}</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.form_desc') }}</p>
            </div>

            <!-- Notification when no category selected -->
            <div 
                v-if="!selectedCategoryId" 
                class="p-4 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 text-slate-450 dark:text-slate-400 text-xs flex gap-2.5 items-start"
            >
                <Info class="h-4 w-4 text-slate-400 flex-shrink-0 mt-0.5" />
                <div class="leading-relaxed" v-html="__('pages.services.form_warning_html')">
                </div>
            </div>

            <!-- Active Form -->
            <form v-else @submit.prevent="submitReport" class="space-y-4">
                <!-- Readonly Category Display -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.services.kategori_terpilih') }}
                    </label>
                    <div class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 flex items-center justify-between gap-4">
                        <div class="text-xs font-bold text-slate-800 dark:text-white uppercase leading-none">
                            {{ activeCategory?.name }}
                        </div>
                        <span 
                            :class="[
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase shrink-0 border',
                                isMedik 
                                    ? 'bg-indigo-50/90 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/80' 
                                    : 'bg-emerald-50/90 dark:bg-white/10 text-emerald-700 dark:text-white border-emerald-200 dark:border-white/20'
                            ]"
                        >
                            {{ activeFeature?.name }}
                        </span>
                    </div>
                </div>

                <!-- Room Picker -->
                <div class="space-y-1.5 relative">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('pages.services.pilih_ruangan') }} <span class="text-red-400">*</span>
                    </label>

                    <!-- Case 1: User HAS an assigned room (Auto-selected & Locked) -->
                    <div v-if="userAssignedRoom" class="bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-white uppercase leading-none">{{ userAssignedRoom.name }}</div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-505 font-medium mt-1.5 leading-none">
                                {{ formatRoomDetails(userAssignedRoom) || 'Lokasi Ruangan Penempatan Anda' }}
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-200/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 border border-slate-300/40 dark:border-slate-700/50 shrink-0">
                            <Lock class="h-3 w-3 text-slate-500" />
                            {{ __('Terkunci') }}
                        </span>
                    </div>

                    <!-- Case 2: User has NO assigned room (Custom Searchable & Scrollable Dropdown) -->
                    <div v-else>
                        <div v-if="isRoomsLoading" class="h-[46px] w-full bg-slate-200/60 dark:bg-slate-800 rounded-xl animate-pulse"></div>
                        <SearchableSelect
                            v-else
                            v-model="form.room_id"
                            :options="formattedRooms"
                            :placeholder="__('pages.services.pilih_ruangan_default')"
                            :search-placeholder="__('Cari nama ruangan, gedung, atau lantai...')"
                            :not-found-text="__('Ruangan tidak ditemukan')"
                            value-key="id"
                            label-key="name"
                            subtitle-key="location_floor"
                        />
                    </div>
                    <div v-if="form.errors.room_id" class="text-[10px] text-red-500 font-semibold">{{ form.errors.room_id }}</div>
                </div>

                <!-- Tingkat Urgensi Pelaporan Selector -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ __('Tingkat Urgensi Pelaporan') }} <span class="text-red-400">*</span>
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
                                    ROUTINE (STANDAR)
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

                    <!-- Priority Info / Explanation Box -->
                    <div 
                        v-if="form.priority === 'ROUTINE'" 
                        class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs flex gap-3 items-start animate-fade-in"
                    >
                        <Clock class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                        <div class="leading-relaxed space-y-0.5">
                            <strong class="font-extrabold block text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">
                                Kategori Routine (Standar)
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
                        {{ __('pages.services.deskripsi_kendala') }} <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        v-model="form.problem_description" 
                        required
                        rows="4"
                        :placeholder="__('pages.services.deskripsi_placeholder')"
                        class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150 leading-relaxed"
                    ></textarea>
                    <div v-if="form.errors.problem_description" class="text-[10px] text-red-500 font-semibold">{{ form.errors.problem_description }}</div>
                </div>

                <!-- Media Attachments (Required) -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {{ __('pages.services.lampiran_foto') }} <span class="text-red-400">*</span>
                        </label>
                        <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ attachmentPreviews.length }}/{{ MAX_FILES }}</span>
                    </div>

                    <!-- Hidden inputs -->
                    <input ref="fileInputRef" type="file" class="hidden" accept="image/*,video/*" multiple @change="handleFileSelect" />
                    <input ref="cameraInputRef" type="file" class="hidden" accept="image/*" capture="environment" @change="handleFileSelect" />

                    <!-- Previews Grid -->
                    <div v-if="attachmentPreviews.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                        <div 
                            v-for="(att, idx) in attachmentPreviews" 
                            :key="idx" 
                            class="relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 aspect-square bg-slate-50 dark:bg-slate-950"
                        >
                            <img v-if="att.type === 'image'" :src="att.preview" alt="Preview" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 text-slate-400">
                                <Video class="h-6 w-6" />
                                <span class="text-[9px] font-semibold truncate max-w-full px-1">{{ att.name }}</span>
                            </div>
                            <button
                                type="button"
                                @click="removeAttachment(idx)"
                                class="absolute top-1 right-1 h-6 w-6 flex items-center justify-center rounded-md bg-black/50 hover:bg-black/70 text-white transition"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                            <div class="absolute bottom-0 inset-x-0 bg-black/40 px-1.5 py-0.5">
                                <span class="text-[8px] text-white font-semibold truncate block">{{ (att.size / 1024).toFixed(0) }} KB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Drop Zone + Action Buttons -->
                    <div v-if="canAddMore" class="space-y-2">
                        <div 
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop="handleFileDrop"
                            @click="fileInputRef?.click()"
                            :class="[
                                'border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center',
                                attachmentPreviews.length > 0 ? 'min-h-[80px]' : 'min-h-[120px]',
                                dragOver 
                                    ? 'border-emerald-400 dark:border-white bg-emerald-50/20 dark:bg-white/10'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/30 dark:bg-slate-950/20'
                            ]"
                        >
                            <div class="h-9 w-9 rounded-full flex items-center justify-center mb-1.5 bg-emerald-50 dark:bg-white/10 text-emerald-500 dark:text-white">
                                <UploadCloud class="h-4.5 w-4.5" />
                            </div>
                            <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">{{ __('pages.services.lampiran_upload_label') }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.lampiran_formats') }} ({{ __('pages.services.lampiran_max_size') }})</p>
                        </div>

                        <!-- Camera Button -->
                        <button
                            type="button"
                            @click="cameraInputRef?.click()"
                            class="w-full h-10 rounded-xl border border-emerald-200 dark:border-white/20 text-emerald-600 dark:text-white hover:bg-emerald-50 dark:hover:bg-white/10 text-xs font-semibold flex items-center justify-center gap-2 transition duration-150"
                        >
                            <Camera class="h-4 w-4" />
                            {{ __('pages.services.lampiran_kamera') }}
                        </button>
                    </div>

                    <div v-if="form.errors.attachments" class="text-[10px] text-red-500 font-semibold">{{ form.errors.attachments }}</div>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    :class="[
                        'w-full h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 transition duration-200 disabled:opacity-50',
                        isMedik 
                            ? 'bg-indigo-600 hover:bg-indigo-500' 
                            : 'bg-emerald-600 hover:bg-emerald-500 dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900'
                    ]"
                >
                    <Send class="h-4 w-4" />
                    <span>{{ form.processing ? __('pages.services.btn_sending') : __('pages.services.btn_kirim') }}</span>
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
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
