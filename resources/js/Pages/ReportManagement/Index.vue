<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Wrench } from '@lucide/vue';


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
    if (roleId === 10) {
        return TechnicianIndex;
    } else if (roleId === 11) {
        return ReporterIndex;
    } else {
        // Roles 1-9 (Admin, Direktur, Kabid, Kabag, Kaseksi, Kasubbag, Ka Instalasi, Sekr Instalasi, PJ Ruangan)
        return UnitHeadIndex;
    }
});

const title = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 10) return 'Daftar Tugas Saya';
    if (roleId === 9) return 'Laporan & Disposisi Ruangan';
    if ([1, 2, 3, 4, 5, 6, 7, 8].includes(roleId)) return 'Manajemen & Disposisi Laporan';
    return 'Manajemen Laporan';
});

const subtitle = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 10) {
        return 'Papan tugas operasional, respon kedatangan, dan pelaporan bukti kerja.';
    }
    if ([1, 2, 3, 4, 5, 6, 7, 8, 9].includes(roleId)) {
        return 'Laci validasi, prioritas, dan disposisi tiket penunjang.';
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
                <div class="hidden sm:flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Wrench class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                {{ title }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                {{ subtitle }}
                            </p>
                        </div>
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
