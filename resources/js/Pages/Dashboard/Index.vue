<script setup>
import { computed, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, 
    Activity, 
    TrendingUp, 
    AlertCircle, 
    ArrowUpRight,
    Settings
} from '@lucide/vue';

const props = defineProps({
    totalTicketsCount: {
        type: Number,
        default: 0
    },
    medikTicketsCount: {
        type: Number,
        default: 0
    },
    nonMedikTicketsCount: {
        type: Number,
        default: 0
    },
    pendingTicketsCount: {
        type: Number,
        default: 0
    },
    recentTickets: {
        type: Array,
        default: () => []
    },
    breakdownData: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const now = new Date();
    
    // Check if today
    const isToday = date.toDateString() === now.toDateString();
    
    // Check if yesterday
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday = date.toDateString() === yesterday.toDateString();
    
    const timeStr = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    
    if (isToday) {
        return proxy.__('pages.dashboard.today') + ', ' + timeStr;
    } else if (isYesterday) {
        return proxy.__('pages.dashboard.yesterday') + ', ' + timeStr;
    } else {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ', ' + timeStr;
    }
};

const stats = computed(() => {
    const total = props.totalTicketsCount || 1;
    const medikPct = Math.round((props.medikTicketsCount / total) * 100);
    const nonMedikPct = Math.round((props.nonMedikTicketsCount / total) * 100);

    return [
        { label: proxy.__('pages.dashboard.total_reports'), value: String(props.totalTicketsCount), change: proxy.__('pages.dashboard.week_increase'), isPositive: true, icon: FileText, color: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-950/30' },
        { label: proxy.__('pages.dashboard.medical_support'), value: String(props.medikTicketsCount), change: `${medikPct}% ${proxy.__('pages.dashboard.pct_of_total')}`, isPositive: true, icon: Activity, color: 'text-sky-600 dark:text-sky-400', bg: 'bg-sky-50 dark:bg-sky-950/30' },
        { label: proxy.__('pages.dashboard.non_medical_support'), value: String(props.nonMedikTicketsCount), change: `${nonMedikPct}% ${proxy.__('pages.dashboard.pct_of_total')}`, isPositive: true, icon: Settings, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
        { label: proxy.__('pages.dashboard.waiting_verification'), value: String(props.pendingTicketsCount), change: proxy.__('pages.dashboard.yesterday_decrease'), isPositive: false, icon: AlertCircle, color: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-950/30' },
    ];
});

const recentReports = computed(() => {
    return props.recentTickets.map(ticket => {
        // Find division name to determine if Medik or Non-Medik
        const divName = ticket.category?.unit_feature?.supporting_unit?.division?.name ?? '';
        const isMedik = divName.toLowerCase().includes('medik') && !divName.toLowerCase().includes('non-medik');
        
        // Supporting unit name formatted nicely
        const rawUnitName = ticket.category?.unit_feature?.supporting_unit?.name ?? '';
        const unitName = rawUnitName ? rawUnitName.charAt(0).toUpperCase() + rawUnitName.slice(1).toLowerCase() : '';
        
        // Map ticket status
        // COMPLETED -> 'Verified', others -> 'Pending'
        const statusMap = ticket.status === 'COMPLETED' ? 'Verified' : 'Pending';

        return {
            id: ticket.ticket_number,
            date: formatDate(ticket.created_at),
            author: ticket.reporter?.name ?? '-',
            category: isMedik ? 'Medik' : 'Non-Medik',
            type: unitName,
            title: ticket.problem_description,
            status: statusMap
        };
    });
});

const categoriesBreakdown = computed(() => {
    return props.breakdownData.map(item => {
        const divisionLabel = item.division_name.toLowerCase().includes('medik') && !item.division_name.toLowerCase().includes('non-medik')
            ? proxy.__('pages.dashboard.medical_support').replace('Penunjang ', '')
            : proxy.__('pages.dashboard.non_medical_support').replace('Penunjang ', '');
            
        // Convert unit name (e.g. IPSRS) to a nice display name
        const rawName = item.name;
        let formattedName = rawName;
        if (['IPSRS', 'CSSD', 'SIMRS'].includes(rawName.toUpperCase())) {
            formattedName = rawName.toUpperCase();
        } else {
            formattedName = rawName.charAt(0).toUpperCase() + rawName.slice(1).toLowerCase();
        }

        return {
            name: `${formattedName} (${divisionLabel})`,
            percentage: item.percentage,
            count: item.count,
            color: item.color
        };
    });
});
</script>

<template>
    <Head :title="__('Dashboard')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4">
            <div class="w-full space-y-4">

                <!-- Welcome Card -->
                <div class="overflow-hidden bg-gradient-to-r from-indigo-600 to-indigo-800 dark:from-indigo-950 dark:to-slate-900 border border-transparent dark:border-slate-800 shadow-sm rounded-2xl text-white p-8 relative">
                    <!-- Text Info -->
                    <div class="relative z-10 max-w-xl">
                        <h3 class="text-2xl font-extrabold mb-1">PESU PELUH</h3>
                        <p class="text-indigo-100 dark:text-slate-300 text-sm font-medium leading-relaxed">
                            {{ __('pages.dashboard.desc') }}
                        </p>
                    </div>

                    <!-- Abstract Geometric Background Shapes (Watermark style) -->
                    <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-20">
                        <div class="absolute -right-16 -top-16 w-80 h-80 border-2 border-white rounded-[60px] rotate-[15deg]"></div>
                        <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                        <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div 
                        v-for="stat in stats" 
                        :key="stat.label"
                        class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between"
                    >
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ stat.label }}</span>
                            <div class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ stat.value }}</div>
                            <div class="flex items-center gap-1 mt-1">
                                <TrendingUp v-if="stat.isPositive" class="h-3 w-3 text-emerald-500" />
                                <span :class="['text-[11px] font-medium', stat.isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500']">
                                    {{ stat.change }}
                                </span>
                            </div>
                        </div>
                        <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.bg]">
                            <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                        </div>
                    </div>
                </div>

                <!-- Content Rows -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    
                    <!-- Left: Recent Reports Table -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-bold text-slate-950 dark:text-white">{{ __('pages.dashboard.recent_activities') }}</h4>
                                <Link 
                                    :href="route('reports.history')" 
                                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 flex items-center gap-1 transition-colors duration-150"
                                >
                                    {{ __('pages.dashboard.view_all') }}
                                    <ArrowUpRight class="h-3.5 w-3.5" />
                                </Link>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                            <th class="pb-3 pr-4">{{ __('pages.dashboard.id_title') }}</th>
                                            <th class="pb-3 px-4">{{ __('pages.dashboard.reporter') }}</th>
                                            <th class="pb-3 px-4">{{ __('pages.dashboard.unit_category') }}</th>
                                            <th class="pb-3 pl-4 text-right">{{ __('pages.dashboard.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-300">
                                        <tr v-if="recentReports.length === 0">
                                            <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                                {{ __('Belum ada aktivitas laporan terbaru.') }}
                                            </td>
                                        </tr>
                                        <tr v-else v-for="report in recentReports" :key="report.id" class="align-middle">
                                            <td class="py-3.5 pr-4">
                                                <div class="font-bold text-slate-950 dark:text-white">{{ report.id }}</div>
                                                <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate max-w-[180px]" :title="report.title">{{ report.title }}</div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ report.author }}</div>
                                                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ report.date }}</div>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold',
                                                    report.category === 'Medik' 
                                                        ? 'bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' 
                                                        : 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400'
                                                ]">
                                                    {{ report.type || '-' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 pl-4 text-right whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                    report.status === 'Verified'
                                                        ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300'
                                                        : 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300'
                                                ]">
                                                    {{ report.status === 'Verified' ? __('Verified') : __('Pending') }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Unit volume breakdown -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                        <div>
                            <h4 class="text-base font-bold text-slate-950 dark:text-white mb-4">{{ __('pages.dashboard.unit_volume') }}</h4>
                            <div class="space-y-4">
                                <div v-if="categoriesBreakdown.length === 0" class="text-center py-8 text-xs text-slate-400 dark:text-slate-500 font-semibold">
                                    {{ __('Belum ada data volume pelaporan unit.') }}
                                </div>
                                <div v-else v-for="category in categoriesBreakdown" :key="category.name" class="space-y-1">
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <span class="truncate max-w-[170px]" :title="category.name">{{ category.name }}</span>
                                        <span>{{ category.count }} {{ __('pages.dashboard.reports_count') }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                        <div 
                                            :class="['h-full rounded-full', category.color]"
                                            :style="{ width: `${category.percentage}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
