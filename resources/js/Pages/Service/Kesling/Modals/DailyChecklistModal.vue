<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    ClipboardCheck, 
    Droplet, 
    Sparkles, 
    Wind, 
    Box,
    Check,
    CheckCircle2,
    X,
    AlertCircle,
    Camera,
    Image as ImageIcon,
    Trash2
} from '@lucide/vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    rooms: {
        type: Array,
        default: () => []
    },
    toilets: {
        type: Array,
        default: () => []
    },
    initialToilet: {
        type: Object,
        default: null
    },
    existingInspection: {
        type: Object,
        default: null
    },
    selectedDate: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['close']);

// Titik Toilet dari Manajemen Toilet
const availableToilets = computed(() => {
    if (props.toilets && props.toilets.length > 0) {
        return props.toilets.map(t => {
            const b = t.building ? t.building : '';
            const f = t.floor ? t.floor : '';
            const loc = [b, f].filter(Boolean).join(' - ');
            const label = loc ? `${t.name} (${loc})` : t.name;
            return {
                id: t.id,
                name: t.name,
                location: loc,
                code: t.code,
                status: t.status || 'AKTIF',
                value: t.id,
                display: label
            };
        });
    }

    // Fallback jika data toilet kosong
    return (props.rooms || []).map(r => ({
        id: r.id,
        name: r.name,
        location: [r.building_name, r.location_floor].filter(Boolean).join(' - '),
        code: r.code,
        status: 'AKTIF',
        value: r.id,
        display: `${r.name} (${r.building_name || 'Gedung Utama'} - ${r.location_floor || 'Lantai 1'})`
    }));
});

// Auto-determine current shift based on hour (WITA / Local time)
const getCurrentShift = () => {
    const now = new Date();
    const hour = now.getHours();
    if (hour >= 7 && hour < 14) return 'PAGI (07:00 - 14:00 WITA)';
    if (hour >= 14 && hour < 21) return 'SIANG (14:00 - 21:00 WITA)';
    return 'MALAM (21:00 - 07:00 WITA)';
};

// Form state
const form = useForm({
    inspection_id: null,
    toilet_id: null,
    inspection_date: '',
    location_point: '',
    shift: getCurrentShift(),
    water_flush_ok: null,
    floor_mirror_clean: null,
    supplies_ready: null,
    air_fragrance_ok: null,
    notes: '',
    photo: null,
    photos: [],
    existing_photos: [],
    photos_sync: true,
});

const onToiletChange = () => {
    const found = availableToilets.value.find(t => String(t.id) === String(form.toilet_id));
    if (found) {
        form.location_point = found.display;
    }
};

const cameraInput = ref(null);
const fileInput = ref(null);
const photoPreview = ref(null);
const photoItems = ref([]);
const validationError = ref('');

const isEditMode = computed(() => !!form.inspection_id);

watch(() => props.show, (newVal) => {
    if (newVal) {
        validationError.value = '';
        form.inspection_date = props.selectedDate || '';

        if (props.existingInspection) {
            form.inspection_id = props.existingInspection.id;
            const insp = props.existingInspection;
            form.toilet_id = insp.toilet_id || (insp.toilet ? insp.toilet.id : null);
            if (insp.toilet) {
                const b = insp.toilet.building || '';
                const f = insp.toilet.floor || '';
                const loc = [b, f].filter(Boolean).join(' - ');
                form.location_point = loc ? `${insp.toilet.name} (${loc})` : insp.toilet.name;
            } else {
                form.location_point = insp.location || '';
            }
            form.shift = insp.shift || getCurrentShift();
            form.water_flush_ok = insp.water_flush_ok ?? true;
            form.floor_mirror_clean = insp.floor_mirror_clean ?? true;
            form.supplies_ready = insp.supplies_ready ?? true;
            form.air_fragrance_ok = insp.air_fragrance_ok ?? true;
            form.notes = insp.notes || '';
            form.photo = null;
            const existingList = Array.isArray(insp.photo_list) && insp.photo_list.length > 0
                ? insp.photo_list
                : (insp.photo ? [insp.photo] : []);
            photoItems.value = existingList.map((url, idx) => ({
                file: null,
                previewUrl: url,
                name: `Foto Patroli #${idx + 1}`,
                isExisting: true,
            }));
            photoPreview.value = existingList[0] || null;
        } else if (props.initialToilet) {
            form.inspection_id = null;
            const t = props.initialToilet;
            form.toilet_id = t.id;
            const b = t.building ? t.building : '';
            const f = t.floor ? t.floor : '';
            const loc = [b, f].filter(Boolean).join(' - ');
            form.location_point = loc ? `${t.name} (${loc})` : t.name;
            form.shift = getCurrentShift();
            form.water_flush_ok = null;
            form.floor_mirror_clean = null;
            form.supplies_ready = null;
            form.air_fragrance_ok = null;
            form.notes = '';
            form.photo = null;
            photoItems.value = [];
            photoPreview.value = null;
        } else {
            form.inspection_id = null;
            form.toilet_id = null;
            form.location_point = '';
            form.shift = getCurrentShift();
            form.water_flush_ok = null;
            form.floor_mirror_clean = null;
            form.supplies_ready = null;
            form.air_fragrance_ok = null;
            form.notes = '';
            form.photo = null;
            photoItems.value = [];
            photoPreview.value = null;
        }
    }
});

const isAllCriteriaSelected = computed(() => {
    return form.water_flush_ok !== null &&
           form.floor_mirror_clean !== null &&
           form.supplies_ready !== null &&
           form.air_fragrance_ok !== null;
});

const triggerCamera = () => {
    validationError.value = '';
    cameraInput.value?.click();
};

const openFileInput = () => {
    validationError.value = '';
    fileInput.value?.click();
};

const handlePhotoChange = (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;

    const maxFiles = 5;
    const remainingSlots = maxFiles - photoItems.value.length;
    if (remainingSlots <= 0) {
        validationError.value = `Maksimal ${maxFiles} foto yang dapat diunggah.`;
        event.target.value = '';
        return;
    }

    const filesToAdd = files.slice(0, remainingSlots);
    if (files.length > remainingSlots) {
        validationError.value = `Hanya ${remainingSlots} foto lagi yang dapat ditambahkan (maksimal ${maxFiles} foto).`;
    }

    filesToAdd.forEach((file) => {
        if (file.size > 10 * 1024 * 1024) {
            validationError.value = `Ukuran foto "${file.name}" melebihi batas maksimal 10MB.`;
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            photoItems.value.push({
                file,
                previewUrl: e.target.result,
                name: file.name,
                isExisting: false,
            });
            photoPreview.value = photoItems.value[0]?.previewUrl || null;
        };
        reader.readAsDataURL(file);
    });

    event.target.value = '';
};

const removePhoto = (index) => {
    if (typeof index === 'number') {
        photoItems.value.splice(index, 1);
    } else {
        photoItems.value = [];
    }
    photoPreview.value = photoItems.value[0]?.previewUrl || null;
    if (photoItems.value.length === 0) {
        form.photo = null;
        if (cameraInput.value) cameraInput.value.value = '';
        if (fileInput.value) fileInput.value.value = '';
    }
};

const handleClose = () => {
    validationError.value = '';
    form.reset();
    form.water_flush_ok = null;
    form.floor_mirror_clean = null;
    form.supplies_ready = null;
    form.air_fragrance_ok = null;
    removePhoto();
    emit('close');
};

const submit = () => {
    validationError.value = '';

    if (!form.toilet_id && !form.location_point) {
        validationError.value = 'Silakan pilih titik toilet / lokasi inspeksi.';
        return;
    }

    if (!isAllCriteriaSelected.value) {
        validationError.value = 'Mohon tentukan status centang (OK) atau silang (Kendala) untuk seluruh 4 komponen fisik.';
        return;
    }

    if (!form.notes || !form.notes.trim()) {
        validationError.value = 'Catatan patroli wajib diisi.';
        return;
    }

    if (photoItems.value.length === 0) {
        validationError.value = 'Foto bukti patroli toilet wajib disertakan.';
        return;
    }

    form.photos = photoItems.value.filter(p => p.file).map(p => p.file);
    form.existing_photos = photoItems.value.filter(p => p.isExisting).map(p => p.previewUrl);
    form.photos_sync = true;

    form.post(route('pansinar.inspections.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            handleClose();
        }
    });
};
</script>

<template>
    <Modal :show="show" @close="handleClose" max-width="lg">
        <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900 justify-between">
            <!-- Solid Emerald Sticky Header -->
            <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                <div class="flex items-center gap-3 pr-2">
                    <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                        <ClipboardCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black tracking-tight text-white leading-tight">
                            {{ isEditMode ? 'Edit Hasil Ceklis Patroli Sanitasi' : 'Form Ceklis Patroli Sanitasi Toilet' }}
                        </h3>
                        <p class="text-[11px] text-emerald-100/90 dark:text-emerald-200/80 font-medium leading-tight mt-0.5">
                            {{ isEditMode ? 'Perbarui evaluasi 4 pilar standar sanitasi titik ini' : 'Pemeriksaan fisik langsung 4 pilar standar sanitasi' }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col flex-1 min-h-0 justify-between">
                <div class="p-5 sm:p-6 overflow-y-auto space-y-4 max-h-[calc(100dvh-220px)] sm:max-h-[65vh] overscroll-contain touch-pan-y">
                    <!-- Validation Warning Banner -->
                    <div v-if="validationError" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400" />
                        <span>{{ validationError }}</span>
                    </div>

                    <!-- Location Point Selection (Mengambil dari Manajemen Toilet) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Titik Toilet / Lokasi Inspeksi <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="form.toilet_id"
                            @change="onToiletChange"
                            required
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-none"
                        >
                            <option :value="null" disabled>Pilih Titik Toilet yang Diinspeksi...</option>
                            <option 
                                v-for="toilet in availableToilets" 
                                :key="toilet.id" 
                                :value="toilet.id"
                                :disabled="toilet.status === 'NONAKTIF'"
                            >
                                {{ toilet.display }} {{ toilet.status === 'NONAKTIF' ? '— [NONAKTIF]' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- 4 Criteria Checklist: Tombol Icon Saja (Centang & X) Tanpa Teks -->
                    <div class="space-y-2.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Komponen Pemeriksaan Fisik <span class="text-rose-500">*</span>
                            </label>
                        </div>

                        <!-- Pilar 1: Air Bersih & Kloset -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/40 flex items-center justify-between gap-3 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                                    <Droplet class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white leading-tight">Ketersediaan Air</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">Aliran air bersih lancar serta kran, selang, atau flush berfungsi</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="form.water_flush_ok = true"
                                    title="Kondisi Sesuai / Baik (OK)"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.water_flush_ok === true
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs ring-2 ring-emerald-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 hover:border-emerald-300 dark:hover:text-emerald-400'
                                    ]"
                                >
                                    <Check class="w-4 h-4 stroke-[2.5]" />
                                </button>
                                <button
                                    type="button"
                                    @click="form.water_flush_ok = false"
                                    title="Ada Kendala / Rusak"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.water_flush_ok === false
                                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs ring-2 ring-rose-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400'
                                    ]"
                                >
                                    <X class="w-4 h-4 stroke-[2.5]" />
                                </button>
                            </div>
                        </div>

                        <!-- Pilar 2: Lantai & Cermin -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/40 flex items-center justify-between gap-3 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                                    <Sparkles class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white leading-tight">Kebersihan</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">Kondisi lantai, tembok, kloset, serta wastafel/cermin bersih</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="form.floor_mirror_clean = true"
                                    title="Kondisi Sesuai / Baik (OK)"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.floor_mirror_clean === true
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs ring-2 ring-emerald-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 hover:border-emerald-300 dark:hover:text-emerald-400'
                                    ]"
                                >
                                    <Check class="w-4 h-4 stroke-[2.5]" />
                                </button>
                                <button
                                    type="button"
                                    @click="form.floor_mirror_clean = false"
                                    title="Ada Kendala / Rusak"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.floor_mirror_clean === false
                                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs ring-2 ring-rose-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400'
                                    ]"
                                >
                                    <X class="w-4 h-4 stroke-[2.5]" />
                                </button>
                            </div>
                        </div>

                        <!-- Pilar 3: Sabun & Tisu -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/40 flex items-center justify-between gap-3 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                                    <Box class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white leading-tight">Ketersediaan Sabun & Tisu</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">Sabun/tisu tersedia sesuai fasilitas, tempat sampah terkelola</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="form.supplies_ready = true"
                                    title="Kondisi Sesuai / Baik (OK)"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.supplies_ready === true
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs ring-2 ring-emerald-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 hover:border-emerald-300 dark:hover:text-emerald-400'
                                    ]"
                                >
                                    <Check class="w-4 h-4 stroke-[2.5]" />
                                </button>
                                <button
                                    type="button"
                                    @click="form.supplies_ready = false"
                                    title="Ada Kendala / Rusak"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.supplies_ready === false
                                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs ring-2 ring-rose-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400'
                                    ]"
                                >
                                    <X class="w-4 h-4 stroke-[2.5]" />
                                </button>
                            </div>
                        </div>

                        <!-- Pilar 4: Keharuman & Sirkulasi -->
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/40 flex items-center justify-between gap-3 hover:border-slate-300 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400">
                                    <Wind class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-slate-900 dark:text-white leading-tight">Keharuman & Sirkulasi Udara</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">Ruangan bebas bau; pengharum, ventilasi/exhaust fan berfungsi</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="form.air_fragrance_ok = true"
                                    title="Kondisi Sesuai / Baik (OK)"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.air_fragrance_ok === true
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs ring-2 ring-emerald-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 hover:border-emerald-300 dark:hover:text-emerald-400'
                                    ]"
                                >
                                    <Check class="w-4 h-4 stroke-[2.5]" />
                                </button>
                                <button
                                    type="button"
                                    @click="form.air_fragrance_ok = false"
                                    title="Ada Kendala / Rusak"
                                    :class="[
                                        'h-9 w-9 rounded-xl flex items-center justify-center transition-all cursor-pointer border',
                                        form.air_fragrance_ok === false
                                            ? 'bg-rose-600 text-white border-rose-600 shadow-xs ring-2 ring-rose-500/20 scale-105'
                                            : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 hover:border-rose-300 dark:hover:text-rose-400'
                                    ]"
                                >
                                    <X class="w-4 h-4 stroke-[2.5]" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Patroli (Wajib) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Catatan Patroli / Temuan <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            v-model="form.notes"
                            rows="2"
                            required
                            placeholder="Tuliskan catatan kondisi fisik toilet atau temuan patroli (contoh: Kondisi bersih, sabun terisi penuh)..."
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 shadow-none resize-none leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Lampiran Foto Bukti Patroli (Wajib) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Foto Bukti Patroli <span class="text-rose-500">*</span>
                        </label>

                        <!-- Preview Foto Terpilih (Multi-file Grid) -->
                        <div v-if="photoItems.length > 0" class="space-y-2.5">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="(item, idx) in photoItems"
                                    :key="'insp-photo-' + idx"
                                    class="relative aspect-square rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                                >
                                    <img :src="item.previewUrl" :alt="item.name" class="w-full h-full object-cover" />
                                    <button
                                        type="button"
                                        @click="removePhoto(idx)"
                                        class="absolute top-1.5 right-1.5 h-6 w-6 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white flex items-center justify-center shadow-sm cursor-pointer transition active:scale-90"
                                        title="Hapus Foto Ini"
                                    >
                                        <X class="w-3.5 h-3.5 stroke-[2.5]" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-9 w-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">
                                            {{ photoItems.length }} / 5 Foto Terlampir
                                        </p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                            {{ photoItems.length < 5 ? 'Masih bisa tambah foto lagi' : 'Batas maksimal 5 foto tercapai' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full sm:w-auto shrink-0">
                                    <button
                                        v-if="photoItems.length < 5"
                                        type="button"
                                        @click="triggerCamera"
                                        class="h-9 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap"
                                        title="Tambah Foto Kamera"
                                    >
                                        <Camera class="h-3.5 w-3.5 shrink-0" />
                                        <span>Kamera</span>
                                    </button>
                                    <button
                                        v-if="photoItems.length < 5"
                                        type="button"
                                        @click="openFileInput"
                                        class="h-9 px-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap"
                                        title="Tambah dari Galeri"
                                    >
                                        <ImageIcon class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Galeri</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removePhoto()"
                                        class="col-span-2 sm:col-span-1 h-9 px-3 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-bold inline-flex items-center justify-center gap-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap"
                                        title="Hapus Semua Foto"
                                    >
                                        <Trash2 class="h-3.5 w-3.5 shrink-0" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Box dengan Pilihan: Buka Kamera vs Pilih Galeri (Sesuai Aduan Masyarakat) -->
                        <div
                            v-else
                            class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-4 sm:p-5 text-center bg-slate-50/50 dark:bg-slate-800/40 space-y-3"
                        >
                            <div class="flex flex-col items-center gap-1">
                                <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <Camera class="w-5 h-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Ambil Foto atau Pilih dari Galeri
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    Bisa pilih beberapa foto sekaligus (Maks. 5 foto, 10MB/file)
                                </p>
                            </div>

                            <!-- Tombol Pilihan: Buka Kamera & Pilih Galeri -->
                            <div class="flex flex-wrap items-center justify-center gap-2.5 w-full max-w-xs mx-auto pt-0.5">
                                <button
                                    type="button"
                                    @click="triggerCamera"
                                    class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-bold transition cursor-pointer shadow-xs"
                                >
                                    <Camera class="h-4 w-4 shrink-0" />
                                    <span>Buka Kamera</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openFileInput"
                                    class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 active:scale-95 text-slate-700 dark:text-slate-200 text-xs font-bold transition cursor-pointer shadow-xs"
                                >
                                    <ImageIcon class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <span>Pilih Galeri</span>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Inputs: Kamera & Galeri File (Multiple) -->
                        <input
                            ref="cameraInput"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            class="hidden"
                            @change="handlePhotoChange"
                        />
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            multiple
                            class="hidden"
                            @change="handlePhotoChange"
                        />
                    </div>
                </div>

                <!-- Sticky Action Footer -->
                <div class="sticky bottom-0 z-30 shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 p-4 pt-3.5 pb-8 sm:pb-5 sm:p-5 shadow-lg sm:shadow-none">
                    <!-- Mobile View: Stacked Full-Width Buttons identical to AduanMasyarakat -->
                    <div class="flex flex-col gap-2.5 w-full sm:hidden">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 font-semibold text-sm text-white transition-all duration-200 hover:bg-emerald-500 active:scale-[0.99] focus:outline-none disabled:cursor-not-allowed disabled:opacity-70 shadow-sm"
                        >
                            <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            <span v-else>{{ form.processing ? 'Menyimpan...' : 'Simpan Ceklis Inspeksi' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="handleClose"
                            :disabled="form.processing"
                            class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-semibold text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-[0.99] focus:outline-none disabled:opacity-50"
                        >
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Desktop View: Aligned to right -->
                    <div class="hidden sm:flex items-center justify-end gap-3">
                        <SecondaryButton type="button" @click="handleClose" class="h-11 px-5 cursor-pointer">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton 
                            type="submit" 
                            :disabled="form.processing" 
                            class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold cursor-pointer active:scale-95"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Ceklis Inspeksi' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
