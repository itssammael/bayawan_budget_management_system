<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    departments: Array,
    filters: Object,
});

const activeTab = ref('users');
const search = ref(props.filters.search || '');

// User Management State
const confirmingUserDeletion = ref(false);
const userToDelete = ref(null);
const managingUser = ref(false);
const editingUser = ref(null);

const userForm = useForm({
    name: '',
    email: '',
    username: '',
    password: '',
    password_confirmation: '',
    roles: [],
    department_id: '',
});

// Permissions
const isAdmin = computed(() => {
    return usePage().props.auth.user.roles.some(r => r.name === 'admin');
});

const isGlobalAdmin = computed(() => {
    const user = usePage().props.auth.user;
    return user.department && user.department.name === 'Admin';
});

// Role Management State
const confirmingRoleDeletion = ref(false);
const roleToDelete = ref(null);
const managingRole = ref(false);
const editingRole = ref(null);

const roleForm = useForm({
    name: '',
    description: '',
    permissions: [],
});

const permissionOptions = ['C', 'R', 'U', 'D'];

watch(search, (value) => {
    if (activeTab.value === 'users') {
        router.get(route('users.index'), { search: value }, {
            preserveState: true,
            replace: true
        });
    }
});

// User Actions
const openCreateUserModal = () => {
    editingUser.value = null;
    userForm.reset();
    userForm.email = '@bayawancity.gov.ph';
    if (!isGlobalAdmin.value) {
        userForm.department_id = usePage().props.auth.user.department_id;
    }
    managingUser.value = true;
};

const openEditUserModal = (user) => {
    editingUser.value = user;
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.username = user.username;
    userForm.password = '';
    userForm.password_confirmation = '';
    userForm.roles = user.roles.map(r => r.id);
    userForm.department_id = user.department_id || '';
    managingUser.value = true;
};

const saveUser = () => {
    if (editingUser.value) {
        userForm.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeUserModal(),
        });
    } else {
        userForm.post(route('users.store'), {
            onSuccess: () => closeUserModal(),
        });
    }
};

const confirmUserDeletion = (user) => {
    userToDelete.value = user;
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => (confirmingUserDeletion.value = false),
    });
};

const closeUserModal = () => {
    managingUser.value = false;
    userForm.reset();
    editingUser.value = null;
};

// Role Actions
const openCreateRoleModal = () => {
    editingRole.value = null;
    roleForm.reset();
    managingRole.value = true;
};

const openEditRoleModal = (role) => {
    editingRole.value = role;
    roleForm.name = role.name;
    roleForm.description = role.description;
    roleForm.permissions = role.permissions || [];
    managingRole.value = true;
};

const saveRole = () => {
    if (editingRole.value) {
        roleForm.put(route('roles.update', editingRole.value.id), {
            onSuccess: () => closeRoleModal(),
        });
    } else {
        roleForm.post(route('roles.store'), {
            onSuccess: () => closeRoleModal(),
        });
    }
};

const confirmRoleDeletion = (role) => {
    roleToDelete.value = role;
    confirmingRoleDeletion.value = true;
};

const deleteRole = () => {
    router.delete(route('roles.destroy', roleToDelete.value.id), {
        onSuccess: () => (confirmingRoleDeletion.value = false),
    });
};

const closeRoleModal = () => {
    managingRole.value = false;
    roleForm.reset();
    editingRole.value = null;
};

const getPermissionColor = (p) => {
    switch (p) {
        case 'C': return 'bg-green-100 text-green-600';
        case 'R': return 'bg-blue-100 text-blue-600';
        case 'U': return 'bg-yellow-100 text-yellow-600';
        case 'D': return 'bg-red-100 text-red-600';
        default: return 'bg-gray-100 text-gray-600';
    }
};
</script>

<template>
    <AppLayout title="Users & Roles">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    User Management
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                @click="activeTab = 'users'"
                                :class="[activeTab === 'users' ? 'border-[var(--accent-color)] text-[var(--accent-color-dark)] bg-[var(--accent-color-light)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200']"
                            >
                                System Users
                            </button>
                            <button
                                v-if="isGlobalAdmin"
                                @click="activeTab = 'roles'"
                                :class="[activeTab === 'roles' ? 'border-[var(--accent-color)] text-[var(--accent-color-dark)] bg-[var(--accent-color-light)]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200']"
                            >
                                System Roles
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Users Tab -->
                        <div v-if="activeTab === 'users'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">System Users</h3>
                                <div class="flex space-x-4">
                                    <input
                                        v-model="search"
                                        type="search"
                                        placeholder="Search users..."
                                        class="border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)] rounded-md shadow-sm text-sm"
                                    >
                                    <PrimaryButton v-if="isAdmin" @click="openCreateUserModal">
                                        ADD USER
                                    </PrimaryButton>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Email/Username</th>
                                        <th class="px-6 py-3 text-left">Department</th>
                                        <th class="px-6 py-3 text-left">Roles</th>
                                        <th class="px-6 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <img class="h-8 w-8 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ user.email }}</div>
                                            <div class="text-xs text-gray-500">{{ user.username }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ user.department ? user.department.name : '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="role in user.roles" :key="role.id" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">
                                                    {{ role.name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center space-x-2">
                                            <button v-if="isAdmin" @click="openEditUserModal(user)" class="text-[var(--accent-color-dark)] hover:text-[var(--accent-color)] font-medium text-xs uppercase tracking-widest transition">Edit</button>
                                            <button 
                                                v-if="isAdmin && user.id !== $page.props.auth.user.id"
                                                @click="confirmUserDeletion(user)" 
                                                class="text-red-600 hover:text-red-900 font-medium text-xs uppercase tracking-widest"
                                            >
                                                Delete
                                            </button>
                                            <span v-if="!isAdmin" class="text-gray-400 text-[10px] italic">No Access</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-6">
                                <Pagination :links="users.links" />
                            </div>
                        </div>

                        <!-- Roles Tab -->
                        <div v-if="activeTab === 'roles'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">System Roles</h3>
                                <PrimaryButton @click="openCreateRoleModal">
                                    ADD ROLE
                                </PrimaryButton>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Permissions</th>
                                        <th class="px-6 py-3 text-left">Description</th>
                                        <th class="px-6 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                    <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ role.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-1">
                                                <span v-for="p in permissionOptions" :key="p" 
                                                    :class="['w-6 h-6 flex items-center justify-center rounded text-[10px] font-bold', role.permissions?.includes(p) ? getPermissionColor(p) : 'bg-gray-50 text-gray-300 opacity-50']"
                                                >
                                                    {{ p }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ role.description }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center space-x-2">
                                            <button @click="openEditRoleModal(role)" class="inline-flex items-center px-4 py-1 border border-gray-300 rounded-md font-semibold text-[10px] text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:text-gray-800 active:bg-gray-50 transition transition">
                                                {{ role.name === 'admin' ? 'VIEW' : 'EDIT' }}
                                            </button>
                                            <button 
                                                v-if="role.name !== 'admin'"
                                                @click="confirmRoleDeletion(role)" 
                                                class="inline-flex items-center px-4 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-[10px] text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition"
                                            >
                                                DELETE
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Create/Edit Modal -->
        <DialogModal :show="managingUser" @close="closeUserModal">
            <template #title>
                {{ editingUser ? 'Edit User' : 'Add New User' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput v-model="userForm.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="userForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput v-model="userForm.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="userForm.errors.email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="username" value="Username" />
                        <TextInput v-model="userForm.username" type="text" class="mt-1 block w-full" required />
                        <InputError :message="userForm.errors.username" class="mt-2" />
                    </div>
                    
                    <div>
                        <InputLabel for="department_id" value="Department" />
                        <select 
                            v-model="userForm.department_id" 
                            class="mt-1 block w-full border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)] rounded-md shadow-sm"
                            :disabled="!isGlobalAdmin"
                        >
                            <option value="">No Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                        <InputError :message="userForm.errors.department_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Roles" />
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label v-for="role in roles" :key="role.id" class="flex items-center">
                                <input type="checkbox" :value="role.id" v-model="userForm.roles" class="rounded border-gray-300 text-[var(--accent-color)] shadow-sm focus:ring-[var(--accent-color)]">
                                <span class="ml-2 text-sm text-gray-600">{{ role.name }}</span>
                            </label>
                        </div>
                        <InputError :message="userForm.errors.roles" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="password" :value="editingUser ? 'New Password (leave blank to keep current)' : 'Password'" />
                        <TextInput v-model="userForm.password" type="password" class="mt-1 block w-full" :required="!editingUser" />
                        <InputError :message="userForm.errors.password" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput v-model="userForm.password_confirmation" type="password" class="mt-1 block w-full" :required="!editingUser" />
                        <InputError :message="userForm.errors.password_confirmation" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeUserModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': userForm.processing }" :disabled="userForm.processing" @click="saveUser">
                    {{ editingUser ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Role Create/Edit Modal -->
        <DialogModal :show="managingRole" @close="closeRoleModal">
            <template #title>
                {{ editingRole ? (editingRole.name === 'admin' ? 'View Role' : 'Edit Role') : 'Add New Role' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="role_name" value="Name" />
                        <TextInput v-model="roleForm.name" type="text" class="mt-1 block w-full" required :disabled="editingRole?.name === 'admin'" />
                        <InputError :message="roleForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Permissions" />
                        <div class="mt-2 flex space-x-4">
                            <label v-for="p in permissionOptions" :key="p" class="flex items-center">
                                <input type="checkbox" :value="p" v-model="roleForm.permissions" class="rounded border-gray-300 text-[var(--accent-color)] shadow-sm focus:ring-[var(--accent-color)]" :disabled="editingRole?.name === 'admin'">
                                <span class="ml-2 text-sm text-gray-600 font-bold" :class="getPermissionColor(p)">{{ p }}</span>
                            </label>
                        </div>
                        <InputError :message="roleForm.errors.permissions" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="role_description" value="Description" />
                        <textarea v-model="roleForm.description" class="w-full mt-1 border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)] rounded-md shadow-sm" rows="3" :disabled="editingRole?.name === 'admin'"></textarea>
                        <InputError :message="roleForm.errors.description" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeRoleModal">Cancel</SecondaryButton>
                <PrimaryButton v-if="editingRole?.name !== 'admin'" class="ml-3" :class="{ 'opacity-25': roleForm.processing }" :disabled="roleForm.processing" @click="saveRole">
                    {{ editingRole ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- User Delete Confirmation Modal -->
        <DialogModal :show="confirmingUserDeletion" @close="confirmingUserDeletion = false">
            <template #title>Delete User</template>
            <template #content>
                Are you sure you want to delete <strong>{{ userToDelete?.name }}</strong>? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingUserDeletion = false">Cancel</SecondaryButton>
                <button 
                    class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition" 
                    @click="deleteUser"
                >
                    Delete User
                </button>
            </template>
        </DialogModal>

        <!-- Role Delete Confirmation Modal -->
        <DialogModal :show="confirmingRoleDeletion" @close="confirmingRoleDeletion = false">
            <template #title>Delete Role</template>
            <template #content>
                Are you sure you want to delete <strong>{{ roleToDelete?.name }}</strong>? Users assigned to this role will no longer have its permissions.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingRoleDeletion = false">Cancel</SecondaryButton>
                <button 
                    class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition" 
                    @click="deleteRole"
                >
                    Delete Role
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
