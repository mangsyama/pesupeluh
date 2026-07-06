<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-0 bg-white dark:bg-slate-900 overflow-hidden',
    },
    open: {
        type: Boolean,
        default: undefined,
    },
});

const emit = defineEmits(['update:open']);

const internalOpen = ref(false);
const dropdownRoot = ref(null);
const open = computed({
    get: () => props.open ?? internalOpen.value,
    set: (value) => {
        if (props.open !== undefined) {
            emit('update:open', value);
        }
        internalOpen.value = value;
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const closeOnOutsideClick = (e) => {
    if (!open.value || !dropdownRoot.value) return;
    if (e.target instanceof Node && !dropdownRoot.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('click', closeOnOutsideClick);
});
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('click', closeOnOutsideClick);
});

const widthClass = computed(() => {
    return {
        48: 'w-48',
        56: 'w-56',
        64: 'w-64',
        96: 'w-96',
        'mobile-nav': 'w-[calc(100vw-2rem)] sm:w-96',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    } else {
        return 'origin-top';
    }
});
</script>

<template>
    <div ref="dropdownRoot" class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-show="open"
                class="absolute z-50 mt-2.5 rounded-xl shadow-xl"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
            >
                <div
                    class="rounded-xl border border-slate-100 dark:border-slate-800"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
