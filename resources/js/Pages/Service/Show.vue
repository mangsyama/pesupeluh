<script setup>
import { ref, computed, markRaw, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    HeartPulse, 
    Wrench, 
    Activity, 
    ClipboardList, 
    Gauge, 
    FilePlus,
    Utensils,
    Shirt,
    Leaf,
    Pill,
    Scan,
    Microscope,
    Sparkles,
    Stethoscope,
    ShieldCheck,
    AlertCircle
} from '@lucide/vue';
import ReportingForm from './Partials/Ipsrs/ReportingForm.vue';
import CalibrationForm from './Partials/Ipsrs/CalibrationForm.vue';
import ProposalForm from './Partials/Ipsrs/ProposalForm.vue';

const props = defineProps({
    unit: {
        type: Object,
        default: () => ({})
    },
    rooms: {
        type: Array,
        default: () => []
    }
});

const isMedik = computed(() => {
    return props.unit?.division?.name?.toLowerCase().includes('medik') && 
           !props.unit?.division?.name?.toLowerCase().includes('non-medik');
});

// Current selected feature ID (defaults to the first feature if available)
const selectedFeatureId = ref(null);

watch(() => props.unit, (newUnit) => {
    if (newUnit?.unit_features?.[0]?.id && !selectedFeatureId.value) {
        selectedFeatureId.value = newUnit.unit_features[0].id;
    }
}, { immediate: true });

// Selected feature object
const activeFeature = computed(() => {
    return props.unit?.unit_features?.find(f => f.id === selectedFeatureId.value) || props.unit?.unit_features?.[0] || null;
});

// Component registry mapping feature names to modular components
const featureComponents = {
    pelaporan: markRaw(ReportingForm),
    kalibrasi: markRaw(CalibrationForm),
    usulan: markRaw(ProposalForm)
};

const activeComponent = computed(() => {
    const featureKey = activeFeature.value?.name?.toLowerCase();
    return featureComponents[featureKey] || null;
});

// Icon registry mapping feature names to specific graphic icons
const featureIcons = {
    pelaporan: ClipboardList,
    kalibrasi: Gauge,
    usulan: FilePlus
};

const getFeatureIcon = (name) => {
    const key = name?.toLowerCase();
    return featureIcons[key] || Activity;
};

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

const getUnitIcon = (name) => {
    const key = name?.toLowerCase();
    if (unitIcons[key]) return unitIcons[key];
    return isMedik.value ? Stethoscope : ShieldCheck;
};

const selectFeature = (id) => {
    selectedFeatureId.value = id;
};
</script>

<template>
    <Head :title="(unit?.name ? (unit.name + ' - ') : '') + __('Layanan')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">
                
                <div class="w-full space-y-4">
                    <!-- Premium Header -->
                    <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div 
                                :class="[
                                    'h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0',
                                    'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400'
                                ]"
                            >
                                <component :is="getUnitIcon(unit.name)" class="h-6 w-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                                        {{ unit.name }}
                                    </h2>
                                    <span 
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        ]"
                                    >
                                        {{ unit.division?.name }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                                    {{ unit.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Main Layout -->
                    <div v-if="unit.unit_features && unit.unit_features.length > 0" class="w-full space-y-4">
                        
                        <!-- Feature Tabs -->
                        <div class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">{{ __('pages.services.pilih_layanan') }}</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ __('pages.services.pilih_layanan_desc') }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div 
                                    v-for="feature in unit.unit_features" 
                                    :key="feature.id"
                                    @click="selectFeature(feature.id)"
                                    :class="[
                                        'p-4 rounded-xl border text-left transition-all duration-200 select-none cursor-pointer flex items-center gap-3',
                                        selectedFeatureId === feature.id
                                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40'
                                            : 'border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900/40 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20 hover:border-emerald-300 dark:hover:border-emerald-800/60'
                                    ]"
                                >
                                    <div 
                                        :class="[
                                            'h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors duration-150',
                                            selectedFeatureId === feature.id
                                                ? 'bg-emerald-100 dark:bg-emerald-900/60'
                                                : 'bg-slate-100 dark:bg-slate-800'
                                        ]"
                                    >
                                        <component 
                                            :is="getFeatureIcon(feature.name)"
                                            :class="[
                                                'h-5 w-5 transition-colors duration-150',
                                                selectedFeatureId === feature.id
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-slate-500 dark:text-slate-400'
                                            ]"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div 
                                            :class="[
                                                'text-sm font-bold truncate transition-colors duration-150',
                                                selectedFeatureId === feature.id
                                                    ? 'text-emerald-700 dark:text-emerald-300'
                                                    : 'text-slate-800 dark:text-slate-200'
                                            ]"
                                        >
                                            {{ feature.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Component Rendering -->
                        <component 
                            v-if="activeComponent"
                            :is="activeComponent"
                            :unit="unit"
                            :active-feature="activeFeature"
                            :rooms="rooms"
                            :is-medik="false"
                        />

                    </div>

                    <!-- Empty State (No features available) -->
                    <div 
                        v-else 
                        class="bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-4"
                    >
                        <div class="h-16 w-16 rounded-full bg-slate-50 dark:bg-slate-950 flex items-center justify-center text-slate-455 dark:text-slate-500">
                            <AlertCircle class="h-8 w-8" />
                        </div>
                        <div class="space-y-1 max-w-sm">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('pages.services.empty_features_title') }}</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-505 leading-relaxed">
                                {{ __('pages.services.empty_features_desc') }}
                            </p>
                        </div>
                    </div>
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