<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import StaffDashboard from './Partials/StaffDashboard.vue';
import TechnicianDashboard from './Partials/TechnicianDashboard.vue';
import ManagementDashboard from './Partials/ManagementDashboard.vue';

const props = defineProps({
    userRole: {
        type: String,
        default: 'GLOBAL'
    },
    dashboardStats: {
        type: Object,
        default: () => null
    }
});

// Role detection
const isTechnician = computed(() => {
    const roleInStats = (props.dashboardStats?.role || '').toUpperCase();
    const userRoleStr = (props.userRole || '').toUpperCase();
    return roleInStats === 'TECHNICIAN' || roleInStats === 'TEKNISI' || userRoleStr === 'TEKNISI' || userRoleStr === 'TECHNICIAN';
});

const isReporter = computed(() => {
    const roleInStats = (props.dashboardStats?.role || '').toUpperCase();
    const userRoleStr = (props.userRole || '').toUpperCase();
    return roleInStats === 'REPORTER' || userRoleStr === 'REPORTER' || userRoleStr === 'STAFF';
});
</script>

<template>
    <Head :title="__('Dashboard')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full space-y-4">

                <!-- 1. TECHNICIAN DASHBOARD -->
                <TechnicianDashboard
                    v-if="isTechnician"
                    :dashboard-stats="dashboardStats"
                    :user-role="userRole"
                />

                <!-- 2. STAFF / REPORTER DASHBOARD -->
                <StaffDashboard
                    v-else-if="isReporter"
                    :dashboard-stats="dashboardStats"
                    :user-role="userRole"
                />

                <!-- 3. INTEGRATED MANAGEMENT DASHBOARD (Executive, Unit Head, Room Head) -->
                <ManagementDashboard
                    v-else
                    :dashboard-stats="dashboardStats"
                    :user-role="userRole"
                />

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes spa-fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-spa-fade-in {
  animation: spa-fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
