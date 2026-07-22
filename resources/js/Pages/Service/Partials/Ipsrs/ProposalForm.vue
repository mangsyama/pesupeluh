<script setup>
import { FilePlus } from '@lucide/vue';

const props = defineProps({
    unit: {
        type: Object,
        required: true
    },
    activeFeature: {
        type: Object,
        required: true
    },
    isMedik: {
        type: Boolean,
        required: true
    }
});
</script>

<template>
    <!-- Placeholder Premium for Usulan -->
    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4 shadow-sm min-h-[350px] animate-spa-fade-in">
        <div :class="[
            'h-16 w-16 rounded-full flex items-center justify-center text-slate-455 dark:text-slate-500',
            isMedik ? 'bg-indigo-50 dark:bg-indigo-950/30' : 'bg-emerald-50 dark:bg-emerald-950/30'
        ]">
            <FilePlus :class="['h-8 w-8', isMedik ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400']" />
        </div>
        <div class="space-y-1.5 max-w-md">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                {{ __('Modul Usulan') }}
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                {{ __('Formulir Usulan sedang disesuaikan dengan kebutuhan administrasi rumah sakit.') }}
            </p>
        </div>
        <div class="pt-2">
            <span :class="[
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wide uppercase',
                isMedik ? 'bg-indigo-100/75 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300' : 'bg-emerald-100/75 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300'
            ]">
                <span :class="['h-1.5 w-1.5 rounded-full', isMedik ? 'bg-indigo-500' : 'bg-emerald-500', 'animate-pulse']" />
                {{ __('Dalam Pengembangan') }}
            </span>
        </div>
    </div>
</template>
