<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    items: Object,
    filters: Object,
    fund_sources: Array,
    budget_years: Array,
    budget_classifications: Array,
    ppsas: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const confirmingItemDeletion = ref(false);
const itemToDelete = ref(null);
const managingItem = ref(false);
const editingItem = ref(null);

const form = useForm({
    budget_year_id: '',
    budget_classification_id: '',
    fund_source_id: '',
    ppsa_id: '',
    aip_reference_code: '',
    ppa_description: '',
    department_id: '',
    start_date: '',
    end_date: '',
    expected_outputs: '',
    amount: 0,
    implementing_department_id: '',
    implementing_departments: [],
});

watch(search, (value) => {
    router.get(route('budget.aip'), { search: value }, {
        preserveState: true,
        replace: true
    });
});

watch(() => form.implementing_departments, (departments) => {
    if (departments && departments.length > 0) {
        form.amount = departments.reduce((sum, dept) => sum + Number(dept.amount || 0), 0);
    }
}, { deep: true });

const addImplementingDepartment = () => {
    if (!form.implementing_department_id) return;
    
    // Check if already added
    if (form.implementing_departments.some(d => d.department_id === form.implementing_department_id)) {
        form.implementing_department_id = '';
        return;
    }
    
    const dept = props.departments.find(d => d.id === form.implementing_department_id);
    if (dept) {
        form.implementing_departments.push({
            department_id: dept.id,
            name: dept.name,
            amount: 0
        });
    }
    
    form.implementing_department_id = '';
};

const removeImplementingDepartment = (index) => {
    form.implementing_departments.splice(index, 1);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }
    
    managingItem.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.budget_year_id = item.budget_year_id;
    form.budget_classification_id = item.budget_classification_id;
    form.fund_source_id = item.fund_source_id;
    form.ppsa_id = item.ppsa_id || '';
    form.aip_reference_code = item.aip_reference_code;
    form.ppa_description = item.ppa_description;
    form.department_id = item.department_id;
    form.start_date = item.start_date;
    form.end_date = item.end_date;
    form.expected_outputs = item.expected_outputs;
    form.amount = item.amount;
    
    // Populate implementing departments
    if (item.implementing_departments) {
        form.implementing_departments = item.implementing_departments.map(d => ({
            department_id: d.department_id,
            name: d.department ? d.department.name : 'Unknown',
            amount: d.amount
        }));
    } else {
        form.implementing_departments = [];
    }
    
    managingItem.value = true;
};

const saveItem = () => {
    if (editingItem.value) {
        form.put(route('budget.aip.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('budget.aip.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingItemDeletion.value = true;
};

const deleteItem = () => {
    router.delete(route('budget.aip.destroy', itemToDelete.value.id), {
        onSuccess: () => (confirmingItemDeletion.value = false),
    });
};

const closeModal = () => {
    managingItem.value = false;
    form.reset();
    editingItem.value = null;
};

const showDepartment = computed(() => {
    const user = usePage().props.auth.user;
    return user.department && user.department.name === 'Admin';
});

const filteredFundSources = computed(() => {
    return props.fund_sources.filter(fs => 
        fs.name
    );
});

const availableDepartments = computed(() => {
    return props.departments.filter(d => 
        !form.implementing_departments.some(selected => selected.department_id === d.id)
    );
});
</script>

<template>
    <AppLayout title="Program Monitoring (AIP)">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Program Monitoring (Annual Investment Program)
                </h2>
                <div class="flex space-x-4">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search AIP Items..."
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                    >
                    <PrimaryButton @click="openCreateModal">
                        Add AIP Item
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 uppercase text-[10px] font-bold text-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-left">Ref. Code</th>
                                <th class="px-3 py-3 text-left">PPA Description</th>
                                <th v-if="showDepartment" class="px-3 py-3 text-left">Department</th>
                                <th class="px-3 py-3 text-left">Impl. Depts</th>
                                <th class="px-3 py-3 text-left">Dates</th>
                                <th class="px-3 py-3 text-left">Fund Source</th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex flex-col">
                                        <div class="px-3 border-b-2 text-center">Budget</div>
                                        <div class="flex">
                                            <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">Classification</div>
                                            <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">Amount</div>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[10px]">
                            <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50 transition">
                                <td class="px-3 py-4 font-semibold text-gray-900">{{ item.aip_reference_code }}</td>
                                <td class="px-3 py-4 max-w-xs whitespace-normal">{{ item.ppa_description }}</td>
                                <td v-if="showDepartment" class="px-3 py-4">{{ item.department ? item.department.name : '-' }}</td>
                                
                                <td class="px-3 py-4">
                                    <div class="flex flex-col space-y-1">
                                        <span v-for="dept in item.implementing_departments" :key="dept.id" class="text-[10px] text-gray-600 uppercase font-semibold">
                                            • {{ dept.department ? (dept.department.shortname || dept.department.name) : 'Unknown' }}
                                        </span>
                                        <span v-if="!item.implementing_departments || item.implementing_departments.length === 0" class="text-gray-400">-</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-gray-500">
                                    {{ formatDate(item.start_date) }} -<br>
                                    {{ formatDate(item.end_date) }}
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">{{ item.fund_source ? item.fund_source.name : '-' }}</td>
                                 <td class="px-3 py-4 text-left">
                                    <div class="flex">
                                        <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">{{ item.budget_classification ? item.budget_classification.classification_name : '-' }}</div>
                                        <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">{{ formatCurrency(item.amount) }}</div>
                                    </div>
                                    
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-center space-x-2 text-xs">
                                    <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click="confirmDeletion(item)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center">
                    <Pagination :links="items.links" />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="managingItem" @close="closeModal">
            <template #title>
                {{ editingItem ? 'Edit AIP Item' : 'Add New AIP Item' }}
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="budget_year" value="Budget Year" />
                        <SearchableSelect 
                            v-model="form.budget_year_id" 
                            :options="budget_years" 
                            label="year"
                            placeholder="Select Budget Year"
                            :error="form.errors.budget_year_id"
                        />
                        <InputError :message="form.errors.budget_year_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="aip_reference_code" value="AIP Reference Code" />
                        <TextInput v-model="form.aip_reference_code" type="text" class="mt-1 block w-full" placeholder="e.g. 1000-1-01-011" />
                        <InputError :message="form.errors.aip_reference_code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="budget_classification" value="Budget Classification" />
                        <SearchableSelect 
                            v-model="form.budget_classification_id" 
                            :options="budget_classifications"
                            label="classification_name"
                            placeholder="Select Budget Classification"
                            :error="form.errors.budget_classification_id"
                        />
                        <InputError :message="form.errors.budget_classification_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="fund_source" value="Fund Source" />
                        <SearchableSelect 
                            v-model="form.fund_source_id" 
                            :options="filteredFundSources" 
                            placeholder="Select Fund Source"
                            :error="form.errors.fund_source_id"
                        />
                        <InputError :message="form.errors.fund_source_id" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="implementing_department" value="Implementing Department" />
                        <div class="flex flex-col space-y-2">
                            <div class="w-full">
                                <SearchableSelect 
                                    v-model="form.implementing_department_id" 
                                    :options="availableDepartments" 
                                    placeholder="Select Department"
                                    @update:modelValue="addImplementingDepartment"
                                />
                            </div>
                            <div class="border-2 border-gray-300 rounded-md p-2 h-48 overflow-y-auto">
                                <div v-if="form.implementing_departments.length === 0" class="text-gray-400 text-xs text-center py-4">
                                    No implementing departments added yet.
                                </div>
                                <div v-for="(dept, index) in form.implementing_departments" :key="dept.department_id" class="flex items-center space-x-2 mb-2 p-2 bg-gray-50 rounded border border-gray-200">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold text-gray-700 truncate" :title="dept.name">{{ dept.name }}</p>
                                    </div>
                                    <div class="w-32">
                                        <CurrencyInput v-model="dept.amount" class="w-full h-8 text-[10px]" />
                                    </div>
                                    <button @click="removeImplementingDepartment(index)" class="p-1 text-red-600 hover:bg-red-50 rounded transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.implementing_departments" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="ppsa_id" value="PPSAs" />
                        <SearchableSelect 
                            v-model="form.ppsa_id" 
                            :options="ppsas" 
                            placeholder="Select PPSA"
                        />
                        <InputError :message="form.errors.ppsa_id" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="ppa_description" value="PPA Description" />
                        <textarea v-model="form.ppa_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2"></textarea>
                        <InputError :message="form.errors.ppa_description" class="mt-2" />
                    </div>

                    <div v-if="showDepartment" class="md:col-span-2">
                        <InputLabel for="department_id" value="Department" />
                        <SearchableSelect 
                            v-model="form.department_id" 
                            :options="departments" 
                            placeholder="Select Department"
                            :error="form.errors.department_id"
                        />
                        <InputError :message="form.errors.department_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="start_date" value="Start Date" />
                        <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.start_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="end_date" value="End Date" />
                        <TextInput v-model="form.end_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.end_date" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="expected_outputs" value="Expected Outputs" />
                        <textarea v-model="form.expected_outputs" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2"></textarea>
                        <InputError :message="form.errors.expected_outputs" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="amount" value="Budget Amount" />
                        <CurrencyInput v-model="form.amount" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveItem">
                    {{ editingItem ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Confirmation Modal -->
        <DialogModal :show="confirmingItemDeletion" @close="confirmingItemDeletion = false">
            <template #title>Delete AIP Item</template>
            <template #content>Are you sure you want to delete this AIP item? This action cannot be undone.</template>
            <template #footer>
                <SecondaryButton @click="confirmingItemDeletion = false">Cancel</SecondaryButton>
                <button class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition" @click="deleteItem">
                    Delete
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
