<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full rounded-xl border border-slate-200 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-900 text-slate-950 dark:text-slate-100 py-2.5 px-4 text-sm focus:border-indigo-500 focus:ring-0 transition duration-150 outline-none"
        v-model="model"
        ref="input"
    />
</template>
