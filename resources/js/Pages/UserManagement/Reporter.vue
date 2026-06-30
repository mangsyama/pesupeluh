<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import UserList from './Partials/UserList.vue';

defineProps({
    users: {
        type: Array,
        required: true
    },
    rooms: {
        type: Array,
        required: true
    },
    supportingUnits: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <Head :title="__('pages.user_management.reporter')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4 animate-spa-fade-in">
            <div class="w-full">
                <UserList 
                    :users="users" 
                    :role-id="8" 
                    :role-name="__('menu.reporter')" 
                    :description="__('pages.user_management.desc_reporter')"
                    :rooms="rooms"
                    :supporting-units="supportingUnits"
                    :show-placement="true" 
                />
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