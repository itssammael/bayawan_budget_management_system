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
    appropriations: Object,
    filters: Object,
    fund_sources: Array,
    budget_years: Array,
    ppsas: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const confirmingAppropriationDeletion = ref(false);
const itemToDelete = ref(null);
const managingAppropriation = ref(false);
const editingAppropriation = ref(null);

const form = useForm({
    fund_source_id: '',
    budget_year_id: '',
    ppsa_id: '',
    appropriation_type: '',
    account_code: '',
    ppa_description: '',
    appropriated_amount: 0,
    allotment: 0,
    obligation: 0,
    remarks: '',
    department_id: '',
});

watch(search, (value) => {
    router.get(route('budget.appropriations'), { search: value }, {
        preserveState: true,
        replace: true
    });
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const getStatusColor = (remarks) => {
    const status = remarks?.toLowerCase() || '';
    if (status.includes('completed') || status.includes('implemented')) return 'text-green-600 bg-green-100';
    if (status.includes('on process') || status.includes('ongoing')) return 'text-blue-600 bg-blue-100';
    if (status.includes('cancelled')) return 'text-red-600 bg-red-100';
    return 'text-gray-600 bg-gray-100';
};

const openCreateModal = () => {
    editingAppropriation.value = null;
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }
    
    managingAppropriation.value = true;
};

const openEditModal = (item) => {
    editingAppropriation.value = item;
    form.fund_source_id = item.fund_source_id;
    form.budget_year_id = item.budget_year_id;
    form.ppsa_id = item.ppsa_id;
    form.appropriation_type = item.appropriation_type || '';
    form.account_code = item.account_code;
    form.ppa_description = item.ppa_description;
    form.appropriated_amount = item.appropriated_amount;
    form.allotment = item.allotment;
    form.obligation = item.obligation;
    form.remarks = item.remarks;
    form.department_id = item.department_id || '';
    managingAppropriation.value = true;
};

const saveAppropriation = () => {
    if (editingAppropriation.value) {
        form.put(route('budget.appropriations.update', editingAppropriation.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('budget.appropriations.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingAppropriationDeletion.value = true;
};

const deleteAppropriation = () => {
    router.delete(route('budget.appropriations.destroy', itemToDelete.value.id), {
        onSuccess: () => (confirmingAppropriationDeletion.value = false),
    });
};

const closeModal = () => {
    managingAppropriation.value = false;
    form.reset();
    editingAppropriation.value = null;
};

const showDepartment = computed(() => {
    const user = usePage().props.auth.user;
    return user.department && user.department.name === 'Admin';
});

const appropriationTypes = [
    { id: 'MOOE', name: 'MOOE' },
    { id: 'Capital Outlay', name: 'Capital Outlay' },
];
</script>

<template>
    <AppLayout title="Detailed Appropriations">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detailed Appropriations
                </h2>
                <div class="flex space-x-4">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search PPA..."
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                    >
                    <PrimaryButton @click="openCreateModal">
                        Add New Appropriation
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Year</th>
                                <th class="px-4 py-3 text-left">Account Code</th>
                                <th class="px-4 py-3 text-left">PPSA</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th v-if="showDepartment" class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-left">PPA Description</th>
                                <th class="px-4 py-3 text-right">Appropriation</th>
                                <th class="px-4 py-3 text-right">Allotment</th>
                                <th class="px-4 py-3 text-right">Obligation</th>
                                <th class="px-4 py-3 text-right">Balance</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            <tr v-for="item in appropriations.data" :key="item.id" class="hover:bg-gray-50 transition text-xs">
                                <td class="px-4 py-4 whitespace-nowrap">{{ item.budget_year.year }}</td>
                                <td class="px-4 py-4 whitespace-nowrap font-mono">{{ item.account_code }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ item.ppsa.name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span v-if="item.appropriation_type" class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-[10px] font-medium border border-gray-200">{{ item.appropriation_type }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td v-if="showDepartment" class="px-4 py-4 whitespace-nowrap">{{ item.department ? item.department.name : '-' }}</td>
                                <td class="px-4 py-4 max-w-xs truncate" :title="item.ppa_description">{{ item.ppa_description }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">{{ formatCurrency(item.appropriated_amount) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">{{ formatCurrency(item.allotment) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">{{ formatCurrency(item.obligation) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-right font-semibold text-blue-600">{{ formatCurrency(item.balance) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span :class="['px-2 py-1 rounded-full text-[10px] font-medium uppercase', getStatusColor(item.remarks)]">
                                        {{ item.remarks || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center space-x-2">
                                    <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                                    <button @click="confirmDeletion(item)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <Pagination :links="appropriations.links" />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="managingAppropriation" @close="closeModal">
            <template #title>
                {{ editingAppropriation ? 'Edit Appropriation' : 'Add New Appropriation' }}
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="fund_source" value="Fund Source" />
                        <SearchableSelect 
                            v-model="form.fund_source_id" 
                            :options="fund_sources" 
                            placeholder="Select Fund Source"
                            :error="form.errors.fund_source_id"
                        />
                        <InputError :message="form.errors.fund_source_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="budget_year" value="Budget Year" />
                        <SearchableSelect 
                            v-model="form.budget_year_id" 
                            :options="budget_years" 
                            label="year"
                            placeholder="Select Year"
                            :error="form.errors.budget_year_id"
                        />
                        <InputError :message="form.errors.budget_year_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="ppsa" value="PPSA" />
                        <SearchableSelect 
                            v-model="form.ppsa_id" 
                            :options="ppsas" 
                            placeholder="Select PPSA"
                            :error="form.errors.ppsa_id"
                        />
                        <InputError :message="form.errors.ppsa_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="appropriation_type" value="Appropriation Type" />
                        <SearchableSelect 
                            v-model="form.appropriation_type" 
                            :options="appropriationTypes" 
                            placeholder="Select Type (Optional)"
                            :error="form.errors.appropriation_type"
                        />
                        <InputError :message="form.errors.appropriation_type" class="mt-2" />
                    </div>
                    
                    <div v-if="showDepartment">
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
                        <InputLabel for="account_code" value="Account Code" />
                        <TextInput v-model="form.account_code" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.account_code" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="ppa_description" value="PPA Description" />
                        <textarea v-model="form.ppa_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3"></textarea>
                        <InputError :message="form.errors.ppa_description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="appropriated_amount" value="Appropriated Amount" />
                        <CurrencyInput v-model="form.appropriated_amount" class="mt-1 block w-full" />
                        <InputError :message="form.errors.appropriated_amount" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="allotment" value="Allotment" />
                        <CurrencyInput v-model="form.allotment" class="mt-1 block w-full" />
                        <InputError :message="form.errors.allotment" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="obligation" value="Obligation" />
                        <CurrencyInput v-model="form.obligation" class="mt-1 block w-full" />
                        <InputError :message="form.errors.obligation" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="remarks" value="Remarks/Status" />
                        <TextInput v-model="form.remarks" type="text" class="mt-1 block w-full" placeholder="Implemented, Ongoing, etc." />
                        <InputError :message="form.errors.remarks" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveAppropriation">
                    {{ editingAppropriation ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Confirmation Modal -->
        <DialogModal :show="confirmingAppropriationDeletion" @close="confirmingAppropriationDeletion = false">
            <template #title>Delete Appropriation</template>
            <template #content>Are you sure you want to delete this appropriation? This action cannot be undone.</template>
            <template #footer>
                <SecondaryButton @click="confirmingAppropriationDeletion = false">Cancel</SecondaryButton>
                <button class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:border-red-900 focus:shadow-outline-red disabled:opacity-25 transition" @click="deleteAppropriation">
                    Delete
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
