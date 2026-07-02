<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ReporterTicketShow from '@/Pages/Ticket/Partials/ReporterTicketShow.vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true
    },
    personal: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <Head :title="`Detail Laporan - #${ticket.ticket_number}`" />
    
    <AuthenticatedLayout>
        <!-- Personal reports are strictly read-only tracker (ReporterTicketShow) -->
        <ReporterTicketShow 
            :ticket="ticket" 
            :personal="true" 
        />
    </AuthenticatedLayout>
</template>
