<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ReporterShow from './Partials/ReporterShow.vue';

const props = defineProps({
    ticket: {
        type: Object,
        default: () => null
    },
    personal: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <Head :title="`Detail Laporan ${ticket?.ticket_number ? ('- #' + ticket.ticket_number) : ''}`" />
    
    <AuthenticatedLayout>
        <!-- Personal reports are strictly read-only tracker (ReporterShow) -->
        <ReporterShow 
            :ticket="ticket" 
            :personal="true" 
        />
    </AuthenticatedLayout>
</template>
