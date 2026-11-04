<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Plus, Search, Layers, Edit2, Trash2, ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    supportingUnits: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

const searchQuery = ref('');
const showCategoryModal = ref(false);
const isEditingCategory = ref(false);

const unitOptions = computed(() => (props.supportingUnits || []));

const categoryForm = useForm({
    id: null,
    name: '',
    description: '',
    supporting_unit_id: ''
});

// Search and Filter
const filteredCategories = computed(() => {
    const list = props.categories || [];
    if (!searchQuery.value.trim()) return list;
    const query = searchQuery.value.toLowerCase();
    return list.filter(cat => 
        cat.name.toLowerCase().includes(query) || 
        (cat.description && cat.description.toLowerCase().includes(query)) || 
        (cat.supporting_unit && cat.supporting_unit.name.toLowerCase().includes(query))
    );
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = ref(10);

const totalCount = computed(() => filteredCategories.value.length);
const lastPage = computed(() => Math.ceil(totalCount.value / itemsPerPage.value) || 1);
const fromCount = computed(() => totalCount.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1);
const toCount = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalCount.value));

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCategories.value.slice(start, start + itemsPerPage.value);
});

const hasPrev = computed(() => currentPage.value > 1);
const hasNext = computed(() => currentPage.value < lastPage.value);

const goToPrev = () => { if (currentPage.value > 1) currentPage.value--; };
const goToNext = () => { if (currentPage.value < lastPage.value) currentPage.value++; };

watch(searchQuery, () => {
    currentPage.value = 1;
});

// Modal Actions
const openAddCategoryModal = () => {
    isEditingCategory.value = false;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.supporting_unit_id = props.supportingUnits && props.supportingUnits.length > 0 ? props.supportingUnits[0].id : '';
    showCategoryModal.value = true;
};

const openEditCategoryModal = (cat) => {
    isEditingCategory.value = true;
    categoryForm.clearErrors();
    categoryForm.id = cat.id;
    categoryForm.name = cat.name;
    categoryForm.description = cat.description || '';
    categoryForm.supporting_unit_id = cat.supporting_unit_id || '';
    showCategoryModal.value = true;
};

const submitCategoryForm = () => {
    if (isEditingCategory.value) {
        categoryForm.put(route('service-management.categories.update', categoryForm.id), {
            onSuccess: () => {
                showCategoryModal.value = false;
            }
        });
    } else {
        categoryForm.post(route('service-management.categories.store'), {
            onSuccess: () => {
                showCategoryModal.value = false;
                categoryForm.reset();
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
            router.delete(route('service-management.categories.destroy', cat.id));
        }
    });
};
</script>

<template>
    <Head :title="__('pages.service_management.categories.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel (Title, Description, Search & Add Buttons) -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Layers class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                {{ __('pages.service_management.categories.title') }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                {{ __('pages.service_management.categories.description') }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                :placeholder="__('pages.service_management.categories.search_placeholder')" 
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-0 focus:border-emerald-500 dark:focus:border-white transition-all duration-150 shadow-none"
                            />
                        </div>
                        
                        <!-- Add Button -->
                        <button 
                            @click="openAddCategoryModal"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 font-bold text-sm rounded-xl transition duration-150 whitespace-nowrap shadow-none border-0"
                        >
                            <Plus class="h-4 w-4" />
                            {{ __('pages.service_management.categories.add_button') }}
                        </button>
                    </div>
                </div>

                <!-- Categories Content Table & Cards -->
                <div class="space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                        <!-- Desktop Table View (>= md) -->
                        <div class="hidden md:block overflow-x-auto rounded-b-2xl">
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
                                        v-for="cat in paginatedCategories" 
                                        :key="cat.id"
                                        class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-slate-955 dark:text-white">{{ cat.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 leading-relaxed break-words max-w-md">{{ cat.description || '-' }}</td>
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
                                                    class="p-2 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 transition duration-150 cursor-pointer"
                                                    :title="__('Edit')"
                                                >
                                                    <Edit2 class="h-3.5 w-3.5" />
                                                </button>
                                                <button 
                                                    @click="deleteCategory(cat)" 
                                                    class="p-2 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 transition duration-150 cursor-pointer"
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

                        <!-- Mobile Card View (< md) -->
                        <div class="md:hidden p-4 space-y-3 bg-slate-50/30 dark:bg-slate-950/10">
                            <div v-if="filteredCategories.length === 0" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 p-10 text-center rounded-2xl text-slate-400 dark:text-slate-500 text-xs font-medium">
                                {{ __('pages.service_management.categories.empty_data') }}
                            </div>
                            <div
                                v-else
                                v-for="cat in paginatedCategories"
                                :key="'mobile-cat-' + cat.id"
                                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm space-y-3 transition-all duration-150"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="font-extrabold text-sm text-slate-900 dark:text-white leading-snug">
                                        {{ cat.name }}
                                    </div>
                                    <span v-if="cat.supporting_unit" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700 shrink-0">
                                        {{ cat.supporting_unit.name }}
                                    </span>
                                </div>

                                <div v-if="cat.description" class="text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800/50 leading-relaxed">
                                    {{ cat.description }}
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <button 
                                        @click="openEditCategoryModal(cat)" 
                                        class="flex-1 py-2 px-3 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:hover:bg-emerald-900/60 border border-emerald-200/50 dark:border-emerald-900/40 text-xs font-bold flex items-center justify-center gap-1.5 transition duration-150 cursor-pointer"
                                    >
                                        <Edit2 class="h-3.5 w-3.5" />
                                        <span>Edit</span>
                                    </button>
                                    <button 
                                        @click="deleteCategory(cat)" 
                                        class="flex-1 py-2 px-3 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:hover:bg-rose-900/60 border border-rose-200/50 dark:border-rose-900/40 text-xs font-bold flex items-center justify-center gap-1.5 transition duration-150 cursor-pointer"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="lastPage > 1" class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2"></div>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                                    {{ fromCount }}–{{ toCount }} dari {{ totalCount }}
                                </span>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="goToPrev"
                                        :disabled="!hasPrev"
                                        class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                        aria-label="Halaman sebelumnya"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="goToNext"
                                        :disabled="!hasNext"
                                        class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                                        aria-label="Halaman berikutnya"
                                    >
                                        <ChevronRight class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CATEGORY MODAL -->
        <Modal :show="showCategoryModal" :close-on-click-outside="false" @close="showCategoryModal = false" max-width="md">
            <div class="flex flex-col h-full sm:h-auto min-h-screen sm:min-h-0 bg-white dark:bg-slate-900">
                <!-- Solid Emerald Sticky Header (Standard Pesu Peluh) -->
                <div class="bg-emerald-600 dark:bg-emerald-950/90 text-white p-4 sm:p-5 flex items-center justify-between sticky top-0 z-10 shrink-0 border-b border-emerald-500/30 dark:border-emerald-800/50 shadow-sm">
                    <div class="flex items-center gap-3 pr-2">
                        <div class="h-10 w-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center flex-shrink-0">
                            <Layers class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-white leading-tight">
                                {{ isEditingCategory ? __('pages.service_management.categories.edit_title') : __('pages.service_management.categories.add_title') }}
                            </h3>
                            <p class="text-xs text-emerald-100/90 dark:text-emerald-200/90 mt-0.5 font-medium">
                                {{ isEditingCategory ? 'Perbarui data kategori layanan' : 'Isi data kategori layanan baru' }}
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitCategoryForm" class="flex flex-col flex-1 justify-between min-h-0">
                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">{{ __('pages.service_management.categories.label_name') }} <span class="text-red-500">*</span></label>
                            <input 
                                v-model="categoryForm.name"
                                type="text" 
                                required
                                class="w-full px-3.5 py-2.5 text-xs border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150"
                                :placeholder="__('pages.service_management.categories.placeholder_name')"
                            />
                            <div v-if="categoryForm.errors.name" class="text-[10px] text-red-500 font-semibold mt-1">{{ categoryForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Unit Penunjang</label>
                            <SearchableSelect
                                v-model="categoryForm.supporting_unit_id"
                                :options="unitOptions"
                                :searchable="true"
                                value-key="id"
                                label-key="name"
                                placeholder="Pilih Unit Penunjang..."
                                search-placeholder="Cari unit penunjang..."
                            />
                            <div v-if="categoryForm.errors.supporting_unit_id" class="text-[10px] text-red-500 font-semibold mt-1">{{ categoryForm.errors.supporting_unit_id }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">{{ __('pages.service_management.categories.label_description') }}</label>
                            <textarea 
                                v-model="categoryForm.description"
                                rows="4"
                                class="w-full p-3.5 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none transition duration-150 min-h-[100px]"
                                :placeholder="__('pages.service_management.categories.placeholder_description')"
                            ></textarea>
                            <div v-if="categoryForm.errors.description" class="text-[10px] text-red-500 font-semibold mt-1">{{ categoryForm.errors.description }}</div>
                        </div>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-end gap-3 sticky bottom-0 z-10 shrink-0">
                        <SecondaryButton type="button" @click="showCategoryModal = false" class="h-11 px-5">{{ __('global.cancel') }}</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="categoryForm.processing" class="h-11 px-6 !bg-emerald-600 hover:!bg-emerald-500 font-bold">
                            {{ categoryForm.processing ? __('pages.service_management.categories.btn_saving') || 'Menyimpan...' : __('pages.service_management.categories.btn_save') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
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
