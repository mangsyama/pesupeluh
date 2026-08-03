<script setup>
import { ref, computed, watch, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Edit2, Trash2, X, Stethoscope, ShieldCheck } from '@lucide/vue';

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
                proxy.$toast(proxy.__('pages.service_management.supporting_units.unit.toast_updated'), 'success');
            }
        });
    } else {
        unitForm.post(route('service-management.units.store'), {
            onSuccess: () => {
                showUnitModal.value = false;
                unitForm.reset();
                proxy.$toast(proxy.__('pages.service_management.supporting_units.unit.toast_added'), 'success');
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
            router.delete(route('service-management.units.destroy', unit.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.service_management.supporting_units.unit.toast_deleted'), 'success');
                }
            });
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
        <Teleport to="body">
            <div v-if="showUnitModal" @click.self="showUnitModal = false" class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-md bg-white dark:bg-slate-900 border-0 rounded-none sm:rounded-2xl shadow-2xl overflow-hidden transition-all duration-300 flex flex-col">
                    <!-- Header Modal Warna Hijau -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 bg-emerald-600 dark:bg-emerald-700 text-white rounded-none sm:rounded-t-2xl shrink-0 shadow-sm">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>{{ isEditingUnit ? 'Edit Unit Penunjang' : 'Tambah Unit Penunjang' }}</span>
                        </h3>
                        <button type="button" @click="showUnitModal = false" class="p-1.5 text-emerald-100 hover:text-white hover:bg-white/10 rounded-lg transition-colors" aria-label="Tutup modal">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitUnitForm" class="flex flex-col flex-1 overflow-hidden min-h-0">
                        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Kelompok Layanan</label>
                                <SearchableSelect
                                    v-model="unitForm.type"
                                    :options="typeOptions"
                                    :searchable="false"
                                    value-key="value"
                                    label-key="label"
                                    placeholder="Pilih Kelompok Layanan..."
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Nama Unit Penunjang</label>
                                <input 
                                    v-model="unitForm.name"
                                    @input="autoGenerateSlug"
                                    type="text" 
                                    class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                    placeholder="Contoh: IPSRS"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Slug URL / Identifier</label>
                                <input 
                                    v-model="unitForm.slug"
                                    type="text" 
                                    class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                    placeholder="ipsrs"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Deskripsi Singkat</label>
                                <textarea 
                                    v-model="unitForm.description"
                                    rows="3" 
                                    class="w-full p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                    placeholder="Jelaskan peran unit penunjang ini..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Status Ketersediaan</label>
                                <SearchableSelect
                                    v-model="unitForm.status"
                                    :options="statusOptions"
                                    :searchable="false"
                                    value-key="value"
                                    label-key="label"
                                    placeholder="Pilih Status Ketersediaan..."
                                />
                            </div>
                        </div>

                        <!-- Fixed Bottom Footer (pinned at bottom) -->
                        <div class="flex justify-end gap-3 px-5 sm:px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/50 flex-shrink-0">
                            <button type="button" @click="showUnitModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="unitForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-sm rounded-xl transition duration-150 border-0 shadow-sm disabled:opacity-50">Simpan Unit</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
