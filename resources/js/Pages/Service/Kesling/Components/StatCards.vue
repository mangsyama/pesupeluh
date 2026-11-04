<script setup>
import { 
    AlertCircle, 
    Clock, 
    CheckCircle2, 
    BellRing, 
    TrendingUp,
    ShieldCheck
} from '@lucide/vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            openTicketsCount: 0,
            slaComplianceRate: 100,
            dailyChecklistProgress: '0 / 0 Titik',
            dailyChecklistPercent: 0,
            panicIncidentCount: 0,
        })
    }
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat Card 1: Aduan Terbuka -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1 flex-1 min-w-0 pr-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                    Aduan Terbuka
                </span>
                <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                    {{ stats.openTicketsCount }}
                </div>
                <div class="flex items-center gap-1.5 text-[11px]">
                    <span :class="stats.openTicketsCount > 0 ? 'font-bold text-amber-600 dark:text-amber-400' : 'font-bold text-emerald-600 dark:text-emerald-400'">
                        {{ stats.openTicketsCount > 0 ? 'Menunggu Penanganan' : 'Semua Terkendali' }}
                    </span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <AlertCircle class="h-6 w-6" />
            </div>
        </div>

        <!-- Stat Card 2: Kepatuhan SLA -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1 flex-1 min-w-0 pr-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                    Kepatuhan SLA
                </span>
                <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-tight">
                    {{ stats.slaComplianceRate }}%
                </div>
                <div class="flex items-center gap-1.5 text-[11px]">
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-0.5">
                        <TrendingUp class="w-3.5 h-3.5" />
                        Target ≥ 90%
                    </span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span class="text-slate-500 dark:text-slate-400">Tercapai</span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                <Clock class="h-6 w-6" />
            </div>
        </div>

        <!-- Stat Card 3: Ceklis Inspeksi Harian -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1 flex-1 min-w-0 pr-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                    Ceklis Patroli Harian
                </span>
                <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                    {{ stats.dailyChecklistPercent }}%
                </div>
                <div class="flex items-center gap-1.5 text-[11px]">
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ stats.dailyChecklistProgress }}</span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Tervalidasi</span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                <CheckCircle2 class="h-6 w-6" />
            </div>
        </div>

        <!-- Stat Card 4: Insiden Darurat (Panic Button) -->
        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div class="space-y-1 flex-1 min-w-0 pr-2">
                <span class="text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider block truncate">
                    Insiden Panic Button
                </span>
                <div class="text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                    {{ stats.panicIncidentCount }}
                </div>
                <div class="flex items-center gap-1.5 text-[11px]">
                    <span :class="stats.panicIncidentCount > 0 ? 'font-bold text-rose-600 dark:text-rose-400' : 'font-bold text-emerald-600 dark:text-emerald-400'">
                        {{ stats.panicIncidentCount > 0 ? 'Perlu Tindakan Cepat' : 'Aman Terkendali' }}
                    </span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                <BellRing class="h-6 w-6" />
            </div>
        </div>
    </div>
</template>
