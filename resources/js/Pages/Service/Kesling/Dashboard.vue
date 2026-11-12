<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HeaderBanner from './Components/HeaderBanner.vue';
import StatCards from './Components/StatCards.vue';
import VueApexCharts from 'vue3-apexcharts';

import { 
    Ticket, 
    ClipboardCheck, 
    QrCode, 
    ArrowUpRight, 
    Clock, 
    Wrench,
    AlertCircle, 
    CheckCircle2, 
    MapPin, 
    Radio,
    AlertTriangle,
    Info,
    User,
    Check,
    X,
    TrendingUp,
    ShieldCheck,
    BarChart3,
    PieChart,
    Activity,
    Droplets,
    Sparkles,
    Wind,
    Layers,
    Building2
} from '@lucide/vue';

import CreateSanitationTicketModal from './Modals/CreateSanitationTicketModal.vue';
import ResolveTicketModal from './Modals/ResolveTicketModal.vue';

const props = defineProps({
    unit: { type: Object, default: () => ({}) },
    rooms: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    tickets: { type: Array, default: () => [] },
    inspections: { type: Array, default: () => [] },
    wasteData: { type: Object, default: () => ({}) },
    qrConfig: { type: Object, default: () => ({}) },
    personInCharge: { type: Object, default: () => ({}) }
});

const isMounted = ref(false);
const isDark = ref(false);

const checkTheme = () => {
    if (typeof document !== 'undefined') {
        isDark.value = document.documentElement.classList.contains('dark');
    }
};

let themeObserver = null;

onMounted(() => {
    isMounted.value = true;
    checkTheme();

    if (typeof MutationObserver !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            checkTheme();
        });
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
    }
});

const showCreateModal = ref(false);
const showResolveModal = ref(false);
const selectedTicketForResolution = ref(null);

const openResolveModal = (ticket) => {
    selectedTicketForResolution.value = ticket;
    showResolveModal.value = true;
};

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

// Urgensi Helper
const getUrgencyBadge = (urgency) => {
    if (urgency === 'DARURAT') {
        return {
            label: 'Darurat',
            icon: AlertTriangle,
            class: 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
        };
    }
    return {
        label: 'Normal',
        icon: Info,
        class: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/50'
    };
};

// Status Helper (Menunggu, Dikerjakan, Selesai)
const getStatusBadge = (status) => {
    switch (status) {
        case 'MENUNGGU':
        case 'OPEN':
            return {
                label: 'Menunggu',
                icon: AlertCircle,
                class: 'bg-orange-50 text-orange-700 border-orange-200/80 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900/50'
            };
        case 'DIKERJAKAN':
        case 'IN_PROGRESS':
            return {
                label: 'Dikerjakan',
                icon: Wrench,
                class: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-900/50'
            };
        case 'SELESAI':
        case 'RESOLVED':
        case 'CLOSED':
        default:
            return {
                label: 'Selesai',
                icon: CheckCircle2,
                class: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'
            };
    }
};

// Action Button Helper (Respon Darurat / Selesaikan Darurat jika DARURAT, Verifikasi saat Menunggu, Selesaikan saat Dikerjakan, Detail saat Selesai)
const getActionButtonText = (status, urgency) => {
    const s = status?.toUpperCase();
    if (s === 'DIKERJAKAN' || s === 'IN_PROGRESS') {
        return urgency === 'DARURAT' ? 'Selesaikan Darurat' : 'Selesaikan';
    }
    if (s === 'MENUNGGU' || s === 'OPEN') {
        return urgency === 'DARURAT' ? 'Respon Darurat' : 'Verifikasi';
    }
    return 'Detail';
};

const getActionButtonClass = (status, urgency) => {
    const s = status?.toUpperCase();
    if (urgency === 'DARURAT' && (s === 'MENUNGGU' || s === 'OPEN' || s === 'DIKERJAKAN' || s === 'IN_PROGRESS')) {
        return 'bg-rose-600 hover:bg-rose-500 text-white border-transparent shadow-xs animate-pulse';
    }
    if (s === 'DIKERJAKAN' || s === 'IN_PROGRESS') {
        return 'bg-blue-600 hover:bg-blue-500 text-white border-transparent';
    }
    if (s === 'MENUNGGU' || s === 'OPEN') {
        return 'bg-emerald-600 hover:bg-emerald-500 text-white border-transparent';
    }
    return 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700';
};

// Format Lokasi Gabungan (misal: Toilet Umum Disabilitas (Gedung C - Lantai 1))
const formatTicketLocation = (ticket) => {
    const point = ticket.location_point || 'Toilet Fasilitas Sanitasi';
    if (point.includes('(') && point.includes(')')) {
        return point;
    }
    const building = ticket.location_building;
    const floor = ticket.location_floor;
    if (building && floor) {
        return `${point} (${building} - ${floor})`;
    } else if (building) {
        return `${point} (${building})`;
    } else if (floor) {
        return `${point} (${floor})`;
    }
    return point;
};

// Inspeksi Status Helper
const getInspectionBadge = (status) => {
    if (status === 'EXCELLENT') {
        return {
            label: 'Sempurna',
            icon: CheckCircle2,
            class: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50'
        };
    } else if (status === 'NEED_ATTENTION') {
        return {
            label: 'Perlu Perhatian',
            icon: AlertCircle,
            class: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50'
        };
    }
    return {
        label: 'Menunggu Patroli',
        icon: Clock,
        class: 'bg-slate-100 text-slate-700 border-slate-200/80 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
    };
};

// Summary Analytics
const totalTickets = computed(() => props.tickets?.length || 0);
const emergencyCount = computed(() => props.tickets?.filter(t => t.urgency === 'DARURAT').length || 0);
const normalCount = computed(() => props.tickets?.filter(t => t.urgency === 'NORMAL' || !t.urgency).length || 0);
const waitingCount = computed(() => props.tickets?.filter(t => t.status === 'MENUNGGU' || t.status === 'OPEN').length || 0);
const inProgressCount = computed(() => props.tickets?.filter(t => t.status === 'DIKERJAKAN' || t.status === 'IN_PROGRESS').length || 0);
const resolvedCount = computed(() => props.tickets?.filter(t => t.status === 'SELESAI' || t.status === 'RESOLVED' || t.status === 'CLOSED').length || 0);

// Data Toilet
const toiletLocations = computed(() => {
    if (props.toilets && props.toilets.length > 0) return props.toilets;
    if (props.qrConfig?.locations && props.qrConfig.locations.length > 0) return props.qrConfig.locations;
    return [];
});
const activeToiletCount = computed(() => toiletLocations.value.filter(t => t.status === 'AKTIF').length);
const buildingBreakdown = computed(() => {
    const list = toiletLocations.value;
    const counts = {};
    list.forEach(item => {
        const b = item.building || 'Gedung RS';
        counts[b] = (counts[b] || 0) + 1;
    });
    return Object.entries(counts).map(([name, count]) => ({ name, count }));
});

// Mutu Standar 4 Pilar
const pillarAir = computed(() => props.stats?.pillarStats?.air ?? 100);
const pillarLantai = computed(() => props.stats?.pillarStats?.lantai ?? 100);
const pillarSabun = computed(() => props.stats?.pillarStats?.sabun ?? 100);
const pillarAroma = computed(() => props.stats?.pillarStats?.aroma ?? 100);

const overallMutu = computed(() => {
    return Math.round((Number(pillarAir.value) + Number(pillarLantai.value) + Number(pillarSabun.value) + Number(pillarAroma.value)) / 4);
});

const mutuGrade = computed(() => {
    if (overallMutu.value >= 90) return 'Sempurna - Grade A';
    if (overallMutu.value >= 75) return 'Baik - Grade B';
    return 'Perlu Perhatian - Grade C';
});

// ==========================================
// APEXCHARTS CONFIGURATIONS
// ==========================================

// 1. Trend Area Chart (Aduan Normal vs Darurat vs Selesai)
const trendDays = computed(() => {
    if (props.stats?.trendData && props.stats.trendData.length > 0) {
        return props.stats.trendData;
    }
    const days = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        const dayStr = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        days.push({ date: dayStr, normal: 0, darurat: 0, selesai: 0 });
    }
    return days;
});

const trendSeries = computed(() => [
    {
        name: 'Aduan Normal',
        data: trendDays.value.map(d => d.normal)
    },
    {
        name: 'Aduan Darurat',
        data: trendDays.value.map(d => d.darurat)
    },
    {
        name: 'Terselesaikan',
        data: trendDays.value.map(d => d.selesai)
    }
]);

const trendChartOptions = computed(() => {
    const dark = isDark.value;
    return {
        chart: {
            type: 'area',
            height: 260,
            toolbar: { show: false },
            fontFamily: 'Inter, system-ui, sans-serif',
            background: 'transparent',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 600
            }
        },
        theme: {
            mode: dark ? 'dark' : 'light'
        },
        colors: ['#0284c7', '#e11d48', '#059669'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        stroke: {
            curve: 'smooth',
            width: [2.5, 2.5, 2.5]
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: dark ? '#334155' : '#f1f5f9',
            strokeDashArray: 4,
            padding: { top: 0, right: 10, bottom: 0, left: 10 }
        },
        xaxis: {
            categories: trendDays.value.map(d => d.date),
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                    fontWeight: 500
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: {
                    colors: dark ? '#94a3b8' : '#64748b',
                    fontSize: '11px'
                },
                formatter: (val) => Math.round(val)
            },
            min: 0,
            forceNiceScale: true
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '11px',
            fontWeight: 600,
            labels: {
                colors: dark ? '#cbd5e1' : '#475569'
            },
            markers: {
                radius: 12
            }
        },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            shared: true,
            intersect: false,
            y: {
                formatter: (val) => `${val} Tiket`
            }
        }
    };
});

// 2. Donut Chart (Distribusi Status & Urgensi)
const donutSeries = computed(() => {
    const s = [
        resolvedCount.value || 0,
        inProgressCount.value || 0,
        waitingCount.value || 0
    ];
    return s.some(v => v > 0) ? s : [1];
});

const donutOptions = computed(() => {
    const dark = isDark.value;
    const hasData = (resolvedCount.value + inProgressCount.value + waitingCount.value) > 0;
    return {
        chart: {
            type: 'donut',
            height: 260,
            fontFamily: 'Inter, system-ui, sans-serif',
            background: 'transparent'
        },
        theme: {
            mode: dark ? 'dark' : 'light'
        },
        labels: hasData 
            ? ['Selesai', 'Dikerjakan', 'Menunggu'] 
            : ['Belum Ada Tiket'],
        colors: hasData 
            ? ['#059669', '#2563eb', '#ea580c'] 
            : ['#cbd5e1'],
        stroke: {
            colors: [dark ? '#0f172a' : '#ffffff'],
            width: 2
        },
        dataLabels: {
            enabled: hasData,
            formatter: (val) => `${Math.round(val)}%`,
            style: {
                fontSize: '11px',
                fontWeight: 'bold'
            },
            dropShadow: { enabled: false }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '11px',
                            color: dark ? '#94a3b8' : '#64748b'
                        },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: '800',
                            color: dark ? '#ffffff' : '#0f172a',
                            formatter: () => `${totalTickets.value}`
                        },
                        total: {
                            show: true,
                            label: 'Total Aduan',
                            color: dark ? '#94a3b8' : '#64748b',
                            fontSize: '10px',
                            fontWeight: 600,
                            formatter: () => `${totalTickets.value}`
                        }
                    }
                }
            }
        },
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontWeight: 500,
            labels: {
                colors: dark ? '#cbd5e1' : '#475569'
            },
            markers: {
                radius: 12
            }
        },
        tooltip: {
            theme: dark ? 'dark' : 'light',
            y: {
                formatter: (val) => `${val} Tiket`
            }
        }
    };
});
</script>

<template>
    <Head title="Dashboard - PAN-SINAR" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                <!-- 1. Header Banner -->
                <HeaderBanner />

                <!-- 2. Indikator Kinerja Utama (Top Stat Cards) -->
                <StatCards :stats="stats" />

                <!-- 3. GRAFIK & ANALITIK KESLING (Tren Aduan, Kedaruratan, & Kepatuhan Sanitasi) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Grafik 1: Tren Aduan Sanitasi (Normal vs Darurat vs Selesai) -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                        <div class="mb-4">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                                Tren Aduan Sanitasi
                            </h3>
                        </div>

                        <!-- Chart Container -->
                        <div class="h-[260px] w-full" v-if="isMounted">
                            <VueApexCharts
                                type="area"
                                height="260"
                                :options="trendChartOptions"
                                :series="trendSeries"
                            />
                        </div>
                    </div>

                    <!-- Grafik 2: Distribusi Status & Urgensi (Donut Chart) -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                                    Status & Urgensi Aduan
                                </h3>
                            </div>
                        </div>

                        <!-- Donut Chart Container -->
                        <div class="h-[250px] w-full flex items-center justify-center my-1" v-if="isMounted">
                            <VueApexCharts
                                type="donut"
                                height="250"
                                :options="donutOptions"
                                :series="donutSeries"
                            />
                        </div>

                        <!-- Mini Footer Info -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-2 gap-2 text-center text-xs">
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-[10px] font-bold text-slate-400 block uppercase">Tiket Menunggu</span>
                                <span class="text-sm font-extrabold text-orange-600 dark:text-orange-400">{{ waitingCount }} Tiket</span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-[10px] font-bold text-slate-400 block uppercase">Telah Selesai</span>
                                <span class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">{{ resolvedCount }} Tiket</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. KEPATUHAN 4 PILAR STANDAR SANITASI RS (Air, Lantai, Sabun, Aroma) -->
                <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800/80 rounded-2xl p-5 sm:p-6 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">
                            Indeks Standar Mutu Sanitasi
                        </h3>
                    </div>

                    <!-- 4 Pillars Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Pilar 1: Ketersediaan Air -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Droplets class="w-4 h-4 text-blue-500 shrink-0" />
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Pilar 1: Ketersediaan Air</span>
                                </div>
                                <span class="text-xs font-extrabold text-blue-600 dark:text-blue-400 shrink-0">{{ pillarAir }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full transition-all duration-500" :style="{ width: pillarAir + '%' }"></div>
                            </div>
                            <p class="text-[10px] text-slate-400">Aliran air bersih lancar serta kran, selang, atau flush berfungsi</p>
                        </div>

                        <!-- Pilar 2: Kebersihan -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Sparkles class="w-4 h-4 text-amber-500 shrink-0" />
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Pilar 2: Kebersihan</span>
                                </div>
                                <span class="text-xs font-extrabold text-amber-600 dark:text-amber-400 shrink-0">{{ pillarLantai }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full transition-all duration-500" :style="{ width: pillarLantai + '%' }"></div>
                            </div>
                            <p class="text-[10px] text-slate-400">Kondisi lantai, tembok, kloset, serta wastafel/cermin bersih</p>
                        </div>

                        <!-- Pilar 3: Ketersediaan Sabun & Tisu -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0" />
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Pilar 3: Ketersediaan Sabun & Tisu</span>
                                </div>
                                <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 shrink-0">{{ pillarSabun }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: pillarSabun + '%' }"></div>
                            </div>
                            <p class="text-[10px] text-slate-400">Sabun/tisu tersedia sesuai fasilitas, tempat sampah terkelola</p>
                        </div>

                        <!-- Pilar 4: Keharuman & Sirkulasi Udara -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Wind class="w-4 h-4 text-teal-500 shrink-0" />
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">Pilar 4: Keharuman & Sirkulasi Udara</span>
                                </div>
                                <span class="text-xs font-extrabold text-teal-600 dark:text-teal-400 shrink-0">{{ pillarAroma }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="bg-teal-500 h-full rounded-full transition-all duration-500" :style="{ width: pillarAroma + '%' }"></div>
                            </div>
                            <p class="text-[10px] text-slate-400">Ruangan bebas bau; pengharum, ventilasi/exhaust fan berfungsi</p>
                        </div>
                    </div>
                </div>

                <!-- 5. GRID RINGKASAN SUB-MODUL PAN-SINAR (Lengkap, Rapi, & Berinformasi Jelas) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- KARTU 1: ADUAN SANITASI & TOILET TERKINI -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                        <div>
                            <!-- Header Kartu -->
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white truncate">
                                        Aduan Sanitasi & Toilet Terkini
                                    </h3>
                                </div>
                                <Link 
                                    :href="route('pansinar.tickets.index')" 
                                    prefetch
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-emerald-700 bg-emerald-50 dark:bg-white/10 dark:text-white hover:bg-emerald-100 transition duration-150 shrink-0"
                                >
                                    <span>Lihat Semua Tiket</span>
                                    <ArrowUpRight class="w-3.5 h-3.5" />
                                </Link>
                            </div>

                            <!-- Ticket List Preview (Desain Rapi, Seragam, & Lengkap) -->
                            <div class="space-y-2.5">
                                <div 
                                    v-for="t in tickets.slice(0, 3)" 
                                    :key="t.id"
                                    class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2 hover:border-emerald-500/40 transition-all text-xs"
                                >
                                    <!-- Baris 1: Nomor Tiket & Badge Urgensi + Status -->
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <Ticket class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                            <span class="font-extrabold text-slate-900 dark:text-white text-xs truncate">
                                                {{ t.ticket_number }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <!-- Badge Urgensi -->
                                            <span :class="[
                                                'px-2 py-0.5 rounded-lg text-[9px] font-extrabold border uppercase inline-flex items-center gap-1',
                                                getUrgencyBadge(t.urgency).class
                                            ]">
                                                <component :is="getUrgencyBadge(t.urgency).icon" class="w-2.5 h-2.5 shrink-0 stroke-[2.5]" />
                                                <span>{{ getUrgencyBadge(t.urgency).label }}</span>
                                            </span>

                                            <!-- Badge Status -->
                                            <span :class="[
                                                'px-2 py-0.5 rounded-lg text-[9px] font-extrabold border uppercase inline-flex items-center gap-1',
                                                getStatusBadge(t.status).class
                                            ]">
                                                <component :is="getStatusBadge(t.status).icon" class="w-2.5 h-2.5 shrink-0 stroke-[2.5]" />
                                                <span>{{ getStatusBadge(t.status).label }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Baris 2: Lokasi Bersambung -->
                                    <div class="flex items-start gap-1.5 text-slate-700 dark:text-slate-200">
                                        <MapPin class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" />
                                        <span class="font-semibold text-xs leading-snug break-words">
                                            {{ formatTicketLocation(t) }}
                                        </span>
                                    </div>

                                    <!-- Baris 3: Uraian Kendala -->
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 italic bg-white dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-800 leading-relaxed">
                                        "{{ t.problem_description || t.category_name }}"
                                    </p>

                                    <!-- Baris 4: Info Pelapor & Tombol Verifikasi/Detail -->
                                    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200/40 dark:border-slate-800/60 text-[10px]">
                                        <div class="flex items-center gap-2 text-slate-400 truncate">
                                            <span class="flex items-center gap-1 truncate">
                                                <User class="w-3 h-3 shrink-0" />
                                                <span class="font-medium truncate">{{ t.reporter_name || 'Masyarakat' }}</span>
                                            </span>
                                            <span>•</span>
                                            <span class="flex items-center gap-1 shrink-0">
                                                <Clock class="w-3 h-3 shrink-0" />
                                                <span>{{ t.created_at_formatted || t.created_at }}</span>
                                            </span>
                                        </div>

                                        <button
                                            @click="openResolveModal(t)"
                                            type="button"
                                            :class="[
                                                'px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase inline-flex items-center gap-1 border transition cursor-pointer active:scale-95 shrink-0',
                                                getActionButtonClass(t.status, t.urgency)
                                            ]"
                                        >
                                            <span>{{ getActionButtonText(t.status, t.urgency) }}</span>
                                            <ArrowUpRight class="w-2.5 h-2.5 stroke-[2.5]" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Empty State dengan Rounded Space -->
                                <div v-if="tickets.length === 0" class="py-8 px-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800/80 text-center flex flex-col items-center justify-center">
                                    <Ticket class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2 stroke-[1.5]" />
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Tidak ada aduan aktif saat ini.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Semua fasilitas toilet berfungsi normal</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 2: PATROLI CEKLIS SANITASI TOILET -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                        <div>
                            <!-- Header Kartu -->
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white truncate">
                                        Patroli Ceklis Sanitasi Toilet
                                    </h3>
                                </div>
                                <Link 
                                    :href="route('pansinar.inspections.index')" 
                                    prefetch
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-teal-700 bg-teal-50 dark:bg-white/10 dark:text-white hover:bg-teal-100 transition duration-150 shrink-0"
                                >
                                    <span>Buka Ceklis</span>
                                    <ArrowUpRight class="w-3.5 h-3.5" />
                                </Link>
                            </div>

                            <!-- Inspection List Preview -->
                            <div class="space-y-2.5">
                                <div 
                                    v-for="insp in inspections.slice(0, 3)" 
                                    :key="insp.id"
                                    class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 space-y-2 hover:border-teal-500/40 transition-all text-xs"
                                >
                                    <!-- Baris 1: Lokasi & Status Badge -->
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-1.5 min-w-0 flex-1">
                                            <MapPin class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" />
                                            <span class="font-extrabold text-slate-900 dark:text-white text-xs leading-snug break-words">
                                                {{ insp.location }}
                                            </span>
                                        </div>
                                        <span :class="[
                                            'px-2 py-0.5 rounded-lg text-[9px] font-extrabold border uppercase inline-flex items-center gap-1 shrink-0',
                                            getInspectionBadge(insp.status).class
                                        ]">
                                            <component :is="getInspectionBadge(insp.status).icon" class="w-2.5 h-2.5 shrink-0 stroke-[2.5]" />
                                            <span>{{ getInspectionBadge(insp.status).label }}</span>
                                        </span>
                                    </div>

                                    <!-- Baris 2: Shift, Waktu & Petugas -->
                                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 flex-wrap gap-1">
                                        <span class="flex items-center gap-1 font-medium">
                                            <Clock class="w-3 h-3 text-slate-400 shrink-0" />
                                            <span>{{ insp.shift }} • {{ insp.checked_at }}</span>
                                        </span>
                                        <span class="flex items-center gap-1 font-semibold text-slate-700 dark:text-slate-300">
                                            <User class="w-3 h-3 text-slate-400 shrink-0" />
                                            <span>{{ insp.inspector }}</span>
                                        </span>
                                    </div>

                                    <!-- Baris 3: 4 Pilar Kepatuhan Mini Pills -->
                                    <div class="pt-2 border-t border-slate-200/40 dark:border-slate-800/60 grid grid-cols-4 gap-1.5 text-center text-[10px]">
                                        <!-- Air -->
                                        <div :class="[
                                            'py-1 px-1 rounded-md border flex items-center justify-center gap-0.5 font-bold',
                                            insp.water_flush_ok ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
                                        ]">
                                            <span>Air</span>
                                            <component :is="insp.water_flush_ok ? Check : X" class="w-2.5 h-2.5 stroke-[3]" />
                                        </div>

                                        <!-- Kebersihan -->
                                        <div :class="[
                                            'py-1 px-1 rounded-md border flex items-center justify-center gap-0.5 font-bold',
                                            insp.floor_mirror_clean ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
                                        ]">
                                            <span>Kebersihan</span>
                                            <component :is="insp.floor_mirror_clean ? Check : X" class="w-2.5 h-2.5 stroke-[3]" />
                                        </div>

                                        <!-- Sabun -->
                                        <div :class="[
                                            'py-1 px-1 rounded-md border flex items-center justify-center gap-0.5 font-bold',
                                            insp.supplies_ready ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
                                        ]">
                                            <span>Sabun</span>
                                            <component :is="insp.supplies_ready ? Check : X" class="w-2.5 h-2.5 stroke-[3]" />
                                        </div>

                                        <!-- Aroma -->
                                        <div :class="[
                                            'py-1 px-1 rounded-md border flex items-center justify-center gap-0.5 font-bold',
                                            insp.air_fragrance_ok ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50'
                                        ]">
                                            <span>Aroma</span>
                                            <component :is="insp.air_fragrance_ok ? Check : X" class="w-2.5 h-2.5 stroke-[3]" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State dengan Rounded Space -->
                                <div v-if="inspections.length === 0" class="py-8 px-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800/80 text-center flex flex-col items-center justify-center">
                                    <ClipboardCheck class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2 stroke-[1.5]" />
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Belum ada inspeksi patroli tercatat.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Hasil ceklis petugas sanitasi akan tampil di sini</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CreateSanitationTicketModal 
            :show="showCreateModal"
            :rooms="rooms"
            :categories="categories"
            @close="showCreateModal = false"
        />

        <ResolveTicketModal 
            :show="showResolveModal"
            :ticket="selectedTicketForResolution"
            @ticketUpdated="handleTicketUpdated"
            @close="showResolveModal = false"
        />
    </AuthenticatedLayout>
</template>
