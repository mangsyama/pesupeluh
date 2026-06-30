<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, Deferred } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Eye, Calendar, User, ChevronLeft, ChevronRight, Filter } from '@lucide/vue';
import { getCurrentInstance } from 'vue';

const { proxy } = getCurrentInstance();

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

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

// Debounce search untuk tidak spam request
let searchTimeout = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(statusFilter, () => applyFilters());

const applyFilters = () => {
    router.get(route('reports.history'), {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const statusConfig = {
    PENDING_VALIDATION: { label: 'Menunggu',     badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' },
    ASSIGNED:           { label: 'Ditugaskan',   badge: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' },
    IN_PROGRESS:        { label: 'Dikerjakan',   badge: 'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-400' },
    PENDING:            { label: 'Tertunda',     badge: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400' },
    COMPLETED:          { label: 'Selesai',      badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' },
    CANCEL:             { label: 'Dibatalkan',   badge: 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' },
};

const getStatus = (status) => statusConfig[status] ?? { label: status, badge: 'bg-slate-100 text-slate-600' };

const showDetails = (ticket) => {
    const st = getStatus(ticket.status);
    proxy.$swal({
        title: `#${ticket.ticket_number}`,
        html: `
            <div class="text-left space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <p><strong>${proxy.__('pages.reports.history.detail_date')}:</strong> ${ticket.created_at_formatted}</p>
                <p><strong>${proxy.__('pages.reports.history.detail_reporter')}:</strong> ${ticket.reporter?.name ?? '-'}</p>
                <p><strong>Ruangan:</strong> ${ticket.room?.name ?? '-'}</p>
                <p><strong>${proxy.__('pages.reports.history.detail_category')}:</strong> ${ticket.category?.name ?? '-'}</p>
                <p><strong>${proxy.__('pages.reports.history.detail_description')}:</strong> ${ticket.problem_description}</p>
                <p><strong>${proxy.__('pages.reports.history.detail_status')}:</strong> <span>${st.label}</span></p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#4f46e5',
    });
};

const goToPage = (url) => {
    if (url) router.visit(url, { preserveState: true });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head :title="__('pages.reports.history.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                            {{ __('pages.reports.history.title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            Seluruh riwayat tiket layanan yang telah diajukan, dapat dicari dan difilter berdasarkan status.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="__('pages.reports.history.search_placeholder')"
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="relative w-full sm:w-52">
                            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            <select
                                v-model="statusFilter"
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 appearance-none"
                            >
                                <option value="">Semua Status</option>
                                <option value="PENDING_VALIDATION">Menunggu Validasi</option>
                                <option value="ASSIGNED">Ditugaskan</option>
                                <option value="IN_PROGRESS">Sedang Dikerjakan</option>
                                <option value="PENDING">Tertunda</option>
                                <option value="COMPLETED">Selesai</option>
                                <option value="CANCEL">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                </div>


                <Deferred data="tickets">
                    <template #fallback>
                        <!-- Skeleton Loader -->
                        <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm animate-pulse">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_reporter') }}</th>
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_category_unit') }}</th>
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_short_desc') }}</th>
                                        <th class="px-6 py-4">{{ __('pages.reports.history.table_status') }}</th>
                                        <th class="px-6 py-4 text-right">{{ __('pages.reports.history.table_actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="i in 5" :key="'skel-' + i" class="border-b border-slate-100 dark:border-slate-800/60">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-32"></div>
                                            <div class="flex items-center gap-1 mt-1.5">
                                                <div class="h-3.5 w-3.5 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                                <div class="h-2.5 bg-slate-150 dark:bg-slate-800/60 rounded w-20"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="h-4 w-4 bg-slate-200 dark:bg-slate-800 rounded-full"></div>
                                                <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-28"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded w-40"></div>
                                            <div class="h-2.5 bg-slate-150 dark:bg-slate-800/60 rounded w-32 mt-1.5"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-48"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="h-5 bg-slate-200 dark:bg-slate-800 rounded-full w-20"></div>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="h-7 w-7 bg-slate-200 dark:bg-slate-800 rounded-lg ml-auto"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <!-- History List Table -->
                    <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-4">{{ __('pages.reports.history.table_id_date') }}</th>
                                <th class="px-6 py-4">{{ __('pages.reports.history.table_reporter') }}</th>
                                <th class="px-6 py-4">{{ __('pages.reports.history.table_category_unit') }}</th>
                                <th class="px-6 py-4">{{ __('pages.reports.history.table_short_desc') }}</th>
                                <th class="px-6 py-4">{{ __('pages.reports.history.table_status') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('pages.reports.history.table_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">

                            <!-- Empty state -->
                            <tr v-if="!tickets.data || tickets.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-400">
                                        <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-sm font-medium">Belum ada data laporan</span>
                                        <span class="text-xs">Data tiket layanan akan muncul di sini</span>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="ticket in tickets.data"
                                :key="'ticket-' + ticket.id"
                                class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                            >
                                <!-- ID / Date -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-950 dark:text-white text-xs">{{ ticket.ticket_number }}</div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                        <Calendar class="h-3.5 w-3.5" />
                                        {{ formatDate(ticket.created_at) }}
                                    </div>
                                </td>

                                <!-- Reporter -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <User class="h-4 w-4 text-slate-400" />
                                        <span>{{ ticket.reporter?.name ?? '-' }}</span>
                                    </div>
                                </td>

                                <!-- Category / Unit -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ ticket.category?.name ?? '-' }}</div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500">{{ ticket.room?.name ?? '-' }}</div>
                                </td>

                                <!-- Desc -->
                                <td class="px-6 py-4 max-w-xs truncate text-slate-600 dark:text-slate-400">
                                    {{ ticket.problem_description }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold', getStatus(ticket.status).badge]">
                                        {{ getStatus(ticket.status).label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="route('tickets.show', ticket.uuid)"
                                        class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150 inline-block"
                                        :title="__('pages.reports.history.btn_view_detail')"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.meta && tickets.meta.last_page > 1" class="flex items-center justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan {{ tickets.meta.from ?? 0 }} – {{ tickets.meta.to ?? 0 }} dari {{ tickets.meta.total ?? 0 }} tiket
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            @click="goToPage(tickets.links?.prev)"
                            :disabled="!tickets.links?.prev"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 px-3">
                            {{ tickets.meta.current_page }} / {{ tickets.meta.last_page }}
                        </span>
                        <button
                            @click="goToPage(tickets.links?.next)"
                            :disabled="!tickets.links?.next"
                            class="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </Deferred>

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