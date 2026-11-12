<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Ticket } from '@lucide/vue';
import TabAduanSanitasi from './Components/Tabs/TabAduanSanitasi.vue';
import ResolveTicketModal from './Modals/ResolveTicketModal.vue';

const props = defineProps({
    unit: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tickets: { type: Array, default: () => [] },
    personInCharge: { type: Object, default: () => ({}) }
});

const showResolveModal = ref(false);
const selectedTicketForResolution = ref(null);

const openResolveModal = (ticket) => {
    selectedTicketForResolution.value = ticket;
    showResolveModal.value = true;
    router.reload({
        only: ['tickets'],
        preserveScroll: true,
        preserveState: true,
    });
};

let refreshInterval = null;
onMounted(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const ticketParam = params.get('ticket') || params.get('ticket_number') || params.get('open');
        if (ticketParam && props.tickets?.length) {
            const matched = props.tickets.find(
                t => t.ticket_number === ticketParam || String(t.id) === String(ticketParam) || t.uuid === ticketParam
            );
            if (matched) {
                openResolveModal(matched);
            }
        }

        refreshInterval = setInterval(() => {
            if (document.visibilityState === 'visible' && !showResolveModal.value) {
                router.reload({
                    only: ['tickets'],
                    preserveScroll: true,
                    preserveState: true,
                });
            }
        }, 20000);
    }
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

const handleTicketUpdated = (data) => {
    if (selectedTicketForResolution.value && selectedTicketForResolution.value.id === data.id) {
        selectedTicketForResolution.value = {
            ...selectedTicketForResolution.value,
            status: data.status
        };
    }
};

watch(() => props.tickets, (newTickets) => {
    if (selectedTicketForResolution.value && newTickets) {
        const found = newTickets.find(t => t.id === selectedTicketForResolution.value.id);
        if (found) {
            selectedTicketForResolution.value = { ...found };
        }
    }
}, { deep: true });
</script>

<template>
    <Head title="Aduan Sanitasi - PAN-SINAR" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Standard PESU PELUH Header Panel -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Ticket class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                Aduan Sanitasi
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                Respon cepat aduan fasilitas toilet, air macet, dan keluhan sanitasi lingkungan.
                            </p>
                        </div>
                    </div>
                </div>

                <TabAduanSanitasi 
                    :tickets="tickets"
                    @openResolveModal="openResolveModal"
                />
            </div>
        </div>

        <!-- Modals -->
        <ResolveTicketModal 
            :show="showResolveModal"
            :ticket="selectedTicketForResolution"
            @ticketUpdated="handleTicketUpdated"
            @close="showResolveModal = false"
        />
    </AuthenticatedLayout>
</template>

