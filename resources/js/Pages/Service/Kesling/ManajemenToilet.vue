<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { MapPin, Plus, Upload } from '@lucide/vue';
import TabManajemenToilet from './Components/Tabs/TabManajemenToilet.vue';

const props = defineProps({
    unit: { type: Object, default: () => ({}) },
    qrConfig: { type: Object, default: () => ({}) },
    toilets: { type: Array, default: () => [] },
    personInCharge: { type: Object, default: () => ({}) }
});

const tabToiletRef = ref(null);

const handleOpenCreate = () => {
    tabToiletRef.value?.openCreateModal();
};

const handleOpenImport = () => {
    tabToiletRef.value?.openImportModal();
};
</script>

<template>
    <Head title="Manajemen Toilet - PAN-SINAR" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Standard PESU PELUH Header Panel (Sesuai Halaman Lain) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <MapPin class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Manajemen Toilet
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                Pendataan titik fasilitas toilet dan pembuatan barcode QR aduan.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <button 
                            type="button"
                            @click="handleOpenImport"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm rounded-xl transition duration-150 shadow-xs cursor-pointer active:scale-95 outline-none focus:outline-none"
                        >
                            <Upload class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span>Import Data</span>
                        </button>

                        <button 
                            type="button"
                            @click="handleOpenCreate"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm rounded-xl transition duration-150 shadow-sm cursor-pointer active:scale-95 outline-none focus:outline-none"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Tambah Toilet Baru</span>
                        </button>
                    </div>
                </div>

                <TabManajemenToilet 
                    ref="tabToiletRef"
                    :toilets="toilets"
                    :qrConfig="qrConfig"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
