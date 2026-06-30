<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Plus, Search } from '@lucide/vue';
import CategoryList from './Partials/CategoryList.vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    unitFeatures: {
        type: Array,
        required: true
    }
});

const searchQuery = ref('');
const categoryListRef = ref(null);

const triggerAddCategory = () => {
    categoryListRef.value?.openAddModal();
};
</script>

<template>
    <Head :title="__('pages.service_management.categories.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel (Title, Description, Search & Add Buttons) -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            {{ __('pages.service_management.categories.title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            {{ __('pages.service_management.categories.description') }}
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                :placeholder="__('pages.service_management.categories.search_placeholder')" 
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 shadow-none"
                            />
                        </div>
                        
                        <!-- Add Button -->
                        <button 
                            @click="triggerAddCategory"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl transition duration-150 whitespace-nowrap shadow-none border-0"
                        >
                            <Plus class="h-4 w-4" />
                            {{ __('pages.service_management.categories.add_button') }}
                        </button>
                    </div>
                </div>

                <!-- CategoryList Component -->
                <div>
                    <CategoryList 
                        ref="categoryListRef"
                        :categories="categories"
                        :unit-features="unitFeatures"
                        :search-query="searchQuery"
                    />
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