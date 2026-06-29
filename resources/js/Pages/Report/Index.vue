<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileText, Download, FileSpreadsheet, BarChart3, CheckCircle2, Clock } from '@lucide/vue';
import { getCurrentInstance } from 'vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total_month: 0, completed: 0, pending: 0 }),
    },
});

const exportPdf = () => {
    proxy.$toast(proxy.__('pages.reports.center.export_toast').replace('{format}', 'PDF'), 'success');
    window.open(route('reports.export.pdf'), '_blank');
};

const exportCsv = () => {
    proxy.$toast(proxy.__('pages.reports.center.export_toast').replace('{format}', 'CSV'), 'success');
    window.location.href = route('reports.export.csv');
};
</script>

<template>
    <Head :title="__('pages.reports.center.title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4">
            <div class="w-full space-y-4">
                <!-- Stats bulan berjalan (data real) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ __('pages.reports.center.stat_total_month') }}</span>
                            <div class="text-2xl font-extrabold text-slate-950 dark:text-white mt-0.5">{{ stats.total_month }}</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                            <CheckCircle2 class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ __('pages.reports.center.stat_verified') }}</span>
                            <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ stats.completed }}</div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-500 flex-shrink-0">
                            <Clock class="h-5 w-5" />
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ __('pages.reports.center.stat_pending') }}</span>
                            <div class="text-2xl font-extrabold text-amber-500 mt-0.5">{{ stats.pending }}</div>
                        </div>
                    </div>
                </div>

                <!-- Export Methods Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- PDF Export Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 mb-4">
                                <FileText class="h-6 w-6" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-950 dark:text-white mb-2">{{ __('pages.reports.center.pdf_title') }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                                {{ __('pages.reports.center.pdf_desc') }}
                            </p>
                        </div>
                        <div>
                            <button
                                @click="exportPdf"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-500 text-white font-semibold text-sm rounded-xl transition duration-150 shadow-sm"
                            >
                                <Download class="h-4 w-4 me-2" />
                                {{ __('pages.reports.center.pdf_btn') }}
                            </button>
                        </div>
                    </div>

                    <!-- CSV Export Card -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 mb-4">
                                <FileSpreadsheet class="h-6 w-6" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-950 dark:text-white mb-2">{{ __('pages.reports.center.csv_title') }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-6">
                                {{ __('pages.reports.center.csv_desc') }}
                            </p>
                        </div>
                        <div>
                            <button
                                @click="exportCsv"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm rounded-xl transition duration-150 shadow-sm"
                            >
                                <Download class="h-4 w-4 me-2" />
                                {{ __('pages.reports.center.csv_btn') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
