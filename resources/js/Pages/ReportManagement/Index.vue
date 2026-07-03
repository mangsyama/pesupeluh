<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ReporterIndex from '@/Pages/Report/Partials/ReporterIndex.vue';
import UnitHeadIndex from './Partials/UnitHeadIndex.vue';
import TechnicianIndex from './Partials/TechnicianIndex.vue';

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

const user = computed(() => usePage().props.auth.user);

const activeComponent = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 5 || roleId === 1 || roleId === 2 || roleId === 3 || roleId === 4) {
        return UnitHeadIndex;
    } else if (roleId === 6) {
        return TechnicianIndex;
    } else {
        // Room Head (Role 7) melihat daftar laporan ruangan mereka
        return ReporterIndex;
    }
});

const title = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 5) return 'Tugas Unit Kerja';
    if (roleId === 6) return 'Daftar Tugas Saya';
    if (roleId === 7) return 'Laporan Ruangan';
    return 'Manajemen Laporan';
});

const subtitle = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 5 || roleId === 1 || roleId === 2 || roleId === 3 || roleId === 4) {
        return 'Laci validasi, prioritas, dan disposisi tiket penunjang.';
    }
    if (roleId === 6) {
        return 'Papan tugas operasional, respon kedatangan, dan pelaporan bukti kerja.';
    }
    if (roleId === 7) {
        return 'Daftar pemantauan status kerusakan aktif di dalam ruangan Anda.';
    }
    return 'Manajemen operasional tiket pelaporan.';
});
</script>

<template>
    <Head :title="title" />
    
    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            {{ title }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>
                
                <!-- Render the matching operational subcomponent -->
                <component 
                    :is="activeComponent" 
                    :tickets="tickets" 
                    :filters="filters" 
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
