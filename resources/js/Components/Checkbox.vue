<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="h-5 w-5 rounded-md border-2 border-slate-200 dark:border-slate-800 text-indigo-600 focus:ring-0 focus:ring-offset-0 dark:bg-slate-900 transition duration-150 cursor-pointer"
    />
</template>
