<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Edit2, Trash2, X } from '@lucide/vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    unitFeatures: {
        type: Array,
        required: true
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

const { proxy } = getCurrentInstance();

const showCategoryModal = ref(false);
const isEditingCategory = ref(false);

const categoryForm = useForm({
    id: null,
    name: '',
    description: '',
    feature_id: ''
});

const filteredCategories = computed(() => {
    if (!props.searchQuery.trim()) return props.categories;
    const query = props.searchQuery.toLowerCase();
    return props.categories.filter(cat => 
        cat.name.toLowerCase().includes(query) || 
        (cat.description && cat.description.toLowerCase().includes(query)) || 
        (cat.unit_feature && cat.unit_feature.name.toLowerCase().includes(query)) ||
        (cat.unit_feature && cat.unit_feature.supporting_unit && cat.unit_feature.supporting_unit.name.toLowerCase().includes(query))
    );
});

const openAddCategoryModal = () => {
    isEditingCategory.value = false;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.feature_id = props.unitFeatures.length > 0 ? props.unitFeatures[0].id : '';
    showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
    isEditingCategory.value = true;
    categoryForm.clearErrors();
    categoryForm.id = cat.id;
    categoryForm.name = cat.name;
    categoryForm.description = cat.description || '';
    categoryForm.feature_id = cat.feature_id || '';
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
        icon: 'warning',
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
                        <th class="px-6 py-4">{{ __('pages.service_management.categories.table_feature') }}</th>
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
                            <div class="font-semibold text-slate-900 dark:text-white">{{ cat.name }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ cat.description || '-' }}</td>
                        <td class="px-6 py-4">
                            <span v-if="cat.unit_feature" class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                {{ cat.unit_feature.supporting_unit ? cat.unit_feature.supporting_unit.name : '' }} • {{ cat.unit_feature.name }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button @click="openEditCategoryModal(cat)" class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"><Edit2 class="h-4 w-4" /></button>
                                <button @click="deleteCategory(cat)" class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"><Trash2 class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- CATEGORY MODAL -->
        <Teleport to="body">
            <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
                            {{ isEditingCategory ? __('pages.service_management.categories.edit_title') : __('pages.service_management.categories.add_title') }}
                        </h3>
                        <button type="button" @click="showCategoryModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <form @submit.prevent="submitCategoryForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.categories.label_name') }}</label>
                            <input 
                                v-model="categoryForm.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.categories.placeholder_name')"
                            />
                            <div v-if="categoryForm.errors.name" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.categories.label_feature') }}</label>
                            <select 
                                v-model="categoryForm.feature_id"
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                            >
                                <option v-for="feat in unitFeatures" :key="feat.id" :value="feat.id">
                                    {{ feat.supporting_unit ? feat.supporting_unit.name : '' }} - {{ feat.name }}
                                </option>
                            </select>
                            <div v-if="categoryForm.errors.feature_id" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.feature_id }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.service_management.categories.label_description') }}</label>
                            <textarea 
                                v-model="categoryForm.description"
                                rows="3"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all duration-150"
                                :placeholder="__('pages.service_management.categories.placeholder_description')"
                            ></textarea>
                            <div v-if="categoryForm.errors.description" class="text-xs text-red-500 mt-1">{{ categoryForm.errors.description }}</div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button type="button" @click="showCategoryModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150">{{ __('global.cancel') }}</button>
                            <button type="submit" :disabled="categoryForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-650 dark:hover:bg-indigo-550 text-white font-semibold text-sm rounded-xl transition duration-150 disabled:opacity-50">{{ __('pages.service_management.categories.btn_save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
