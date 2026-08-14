<script setup>
import { ref, computed, watch, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Edit2, Trash2, X, Stethoscope, ShieldCheck, ChevronLeft, ChevronRight, Activity } from '@lucide/vue';

const props = defineProps({
    supportingUnits: {
        type: Array,
        default: () => []
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const { proxy } = getCurrentInstance();

const showUnitModal = ref(false);
const isEditingUnit = ref(false);

const handleEscapeKeyUnit = (e) => {
    if (e.key === 'Escape' && showUnitModal.value) {
        showUnitModal.value = false;
    }
};

const handlePopStateUnit = () => {
    if (showUnitModal.value) {
        showUnitModal.value = false;
    }
};

let pushHistoryFlagUnit = false;

watch(showUnitModal, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleEscapeKeyUnit);
        window.addEventListener('popstate', handlePopStateUnit);
        try {
            window.history.pushState({ modalOpen: true }, '');
            pushHistoryFlagUnit = true;
        } catch (e) {
            // ignore
        }
    } else {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', handleEscapeKeyUnit);
        window.removeEventListener('popstate', handlePopStateUnit);

        if (pushHistoryFlagUnit && window.history.state && window.history.state.modalOpen) {
            pushHistoryFlagUnit = false;
            try {
                window.history.back();
            } catch (e) {
                // ignore
            }
        } else {
            pushHistoryFlagUnit = false;
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleEscapeKeyUnit);
    window.removeEventListener('popstate', handlePopStateUnit);
    document.body.style.overflow = '';
});

const unitForm = useForm({
    id: null,
    type: 'NON_MEDIK',
    name: '',
    slug: '',
    description: '',
    status: 'IN_DEVELOPMENT'
});

const filteredUnits = computed(() => {
    const list = props.supportingUnits || [];
    if (!props.searchQuery.trim()) return list;
    const query = props.searchQuery.toLowerCase();
    
    return list.filter(u => 
        (u.name && u.name.toLowerCase().includes(query)) ||
        (u.slug && u.slug.toLowerCase().includes(query)) ||
        (u.description && u.description.toLowerCase().includes(query))
    );
});

const medikUnits = computed(() => {
    return filteredUnits.value.filter(u => u.type === 'MEDIK');
});

const nonMedikUnits = computed(() => {
    return filteredUnits.value.filter(u => u.type !== 'MEDIK');
});

const isTypeDropdownOpen = ref(false);
const isStatusDropdownOpen = ref(false);
const typeDropdownRef = ref(null);
const statusDropdownRef = ref(null);

const typeOptions = [
    { value: 'MEDIK', label: 'Penunjang Medik' },
    { value: 'NON_MEDIK', label: 'Penunjang Non-Medik' },
];

const statusOptions = computed(() => [
    { value: 'ACTIVE', label: proxy.__('pages.service_management.supporting_units.unit.status_active') || 'Aktif' },
    { value: 'IN_DEVELOPMENT', label: proxy.__('pages.service_management.supporting_units.unit.status_dev') || 'Dalam Pengembangan' },
    { value: 'MAINTENANCE', label: 'Pemeliharaan / Maintenance' },
    { value: 'INACTIVE', label: proxy.__('pages.service_management.supporting_units.unit.status_inactive') || 'Non-Aktif' }
]);

const selectedTypeLabel = computed(() => {
    const opt = typeOptions.find(t => t.value === unitForm.type);
    return opt ? opt.label : '';
});

const selectedStatusLabel = computed(() => {
    const opt = statusOptions.value.find(s => s.value === unitForm.status);
    return opt ? opt.label : '';
});

const toggleTypeDropdown = (event) => {
    event?.stopPropagation();
    isTypeDropdownOpen.value = !isTypeDropdownOpen.value;
    isStatusDropdownOpen.value = false;
};

const selectType = (val) => {
    unitForm.type = val;
    isTypeDropdownOpen.value = false;
};

const toggleStatusDropdown = (event) => {
    event?.stopPropagation();
    isStatusDropdownOpen.value = !isStatusDropdownOpen.value;
    isTypeDropdownOpen.value = false;
};

const selectStatus = (val) => {
    unitForm.status = val;
    isStatusDropdownOpen.value = false;
};

const autoGenerateSlug = () => {
    if (!isEditingUnit.value && unitForm.name) {
        unitForm.slug = unitForm.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
};

const openAddUnitModal = () => {
    isEditingUnit.value = false;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.type = 'NON_MEDIK';
    unitForm.status = 'IN_DEVELOPMENT';
    isTypeDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    showUnitModal.value = true;
};

const openEditUnitModal = (unit) => {
    isEditingUnit.value = true;
    unitForm.clearErrors();
    unitForm.id = unit.id;
    unitForm.type = unit.type || 'NON_MEDIK';
    unitForm.name = unit.name;
    unitForm.slug = unit.slug || '';
    unitForm.description = unit.description || '';
    unitForm.status = unit.status || 'IN_DEVELOPMENT';
    isTypeDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    showUnitModal.value = true;
};

const submitUnitForm = () => {
    if (isEditingUnit.value) {
        unitForm.put(route('service-management.units.update', unitForm.id), {
            onSuccess: () => {
                showUnitModal.value = false;
            }
        });
    } else {
        unitForm.post(route('service-management.units.store'), {
            onSuccess: () => {
                showUnitModal.value = false;
                unitForm.reset();
            }
        });
    }
};

const deleteUnit = (unit) => {
    proxy.$swal({
        title: proxy.__('pages.service_management.supporting_units.unit.confirm_delete_title'),
        text: proxy.__('pages.service_management.supporting_units.unit.confirm_delete_text').replace('{name}', unit.name),
        icon: 'error',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('global.yes_delete'),
        cancelButtonText: proxy.__('global.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('service-management.units.destroy', unit.id));
        }
    });
};

defineExpose({
    openAddUnitModal
});
</script>

<template>
    <div class="w-full">
        <!-- Empty State -->
        <div v-if="filteredUnits.length === 0" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 text-center text-slate-400 dark:text-slate-500">
            {{ __('Tidak ada data unit penunjang.') }}
        </div>

        <template v-else>
            <!-- 2 Column Layout (Left & Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                
                <!-- LEFT COLUMN: PENUNJANG MEDIK -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold flex-shrink-0">
                            <Stethoscope class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white tracking-wide">
                                Penunjang Medik
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                Daftar unit penunjang operasional medis ( misal: SIMRS, CSSD, dll. )
                            </p>
                        </div>
                    </div>

                    <div v-if="medikUnits.length === 0" class="text-center py-6 text-xs text-slate-400 dark:text-slate-500 italic">
                        Belum ada data unit penunjang medik.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div 
                            v-for="unit in medikUnits" 
                            :key="unit.id"
                            class="bg-slate-50/70 dark:bg-slate-800/40 border border-slate-150 dark:border-slate-800/60 rounded-xl p-4 shadow-2xs hover:shadow-xs transition duration-150 flex flex-col justify-between"
                        >
                            <div>
                                <!-- Header Row -->
                                <div class="flex justify-between items-start pb-2.5 mb-2.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">
                                        {{ unit.name }}
                                    </h4>

                                    <div class="flex items-center gap-1">
                                        <button 
                                            @click="openEditUnitModal(unit)"
                                            class="p-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 transition duration-150"
                                            :title="__('pages.service_management.supporting_units.unit.title_edit')"
                                        >
                                            <Edit2 class="h-3 w-3" />
                                        </button>
                                        <button 
                                            @click="deleteUnit(unit)"
                                            class="p-1 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 transition duration-150"
                                            :title="__('global.delete')"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Description & Status -->
                                <div class="space-y-2">
                                    <p v-if="unit.description" class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-2">
                                        {{ unit.description }}
                                    </p>
                                    <div class="pt-1">
                                        <span 
                                            :class="[
                                                'text-[9px] px-2 py-0.5 rounded-full font-extrabold tracking-wide uppercase inline-flex items-center gap-1.5',
                                                unit.status === 'ACTIVE' 
                                                    ? 'bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400' 
                                                    : (unit.status === 'IN_DEVELOPMENT' 
                                                        ? 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400' 
                                                        : 'bg-rose-100/80 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400')
                                            ]"
                                        >
                                            <span v-if="unit.status === 'ACTIVE'" class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                            {{ unit.status === 'ACTIVE' ? 'Aktif' : (unit.status === 'IN_DEVELOPMENT' ? 'Dalam Pengembangan' : (unit.status === 'MAINTENANCE' ? 'Pemeliharaan' : 'Non-Aktif')) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: PENUNJANG NON-MEDIK -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex items-center justify-center font-bold flex-shrink-0">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white tracking-wide">
                                Penunjang Non-Medik
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                Daftar unit pemeliharaan dan sarana prasarana ( misal: IPSRS, dll. )
                            </p>
                        </div>
                    </div>

                    <div v-if="nonMedikUnits.length === 0" class="text-center py-6 text-xs text-slate-400 dark:text-slate-500 italic">
                        Belum ada data unit penunjang non-medik.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div 
                            v-for="unit in nonMedikUnits" 
                            :key="unit.id"
                            class="bg-slate-50/70 dark:bg-slate-800/40 border border-slate-150 dark:border-slate-800/60 rounded-xl p-4 shadow-2xs hover:shadow-xs transition duration-150 flex flex-col justify-between"
                        >
                            <div>
                                <!-- Header Row -->
                                <div class="flex justify-between items-start pb-2.5 mb-2.5 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <h4 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">
                                        {{ unit.name }}
                                    </h4>

                                    <div class="flex items-center gap-1">
                                        <button 
                                            @click="openEditUnitModal(unit)"
                                            class="p-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 transition duration-150"
                                            :title="__('pages.service_management.supporting_units.unit.title_edit')"
                                        >
                                            <Edit2 class="h-3 w-3" />
                                        </button>
                                        <button 
                                            @click="deleteUnit(unit)"
                                            class="p-1 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 transition duration-150"
                                            :title="__('global.delete')"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Description & Status -->
                                <div class="space-y-2">
                                    <p v-if="unit.description" class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-2">
                                        {{ unit.description }}
                                    </p>
                                    <div class="pt-1">
                                        <span 
                                            :class="[
                                                'text-[9px] px-2 py-0.5 rounded-full font-extrabold tracking-wide uppercase inline-flex items-center gap-1.5',
                                                unit.status === 'ACTIVE' 
                                                    ? 'bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400' 
                                                    : (unit.status === 'IN_DEVELOPMENT' 
                                                        ? 'bg-amber-100/80 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400' 
                                                        : 'bg-rose-100/80 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400')
                                            ]"
                                        >
                                            <span v-if="unit.status === 'ACTIVE'" class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                            {{ unit.status === 'ACTIVE' ? 'Aktif' : (unit.status === 'IN_DEVELOPMENT' ? 'Dalam Pengembangan' : (unit.status === 'MAINTENANCE' ? 'Pemeliharaan' : 'Non-Aktif')) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </template>

        <!-- SUPPORTING UNIT MODAL -->
        <Modal :show="showUnitModal" @close="showUnitModal = false" max-width="md">
            <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                <!-- Solid Emerald Sticky Header (No X button) -->
                <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                    <div class="flex items-center gap-3 pr-2">
                        <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                            <Activity class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-white leading-tight">
                                {{ isEditingUnit ? 'Edit Unit Penunjang' : 'Tambah Unit Penunjang' }}
                            </h3>
                            <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                {{ isEditingUnit ? 'Perbarui data unit penunjang' : 'Isi data unit penunjang baru' }}
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitUnitForm" class="flex flex-col flex-1 justify-between min-h-0">
                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Kelompok Layanan</label>
                            <SearchableSelect
                                v-model="unitForm.type"
                                :options="typeOptions"
                                :searchable="false"
                                value-key="value"
                                label-key="label"
                                placeholder="Pilih Kelompok Layanan..."
                            />
                            <div v-if="unitForm.errors.type" class="text-[10px] text-red-500 font-semibold mt-1">{{ unitForm.errors.type }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Nama Unit Penunjang <span class="text-red-500">*</span></label>
                            <input 
                                v-model="unitForm.name"
                                @input="autoGenerateSlug"
                                type="text" 
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                placeholder="Contoh: IPSRS"
                                required
                            />
                            <div v-if="unitForm.errors.name" class="text-[10px] text-red-500 font-semibold mt-1">{{ unitForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Slug URL / Identifier <span class="text-red-500">*</span></label>
                            <input 
                                v-model="unitForm.slug"
                                type="text" 
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                placeholder="ipsrs"
                                required
                            />
                            <div v-if="unitForm.errors.slug" class="text-[10px] text-red-500 font-semibold mt-1">{{ unitForm.errors.slug }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Deskripsi Singkat</label>
                            <textarea 
                                v-model="unitForm.description"
                                rows="4" 
                                class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150 min-h-[100px]"
                                placeholder="Jelaskan peran unit penunjang ini..."
                            ></textarea>
                            <div v-if="unitForm.errors.description" class="text-[10px] text-red-500 font-semibold mt-1">{{ unitForm.errors.description }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Status Ketersediaan</label>
                            <SearchableSelect
                                v-model="unitForm.status"
                                :options="statusOptions"
                                :searchable="false"
                                value-key="value"
                                label-key="label"
                                placeholder="Pilih Status Ketersediaan..."
                            />
                            <div v-if="unitForm.errors.status" class="text-[10px] text-red-500 font-semibold mt-1">{{ unitForm.errors.status }}</div>
                        </div>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                        <SecondaryButton type="button" @click="showUnitModal = false" class="h-11 px-5">{{ __('global.cancel') || 'Batal' }}</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="unitForm.processing" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold">
                            {{ unitForm.processing ? 'Menyimpan...' : 'Simpan Unit' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
