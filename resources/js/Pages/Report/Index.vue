<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ReporterIndex from './Partials/ReporterIndex.vue';

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: '' }),
    },
});
</script>

<template>
    <Head title="Laporan Saya" />
    
    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            Laporan Saya
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            Daftar semua laporan kerusakan sarana prasarana yang Anda ajukan dan perkembangannya.
                        </p>
                    </div>
                </div>
                
                <!-- Render personal list tracker (ReporterIndex) -->
                <ReporterIndex 
                    :tickets="tickets" 
                    :filters="{ ...filters, personal: true }" 
                />
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