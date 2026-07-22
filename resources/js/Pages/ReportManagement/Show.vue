<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ReporterShow from '@/Pages/Report/Partials/ReporterShow.vue';
import UnitHeadShow from './Partials/UnitHeadShow.vue';
import TechnicianShow from './Partials/TechnicianShow.vue';

const props = defineProps({
    ticket: {
        type: Object,
        default: () => null
    },
    technicians: {
        type: Array,
        default: () => []
    }
});

const user = computed(() => usePage().props.auth.user);

const activeComponent = computed(() => {
    const roleId = Number(user.value?.role_id);
    if (roleId === 5 || roleId === 1 || roleId === 2 || roleId === 3 || roleId === 4) {
        return UnitHeadShow;
    } else if (roleId === 6) {
        return TechnicianShow;
    } else {
        // Room Head (Role 7) melihat detail pelapor biasa
        return ReporterShow;
    }
});
</script>

<template>
    <Head :title="`Manajemen Laporan ${ticket?.ticket_number ? ('- #' + ticket.ticket_number) : ''}`" />
    
    <AuthenticatedLayout>
        <component 
            :is="activeComponent" 
            :ticket="ticket" 
            :technicians="technicians" 
            :personal="false"
        />
    </AuthenticatedLayout>
</template>
