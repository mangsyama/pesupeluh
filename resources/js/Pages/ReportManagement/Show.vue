<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ReporterTicketShow from '@/Pages/Ticket/Partials/ReporterTicketShow.vue';
import UnitHeadTicketShow from '@/Pages/Ticket/Partials/UnitHeadTicketShow.vue';
import TechnicianTicketShow from '@/Pages/Ticket/Partials/TechnicianTicketShow.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true
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
        return UnitHeadTicketShow;
    } else if (roleId === 6) {
        return TechnicianTicketShow;
    } else {
        // Room Head (Role 7) melihat detail pelapor biasa
        return ReporterTicketShow;
    }
});
</script>

<template>
    <Head :title="`Manajemen Laporan - #${ticket.ticket_number}`" />
    
    <AuthenticatedLayout>
        <component 
            :is="activeComponent" 
            :ticket="ticket" 
            :technicians="technicians" 
            :personal="false"
        />
    </AuthenticatedLayout>
</template>
