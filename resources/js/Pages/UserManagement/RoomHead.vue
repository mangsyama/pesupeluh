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
    <Head :title="__('pages.user_management.room_head')" />

    <AuthenticatedLayout>
        <div class="py-4 px-4 sm:px-4 lg:px-4">
            <div class="w-full">
                <UserList 
                    :users="users" 
                    :role-id="7" 
                    :role-name="__('menu.room_head')" 
                    :description="__('pages.user_management.desc_room_head')"
                    :rooms="rooms"
                    :supporting-units="supportingUnits"
                    :show-placement="true" 
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
