<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    closeOnClickOutside: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

const handleBackdropClick = () => {
    if (props.closeOnClickOutside && props.closeable) {
        close();
    }
};

const handlePopState = (e) => {
    if (e?.__preventModalClose) {
        return;
    }
    if (props.show && props.closeable) {
        close();
    }
};

let pushHistoryFlag = false;

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;

            dialog.value?.showModal();
            window.addEventListener('popstate', handlePopState);
            try {
                window.history.pushState({ modalOpen: true }, '');
                pushHistoryFlag = true;
            } catch (e) {
                // ignore
            }
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('popstate', handlePopState);

            if (pushHistoryFlag && window.history.state && window.history.state.modalOpen) {
                pushHistoryFlag = false;
                try {
                    window.history.back();
                } catch (e) {
                    // ignore
                }
            } else {
                pushHistoryFlag = false;
            }

            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        if (e?.__preventModalClose) {
            return;
        }
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    window.removeEventListener('popstate', handlePopState);

    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent outline-none focus:outline-none ring-0 focus:ring-0 border-0"
        ref="dialog"
        @cancel.prevent="close"
    >
        <div
            class="fixed inset-0 z-50 overflow-y-auto px-0 sm:px-4 py-0 sm:py-6 flex min-h-full items-stretch sm:items-center justify-center text-center"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="handleBackdropClick"
                >
                    <div
                        class="absolute inset-0 bg-black/40 transition-opacity"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="my-0 sm:my-auto transform overflow-hidden rounded-none sm:rounded-2xl bg-white text-left shadow-2xl transition-all w-full min-h-screen sm:min-h-0 dark:bg-slate-900 border-0 flex flex-col justify-between outline-none focus:outline-none ring-0 focus:ring-0"
                    :class="maxWidthClass"
                >
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
