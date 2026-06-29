<script setup>
import { ref, getCurrentInstance, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Shield, ShieldAlert, Edit2, Trash2, Search, Plus, X, UserX, UserCheck } from '@lucide/vue';

const props = defineProps({
    users: {
        type: Array,
        required: true
    },
    roleId: {
        type: Number,
        required: true
    },
    roleName: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: ''
    },
    rooms: {
        type: Array,
        default: () => []
    },
    supportingUnits: {
        type: Array,
        default: () => []
    },
    showPlacement: {
        type: Boolean,
        default: false
    },
    roles: {
        type: Array,
        default: () => []
    }
});

const { proxy } = getCurrentInstance();

const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);

const activePhotoUrl = ref(null);
const showPhotoModal = ref(false);

const viewPhoto = (user) => {
    activePhotoUrl.value = user.profile_photo_path;
    showPhotoModal.value = true;
};

const filteredUsers = computed(() => {
    const list = props.users;
    if (!searchQuery.value.trim()) return list;
    const query = searchQuery.value.toLowerCase();
    return list.filter(u => 
        u.name.toLowerCase().includes(query) || 
        u.email.toLowerCase().includes(query)
    );
});

const form = useForm({
    id: null,
    name: '',
    nip: '',
    email: '',
    password: '',
    role_id: props.roles && props.roles.length > 0 ? props.roles[0].id : props.roleId,
    room_id: '',
    supporting_unit_id: '',
    phone_number: '',
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.nip = '';
    form.role_id = props.roles && props.roles.length > 0 ? props.roles[0].id : props.roleId;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.nip = user.nip || '';
    form.email = user.email;
    form.password = '';
    form.role_id = user.role_id;
    form.room_id = user.room_id || '';
    form.supporting_unit_id = user.supporting_unit_id || '';
    form.phone_number = user.phone_number || '';
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('users.update', form.id), {
            onSuccess: () => {
                showModal.value = false;
                proxy.$toast(proxy.__('pages.user_management.alerts.update_success').replace('{role}', props.roleName), 'success');
            },
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                proxy.$toast(proxy.__('pages.user_management.alerts.add_success').replace('{role}', props.roleName), 'success');
            },
        });
    }
};

const toggleStatus = (user) => {
    router.patch(route('users.toggle-active', user.id), {}, {
        onSuccess: () => {
            proxy.$toast(proxy.__('pages.user_management.alerts.status_update_success').replace('{role}', props.roleName), 'success');
        }
    });
};

const deleteUser = (user) => {
    // Prevent self-deletion if logged-in user is the target
    if (user.email === proxy.$page.props.auth.user.email) {
        proxy.$swal({
            title: proxy.__('pages.user_management.alerts.action_denied'),
            text: proxy.__('pages.user_management.alerts.cannot_delete_self'),
            icon: 'error',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    proxy.$swal({
        title: proxy.__('pages.user_management.alerts.are_you_sure'),
        text: proxy.__('pages.user_management.alerts.revoke_warning').replace('{name}', user.name).replace('{role}', props.roleName),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: proxy.__('pages.user_management.alerts.yes_revoke'),
        cancelButtonText: proxy.__('pages.user_management.alerts.cancel')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', user.id), {
                onSuccess: () => {
                    proxy.$toast(proxy.__('pages.user_management.alerts.revoke_success').replace('{role}', props.roleName), 'success');
                }
            });
        }
    });
};
</script>

<template>
    <div>
        <!-- Premium Header Panel (Title, Description, Search, & Add Button) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 p-6 rounded-2xl shadow-sm mb-4">
            <div class="space-y-1">
                <h2 class="text-xl font-extrabold text-slate-950 dark:text-white leading-tight">
                    {{ __('pages.user_management.role') }} {{ roleName }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                    {{ description }}
                </p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-shrink-0">
                <!-- Search Box -->
                <div class="relative w-full sm:w-64">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        :placeholder="__('pages.user_management.search_placeholder_tpl').replace('{role}', roleName)" 
                        class="w-full h-10 pl-9 pr-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-150 shadow-none"
                    />
                </div>
                <!-- Add Button -->
                <button 
                    @click="openAddModal"
                    class="w-full sm:w-auto h-10 inline-flex items-center justify-center gap-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl transition duration-150 whitespace-nowrap"
                >
                    <Plus class="h-4 w-4" />
                    {{ __('pages.user_management.add_role_btn').replace('{role}', roleName) }}
                </button>
            </div>
        </div>

<!-- Table -->
        <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-transparent dark:border-slate-800 rounded-2xl shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-950/20 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">
                        <th class="px-6 py-4">{{ __('pages.user_management.table.name_email') }}</th>
                        <th class="px-6 py-4">{{ __('pages.user_management.table.nip') }}</th>
                        <th class="px-6 py-4">{{ __('pages.user_management.table.phone') }}</th>
                        <th v-if="showPlacement" class="px-6 py-4">{{ __('pages.user_management.table.placement') }}</th>
                        <th class="px-6 py-4">{{ __('pages.user_management.table.role') }}</th>
                        <th class="px-6 py-4">{{ __('pages.user_management.table.status') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('pages.user_management.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm text-slate-800 dark:text-slate-300">
                    <tr v-if="filteredUsers.length === 0">
                        <td :colspan="showPlacement ? 7 : 6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                            {{ __('pages.user_management.table.empty_role').replace('{role}', roleName) }}
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
                                <div class="h-9 w-9 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300">
                                    <img v-if="user.profile_photo_path" :src="user.profile_photo_path" class="h-full w-full object-cover cursor-zoom-in" @click="viewPhoto(user)" :title="__('pages.user_management.table.zoom_photo')" />
                                    <Shield v-else-if="roleId === 1" class="h-4 w-4 text-indigo-500" />
                                    <ShieldAlert v-else class="h-4 w-4 text-slate-500" />
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-950 dark:text-white">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- NIP -->
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">
                            {{ user.nip || '-' }}
                        </td>
                        
                        <!-- Phone -->
                        <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-xs">
                            {{ user.phone_number || '-' }}
                        </td>

                        <!-- Placement -->
                        <td v-if="showPlacement" class="px-6 py-4 text-slate-600 dark:text-slate-400">
                            <div v-if="user.supporting_unit" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ user.supporting_unit.name }} ({{ __('Unit') }})
                            </div>
                            <div v-if="user.room" class="text-[11px] text-slate-450 dark:text-slate-500">
                                {{ user.room.name }} ({{ __('Ruangan') }})
                            </div>
                            <span v-if="!user.supporting_unit && !user.room" class="text-slate-400 dark:text-slate-600">-</span>
                        </td>

                        <!-- Role -->
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">
                                {{ user.role ? user.role.name : roleName }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4">
                            <span 
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                    user.is_active 
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' 
                                        : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400'
                                    ]"
                                >
                                {{ user.is_active ? __('global.verified') : __('global.pending') }}
                            </span>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button 
                                    @click="openEditModal(user)"
                                    class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"
                                    :title="__('Edit')"
                                >
                                    <Edit2 class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="toggleStatus(user)"
                                    :class="[
                                        'p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150',
                                        user.is_active 
                                            ? 'text-slate-400 hover:text-amber-600 dark:hover:text-amber-400' 
                                            : 'text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400'
                                    ]"
                                    :title="user.is_active ? __('pages.user_management.suspend_account') : __('pages.user_management.activate_account')"
                                >
                                    <UserX v-if="user.is_active" class="h-4 w-4" />
                                    <UserCheck v-else class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="deleteUser(user)"
                                    class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"
                                    :title="__('pages.user_management.alerts.yes_revoke')"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Modal (Glassmorphism backdrop & premium cards) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
                <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl overflow-hidden transition-all duration-300">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/55 dark:bg-slate-900/50 rounded-t-2xl">
                        <h3 class="text-base font-bold text-slate-950 dark:text-white">
                            {{ isEditing ? __('pages.user_management.edit_role_title').replace('{role}', roleName) : __('pages.user_management.add_role_title').replace('{role}', roleName) }}
                        </h3>
                        <button type="button" @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.full_name') }}</label>
                            <input 
                                v-model="form.name"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                :placeholder="__('pages.user_management.form.full_name_placeholder')"
                            />
                            <div v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</div>
                        </div>

                        <!-- NIP -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.nip') }}</label>
                            <input 
                                v-model="form.nip"
                                type="text" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                :placeholder="__('pages.user_management.form.nip_placeholder')"
                            />
                            <div v-if="form.errors.nip" class="text-xs text-red-500 mt-1">{{ form.errors.nip }}</div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.email') }}</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                :placeholder="__('pages.user_management.form.email_placeholder')"
                            />
                            <div v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</div>
                        </div>

                        <!-- Specific Role Selection -->
                        <div v-if="roles && roles.length > 0">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.specific_role') }}</label>
                            <select 
                                v-model="form.role_id"
                                required
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            >
                                <option v-for="r in roles" :key="r.id" :value="r.id">
                                    {{ r.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.role_id" class="text-xs text-red-500 mt-1">{{ form.errors.role_id }}</div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                                {{ isEditing ? __('pages.user_management.form.password_optional') : __('pages.user_management.form.password') }}
                            </label>
                            <input 
                                v-model="form.password"
                                type="password" 
                                :required="!isEditing"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                :placeholder="isEditing ? __('pages.user_management.form.password_placeholder_edit') : __('pages.user_management.form.password_placeholder_add')"
                            />
                            <div v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</div>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.phone') }}</label>
                            <input 
                                v-model="form.phone_number"
                                type="text"
                                class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                :placeholder="__('pages.user_management.form.phone_placeholder')"
                            />
                            <div v-if="form.errors.phone_number" class="text-xs text-red-500 mt-1">{{ form.errors.phone_number }}</div>
                        </div>

                        <!-- Room & Supporting Unit (Only if showPlacement is true) -->
                        <div v-if="showPlacement" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.supporting_unit') }}</label>
                                <select 
                                    v-model="form.supporting_unit_id"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="">{{ __('pages.user_management.form.no_unit') }}</option>
                                    <option v-for="unit in supportingUnits" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.supporting_unit_id" class="text-xs text-red-500 mt-1">{{ form.errors.supporting_unit_id }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">{{ __('pages.user_management.form.room') }}</label>
                                <select 
                                    v-model="form.room_id"
                                    class="w-full px-4 py-2 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="">{{ __('pages.user_management.form.no_room') }}</option>
                                    <option v-for="room in rooms" :key="room.id" :value="room.id">
                                        {{ room.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.room_id" class="text-xs text-red-500 mt-1">{{ form.errors.room_id }}</div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 mt-6">
                            <button 
                                type="button" 
                                @click="showModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-xl transition duration-150"
                            >
                                {{ __('pages.user_management.alerts.cancel') }}
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-650 dark:hover:bg-indigo-550 text-white font-semibold text-sm rounded-xl transition duration-150 shadow-sm disabled:opacity-50"
                            >
                                {{ form.processing ? __('pages.user_management.alerts.saving') : __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- PHOTO PREVIEW MODAL -->
        <Teleport to="body">
            <div v-if="showPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" @click="showPhotoModal = false">
                <div class="relative max-w-sm w-full bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800" @click.stop>
                    <button type="button" @click="showPhotoModal = false" class="absolute top-2 right-2 p-1.5 bg-slate-100 dark:bg-slate-850 hover:bg-slate-200 rounded-full text-slate-500 transition">
                        <X class="h-4.5 w-4.5" />
                    </button>
                    <div class="text-center mb-3">
                        <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-400">{{ __('pages.user_management.table.physical_verification') }}</h4>
                    </div>
                    <div class="w-full aspect-[3/4] rounded-xl overflow-hidden border border-slate-100 dark:border-slate-800">
                        <img :src="activePhotoUrl" class="w-full h-full object-cover" />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
