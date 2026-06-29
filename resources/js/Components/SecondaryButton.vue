<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 transition duration-150 ease-in-out hover:bg-slate-100 dark:hover:bg-slate-800/80 focus:outline-none disabled:opacity-50"
    >
        <slot />
    </button>
</template>
