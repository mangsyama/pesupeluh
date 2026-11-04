<script setup>
import { Leaf } from '@lucide/vue';
</script>

<template>
    <!-- Welcome Card Banner (Exact PESU PELUH Dashboard Structure) -->
    <div class="p-[1px] rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 dark:bg-none dark:bg-slate-800 shadow-sm">
        <div class="overflow-hidden bg-gradient-to-r from-emerald-600 to-emerald-800 dark:from-slate-900 dark:to-slate-900 rounded-[15px] text-white p-6 sm:p-8 relative flex items-center justify-between gap-4 sm:gap-6">
            <!-- Text Info -->
            <div class="relative z-10 flex-1 min-w-0 pr-0 sm:pr-24">
                <h3 class="text-2xl font-extrabold mb-1 tracking-tight">PAN-SINAR</h3>
                <p class="text-emerald-100 dark:text-slate-300 text-sm font-medium leading-relaxed break-words">
                    Penunjang Non Medik &mdash; Sistematis, Integratif, Nyata, Akurat, Realtime
                </p>
            </div>

            <!-- Right Leaf Icon (Hidden on mobile responsive) -->
            <div class="hidden sm:flex absolute right-6 sm:right-8 top-1/2 -translate-y-1/2 z-10 items-center justify-center h-16 w-16 opacity-85 hover:opacity-100 transition-opacity pointer-events-none select-none">
                <Leaf class="h-14 w-14 text-white drop-shadow-md" />
            </div>

            <!-- Decorative background patterns (matching PESU PELUH rotated rounded frames) -->
            <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none overflow-hidden select-none">
                <div class="absolute -right-28 -top-28 w-80 h-80 border-2 border-white rounded-[80px] rotate-[15deg]"></div>
                <div class="absolute -right-40 -top-40 w-80 h-80 border-2 border-white rounded-[100px] rotate-[15deg]"></div>
            </div>
        </div>
    </div>
</template>
