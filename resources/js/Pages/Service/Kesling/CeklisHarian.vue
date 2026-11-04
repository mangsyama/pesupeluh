<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ClipboardCheck, Plus } from '@lucide/vue';
import TabCeklisHarian from './Components/Tabs/TabCeklisHarian.vue';
import DailyChecklistModal from './Modals/DailyChecklistModal.vue';

const props = defineProps({
    unit: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    inspections: { type: Array, default: () => [] },
    toilets: { type: Array, default: () => [] },
    personInCharge: { type: Object, default: () => ({}) }
});

const showChecklistModal = ref(false);
const selectedToiletForChecklist = ref(null);
const selectedInspectionForEdit = ref(null);
const selectedDateForChecklist = ref('');

const openChecklist = (payload = {}) => {
    selectedToiletForChecklist.value = payload.toilet || null;
    selectedInspectionForEdit.value = payload.inspection || null;
    selectedDateForChecklist.value = payload.date || '';
    showChecklistModal.value = true;
};

const closeChecklist = () => {
    showChecklistModal.value = false;
    selectedToiletForChecklist.value = null;
    selectedInspectionForEdit.value = null;
    selectedDateForChecklist.value = '';
};
</script>

<template>
    <Head title="Ceklis Harian - PAN-SINAR" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Standard PESU PELUH Header Panel -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <ClipboardCheck class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Ceklis Patroli Harian
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                Monitoring berkala 4 pilar standar sanitasi titik toilet rumah sakit per tanggal dan shift.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="openChecklist()"
                            class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm rounded-xl transition duration-150 shadow-sm cursor-pointer active:scale-95"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Input Ceklis Baru</span>
                        </button>
                    </div>
                </div>

                <TabCeklisHarian 
                    :toilets="toilets"
                    :inspections="inspections"
                    @openChecklistModal="openChecklist"
                />
            </div>
        </div>

        <DailyChecklistModal 
            :show="showChecklistModal"
            :rooms="rooms"
            :toilets="toilets"
            :initialToilet="selectedToiletForChecklist"
            :existingInspection="selectedInspectionForEdit"
            :selectedDate="selectedDateForChecklist"
            @close="closeChecklist"
        />
    </AuthenticatedLayout>
</template>

