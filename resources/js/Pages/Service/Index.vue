<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Stethoscope, 
    ShieldCheck, 
    ArrowRight, 
    Lock,
    Wrench,
    Utensils,
    Shirt,
    Leaf,
    Pill,
    Scan,
    Microscope,
    Sparkles,
    Activity
} from '@lucide/vue';
import { ref, watch, getCurrentInstance, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    initialSection: {
        type: String,
        default: null
    },
    units: {
        type: Array,
        default: () => []
    }
});

const isUnitsLoading = computed(() => !props.units || props.units.length === 0);

const activeSection = ref(props.initialSection);

watch(() => props.initialSection, (newVal) => {
    activeSection.value = newVal;
});

const handleBackClicked = () => {
    activeSection.value = null;
};

onMounted(() => {
    window.addEventListener('services-back-clicked', handleBackClicked);
});

onUnmounted(() => {
    window.removeEventListener('services-back-clicked', handleBackClicked);
});

const { proxy } = getCurrentInstance();

const handleUnitClick = (unit) => {
    if (unit.disabled) {
        proxy.$swal({
            title: proxy.__('pages.services.alerts.dev_title'),
            text: proxy.__('pages.services.alerts.dev_text').replace('{name}', unit.name),
            icon: 'info',
            confirmButtonColor: '#4f46e5',
        });
    } else {
        router.visit(route('services.units.show', unit.slug || unit.name.toLowerCase()));
    }
};

const medikUnits = computed(() => {
    return (props.units || [])
        .filter(u => u.type === 'MEDIK')
        .map(unit => ({
            ...unit,
            disabled: unit.status !== 'ACTIVE'
        }))
        .sort((a, b) => {
            if (a.disabled === b.disabled) return 0;
            return a.disabled ? 1 : -1;
        });
});

const nonMedikUnits = computed(() => {
    return (props.units || [])
        .filter(u => u.type === 'NON_MEDIK')
        .map(unit => ({
            ...unit,
            disabled: unit.status !== 'ACTIVE'
        }))
        .sort((a, b) => {
            if (a.disabled === b.disabled) return 0;
            return a.disabled ? 1 : -1;
        });
});

// Unit icon mapping registry for premium aesthetic visual difference
const unitIcons = {
    ipsrs: Wrench,
    gizi: Utensils,
    laundry: Shirt,
    kesling: Leaf,
    farmasi: Pill,
    radiologi: Scan,
    laboratorium: Microscope,
    cssd: Sparkles
};

const getUnitIcon = (name, slug, type) => {
    const key = (slug || name)?.toLowerCase();
    if (unitIcons[key]) return unitIcons[key];
    
    // Fallback based on type
    return type === 'MEDIK' ? Stethoscope : ShieldCheck;
};
</script>

<template>
    <Head :title="activeSection === 'medik' ? medikDivision?.name : (activeSection === 'non-medik' ? nonMedikDivision?.name : __('pages.services.title'))" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                
                <!-- Premium Header Panel -->
                <div v-if="activeSection === null" class="hidden sm:flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex h-12 w-12 rounded-xl flex-shrink-0 items-center justify-center bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white">
                            <Activity class="h-6 w-6" />
                        </div>
                        <div class="space-y-0.5">
                            <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                                {{ __('pages.services.title') }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl leading-relaxed">
                                {{ __('Pilih divisi layanan penunjang untuk melaporkan kendala teknis atau kebutuhan operasional.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Main Options Container / Instant Width Layout -->
                <div 
                    class="flex flex-col md:flex-row" 
                    :class="[activeSection === null ? 'gap-4' : 'gap-0']"
                >
                        <!-- Card 1: Penunjang Medik -->
                        <component
                            v-if="activeSection === null || activeSection === 'medik'"
                            :is="activeSection === null ? Link : 'div'"
                            :href="activeSection === null ? route('services.medik') : undefined"
                            prefetch
                            class="group relative overflow-hidden bg-white dark:bg-slate-900 border border-white dark:border-slate-800/80 rounded-2xl flex flex-col justify-between"
                            :class="[
                                activeSection === null ? 'cursor-pointer hover:border-slate-200 dark:hover:border-slate-700 shadow-sm w-full md:w-1/2 p-6' : 'cursor-default border-white dark:border-slate-800/80 shadow-sm w-full p-6'
                            ]"
                        >
                            <div class="w-full flex flex-col h-full">
                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" v-if="activeSection === null" />
                                <div>
                                    <!-- Header Row: Icon on left, Badge on right -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex-shrink-0">
                                            <Stethoscope class="h-6 w-6" />
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-white/10 text-emerald-800 dark:text-white flex-shrink-0">
                                            {{ __('pages.services.clinical_badge') }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-lg font-bold text-slate-955 dark:text-white" :class="{ 'group-hover:text-emerald-600 dark:group-hover:text-white transition-colors duration-200': activeSection === null }">
                                        {{ __('Penunjang Medik') }}
                                    </h3>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-if="activeSection === null">
                                        {{ __('Mencakup pelaporan operasional unit penunjang pelayanan medis yang terdiri dari unit Farmasi, Radiologi, Laboratorium, dan CSSD.') }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-else>
                                        {{ __('pages.services.medik_desc_detail') }}
                                    </p>
                                </div>

                                <!-- Footer Action -->
                                <div class="mt-6 flex items-center justify-between" v-if="activeSection === null">
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-white">
                                        {{ __('pages.services.btn_enter_medik') }}
                                    </span>
                                    <div class="h-8 w-8 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex-shrink-0 flex items-center justify-center transition-all duration-300 group-hover:bg-emerald-600 group-hover:text-white dark:group-hover:bg-white dark:group-hover:text-slate-900">
                                        <ArrowRight class="h-4 w-4" />
                                    </div>
                                </div>

                                <!-- Sub-units -->
                                <div 
                                    v-if="activeSection === 'medik'"
                                    class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/80"
                                >
                                    <div class="grid grid-cols-1 min-[450px]:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                        <!-- Skeleton Loading Cards -->
                                        <template v-if="isUnitsLoading">
                                            <div 
                                                v-for="n in 4" 
                                                :key="'skel-medik-' + n"
                                                class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-3.5 sm:p-5 flex flex-col justify-between h-[150px]"
                                            >
                                                <div class="space-y-2.5">
                                                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-slate-200/80 dark:bg-slate-800 animate-pulse"></div>
                                                    <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                    <div class="h-3 w-36 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                </div>
                                                <div class="h-4 w-16 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse"></div>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <div 
                                                v-for="unit in medikUnits" 
                                                :key="unit.id"
                                                @click="handleUnitClick(unit)"
                                                :class="[
                                                    'group/unit relative overflow-hidden p-3.5 sm:p-5 rounded-2xl border transition-all duration-300 h-full flex flex-col justify-between',
                                                    unit.disabled
                                                        ? 'bg-slate-50/50 dark:bg-slate-900/40 border-slate-200/60 dark:border-slate-800/60 opacity-75 cursor-not-allowed'
                                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-white hover:text-emerald-600 dark:hover:text-white cursor-pointer'
                                                ]"
                                            >
                                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover/unit:opacity-100 transition-opacity duration-300" v-if="!unit.disabled" />
                                                
                                                <div class="relative z-10 flex flex-col h-full justify-between">
                                                    <div>
                                                        <!-- Icon / Status -->
                                                        <div :class="[
                                                            'inline-flex items-center justify-center h-8 w-8 sm:h-10 sm:w-10 rounded-xl mb-2.5 sm:mb-3 transition-colors duration-300',
                                                            unit.disabled
                                                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400'
                                                                : 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white'
                                                        ]">
                                                            <Lock v-if="unit.disabled" class="h-3.5 w-3.5 sm:h-4.5 sm:w-4.5" />
                                                            <component :is="getUnitIcon(unit.name, unit.slug, unit.type)" v-else class="h-3.5 w-3.5 sm:h-4.5 sm:w-4.5" />
                                                        </div>

                                                        <!-- Unit Name -->
                                                        <h3 :class="[
                                                            'text-xs sm:text-sm font-bold tracking-wide uppercase leading-tight',
                                                            unit.disabled ? 'text-slate-550 dark:text-slate-400' : 'text-slate-900 dark:text-white group-hover/unit:text-emerald-600 dark:group-hover/unit:text-white transition-colors duration-200'
                                                        ]">
                                                            {{ unit.name }}
                                                        </h3>
                                                        <p :class="[
                                                            'mt-1 sm:mt-1.5 text-[10px] sm:text-[11px] leading-relaxed',
                                                            unit.disabled ? 'text-slate-405 dark:text-slate-505' : 'text-slate-500 dark:text-slate-400'
                                                        ]">
                                                            {{ unit.description }}
                                                        </p>
                                                    </div>

                                                    <!-- Unit Status badge -->
                                                    <div class="mt-3 sm:mt-4 flex items-center justify-between relative z-10">
                                                        <span :class="[
                                                            'inline-flex items-center gap-1 sm:gap-1.5 px-1.5 sm:px-2.5 py-0.5 rounded-full text-[8px] sm:text-[9px] font-bold tracking-wide uppercase',
                                                            unit.disabled
                                                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400'
                                                                : 'bg-emerald-100 dark:bg-white/10 text-emerald-800 dark:text-white'
                                                        ]">
                                                            <span v-if="!unit.disabled" class="h-1 sm:h-1.5 w-1 sm:w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                                            {{ unit.disabled ? __('pages.services.status_development') : __('pages.services.status_active') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </component>

                        <!-- Card 2: Penunjang Non-Medik -->
                        <component
                            v-if="activeSection === null || activeSection === 'non-medik'"
                            :is="activeSection === null ? Link : 'div'"
                            :href="activeSection === null ? route('services.non-medik') : undefined"
                            prefetch
                            class="group relative overflow-hidden bg-white dark:bg-slate-900 border border-white dark:border-slate-800/80 rounded-2xl flex flex-col justify-between"
                            :class="[
                                activeSection === null ? 'cursor-pointer hover:border-slate-200 dark:hover:border-slate-700 shadow-sm w-full md:w-1/2 p-6' : 'cursor-default border-white dark:border-slate-800/80 shadow-sm w-full p-6'
                            ]"
                        >
                            <div class="w-full flex flex-col h-full">
                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" v-if="activeSection === null" />
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex-shrink-0">
                                            <ShieldCheck class="h-6 w-6" />
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-white/10 text-emerald-800 dark:text-white flex-shrink-0">
                                            {{ __('pages.services.operational_badge') }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-lg font-bold text-slate-955 dark:text-white" :class="{ 'group-hover:text-emerald-600 dark:group-hover:text-white transition-colors duration-200': activeSection === null }">
                                        {{ __('Penunjang Non-Medik') }}
                                    </h3>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-if="activeSection === null">
                                        {{ __('Mencakup pelaporan operasional unit penunjang non-medis yang terdiri dari unit Gizi, Laundry, Kesling, dan IPSRS.') }}
                                    </p>
                                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-else>
                                        {{ __('pages.services.non_medik_desc_detail') }}
                                    </p>
                                </div>

                                <!-- Footer Action -->
                                <div class="mt-6 flex items-center justify-between" v-if="activeSection === null">
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-white">
                                        {{ __('pages.services.btn_enter_non_medik') }}
                                    </span>
                                    <div class="h-8 w-8 rounded-full bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white flex-shrink-0 flex items-center justify-center transition-all duration-300 group-hover:bg-emerald-600 group-hover:text-white dark:group-hover:bg-white dark:group-hover:text-slate-900">
                                        <ArrowRight class="h-4 w-4" />
                                    </div>
                                </div>

                                <!-- Sub-units -->
                                <div 
                                    v-if="activeSection === 'non-medik'"
                                    class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/80"
                                >
                                    <div class="grid grid-cols-1 min-[450px]:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                                        <!-- Skeleton Loading Cards -->
                                        <template v-if="isUnitsLoading">
                                            <div 
                                                v-for="n in 4" 
                                                :key="'skel-nonmedik-' + n"
                                                class="bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-3.5 sm:p-5 flex flex-col justify-between h-[150px]"
                                            >
                                                <div class="space-y-2.5">
                                                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-xl bg-slate-200/80 dark:bg-slate-800 animate-pulse"></div>
                                                    <div class="h-4 w-24 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                    <div class="h-3 w-36 bg-slate-200/80 dark:bg-slate-800 rounded animate-pulse"></div>
                                                </div>
                                                <div class="h-4 w-16 bg-slate-200/80 dark:bg-slate-800 rounded-full animate-pulse"></div>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <div 
                                                v-for="unit in nonMedikUnits" 
                                                :key="unit.id"
                                                @click="handleUnitClick(unit)"
                                                :class="[
                                                    'group/unit relative overflow-hidden p-3.5 sm:p-5 rounded-2xl border transition-all duration-300 h-full flex flex-col justify-between',
                                                    unit.disabled
                                                        ? 'bg-slate-50/50 dark:bg-slate-900/40 border-slate-200/60 dark:border-slate-800/60 opacity-75 cursor-not-allowed'
                                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-white hover:text-emerald-600 dark:hover:text-white cursor-pointer'
                                                ]"
                                            >
                                                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover/unit:opacity-100 transition-opacity duration-300" v-if="!unit.disabled" />
                                                
                                                <div class="relative z-10 flex flex-col h-full justify-between">
                                                    <div>
                                                        <!-- Icon / Status -->
                                                        <div :class="[
                                                            'inline-flex items-center justify-center h-8 w-8 sm:h-10 sm:w-10 rounded-xl mb-2.5 sm:mb-3 transition-colors duration-300',
                                                            unit.disabled
                                                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400'
                                                                : 'bg-emerald-50 dark:bg-white/10 text-emerald-600 dark:text-white'
                                                        ]">
                                                            <Lock v-if="unit.disabled" class="h-3.5 w-3.5 sm:h-4.5 sm:w-4.5" />
                                                            <component :is="getUnitIcon(unit.name, unit.slug, unit.type)" v-else class="h-3.5 w-3.5 sm:h-4.5 sm:w-4.5" />
                                                        </div>

                                                        <!-- Unit Name -->
                                                        <h3 :class="[
                                                            'text-xs sm:text-sm font-bold tracking-wide uppercase leading-tight',
                                                            unit.disabled ? 'text-slate-550 dark:text-slate-400' : 'text-slate-900 dark:text-white group-hover/unit:text-emerald-600 dark:group-hover/unit:text-white transition-colors duration-200'
                                                        ]">
                                                            {{ unit.name }}
                                                        </h3>
                                                        <p :class="[
                                                            'mt-1 sm:mt-1.5 text-[10px] sm:text-[11px] leading-relaxed',
                                                            unit.disabled ? 'text-slate-405 dark:text-slate-505' : 'text-slate-500 dark:text-slate-400'
                                                        ]">
                                                            {{ unit.description }}
                                                        </p>
                                                    </div>

                                                    <!-- Unit Status badge -->
                                                    <div class="mt-3 sm:mt-4 flex items-center justify-between relative z-10">
                                                        <span :class="[
                                                            'inline-flex items-center gap-1 sm:gap-1.5 px-1.5 sm:px-2.5 py-0.5 rounded-full text-[8px] sm:text-[9px] font-bold tracking-wide uppercase',
                                                            unit.disabled
                                                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400'
                                                                : 'bg-emerald-100 dark:bg-white/10 text-emerald-800 dark:text-white'
                                                        ]">
                                                            <span v-if="!unit.disabled" class="h-1 sm:h-1.5 w-1 sm:w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                                            {{ unit.disabled ? __('pages.services.status_development') : __('pages.services.status_active') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </component>
                    </div>

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