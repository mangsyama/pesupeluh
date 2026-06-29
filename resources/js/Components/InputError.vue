<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-xs font-semibold mt-1.5 text-red-600 dark:text-red-400">
            {{ message }}
        </p>
    </div>
</template>
