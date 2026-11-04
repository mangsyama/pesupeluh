<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Trash2, Plus } from '@lucide/vue';
import TabPengelolaanLimbah from './Components/Tabs/TabPengelolaanLimbah.vue';
import WasteRecordModal from './Modals/WasteRecordModal.vue';

const props = defineProps({
    unit: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    wasteData: { type: Object, default: () => ({}) },
    personInCharge: { type: Object, default: () => ({}) }
});

const showWasteModal = ref(false);
</script>

<template>
    <Head title="Pengelolaan Limbah - PAN-SINAR" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Standard PESU PELUH Header Panel -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Trash2 class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Pengelolaan Limbah
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                Pencatatan timbangan limbah harian, kapasitas TPS B3, dan manifest Festronik.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="showWasteModal = true"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm rounded-xl transition duration-150 shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Catat Limbah Baru</span>
                        </button>
                    </div>
                </div>

                <TabPengelolaanLimbah 
                    :wasteData="wasteData"
                    @openWasteModal="showWasteModal = true"
                />
            </div>
        </div>

        <WasteRecordModal 
            :show="showWasteModal"
            :rooms="rooms"
            @close="showWasteModal = false"
        />
    </AuthenticatedLayout>
</template>

