<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    departments: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Department Management State
const confirmingDepartmentDeletion = ref(false);
const departmentToDelete = ref(null);
const managingDepartment = ref(false);
const editingDepartment = ref(null);

const departmentForm = useForm({
    name: '',
    shortname: '',
    code: '',
    department_head: '',
});

watch(search, (value) => {
    router.get(route('departments.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
});

// Department Actions
const openCreateDepartmentModal = () => {
    editingDepartment.value = null;
    departmentForm.reset();
    managingDepartment.value = true;
};

const openEditDepartmentModal = (department) => {
    editingDepartment.value = department;
    departmentForm.name = department.name;
    departmentForm.shortname = department.shortname;
    departmentForm.code = department.code;
    departmentForm.department_head = department.department_head;
    managingDepartment.value = true;
};

const saveDepartment = () => {
    if (editingDepartment.value) {
        departmentForm.put(route('departments.update', editingDepartment.value.id), {
            onSuccess: () => closeDepartmentModal(),
        });
    } else {
        departmentForm.post(route('departments.store'), {
            onSuccess: () => closeDepartmentModal(),
        });
    }
};

const confirmDepartmentDeletion = (department) => {
    departmentToDelete.value = department;
    confirmingDepartmentDeletion.value = true;
};

const deleteDepartment = () => {
    router.delete(route('departments.destroy', departmentToDelete.value.id), {
        onSuccess: () => (confirmingDepartmentDeletion.value = false),
    });
};

const closeDepartmentModal = () => {
    managingDepartment.value = false;
    departmentForm.reset();
    editingDepartment.value = null;
};
</script>

<template>
    <AppLayout title="Departments">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Department Management
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Departments</h3>
                            <div class="flex space-x-4">
                                <input
                                    v-model="search"
                                    type="search"
                                    placeholder="Search departments..."
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                >
                                <PrimaryButton @click="openCreateDepartmentModal">
                                    ADD DEPARTMENT
                                </PrimaryButton>
                            </div>
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-500 tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 text-left">Shortname</th>
                                    <th class="px-6 py-3 text-left">Name</th>
                                    <th class="px-6 py-3 text-left">Department Head</th>
                                    <th class="px-6 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                <tr v-for="department in departments.data" :key="department.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">{{ department.shortname || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-normal max-w-xs">
                                        <div class="text-sm font-medium text-gray-900">{{ department.name }}</div>
                                        <div class="text-[10px] text-gray-400 uppercase tracking-tighter">Code: {{ department.code || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ department.department_head || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center space-x-2">
                                        <button @click="openEditDepartmentModal(department)" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-widest">Edit</button>
                                        <button 
                                            @click="confirmDepartmentDeletion(department)" 
                                            class="text-red-600 hover:text-red-900 font-medium text-xs uppercase tracking-widest"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-6" v-if="departments.links">
                            <Pagination :links="departments.links" />
                        </div>
                        <div v-if="departments.data.length === 0" class="text-center py-6 text-gray-500">
                            No departments found.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Create/Edit Modal -->
        <DialogModal :show="managingDepartment" @close="closeDepartmentModal">
            <template #title>
                {{ editingDepartment ? 'Edit Department' : 'Add New Department' }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="code" value="Department Code" />
                        <TextInput v-model="departmentForm.code" type="text" class="mt-1 block w-full" />
                        <InputError :message="departmentForm.errors.code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Department Name" />
                        <TextInput v-model="departmentForm.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="departmentForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="shortname" value="Shortname" />
                        <TextInput v-model="departmentForm.shortname" type="text" class="mt-1 block w-full uppercase" />
                        <InputError :message="departmentForm.errors.shortname" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="department_head" value="Department Head / Contact" />
                        <TextInput v-model="departmentForm.department_head" type="text" class="mt-1 block w-full" />
                        <InputError :message="departmentForm.errors.department_head" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeDepartmentModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': departmentForm.processing }" :disabled="departmentForm.processing" @click="saveDepartment">
                    {{ editingDepartment ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Department Delete Confirmation Modal -->
        <DialogModal :show="confirmingDepartmentDeletion" @close="confirmingDepartmentDeletion = false">
            <template #title>Delete Department</template>
            <template #content>
                Are you sure you want to delete <strong>{{ departmentToDelete?.name }}</strong>? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDepartmentDeletion = false">Cancel</SecondaryButton>
                <button 
                    class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition" 
                    @click="deleteDepartment"
                >
                    Delete Department
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
