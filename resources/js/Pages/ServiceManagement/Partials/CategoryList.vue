<script setup>
import { ref, computed, getCurrentInstance, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Edit2, Trash2, X } from '@lucide/vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
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

const unitOptions = computed(() => (props.supportingUnits || []));

const showCategoryModal = ref(false);
const isEditingCategory = ref(false);

const categoryForm = useForm({
    id: null,
    name: '',
    description: '',
    supporting_unit_id: ''
});

const filteredCategories = computed(() => {
    const list = props.categories || [];
    if (!props.searchQuery.trim()) return list;
    const query = props.searchQuery.toLowerCase();
    return list.filter(cat => 
        cat.name.toLowerCase().includes(query) || 
        (cat.description && cat.description.toLowerCase().includes(query)) || 
        (cat.supporting_unit && cat.supporting_unit.name.toLowerCase().includes(query))
    );
});

const isUnitDropdownOpen = ref(false);
const unitSearchQuery = ref('');
const unitDropdownRef = ref(null);

const handleClickOutsideCategory = (event) => {
    if (isUnitDropdownOpen.value && unitDropdownRef.value && !unitDropdownRef.value.contains(event.target)) {
        isUnitDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutsideCategory);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsideCategory);
});

const selectedUnitLabel = computed(() => {
    if (!categoryForm.supporting_unit_id) return '';
    const selected = (props.supportingUnits || []).find(u => u.id === categoryForm.supporting_unit_id);
    return selected ? selected.name : '';
});

const toggleUnitDropdown = (event) => {
    event?.stopPropagation();
    isUnitDropdownOpen.value = !isUnitDropdownOpen.value;
    if (isUnitDropdownOpen.value) {
        unitSearchQuery.value = '';
    }
};

const selectUnit = (unitId) => {
    categoryForm.supporting_unit_id = unitId;
    isUnitDropdownOpen.value = false;
};

const openAddCategoryModal = () => {
    isEditingCategory.value = false;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.supporting_unit_id = props.supportingUnits && props.supportingUnits.length > 0 ? props.supportingUnits[0].id : '';
    isUnitDropdownOpen.value = false;
    showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
    isEditingCategory.value = true;
    categoryForm.clearErrors();
    categoryForm.id = cat.id;
    categoryForm.name = cat.name;
    categoryForm.description = cat.description || '';
    categoryForm.supporting_unit_id = cat.supporting_unit_id || '';
    isUnitDropdownOpen.value = false;
    showCategoryModal.value = true;
};

const submitCategoryForm = () => {
    if (isEditingCategory.value) {
        categoryForm.put(route('service-management.categories.update', categoryForm.id), {
            onSuccess: () => {
                showCategoryModal.value = false;
                proxy.$toast(proxy.__('pages.service_management.categories.toast_updated'), 'success');
            }
        });
    } else {
        categoryForm.post(route('service-management.categories.store'), {
            onSuccess: () => {
                showCategoryModal.value = false;
                categoryForm.reset();
                proxy.$toast(proxy.__('pages.service_management.categories.toast_added'), 'success');
            }
        });
    }
};

const deleteCategory = (cat) => {
    proxy.$swal({
        title: proxy.__('pages.service_management.categories.confirm_delete_title'),
        text: proxy.__('pages.service_management.categories.confirm_delete_text').replace('{name}', cat.name),
        icon: 'error',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('global.yes_delete'),
        cancelButtonText: proxy.__('global.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('service-management.categories.destroy', cat.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.service_management.categories.toast_deleted'), 'success');
                }
            });
        }
    });
};

defineExpose({
    openAddModal: openAddCategoryModal
});
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                        <th class="px-6 py-4">{{ __('pages.service_management.categories.table_category') }}</th>
                        <th class="px-6 py-4">{{ __('pages.service_management.categories.table_description') }}</th>
                        <th class="px-6 py-4 text-center">Unit Penunjang</th>
                        <th class="px-6 py-4 text-right">{{ __('pages.service_management.categories.table_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                    <tr v-if="filteredCategories.length === 0">
                        <td colspan="4" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">{{ __('pages.service_management.categories.empty_data') }}</td>
                    </tr>
                    <tr 
                        v-else
                        v-for="cat in filteredCategories" 
                        :key="cat.id"
                        class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                    >
                        <td class="px-6 py-4">
                            <div class="font-semibold text-slate-955 dark:text-white">{{ cat.name }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ cat.description || '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span v-if="cat.supporting_unit" class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                {{ cat.supporting_unit.name }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button 
                                    @click="openEditCategoryModal(cat)" 
                                    class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150"
                                    :title="__('Edit')"
                                >
                                    <Edit2 class="h-3.5 w-3.5" />
                                </button>
                                <button 
                                    @click="deleteCategory(cat)" 
                                    class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150"
                                    :title="__('global.delete')"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- CATEGORY MODAL -->
        <Teleport to="body">
            <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300 max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl shrink-0">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditingCategory ? __('pages.service_management.categories.edit_title') : __('pages.service_management.categories.add_title') }}
                        </h3>
                        <button type="button" @click="showCategoryModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitCategoryForm" class="p-6 space-y-4 overflow-y-auto custom-scrollbar">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.categories.label_name') }}</label>
                            <input 
                                v-model="categoryForm.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.categories.placeholder_name')"
                            />
                            <div v-if="categoryForm.errors.name" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Unit Penunjang</label>
                            <SearchableSelect
                                v-model="categoryForm.supporting_unit_id"
                                :options="unitOptions"
                                :searchable="true"
                                value-key="id"
                                label-key="name"
                                placeholder="Pilih Unit Penunjang..."
                                search-placeholder="Cari unit penunjang..."
                            />
                            <div v-if="categoryForm.errors.supporting_unit_id" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.supporting_unit_id }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.categories.label_description') }}</label>
                            <textarea 
                                v-model="categoryForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:border-emerald-500 dark:focus:border-white focus:ring-0 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.categories.placeholder_description')"
                            ></textarea>
                            <div v-if="categoryForm.errors.description" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.description }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showCategoryModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="categoryForm.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-sm rounded-xl transition duration-150 border-0 shadow-sm disabled:opacity-50">{{ __('pages.service_management.categories.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
