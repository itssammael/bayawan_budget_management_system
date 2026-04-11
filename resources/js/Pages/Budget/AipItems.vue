<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
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
    current_budget_year: Object,
    budget_years: Array,
    budget_classifications: Array,
    ppsas: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const confirmingItemDeletion = ref(false);
const itemToDelete = ref(null);
const confirmingSave = ref(false);
const managingItem = ref(false);
const editingItem = ref(null);
const showingSelectionDialog = ref(false);
const isBulkCreate = ref(false);
const bulkItems = ref([]);
const bulkForm = useForm({
    items: [],
});

const form = useForm({
    budget_year_id: props.current_budget_year.id,
    budget_classification_id: '',
    fund_source_id: '',
    ppsa_id: '',
    aip_reference_code: '',
    ppa_description: '',
    department_id: '',
    start_date: `${props.current_budget_year.year}-01-01`,
    end_date: `${props.current_budget_year.year}-12-31`,
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
    showingSelectionDialog.value = true;
};

const selectSingleCreate = () => {
    showingSelectionDialog.value = false;
    isBulkCreate.value = false;
    editingItem.value = null;
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }
    
    managingItem.value = true;
};
console.log(props.items)
const selectBulkCreate = () => {
    showingSelectionDialog.value = false;
    isBulkCreate.value = true;
    editingItem.value = null;
    bulkItems.value = [];
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }
    
    managingItem.value = true;
};

const addToBulkList = () => {
    // Basic validation check before adding
    if (!form.budget_year_id || !form.aip_reference_code || !form.ppa_description || !form.fund_source_id || !form.budget_classification_id) {
        alert('Please fill in all required fields (Year, Ref Code, Description, Fund Source, Appropriation)');
        return;
    }

    const item = {
        budget_year_id: form.budget_year_id,
        budget_year_year: props.budget_years.find(y => y.id === form.budget_year_id)?.year,
        budget_classification_id: form.budget_classification_id,
        budget_classification_name: props.budget_classifications.find(c => c.id === form.budget_classification_id)?.classification_name,
        fund_source_id: form.fund_source_id,
        fund_source_name: props.fund_sources.find(f => f.id === form.fund_source_id)?.name,
        ppsa_id: form.ppsa_id,
        aip_reference_code: form.aip_reference_code,
        ppa_description: form.ppa_description,
        department_id: form.department_id,
        start_date: form.start_date,
        end_date: form.end_date,
        expected_outputs: form.expected_outputs,
        amount: form.amount,
        implementing_departments: JSON.parse(JSON.stringify(form.implementing_departments)),
    };

    bulkItems.value.push(item);
    
    // Clear some fields for next item but keep others (like year, fund source, etc.)
    form.aip_reference_code = '';
    form.ppa_description = '';
    form.expected_outputs = '';
    form.amount = 0;
    form.implementing_departments = [];
};

const removeBulkItem = (index) => {
    bulkItems.value.splice(index, 1);
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
    if (isBulkCreate.value && bulkItems.value.length === 0) {
        alert('Please add at least one item to the list.');
        return;
    }
    confirmingSave.value = true;
};

const proceedSaveItem = () => {
    if (isBulkCreate.value) {
        bulkForm.items = bulkItems.value;
        bulkForm.post(route('budget.aip.bulk-store'), {
            onSuccess: () => { closeModal(); confirmingSave.value = false; },
        });
    } else {
        if (editingItem.value) {
            form.put(route('budget.aip.update', editingItem.value.id), {
                onSuccess: () => { closeModal(); confirmingSave.value = false; },
            });
        } else {
            form.post(route('budget.aip.store'), {
                onSuccess: () => { closeModal(); confirmingSave.value = false; },
            });
        }
    }
};

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingItemDeletion.value = true;
};

const deleteItem = () => {
    router.delete(route('budget.aip.destroy', itemToDelete.value.id), {
        onSuccess: () => { confirmingItemDeletion.value = false; itemToDelete.value = null; },
    });
};

const closeModal = () => {
    managingItem.value = false;
    form.reset();
    bulkForm.reset();
    editingItem.value = null;
    isBulkCreate.value = false;
    bulkItems.value = [];
    confirmingSave.value = false;
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

            <!-- Selection Dialog -->
            <DialogModal :show="showingSelectionDialog" @close="showingSelectionDialog = false" max-width="md">
                <template #title>Create AIP Item</template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-4">
                        <button 
                            @click="selectSingleCreate"
                            class="flex flex-col items-center justify-center p-6 bg-white border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition group"
                        >
                            <div class="p-3 bg-indigo-100 rounded-full text-indigo-600 group-hover:bg-indigo-200 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <span class="font-bold text-gray-700">Single Item</span>
                            <p class="text-xs text-gray-500 text-center mt-1">Create one AIP item at a time</p>
                        </button>

                        <button 
                            @click="selectBulkCreate"
                            class="flex flex-col items-center justify-center p-6 bg-white border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition group"
                        >
                            <div class="p-3 bg-indigo-100 rounded-full text-indigo-600 group-hover:bg-indigo-200 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <span class="font-bold text-gray-700">Bulk Create</span>
                            <p class="text-xs text-gray-500 text-center mt-1">Add multiple items to a list and create all at once</p>
                        </button>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showingSelectionDialog = false">Cancel</SecondaryButton>
                </template>
            </DialogModal>

        <div class="py-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 uppercase text-[10px] font-bold text-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-left">Ref. Code</th>
                                <th class="px-3 py-3 text-left">PPSAS/PPA Description</th>
                                <th v-if="showDepartment" class="px-3 py-3 text-left">Department</th>
                                <th class="px-3 py-3 text-left">Impl. Depts</th>
                                <th class="px-3 py-3 text-left">Dates</th>
                                <th class="px-3 py-3 text-left">Fund Source</th>
                                <th class="px-3 py-3 text-left">
                                    <div class="flex flex-col">
                                        <div class="px-3 border-b-2 text-center">Budget</div>
                                        <div class="flex">
                                            <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">Appropriation</div>
                                            <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">Amount</div>
                                        </div>
                                    </div>
                                </th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[10px]">
                            <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50 transition">
                                <td class="px-3 py-1.5 font-semibold text-gray-900">{{ item.aip_reference_code }}</td>
                                <td class="px-3 py-1.5 max-w-xs whitespace-normal"><span class="font-bold block text-[16px]">{{ item.ppsa.name }}</span><span class="text-[10px]">{{ item.ppa_description }}</span></td>
                                <td v-if="showDepartment" class="px-3 py-1.5">{{ item.department ? item.department.name : '-' }}</td>
                                
                                <td class="px-3 py-1.5">
                                    <div class="flex flex-col space-y-1">
                                        <span v-for="dept in item.implementing_departments" :key="dept.id" class="text-[10px] text-gray-600 uppercase font-semibold">
                                            • {{ dept.department ? (dept.department.shortname || dept.department.name) : 'Unknown' }}
                                        </span>
                                        <span v-if="!item.implementing_departments || item.implementing_departments.length === 0" class="text-gray-400">-</span>
                                    </div>
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-500">
                                    {{ formatDate(item.start_date) }} -<br>
                                    {{ formatDate(item.end_date) }}
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap">{{ item.fund_source ? item.fund_source.name : '-' }}</td>
                                 <td class="px-3 py-1.5 text-left">
                                    <div class="flex">
                                        <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">{{ item.budget_classification ? item.budget_classification.classification_name : '-' }}</div>
                                        <div class="px-3 pt-3 pb-1.5 w-1/2 text-center">{{ formatCurrency(item.amount) }}</div>
                                    </div>
                                    
                                </td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-center space-x-2 text-xs">
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
        <DialogModal :show="managingItem" @close="closeModal" :max-width="isBulkCreate ? '7xl' : '2xl'">
            <template #title>
                <div class="flex justify-between items-center">
                    <span>
                        {{ editingItem ? 'Edit AIP Item' : (isBulkCreate ? 'Bulk Create AIP Items' : 'Add New AIP Item') }}
                    </span>
                    <span v-if="isBulkCreate" class="text-sm font-normal text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                        {{ bulkItems.length }} items in list
                    </span>
                </div>
            </template>

            <template #content>
                <div :class="isBulkCreate ? 'grid grid-cols-1 lg:grid-cols-12 gap-6' : 'grid grid-cols-1 md:grid-cols-2 gap-4'">
                    <div :class="isBulkCreate ? 'lg:col-span-5' : 'md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4'">
                        <div :class="isBulkCreate ? 'grid grid-cols-1 gap-4' : 'grid grid-cols-1 md:grid-cols-2 gap-4 contents'">
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

                            <div :class="isBulkCreate ? '' : 'md:col-span-2'">
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
                                    <div class="border-2 border-gray-300 rounded-md p-2 h-36 overflow-y-auto">
                                        <div v-if="form.implementing_departments.length === 0" class="text-gray-400 text-[10px] text-center py-4">
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

                            <div :class="isBulkCreate ? '' : 'md:col-span-2'">
                                <InputLabel for="ppsa_id" value="PPSAs" />
                                <SearchableSelect 
                                    v-model="form.ppsa_id" 
                                    :options="ppsas" 
                                    placeholder="Select PPSA"
                                />
                                <InputError :message="form.errors.ppsa_id" class="mt-2" />
                            </div>

                            <div :class="isBulkCreate ? '' : 'md:col-span-2'">
                                <InputLabel for="ppa_description" value="PPA Description" />
                                <textarea v-model="form.ppa_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" rows="2"></textarea>
                                <InputError :message="form.errors.ppa_description" class="mt-2" />
                            </div>

                            <div v-if="showDepartment" :class="isBulkCreate ? '' : 'md:col-span-2'">
                                <InputLabel for="department_id" value="Department" />
                                <SearchableSelect 
                                    v-model="form.department_id" 
                                    :options="departments" 
                                    placeholder="Select Department"
                                    :error="form.errors.department_id"
                                />
                                <InputError :message="form.errors.department_id" class="mt-2" />
                            </div>

                            <div :class="isBulkCreate ? 'grid grid-cols-2 gap-4' : 'contents'">
                                <div>
                                    <InputLabel for="start_date" value="Start Date" />
                                    <TextInput v-model="form.start_date" type="date" class="mt-1 block w-full text-sm" />
                                    <InputError :message="form.errors.start_date" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="end_date" value="End Date" />
                                    <TextInput v-model="form.end_date" type="date" class="mt-1 block w-full text-sm" />
                                    <InputError :message="form.errors.end_date" class="mt-2" />
                                </div>
                            </div>

                            <div :class="isBulkCreate ? '' : 'md:col-span-2'">
                                <InputLabel for="expected_outputs" value="Expected Outputs" />
                                <textarea v-model="form.expected_outputs" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" rows="2"></textarea>
                                <InputError :message="form.errors.expected_outputs" class="mt-2" />
                            </div>

                            <div :class="isBulkCreate ? '' : 'md:col-span-2'">
                                <InputLabel for="amount" value="Budget Amount" />
                                <CurrencyInput v-model="form.amount" class="mt-1 block w-full text-sm" />
                                <InputError :message="form.errors.amount" class="mt-2" />
                            </div>

                            <div v-if="isBulkCreate" class="mt-4">
                                <PrimaryButton @click="addToBulkList" class="w-full justify-center">
                                    Add to List
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Items List (Right Side) -->
                    <div v-if="isBulkCreate" class="lg:col-span-7 bg-gray-50 rounded-lg p-4 flex flex-col h-[600px]">
                        <h3 class="font-bold text-gray-700 mb-4 flex justify-between items-center text-sm">
                            Items to be Created
                            <span class="text-xs font-normal text-gray-500">{{ bulkItems.length }} total items</span>
                        </h3>
                        
                        <div class="flex-1 overflow-y-auto border border-gray-200 rounded bg-white">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-700 uppercase">Ref. Code</th>
                                        <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-700 uppercase">Year / Classification</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-bold text-gray-700 uppercase">Amount</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-bold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="(item, index) in bulkItems" :key="index" class="hover:bg-gray-50">
                                        <td class="px-3 py-3 text-[10px] font-medium text-gray-900 border-b border-gray-100">
                                            {{ item.aip_reference_code }}
                                            <p class="text-[9px] text-gray-500 truncate w-40" :title="item.ppa_description">{{ item.ppa_description }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-[10px] text-gray-600 border-b border-gray-100">
                                            {{ item.budget_year_year }} / {{ item.budget_classification_name }}
                                        </td>
                                        <td class="px-3 py-3 text-[10px] text-right font-semibold text-indigo-600 border-b border-gray-100">
                                            {{ formatCurrency(item.amount) }}
                                        </td>
                                        <td class="px-3 py-3 text-center border-b border-gray-100">
                                            <button @click="removeBulkItem(index)" class="text-red-600 hover:text-red-900 transition p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="bulkItems.length === 0">
                                        <td colspan="4" class="px-3 py-10 text-center text-gray-400 text-xs italic">
                                            No items added to the list yet.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="bulkItems.length > 0" class="bg-gray-50 font-bold sticky bottom-0">
                                    <tr>
                                        <td colspan="2" class="px-3 py-2 text-right text-[10px] uppercase">Total Bulk Amount:</td>
                                        <td class="px-3 py-2 text-right text-[10px] text-indigo-700">
                                            {{ formatCurrency(bulkItems.reduce((sum, item) => sum + Number(item.amount), 0)) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-if="bulkForm.errors.items" class="mt-2 text-red-500 text-xs">
                            {{ bulkForm.errors.items }}
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing || bulkForm.processing }" :disabled="form.processing || bulkForm.processing" @click="saveItem">
                    {{ editingItem ? 'Update' : (isBulkCreate ? 'Submit All (' + bulkItems.length + ')' : 'Create') }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Confirm Save Modal -->
        <ConfirmDialog
            :show="confirmingSave"
            type="info"
            title="Confirm Action"
            :content="editingItem ? 'Are you sure you want to update this AIP item?' : (isBulkCreate ? `Are you sure you want to create ${bulkItems.length} AIP item(s)?` : 'Are you sure you want to create this AIP item?')"
            :confirmText="editingItem ? 'Update' : 'Create'"
            @confirm="proceedSaveItem"
            @close="confirmingSave = false"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmDialog
            :show="confirmingItemDeletion"
            type="danger"
            title="Delete AIP Item"
            content="Are you sure you want to delete this AIP item? This action cannot be undone."
            confirmText="Delete"
            @confirm="deleteItem"
            @close="confirmingItemDeletion = false"
        />
    </AppLayout>
</template>
