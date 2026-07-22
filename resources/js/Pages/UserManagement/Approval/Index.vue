<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Search, Calendar, ArrowRight } from '@lucide/vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');

const filteredUsers = computed(() => {
    const list = props.users || [];
    if (!searchQuery.value.trim()) return list;
    const query = searchQuery.value.toLowerCase();
    return list.filter(u => 
        (u.name && u.name.toLowerCase().includes(query)) || 
        (u.email && u.email.toLowerCase().includes(query)) ||
        (u.nip && u.nip.toLowerCase().includes(query))
    );
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="__('pages.user_management.approval_title')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <!-- Premium Header Panel -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-955 dark:text-white leading-tight">
                            {{ __('pages.user_management.approval_title') }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                            {{ __('pages.user_management.approval_desc') }}
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                :placeholder="__('pages.user_management.search_all_placeholder')" 
                                class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 shadow-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- List / Table -->
                <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-6 py-4">{{ __('pages.user_management.table.name_email') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.nip') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.phone') }}</th>
                                <th class="px-6 py-4">{{ __('pages.user_management.table.reg_time') }}</th>
                                <th class="px-6 py-4 text-center">{{ __('pages.user_management.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                    {{ __('pages.user_management.table.empty_approvals') }}
                                </td>
                            </tr>
                            <tr 
                                v-else
                                v-for="user in filteredUsers" 
                                :key="user.id"
                                class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10 transition-colors duration-150"
                            >
                                <!-- Nama / Email -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">
                                            <img 
                                                v-if="user.profile_photo_path" 
                                                :src="user.profile_photo_path" 
                                                class="h-full w-full object-cover" 
                                            />
                                            <User v-else class="h-4.5 w-4.5 text-slate-500" />
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-955 dark:text-white">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- NIP -->
                                <td class="px-6 py-4 text-slate-650 dark:text-slate-400 text-xs">
                                    {{ user.nip || '-' }}
                                </td>
                                
                                <!-- Nomor HP -->
                                <td class="px-6 py-4 text-slate-650 dark:text-slate-400 text-xs">
                                    {{ user.phone_number || '-' }}
                                </td>

                                <!-- Registered Time -->
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        {{ formatDate(user.created_at) }}
                                    </div>
                                </td>
                                
                                <!-- Actions (Direct link to Approval Detail page) -->
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <Link
                                            :href="route('users.approvals.show', user.id)"
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all duration-150 border bg-emerald-600 hover:bg-emerald-500 text-white border-transparent"
                                            title="Detail Pendaftar"
                                        >
                                            <span>Detail</span>
                                            <ArrowRight class="h-3.5 w-3.5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
