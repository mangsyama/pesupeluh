<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Clock, ChevronDown, Check } from '@lucide/vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '07:30'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: '00:00'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const containerRef = ref(null);

const timePresets = [
    '06:00', '07:00', '07:30', '08:00',
    '12:00', '14:00', '15:00', '16:00',
    '17:00', '18:00', '20:00', '21:00'
];

// Hours (00-23)
const hourOptions = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
// Minutes (00-59 in steps of 5)
const minuteOptions = ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'];

const currentHour = computed(() => {
    if (!props.modelValue || !props.modelValue.includes(':')) return '07';
    return props.modelValue.split(':')[0];
});

const currentMinute = computed(() => {
    if (!props.modelValue || !props.modelValue.includes(':')) return '30';
    return props.modelValue.split(':')[1].substring(0, 2);
});

const selectPreset = (timeStr) => {
    emit('update:modelValue', timeStr);
    isOpen.value = false;
};

const setHour = (h) => {
    emit('update:modelValue', `${h}:${currentMinute.value}`);
};

const setMinute = (m) => {
    emit('update:modelValue', `${currentHour.value}:${m}`);
};

const toggleDropdown = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative inline-block text-center w-36 mx-auto select-none">
        <!-- Input Display Button (Centered) -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'w-full py-2.5 px-3 rounded-xl border text-xs font-extrabold flex items-center justify-center gap-2 transition-all duration-150 text-center',
                disabled
                    ? 'opacity-40 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-800 cursor-not-allowed text-slate-400'
                    : isOpen
                        ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-700 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-slate-700 text-slate-800 dark:text-slate-100'
            ]"
        >
            <Clock class="h-3.5 w-3.5 text-emerald-500 shrink-0" />
            <span class="text-xs font-extrabold tracking-wide text-center flex-1">
                {{ modelValue || placeholder }}
            </span>
            <ChevronDown class="h-3.5 w-3.5 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
        </button>

        <!-- Dropdown Popover Picker -->
        <div
            v-if="isOpen"
            class="absolute z-50 left-1/2 -translate-x-1/2 mt-1.5 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 text-left space-y-3 animate-spa-fade-in shadow-md"
        >
            <!-- Direct Typing Row -->
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Ketik Jam:</span>
                <input
                    type="text"
                    :value="modelValue"
                    @input="emit('update:modelValue', $event.target.value)"
                    placeholder="HH:mm"
                    class="w-24 text-center py-1 px-2 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-950 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                />
            </div>

            <!-- Fast Presets Grid (1-Click Selection) -->
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Preset Jam Populer:</div>
                <div class="grid grid-cols-4 gap-1.5">
                    <button
                        v-for="preset in timePresets"
                        :key="preset"
                        type="button"
                        @click="selectPreset(preset)"
                        :class="[
                            'py-1.5 text-[11px] font-bold rounded-lg transition-colors duration-150 text-center',
                            modelValue === preset
                                ? 'bg-emerald-600 text-white font-extrabold shadow-sm'
                                : 'bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-emerald-100 dark:hover:bg-emerald-950/60 hover:text-emerald-700'
                        ]"
                    >
                        {{ preset }}
                    </button>
                </div>
            </div>

            <!-- Hours & Minutes Selectors -->
            <div class="border-t border-slate-100 dark:border-slate-800/80 pt-2.5 space-y-2">
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Pilih Jam & Menit:</div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <!-- Jam (00-23) -->
                    <div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Jam (HH)</div>
                        <select
                            :value="currentHour"
                            @change="setHour($event.target.value)"
                            class="w-full py-1.5 px-2 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                    </div>

                    <!-- Menit (00-55) -->
                    <div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mb-1">Menit (mm)</div>
                        <select
                            :value="currentMinute"
                            @change="setMinute($event.target.value)"
                            class="w-full py-1.5 px-2 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes spa-fade-in {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
