<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { compressImage } from '@/Utils/imageCompressor';
import { 
    Wrench, 
    HeartPulse, 
    Send, 
    Activity, 
    CheckCircle2, 
    AlertCircle,
    Info,
    UploadCloud,
    X,
    ImageIcon,
    Camera,
    Video,
    FilePlus
} from '@lucide/vue';

const props = defineProps({
    unit: {
        type: Object,
        required: true
    },
    rooms: {
        type: Array,
        required: true
    }
});

const isMedik = computed(() => {
    return props.unit.division?.name.toLowerCase().includes('medik') && 
           !props.unit.division?.name.toLowerCase().includes('non-medik');
});

// Current selected feature ID (defaults to the first feature if available)
const selectedFeatureId = ref(props.unit.unit_features?.[0]?.id || null);

// Selected feature object
const activeFeature = computed(() => {
    return props.unit.unit_features?.find(f => f.id === selectedFeatureId.value) || null;
});

// Selected category ID
const selectedCategoryId = ref(null);

const activeCategory = computed(() => {
    return activeFeature.value?.feature_categories?.find(c => c.id === selectedCategoryId.value) || null;
});

// Form state
const form = useForm({
    room_id: '',
    category_id: '',
    problem_description: '',
    attachments: []
});

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

const selectFeature = (id) => {
    selectedFeatureId.value = id;
    selectedCategoryId.value = null; // reset category on feature change
    form.category_id = '';
};

const selectCategory = (id) => {
    selectedCategoryId.value = id;
    form.category_id = id;
};

const submitReport = () => {
    form.post(route('services.tickets.store'), {
        onSuccess: () => {
            form.reset();
            selectedCategoryId.value = null;
            clearAllAttachments();
        }
    });
};
</script>

<template>
    <Head :title="unit.name + ' - ' + __('Layanan')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                
                <!-- Premium Header -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div 
                            :class="[
                                'h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0',
                                isMedik 
                                    ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400' 
                                    : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                            ]"
                        >
                            <HeartPulse v-if="isMedik" class="h-6 w-6" />
                            <Wrench v-else class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                    {{ unit.name }}
                                </h2>
                                <span 
                                    :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase',
                                        isMedik 
                                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' 
                                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    ]"
                                >
                                    {{ unit.division?.name }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                                {{ unit.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Main Layout -->
                <div v-if="unit.unit_features && unit.unit_features.length > 0" class="w-full space-y-4">
                    
                    <!-- Feature Tabs -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">{{ __('pages.services.pilih_layanan') }}</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.pilih_layanan_desc') }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button 
                                v-for="feature in unit.unit_features" 
                                :key="feature.id"
                                type="button"
                                @click="selectFeature(feature.id)"
                                :class="[
                                    'p-4 rounded-xl border text-left transition-all duration-200 focus:outline-none flex items-center gap-3',
                                    selectedFeatureId === feature.id
                                        ? (isMedik 
                                            ? 'border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/20 shadow-sm'
                                            : 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-sm')
                                        : 'border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 hover:bg-slate-55'
                                ]"
                            >
                                <div 
                                    :class="[
                                        'h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors duration-150',
                                        selectedFeatureId === feature.id
                                            ? (isMedik ? 'bg-indigo-100 dark:bg-indigo-900/60' : 'bg-emerald-100 dark:bg-emerald-900/60')
                                            : 'bg-slate-100 dark:bg-slate-800'
                                    ]"
                                >
                                    <Activity 
                                        :class="[
                                            'h-5 w-5 transition-colors duration-150',
                                            selectedFeatureId === feature.id
                                                ? (isMedik ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400')
                                                : 'text-slate-500 dark:text-slate-400'
                                        ]"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div 
                                        :class="[
                                            'text-sm font-bold truncate transition-colors duration-150',
                                            selectedFeatureId === feature.id
                                                ? (isMedik ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400')
                                                : 'text-slate-800 dark:text-slate-200'
                                        ]"
                                    >
                                        {{ feature.name }}
                                    </div>
                                    <span 
                                        :class="[
                                            'text-[10px] block mt-0.5 font-medium transition-colors duration-150',
                                            selectedFeatureId === feature.id
                                                ? (isMedik ? 'text-indigo-500/70 dark:text-indigo-400/70' : 'text-emerald-500/70 dark:text-emerald-400/70')
                                                : 'text-slate-400 dark:text-slate-500'
                                        ]"
                                    >
                                        {{ feature.feature_categories?.length || 0 }} {{ __('pages.services.unit_kategori_suffix') }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

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

                        <div v-if="activeFeature.feature_categories && activeFeature.feature_categories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div 
                                v-for="cat in activeFeature.feature_categories" 
                                :key="cat.id"
                                @click="selectCategory(cat.id)"
                                :class="[
                                    'p-4 rounded-xl border text-left transition-all duration-200 select-none cursor-pointer relative group flex items-start justify-between gap-3 h-auto min-h-[84px]',
                                    selectedCategoryId === cat.id
                                        ? (isMedik 
                                            ? 'border-indigo-500 bg-indigo-50/10 dark:bg-indigo-950/10 text-indigo-950 dark:text-white shadow-sm'
                                            : 'border-emerald-500 bg-emerald-50/10 dark:bg-emerald-950/10 text-emerald-950 dark:text-white shadow-sm')
                                        : 'border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 hover:border-slate-200 dark:hover:border-slate-700'
                                ]"
                            >
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold uppercase tracking-wide leading-tight group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors duration-150">
                                        {{ cat.name }}
                                    </h4>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-relaxed mt-1.5 whitespace-normal break-words">
                                        {{ cat.description || __('Tidak ada deskripsi kategori.') }}
                                    </p>
                                </div>
                                <!-- Checkbox style indicator -->
                                <div 
                                    :class="[
                                        'h-4 w-4 rounded-full border flex items-center justify-center flex-shrink-0 mt-0.5',
                                        selectedCategoryId === cat.id
                                            ? (isMedik ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-emerald-500 bg-emerald-600 text-white')
                                            : 'border-slate-300 dark:border-slate-700'
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
                            <div class="bg-slate-50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-850 rounded-xl p-3.5 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">{{ __('pages.services.kategori_terpilih') }}</span>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-150 uppercase mt-0.5">{{ activeCategory?.name }}</div>
                                </div>
                                <span 
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                        isMedik 
                                            ? 'bg-indigo-50/80 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400' 
                                            : 'bg-emerald-50/80 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                    ]"
                                >
                                    {{ activeFeature?.name }}
                                </span>
                            </div>

                            <!-- Room Picker -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    {{ __('pages.services.pilih_ruangan') }} <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <select 
                                        v-model="form.room_id" 
                                        required
                                        class="w-full h-11 pl-4 pr-10 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                                    >
                                        <option value="" disabled>{{ __('pages.services.pilih_ruangan_default') }}</option>
                                        <option v-for="room in rooms" :key="room.id" :value="room.id">
                                            {{ room.name }} ({{ room.location_floor }})
                                        </option>
                                    </select>
                                </div>
                                <div v-if="form.errors.room_id" class="text-[10px] text-red-500 font-semibold">{{ form.errors.room_id }}</div>
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
                                    class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 leading-relaxed"
                                ></textarea>
                                <div v-if="form.errors.problem_description" class="text-[10px] text-red-500 font-semibold">{{ form.errors.problem_description }}</div>
                            </div>

                            <!-- Media Attachments (Optional) -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        {{ __('pages.services.lampiran_foto') }}
                                        <span class="text-[10px] font-medium normal-case tracking-normal text-slate-400 dark:text-slate-500 ml-1">({{ __('pages.services.lampiran_opsional') }})</span>
                                    </label>
                                    <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ attachmentPreviews.length }}/{{ MAX_FILES }}</span>
                                </div>

                                <!-- Hidden inputs -->
                                <input ref="fileInputRef" type="file" class="hidden" accept="image/*,video/*" multiple @change="handleFileSelect" />
                                <input ref="cameraInputRef" type="file" class="hidden" accept="image/*" capture="environment" @change="handleFileSelect" />

                                <!-- Previews Grid -->
                                <div v-if="attachmentPreviews.length > 0" class="grid grid-cols-3 gap-2">
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
                                                ? (isMedik ? 'border-indigo-400 bg-indigo-50/20 dark:bg-indigo-950/10' : 'border-emerald-400 bg-emerald-50/20 dark:bg-emerald-950/10')
                                                : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/30 dark:bg-slate-950/20'
                                        ]"
                                    >
                                        <div :class="[
                                            'h-9 w-9 rounded-full flex items-center justify-center mb-1.5',
                                            isMedik 
                                                ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-500 dark:text-indigo-400'
                                                : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-500 dark:text-emerald-400'
                                        ]">
                                            <UploadCloud class="h-4.5 w-4.5" />
                                        </div>
                                        <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">{{ __('pages.services.lampiran_upload_label') }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.lampiran_formats') }} ({{ __('pages.services.lampiran_max_size') }})</p>
                                    </div>

                                    <!-- Camera Button -->
                                    <button
                                        type="button"
                                        @click="cameraInputRef?.click()"
                                        :class="[
                                            'w-full h-10 rounded-xl border text-xs font-semibold flex items-center justify-center gap-2 transition duration-150',
                                            isMedik 
                                                ? 'border-indigo-200 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/20'
                                                : 'border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
                                        ]"
                                    >
                                        <Camera class="h-4 w-4" />
                                        {{ __('pages.services.lampiran_kamera') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                :class="[
                                    'w-full h-11 text-xs font-bold rounded-xl text-white shadow-sm flex items-center justify-center gap-2 transition duration-200 disabled:opacity-50',
                                    isMedik 
                                        ? 'bg-indigo-600 hover:bg-indigo-500' 
                                        : 'bg-emerald-600 hover:bg-emerald-500'
                                ]"
                            >
                                <Send class="h-4 w-4" />
                                <span>{{ form.processing ? __('pages.services.btn_sending') : __('pages.services.btn_kirim') }}</span>
                            </button>
                        </form>
                    </div>

                </div>

                <!-- Empty State (No features available) -->
                <div 
                    v-else 
                    class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4"
                >
                    <div class="h-16 w-16 rounded-full bg-slate-50 dark:bg-slate-950 flex items-center justify-center text-slate-450 dark:text-slate-500">
                        <AlertCircle class="h-8 w-8" />
                    </div>
                    <div class="space-y-1 max-w-sm">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('pages.services.empty_features_title') }}</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-505 leading-relaxed">
                            {{ __('pages.services.empty_features_desc') }}
                        </p>
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
</style>