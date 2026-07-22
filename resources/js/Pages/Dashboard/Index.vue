<script setup>
import { computed, getCurrentInstance } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, Deferred } from '@inertiajs/vue3';
import { 
    FileText, 
    Activity, 
    TrendingUp, 
    AlertCircle, 
    ArrowUpRight,
    Settings,
    Clock,
    CheckCircle2
} from '@lucide/vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
    userRole: {
        type: String,
        default: 'GLOBAL'
    },
    dashboardStats: {
        type: Object,
        default: () => null
    }
});

const recentTicketsData = computed(() => props.dashboardStats?.recentTickets ?? []);
const breakdownDataData = computed(() => props.dashboardStats?.breakdownData ?? []);

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
    const ds = props.dashboardStats;
    if (!ds) return [];

    const getStatConfig = (stat) => {
        if (!stat) return null;

        const typeConfig = {
            'total': { icon: FileText, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
            'medik': { icon: Activity, color: 'text-sky-600 dark:text-sky-400', bg: 'bg-sky-50 dark:bg-sky-950/30' },
            'non_medik': { icon: Settings, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
            'progress': { icon: Clock, color: 'text-indigo-600 dark:text-indigo-400', bg: 'bg-indigo-50 dark:bg-indigo-950/30' },
            'completed': { icon: CheckCircle2, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
            'pending': { icon: AlertCircle, color: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-950/30' },
        };

        const config = typeConfig[stat.type] || typeConfig['total'];

        return {
            label: stat.label,
            value: String(stat.value ?? 0),
            icon: config.icon,
            color: config.color,
            bg: config.bg,
        };
    };

    return [
        getStatConfig(ds.stat1),
        getStatConfig(ds.stat2),
        getStatConfig(ds.stat3),
        getStatConfig(ds.stat4),
    ].filter(Boolean);
});

const recentReports = computed(() => {
    return (recentTicketsData.value || []).map(ticket => {
        // Find division name to determine if Medik or Non-Medik
        const divName = ticket.category?.unit_feature?.supporting_unit?.division?.name ?? '';
        const isMedik = divName.toLowerCase().includes('medik') && !divName.toLowerCase().includes('non-medik');
        
        // Supporting unit name formatted nicely
        const rawUnitName = ticket.category?.unit_feature?.supporting_unit?.name ?? '';
        const unitName = rawUnitName ? rawUnitName.charAt(0).toUpperCase() + rawUnitName.slice(1).toLowerCase() : '';
        
        // Map ticket status
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
    return (breakdownDataData.value || []).map(item => {
        const divisionLabel = item.division_name.toLowerCase().includes('medik') && !item.division_name.toLowerCase().includes('non-medik')
            ? proxy.__('pages.dashboard.medical_support').replace('Penunjang ', '')
            : (item.division_name ? proxy.__('pages.dashboard.non_medical_support').replace('Penunjang ', '') : '');
            
        // Convert unit name (e.g. IPSRS) to a nice display name
        const rawName = item.name;
        let formattedName = rawName;
        if (['IPSRS', 'CSSD', 'SIMRS'].includes(rawName.toUpperCase())) {
            formattedName = rawName.toUpperCase();
        } else {
            formattedName = rawName.charAt(0).toUpperCase() + rawName.slice(1).toLowerCase();
        }

        const displayName = divisionLabel ? `${formattedName} (${divisionLabel})` : formattedName;

        return {
            name: displayName,
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
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">

                <!-- Welcome Card -->
                <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-emerald-950 dark:to-slate-900 border border-transparent dark:border-slate-800 shadow-sm rounded-2xl text-white p-8 relative">
                    <!-- Text Info -->
                    <div class="relative z-10 max-w-xl">
                        <h3 class="text-2xl font-extrabold mb-1">PESU PELUH</h3>
                        <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed">
                            {{ __('pages.dashboard.desc') }}
                        </p>
                    </div>

                    <!-- Decorative background patterns -->
                    <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none overflow-hidden select-none">
                        <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                        <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
                    </div>
                </div>

                <Deferred data="dashboardStats">
                    <template #fallback>
                        <!-- Skeleton Loading Matching Dashboard Layout -->
                        <div class="w-full space-y-4">
                            <!-- Skeleton Stats Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between animate-pulse">
                                    <div class="space-y-2 flex-1 pr-4">
                                        <div class="h-3 w-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                        <div class="h-8 w-16 bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                                    </div>
                                    <div class="h-12 w-12 rounded-xl bg-slate-100 dark:bg-slate-800 shrink-0"></div>
                                </div>
                            </div>

                            <!-- Skeleton Content Rows -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <!-- Skeleton Table -->
                                <div :class="[userRole === 'REPORTER' ? 'lg:col-span-3' : 'lg:col-span-2', 'bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 animate-pulse']">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="h-5 w-40 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                        <div class="h-4 w-20 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                                    </div>
                                    <div class="space-y-3">
                                        <div v-for="j in 4" :key="j" class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                                            <div class="space-y-1.5 flex-1">
                                                <div class="h-4 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                                <div class="h-3 w-48 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                                            </div>
                                            <div class="h-5 w-16 bg-slate-200 dark:bg-slate-800 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Skeleton Unit Breakdown (Hidden for REPORTER) -->
                                <div v-if="userRole !== 'REPORTER'" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4 animate-pulse">
                                    <div class="h-5 w-36 bg-slate-200 dark:bg-slate-800 rounded mb-4"></div>
                                    <div class="space-y-4">
                                        <div v-for="k in 5" :key="k" class="space-y-2">
                                            <div class="flex justify-between">
                                                <div class="h-3 w-28 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                                <div class="h-3 w-16 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                            </div>
                                            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Real Data Content -->
                    <div class="w-full space-y-4">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div 
                                v-for="stat in stats" 
                                :key="stat.label"
                                class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between"
                            >
                                <div class="space-y-1">
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-505 uppercase tracking-wider">{{ stat.label }}</span>
                                    <div class="text-3xl font-extrabold text-slate-955 dark:text-white">{{ stat.value }}</div>
                                </div>
                                <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', stat.bg]">
                                    <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                                </div>
                            </div>
                        </div>

                        <!-- Content Rows -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            
                            <!-- Left: Recent Reports Table -->
                            <div :class="[userRole === 'REPORTER' ? 'lg:col-span-3' : 'lg:col-span-2', 'bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4']">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-base font-bold text-slate-955 dark:text-white">{{ __('pages.dashboard.recent_activities') }}</h4>
                                        <Link 
                                            :href="route('reports.history')" 
                                            prefetch
                                            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 flex items-center gap-1 transition-colors duration-150"
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
                                                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-505">
                                                        {{ __('Belum ada aktivitas laporan terbaru.') }}
                                                    </td>
                                                </tr>
                                                <tr v-else v-for="report in recentReports" :key="report.id" class="align-middle">
                                                    <td class="py-3.5 pr-4">
                                                        <div class="font-bold text-slate-955 dark:text-white">{{ report.id }}</div>
                                                        <div class="text-[11px] text-slate-405 mt-0.5 truncate max-w-[240px]" :title="report.title">{{ report.title }}</div>
                                                    </td>
                                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                                        <div class="font-medium text-slate-800 dark:text-slate-200">{{ report.author }}</div>
                                                        <div class="text-[10px] text-slate-405 mt-0.5">{{ report.date }}</div>
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

                            <!-- Right: Unit volume breakdown (Hidden for REPORTER) -->
                            <div v-if="userRole !== 'REPORTER'" class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between space-y-4">
                                <div>
                                    <h4 class="text-base font-bold text-slate-955 dark:text-white mb-4">{{ __('pages.dashboard.unit_volume') }}</h4>
                                    <div class="space-y-4">
                                        <div v-if="categoriesBreakdown.length === 0" class="text-center py-8 text-xs text-slate-400 dark:text-slate-505 font-semibold">
                                            {{ __('Belum ada data volume pelaporan unit.') }}
                                        </div>
                                        <div v-else v-for="category in categoriesBreakdown" :key="category.name" class="space-y-1">
                                            <div class="flex items-center justify-between text-xs font-semibold text-slate-705 dark:text-slate-300">
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
