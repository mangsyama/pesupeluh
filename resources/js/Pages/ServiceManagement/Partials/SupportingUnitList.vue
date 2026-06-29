<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Building2, Edit2, Trash2, X } from '@lucide/vue';

const props = defineProps({
    divisions: {
        type: Array,
        required: true
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
    if (!props.searchQuery.trim()) return props.divisions;
    const query = props.searchQuery.toLowerCase();
    
    return props.divisions
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
        icon: 'warning',
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
                        confirmButtonColor: '#4f46e5'
                    });
                }
            });
        }
    });
};

const openAddUnitModal = () => {
    isEditingUnit.value = false;
    unitForm.reset();
    unitForm.clearErrors();
    unitForm.description = '';
    unitForm.status = 'IN_DEVELOPMENT';
    unitForm.division_id = props.divisions.length > 0 ? props.divisions[0].id : '';
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
        icon: 'warning',
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
                        confirmButtonColor: '#4f46e5'
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
                        <div class="flex items-center gap-1">
                            <button 
                                @click="openEditDivisionModal(div)"
                                class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded transition"
                                :title="__('pages.service_management.supporting_units.division.title_edit')"
                            >
                                <Edit2 class="h-3.5 w-3.5" />
                            </button>
                            <button 
                                @click="deleteDivision(div)"
                                class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded transition"
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
                                        class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded transition"
                                        :title="__('pages.service_management.supporting_units.unit.title_edit')"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                    </button>
                                    <button 
                                        @click="deleteUnit(unit)"
                                        class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded transition"
                                        :title="__('global.delete')"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
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

        <!-- DIVISION MODAL -->
        <Teleport to="body">
            <div v-if="showDivisionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
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
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.division.placeholder_name')"
                            />
                            <div v-if="divisionForm.errors.name" class="text-xs text-red-500 mt-1">{{ divisionForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.division.label_description') }}</label>
                            <textarea 
                                v-model="divisionForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.division.placeholder_description')"
                            ></textarea>
                            <div v-if="divisionForm.errors.description" class="text-xs text-red-500 mt-1">{{ divisionForm.errors.description }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showDivisionModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="divisionForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-650 dark:hover:bg-indigo-550 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50">{{ __('pages.service_management.supporting_units.division.btn_save') }}</button>
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
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
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
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.unit.placeholder_name')"
                            />
                            <div v-if="unitForm.errors.name" class="text-xs text-red-500 mt-1">{{ unitForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_division') }}</label>
                            <select 
                                v-model="unitForm.division_id"
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                            >
                                <option v-for="div in divisions" :key="div.id" :value="div.id">
                                    {{ div.name }}
                                </option>
                            </select>
                            <div v-if="unitForm.errors.division_id" class="text-xs text-red-500 mt-1">{{ unitForm.errors.division_id }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_description') }}</label>
                            <textarea 
                                v-model="unitForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.supporting_units.unit.placeholder_description')"
                            ></textarea>
                            <div v-if="unitForm.errors.description" class="text-xs text-red-500 mt-1">{{ unitForm.errors.description }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.supporting_units.unit.label_status') }}</label>
                            <select 
                                v-model="unitForm.status"
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                            >
                                <option value="ACTIVE">{{ __('pages.service_management.supporting_units.unit.status_active') }}</option>
                                <option value="IN_DEVELOPMENT">{{ __('pages.service_management.supporting_units.unit.status_dev') }}</option>
                                <option value="INACTIVE">{{ __('pages.service_management.supporting_units.unit.status_inactive') }}</option>
                            </select>
                            <div v-if="unitForm.errors.status" class="text-xs text-red-500 mt-1">{{ unitForm.errors.status }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showUnitModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="unitForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-650 dark:hover:bg-indigo-550 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50">{{ __('pages.service_management.supporting_units.unit.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
