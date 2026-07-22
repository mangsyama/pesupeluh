<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { useForm, router, Deferred } from '@inertiajs/vue3';
import { Edit2, Trash2, X, ChevronDown, Search, Check, Building2 } from '@lucide/vue';

const props = defineProps({
    divisions: {
        type: Array,
        default: () => []
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const { proxy } = getCurrentInstance();

const showDivisionModal = ref(false);
const isEditingDivision = ref(false);

const showUnitModal = ref(false);
const isEditingUnit = ref(false);

const divisionForm = useForm({
    id: null,
    name: '',
    description: ''
});

const unitForm = useForm({
    id: null,
    name: '',
    division_id: '',
    description: '',
    status: 'IN_DEVELOPMENT'
});

const filteredDivisions = computed(() => {
    const list = props.divisions || [];
    if (!props.searchQuery.trim()) return list;
    const query = props.searchQuery.toLowerCase();
    
    return list
        .filter(div => 
            div.name.toLowerCase().includes(query) || 
            (div.supporting_units && div.supporting_units.some(unit => unit.name.toLowerCase().includes(query)))
        )
        .map(div => ({
            ...div,
            supporting_units: div.supporting_units 
                ? div.supporting_units.filter(unit => unit.name.toLowerCase().includes(query))
                : []
        }));
});

const openAddDivisionModal = () => {
    isEditingDivision.value = false;
    divisionForm.reset();
    divisionForm.clearErrors();
    showDivisionModal.value = true;
};

const openEditDivisionModal = (div) => {
    isEditingDivision.value = true;
    divisionForm.clearErrors();
    divisionForm.id = div.id;
    divisionForm.name = div.name;
    divisionForm.description = div.description || '';
    showDivisionModal.value = true;
};

const submitDivisionForm = () => {
    if (isEditingDivision.value) {
        divisionForm.put(route('service-management.divisions.update', divisionForm.id), {
            onSuccess: () => {
                showDivisionModal.value = false;
                proxy.$toast(proxy.__('pages.service_management.supporting_units.division.toast_updated'), 'success');
            }
        });
    } else {
        divisionForm.post(route('service-management.divisions.store'), {
            onSuccess: () => {
                showDivisionModal.value = false;
                divisionForm.reset();
                proxy.$toast(proxy.__('pages.service_management.supporting_units.division.toast_added'), 'success');
            }
        });
    }
};

const deleteDivision = (div) => {
    proxy.$swal({
        title: proxy.__('pages.service_management.supporting_units.division.confirm_delete_title'),
        text: proxy.__('pages.service_management.supporting_units.division.confirm_delete_text').replace('{name}', div.name),
        icon: 'error',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('global.yes_delete'),
        cancelButtonText: proxy.__('global.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('service-management.divisions.destroy', div.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.service_management.supporting_units.division.toast_deleted'), 'success');
                },
                onError: (errors) => {
                    proxy.$swal({
                        title: proxy.__('pages.service_management.supporting_units.division.error_delete_title'),
                        text: proxy.__('pages.service_management.supporting_units.division.error_delete_text'),
                        icon: 'error',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

const isDivisionDropdownOpen = ref(false);
const divisionSearchQuery = ref('');

const dropdownDivisions = computed(() => {
    const q = divisionSearchQuery.value.trim().toLowerCase();
    const list = props.divisions || [];
    if (!q) return list;
    return list.filter(d => (d.name || '').toLowerCase().includes(q));
});

const selectedDivisionLabel = computed(() => {
    if (!unitForm.division_id) return '';
    const selected = (props.divisions || []).find(d => d.id === unitForm.division_id);
    return selected ? selected.name : '';
});

const toggleDivisionDropdown = () => {
    isDivisionDropdownOpen.value = !isDivisionDropdownOpen.value;
    if (isDivisionDropdownOpen.value) {
        divisionSearchQuery.value = '';
    }
};

const selectDivision = (divId) => {
    unitForm.division_id = divId;
    isDivisionDropdownOpen.value = false;
};

const isStatusDropdownOpen = ref(false);
const statusOptions = computed(() => [
    { value: 'ACTIVE', label: proxy.__('pages.service_management.supporting_units.unit.status_active') },
    { value: 'IN_DEVELOPMENT', label: proxy.__('pages.service_management.supporting_units.unit.status_dev') },
    { value: 'INACTIVE', label: proxy.__('pages.service_management.supporting_units.unit.status_inactive') }
]);

const selectedStatusLabel = computed(() => {
    const opt = statusOptions.value.find(s => s.value === unitForm.status);
    return opt ? opt.label : '';
});

const selectStatus = (val) => {
    unitForm.status = val;
    isStatusDropdownOpen.value = false;
};

const openAddUnitModal = () => {
    isEditingUnit.value = false;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.description = '';
    unitForm.status = 'IN_DEVELOPMENT';
    unitForm.division_id = props.divisions && props.divisions.length > 0 ? props.divisions[0].id : '';
    isDivisionDropdownOpen.value = false;
    isStatusDropdownOpen.value = false;
    showUnitModal.value = true;
};

const openEditUnitModal = (unit) => {
    isEditingUnit.value = true;
    unitForm.clearErrors();
    unitForm.id = unit.id;
    unitForm.name = unit.name;
    unitForm.division_id = unit.division_id;
    unitForm.description = unit.description || '';
    unitForm.status = unit.status || 'IN_DEVELOPMENT';
    isDivisionDropdownOpen.value = false;
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
                },
                onError: (errors) => {
                    proxy.$swal({
                        title: proxy.__('pages.service_management.supporting_units.unit.error_delete_title'),
                        text: proxy.__('pages.service_management.supporting_units.unit.error_delete_text'),
                        icon: 'error',
                        confirmButtonColor: '#10b981'
                    });
                }
            });
        }
    });
};

defineExpose({
    openAddDivisionModal,
    openAddUnitModal
});
</script>

<template>
    <div class="space-y-4">
        <!-- Grid Layout for Divisions and nested Units -->
        <Deferred data="divisions">
            <template #fallback>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-pulse">
                    <div v-for="i in 4" :key="'div-skel-' + i" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="space-y-2">
                                <div class="h-4 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="h-3 w-48 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                            </div>
                            <div class="h-6 w-12 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-3 w-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
                            <div class="p-3 bg-slate-50/50 dark:bg-slate-950/20 border border-slate-100 dark:border-slate-800 rounded-xl space-y-2">
                                <div class="flex justify-between">
                                    <div class="h-4 w-28 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                    <div class="h-4 w-12 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                                </div>
                                <div class="h-3 w-36 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #default>
                <div v-if="filteredDivisions.length === 0" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 text-center text-slate-400 dark:text-slate-500">
                    {{ __('pages.service_management.supporting_units.empty_data') }}
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="div in filteredDivisions" 
                        :key="div.id"
                        class="bg-white dark:bg-slate-900 border border-white dark:border-slate-800/80 rounded-2xl p-5 shadow-sm flex flex-col justify-between"
                    >
                        <div>
                            <!-- Division Header -->
                            <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wide">
                                            {{ div.name }}
                                        </h3>
                                        <p v-if="div.description" class="text-[11px] text-slate-400 dark:text-slate-500 leading-normal mt-0.5">
                                            {{ div.description }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        @click="openEditDivisionModal(div)"
                                        class="p-1.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                        :title="__('pages.service_management.supporting_units.division.title_edit')"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                    </button>
                                    <button 
                                        @click="deleteDivision(div)"
                                        class="p-1.5 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150"
                                        :title="__('global.delete')"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Nested Supporting Units List -->
                            <div class="space-y-2">
                                <div class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2 block">
                                    {{ __('pages.service_management.supporting_units.unit.list_title') }}
                                </div>
                                <div v-if="(div.supporting_units || []).length === 0" class="text-xs text-slate-400 dark:text-slate-500 italic py-2">
                                    {{ __('pages.service_management.supporting_units.unit.empty_unit') }}
                                </div>
                                <div 
                                    v-else
                                    v-for="unit in div.supporting_units" 
                                    :key="unit.id"
                                    class="p-3 bg-slate-50/50 dark:bg-slate-950/20 border border-slate-100 dark:border-slate-800/60 rounded-xl hover:border-slate-200 dark:hover:border-slate-700 transition duration-150 space-y-1.5"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                                {{ unit.name }}
                                            </span>
                                            <span 
                                                :class="[
                                                    'text-[8px] px-1.5 py-0.5 rounded font-extrabold tracking-wide uppercase',
                                                    unit.status === 'ACTIVE' 
                                                        ? 'bg-emerald-50 text-emerald-750 dark:bg-emerald-950/30 dark:text-emerald-400' 
                                                        : (unit.status === 'IN_DEVELOPMENT' 
                                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' 
                                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-455')
                                                ]"
                                            >
                                                {{ unit.status === 'IN_DEVELOPMENT' ? __('pages.service_management.supporting_units.unit.badge_dev') : (unit.status === 'ACTIVE' ? __('pages.service_management.supporting_units.unit.badge_active') : __('pages.service_management.supporting_units.unit.badge_inactive')) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button 
                                                @click="openEditUnitModal(unit)"
                                                class="p-1.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                                :title="__('pages.service_management.supporting_units.unit.title_edit')"
                                            >
                                                <Edit2 class="h-3 w-3" />
                                            </button>
                                            <button 
                                                @click="deleteUnit(unit)"
                                                class="p-1.5 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150"
                                                :title="__('global.delete')"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="unit.description" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-normal italic">
                                        {{ unit.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Deferred>

        <!-- DIVISION MODAL -->
        <Teleport to="body">
            <div v-if="showDivisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-955 dark:text-white">
                            {{ isEditingDivision ? __('pages.service_management.supporting_units.division.title_edit') : __('pages.service_management.supporting_units.division.title_add') }}
                        </h3>
                        <button type="button" @click="showDivisionModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitDivisionForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.division.label_name') }}</label>
                            <input 
                                v-model="divisionForm.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.division.placeholder_name')"
                            />
                            <div v-if="divisionForm.errors.name" class="text-xs text-red-500 mt-1">{{ divisionForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.division.label_description') }}</label>
                            <textarea 
                                v-model="divisionForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.division.placeholder_description')"
                            ></textarea>
                            <div v-if="divisionForm.errors.description" class="text-xs text-red-500 mt-1">{{ divisionForm.errors.description }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showDivisionModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="divisionForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50">{{ __('pages.service_management.supporting_units.division.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- SUPPORTING UNIT MODAL -->
        <Teleport to="body">
            <div v-if="showUnitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-955 dark:text-white">
                            {{ isEditingUnit ? __('pages.service_management.supporting_units.unit.title_edit') : __('pages.service_management.supporting_units.unit.title_add') }}
                        </h3>
                        <button type="button" @click="showUnitModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitUnitForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_name') }}</label>
                            <input 
                                v-model="unitForm.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.unit.placeholder_name')"
                            />
                            <div v-if="unitForm.errors.name" class="text-xs text-red-500 mt-1">{{ unitForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_division') }}</label>
                            <div class="relative">
                                <button 
                                    type="button"
                                    @click="toggleDivisionDropdown"
                                    class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                >
                                    <span v-if="selectedDivisionLabel" class="font-semibold text-slate-800 dark:text-slate-100 truncate">
                                        {{ selectedDivisionLabel }}
                                    </span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">
                                        Pilih Divisi...
                                    </span>
                                    <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isDivisionDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                </button>

                                <div 
                                    v-if="isDivisionDropdownOpen"
                                    class="absolute z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-2 shadow-lg"
                                >
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                        <input
                                            v-model="divisionSearchQuery"
                                            type="text"
                                            placeholder="Cari divisi..."
                                            class="w-full h-8 pl-8 pr-3 text-xs border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                        />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-1 pr-1">
                                        <button
                                            v-for="div in dropdownDivisions"
                                            :key="div.id"
                                            type="button"
                                            @click="selectDivision(div.id)"
                                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                            :class="unitForm.division_id === div.id ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                        >
                                            <span class="truncate">{{ div.name }}</span>
                                            <Check v-if="unitForm.division_id === div.id" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        </button>
                                        <div v-if="dropdownDivisions.length === 0" class="px-3 py-2 text-xs text-slate-400 italic text-center">
                                            Tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="unitForm.errors.division_id" class="text-xs text-red-500 mt-1">{{ unitForm.errors.division_id }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_description') }}</label>
                            <textarea 
                                v-model="unitForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.unit.placeholder_description')"
                            ></textarea>
                            <div v-if="unitForm.errors.description" class="text-xs text-red-500 mt-1">{{ unitForm.errors.description }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_status') }}</label>
                            <div class="relative">
                                <button 
                                    type="button"
                                    @click="isStatusDropdownOpen = !isStatusDropdownOpen"
                                    class="w-full h-10 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-150"
                                >
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 truncate">
                                        {{ selectedStatusLabel }}
                                    </span>
                                    <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isStatusDropdownOpen ? 'rotate-180 text-emerald-500' : '']" />
                                </button>

                                <div 
                                    v-if="isStatusDropdownOpen"
                                    class="absolute z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden p-2 space-y-1 shadow-lg"
                                >
                                    <button
                                        v-for="opt in statusOptions"
                                        :key="opt.value"
                                        type="button"
                                        @click="selectStatus(opt.value)"
                                        class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium transition-colors flex items-center justify-between hover:bg-emerald-50/50 dark:hover:bg-emerald-950/30"
                                        :class="unitForm.status === opt.value ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-700 dark:text-slate-300'"
                                    >
                                        <span class="truncate">{{ opt.label }}</span>
                                        <Check v-if="unitForm.status === opt.value" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    </button>
                                </div>
                            </div>
                            <div v-if="unitForm.errors.status" class="text-xs text-red-500 mt-1">{{ unitForm.errors.status }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showUnitModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="unitForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50">{{ __('pages.service_management.supporting_units.unit.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
