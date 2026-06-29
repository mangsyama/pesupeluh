<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { HeartPulse, Wrench, ArrowRight, Lock } from '@lucide/vue';
import { ref, watch, getCurrentInstance, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    initialSection: {
        type: String,
        default: null
    },
    divisions: {
        type: Array,
        required: true
    }
});

const activeSection = ref(props.initialSection);
const isBacking = ref(false);

watch(() => props.initialSection, (newVal) => {
    if (newVal === null && activeSection.value !== null) {
        isBacking.value = true;
        activeSection.value = null;
        setTimeout(() => {
            isBacking.value = false;
        }, 100);
    } else {
        activeSection.value = newVal;
    }
});

const handleBackClicked = () => {
    isBacking.value = true;
    activeSection.value = null;
    setTimeout(() => {
        isBacking.value = false;
    }, 100);
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
        router.visit(route('services.units.show', unit.name.toLowerCase()));
    }
};

const medikDivision = computed(() => {
    return props.divisions.find(d => d.name.toLowerCase().includes('medik') && !d.name.toLowerCase().includes('non-medik'));
});

const nonMedikDivision = computed(() => {
    return props.divisions.find(d => d.name.toLowerCase().includes('non-medik'));
});

const medikUnits = computed(() => {
    return (medikDivision.value?.supporting_units || [])
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
    return (nonMedikDivision.value?.supporting_units || [])
        .map(unit => ({
            ...unit,
            disabled: unit.status !== 'ACTIVE'
        }))
        .sort((a, b) => {
            if (a.disabled === b.disabled) return 0;
            return a.disabled ? 1 : -1;
        });
});
</script>

<template>
    <Head :title="activeSection === 'medik' ? medikDivision?.name : (activeSection === 'non-medik' ? nonMedikDivision?.name : __('pages.services.title'))" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4">
            <div class="w-full">
                
                <!-- Main Options Container / Animated Flex Width Layout -->
                <div 
                    class="flex flex-col md:flex-row" 
                    :class="[
                        isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                        activeSection === null ? 'gap-4' : 'gap-0'
                    ]"
                >
                    <!-- Card 1: Penunjang Medik -->
                    <component
                        :is="activeSection === null ? Link : 'div'"
                        :href="activeSection === null ? route('services.medik') : undefined"
                        prefetch
                        class="group relative overflow-hidden bg-white dark:bg-slate-900 border border-white dark:border-slate-800/80 rounded-2xl flex flex-col justify-between"
                        :class="[
                            isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                            activeSection === null ? 'cursor-pointer hover:border-slate-200 dark:hover:border-slate-700 shadow-sm' : 'cursor-default border-white dark:border-slate-800/80 shadow-sm',
                            activeSection === 'medik' ? 'w-full p-6' : (activeSection === 'non-medik' ? 'w-0 h-0 md:w-0 md:h-0 opacity-0 pointer-events-none overflow-hidden p-0 border-0 border-transparent shadow-none' : 'w-full md:w-1/2 p-6')
                        ]"
                    >
                        <div 
                            class="w-full flex flex-col h-full"
                            :class="[
                                isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                                activeSection === null || activeSection === 'medik' ? 'min-w-[280px] md:min-w-[320px]' : 'min-w-0'
                            ]"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" v-if="activeSection === null" />
                            <div>
                                <!-- Header Row: Icon on left, Badge on right -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                                        <HeartPulse class="h-6 w-6" />
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 flex-shrink-0">
                                        {{ __('pages.services.clinical_badge') }}
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-bold text-slate-950 dark:text-white" :class="{ 'group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors duration-200': activeSection === null }">
                                    {{ medikDivision?.name }}
                                </h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-if="activeSection === null">
                                    {{ medikDivision?.description }}
                                </p>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-else>
                                    {{ __('pages.services.medik_desc_detail') }}
                                </p>
                            </div>

                            <!-- Footer Action -->
                            <div class="mt-6 flex items-center justify-between" v-if="activeSection === null">
                                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ __('pages.services.btn_enter_medik') }}
                                </span>
                                <div class="h-8 w-8 rounded-full bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 flex-shrink-0 flex items-center justify-center transition-all duration-300 group-hover:bg-indigo-600 group-hover:text-white dark:group-hover:bg-indigo-550">
                                    <ArrowRight class="h-4 w-4" />
                                </div>
                            </div>

                            <!-- Sub-units (Nested inside card with smooth CSS max-height transition) -->
                            <div 
                                class="overflow-hidden"
                                :class="[
                                    isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                                    activeSection === 'medik' ? 'max-h-[1500px] opacity-100 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/80' : 'max-h-0 opacity-0 mt-0 pt-0 border-t-0 border-transparent'
                                ]"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div 
                                        v-for="unit in medikUnits" 
                                        :key="unit.name"
                                        @click.stop="handleUnitClick(unit)"
                                        :class="[
                                            'group/unit relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between text-left transition-all duration-300 select-none border',
                                            unit.disabled 
                                                ? 'bg-slate-50/50 dark:bg-slate-950/20 border-slate-200/50 dark:border-slate-800/60 text-slate-400 dark:text-slate-600 cursor-not-allowed opacity-75'
                                                : 'bg-white dark:bg-slate-900 border-indigo-100 dark:border-indigo-950/40 hover:border-indigo-500 hover:text-indigo-600 cursor-pointer'
                                        ]"
                                    >
                                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-transparent opacity-0 group-hover/unit:opacity-100 transition-opacity duration-300" v-if="!unit.disabled" />
                                        
                                        <div class="relative z-10">
                                            <!-- Icon / Status -->
                                            <div :class="[
                                                'inline-flex items-center justify-center h-10 w-10 rounded-xl mb-3 transition-colors duration-300',
                                                unit.disabled
                                                    ? 'bg-slate-100 dark:bg-slate-900/60 text-slate-400 dark:text-slate-600'
                                                    : 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400'
                                            ]">
                                                <Lock v-if="unit.disabled" class="h-4.5 w-4.5" />
                                                <HeartPulse v-else class="h-4.5 w-4.5" />
                                            </div>

                                            <!-- Unit Name -->
                                            <h3 :class="[
                                                'text-sm font-bold tracking-wide uppercase',
                                                unit.disabled ? 'text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-white group-hover/unit:text-indigo-600 dark:group-hover/unit:text-indigo-400 transition-colors duration-200'
                                            ]">
                                                {{ unit.name }}
                                            </h3>
                                            <p :class="[
                                                'mt-1.5 text-[11px] leading-relaxed',
                                                unit.disabled ? 'text-slate-400 dark:text-slate-505' : 'text-slate-505 dark:text-slate-400'
                                            ]">
                                                {{ unit.description }}
                                            </p>
                                        </div>

                                        <!-- Status Badge -->
                                        <div class="mt-4 flex items-center justify-between relative z-10">
                                            <span :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wide uppercase',
                                                unit.disabled
                                                    ? 'bg-slate-100 dark:bg-slate-950 text-slate-400 dark:text-slate-500'
                                                    : 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300'
                                            ]">
                                                <span v-if="!unit.disabled" class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse" />
                                                {{ unit.disabled ? __('pages.services.status_development') : __('pages.services.status_active') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </component>

                    <!-- Card 2: Penunjang Non-Medik -->
                    <component
                        :is="activeSection === null ? Link : 'div'"
                        :href="activeSection === null ? route('services.non-medik') : undefined"
                        prefetch
                        class="group relative overflow-hidden bg-white dark:bg-slate-900 border border-white dark:border-slate-800/80 rounded-2xl flex flex-col justify-between"
                        :class="[
                            isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                            activeSection === null ? 'cursor-pointer hover:border-slate-200 dark:hover:border-slate-700 shadow-sm' : 'cursor-default border-white dark:border-slate-800/80 shadow-sm',
                            activeSection === 'non-medik' ? 'w-full p-6' : (activeSection === 'medik' ? 'w-0 h-0 md:w-0 md:h-0 opacity-0 pointer-events-none overflow-hidden p-0 border-0 border-transparent shadow-none' : 'w-full md:w-1/2 p-6')
                        ]"
                    >
                        <div 
                            class="w-full flex flex-col h-full"
                            :class="[
                                isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                                activeSection === null || activeSection === 'non-medik' ? 'min-w-[280px] md:min-w-[320px]' : 'min-w-0'
                            ]"
                        >
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" v-if="activeSection === null" />
                            <div>
                                <!-- Header Row: Icon on left, Badge on right -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                                        <Wrench class="h-6 w-6" />
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 flex-shrink-0">
                                        {{ __('pages.services.operational_badge') }}
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-bold text-slate-950 dark:text-white" :class="{ 'group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-200': activeSection === null }">
                                    {{ nonMedikDivision?.name }}
                                </h3>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-if="activeSection === null">
                                    {{ nonMedikDivision?.description }}
                                </p>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed" v-else>
                                    {{ __('pages.services.non_medik_desc_detail') }}
                                </p>
                            </div>

                            <!-- Footer Action -->
                            <div class="mt-6 flex items-center justify-between" v-if="activeSection === null">
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ __('pages.services.btn_enter_non_medik') }}
                                </span>
                                <div class="h-8 w-8 rounded-full bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex-shrink-0 flex items-center justify-center transition-all duration-300 group-hover:bg-emerald-600 group-hover:text-white dark:group-hover:bg-emerald-500">
                                    <ArrowRight class="h-4 w-4" />
                                </div>
                            </div>

                            <!-- Sub-units (Nested inside card with smooth CSS max-height transition) -->
                            <div 
                                class="overflow-hidden"
                                :class="[
                                    isBacking ? 'transition-none duration-0' : 'transition-all duration-500 ease-in-out',
                                    activeSection === 'non-medik' ? 'max-h-[1500px] opacity-100 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800/80' : 'max-h-0 opacity-0 mt-0 pt-0 border-t-0 border-transparent'
                                ]"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div 
                                        v-for="unit in nonMedikUnits" 
                                        :key="unit.name"
                                        @click.stop="handleUnitClick(unit)"
                                        :class="[
                                            'group/unit relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between text-left transition-all duration-300 select-none border',
                                            unit.disabled 
                                                ? 'bg-slate-50/50 dark:bg-slate-950/20 border-slate-200/50 dark:border-slate-800/60 text-slate-400 dark:text-slate-600 cursor-not-allowed opacity-75'
                                                : 'bg-white dark:bg-slate-900 border-emerald-100 dark:border-emerald-950/40 hover:border-emerald-500 hover:text-emerald-600 cursor-pointer'
                                        ]"
                                    >
                                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover/unit:opacity-100 transition-opacity duration-300" v-if="!unit.disabled" />
                                        
                                        <div class="relative z-10">
                                            <!-- Icon / Status -->
                                            <div :class="[
                                                'inline-flex items-center justify-center h-10 w-10 rounded-xl mb-3 transition-colors duration-300',
                                                unit.disabled
                                                    ? 'bg-slate-100 dark:bg-slate-900/60 text-slate-400 dark:text-slate-600'
                                                    : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                                            ]">
                                                <Lock v-if="unit.disabled" class="h-4.5 w-4.5" />
                                                <Wrench v-else class="h-4.5 w-4.5" />
                                            </div>

                                            <!-- Unit Name -->
                                            <h3 :class="[
                                                'text-sm font-bold tracking-wide uppercase',
                                                unit.disabled ? 'text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-white group-hover/unit:text-emerald-600 dark:group-hover/unit:text-emerald-400 transition-colors duration-200'
                                            ]">
                                                {{ unit.name }}
                                            </h3>
                                            <p :class="[
                                                'mt-1.5 text-[11px] leading-relaxed',
                                                unit.disabled ? 'text-slate-400 dark:text-slate-500' : 'text-slate-505 dark:text-slate-400'
                                            ]">
                                                {{ unit.description }}
                                            </p>
                                        </div>

                                        <!-- Status Badge -->
                                        <div class="mt-4 flex items-center justify-between relative z-10">
                                            <span :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wide uppercase',
                                                unit.disabled
                                                    ? 'bg-slate-100 dark:bg-slate-950 text-slate-400 dark:text-slate-500'
                                                    : 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300'
                                            ]">
                                                <span v-if="!unit.disabled" class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                                {{ unit.disabled ? __('pages.services.status_development') : __('pages.services.status_active') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </component>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
