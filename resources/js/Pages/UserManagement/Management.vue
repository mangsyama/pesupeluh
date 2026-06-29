<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import UserList from './Partials/UserList.vue';

defineProps({
    users: {
        type: Array,
        required: true
    },
    managementRoles: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <Head :title="__('pages.user_management.management')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4">
            <div class="w-full">
                <UserList 
                    :users="users" 
                    :role-id="2" 
                    :role-name="__('menu.management')" 
                    :description="__('pages.user_management.desc_management')"
                    :show-placement="false" 
                    :roles="managementRoles"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
