<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Search, Check } from '@lucide/vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object],
        default: null,
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Pilih pilihan...',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari...',
    },
    notFoundText: {
        type: String,
        default: 'Data tidak ditemukan',
    },
    valueKey: {
        type: String,
        default: 'id',
    },
    labelKey: {
        type: String,
        default: 'name',
    },
    subtitleKey: {
        type: String,
        default: 'location_floor',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    searchable: {
        type: Boolean,
        default: true,
    },
    absolute: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);

const selectedOption = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }
    return props.options.find(opt => {
        const val = typeof opt === 'object' ? opt[props.valueKey] : opt;
        return String(val) === String(props.modelValue);
    });
});

const selectedLabel = computed(() => {
    if (!selectedOption.value) return '';
    return typeof selectedOption.value === 'object'
        ? selectedOption.value[props.labelKey]
        : selectedOption.value;
});

const filteredOptions = computed(() => {
    if (!searchQuery.value || !props.searchable) {
        return props.options;
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.options.filter(opt => {
        if (typeof opt !== 'object') {
            return String(opt).toLowerCase().includes(q);
        }
        const label = String(opt[props.labelKey] || '').toLowerCase();
        const subtitle = String(opt[props.subtitleKey] || '').toLowerCase();
        return label.includes(q) || subtitle.includes(q);
    });
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
    }
};

const selectOption = (opt) => {
    const val = typeof opt === 'object' ? opt[props.valueKey] : opt;
    emit('update:modelValue', val);
    emit('change', opt);
    isOpen.value = false;
    searchQuery.value = '';
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside, true);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside, true);
});
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <!-- Toggle Button -->
        <button
            type="button"
            @click="toggleDropdown"
            :disabled="disabled"
            :class="[
                'w-full h-11 px-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-850 dark:text-slate-200 text-sm flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-white transition-all duration-150',
                disabled ? 'opacity-60 cursor-not-allowed bg-slate-100 dark:bg-slate-900/60' : 'cursor-pointer'
            ]"
        >
            <span v-if="selectedLabel" class="text-slate-800 dark:text-slate-100 truncate whitespace-nowrap font-medium">
                {{ selectedLabel }}
            </span>
            <span v-else class="text-slate-400 dark:text-slate-500 font-normal whitespace-nowrap">
                {{ placeholder }}
            </span>
            <ChevronDown :class="['h-4 w-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2', isOpen ? 'rotate-180 text-emerald-500 dark:text-white' : '']" />
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            :class="[
                'mt-1.5 min-w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-2 space-y-2 animate-spa-fade-in shadow-lg',
                absolute ? 'absolute z-50 right-0' : 'relative z-10'
            ]"
        >
            <!-- Search Input -->
            <div v-if="searchable" class="relative">
                <Search class="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full h-9 pl-9 pr-3 text-sm bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:focus:ring-white"
                    @click.stop
                />
            </div>

            <!-- Scrollable List -->
            <div class="max-h-56 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                <div
                    v-if="filteredOptions.length === 0"
                    class="p-3 text-center text-sm text-slate-400 dark:text-slate-500 font-medium whitespace-nowrap"
                >
                    {{ notFoundText }}
                </div>
                <button
                    v-else
                    v-for="(opt, idx) in filteredOptions"
                    :key="typeof opt === 'object' ? opt[valueKey] ?? idx : opt"
                    type="button"
                    @click.stop="selectOption(opt)"
                    :class="[
                        'w-full text-left px-3 py-2.5 rounded-lg text-sm flex items-center justify-between transition-colors duration-150 gap-3',
                        String(typeof opt === 'object' ? opt[valueKey] : opt) === String(modelValue)
                            ? 'bg-emerald-50 dark:bg-white/10 text-emerald-700 dark:text-white font-bold'
                            : 'hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300 font-medium'
                    ]"
                >
                    <div class="min-w-0">
                        <div class="text-sm font-semibold whitespace-nowrap">
                            {{ typeof opt === 'object' ? opt[labelKey] : opt }}
                        </div>
                        <div
                            v-if="typeof opt === 'object' && opt[subtitleKey]"
                            class="text-xs text-slate-400 dark:text-slate-500 font-normal mt-0.5 whitespace-nowrap"
                        >
                            {{ opt[subtitleKey] }}
                        </div>
                    </div>
                    <Check
                        v-if="String(typeof opt === 'object' ? opt[valueKey] : opt) === String(modelValue)"
                        class="h-4 w-4 text-emerald-600 dark:text-white shrink-0 ml-2"
                    />
                </button>
            </div>
        </div>
    </div>
</template>
