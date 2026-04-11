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
    transactions: Object,
    filters: Object,
    appropriations: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const confirmingTransactionDeletion = ref(false);
const itemToDelete = ref(null);
const confirmingSave = ref(false);
const managingTransaction = ref(false);
const editingTransaction = ref(null);

const form = useForm({
    appropriation_id: '',
    transaction_no: '',
    item_description: '',
    estimated_cost: 0,
    actual_cost: 0,
    award_date: '',
    delivery_date: '',
    status: 'Planned',
    remarks: '',
    department_id: '',
});

watch(search, (value) => {
    router.get(route('budget.procurement'), { search: value }, {
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const openCreateModal = () => {
    editingTransaction.value = null;
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }

    managingTransaction.value = true;
};

const openEditModal = (item) => {
    editingTransaction.value = item;
    form.appropriation_id = item.appropriation_id;
    form.transaction_no = item.transaction_no;
    form.item_description = item.item_description;
    form.estimated_cost = item.estimated_cost;
    form.actual_cost = item.actual_cost;
    form.award_date = item.award_date;
    form.delivery_date = item.delivery_date;
    form.status = item.status;
    form.remarks = item.remarks;
    form.department_id = item.department_id || '';
    managingTransaction.value = true;
};

const saveTransaction = () => {
    confirmingSave.value = true;
};

const proceedSaveTransaction = () => {
    if (editingTransaction.value) {
        form.put(route('budget.procurement.update', editingTransaction.value.id), {
            onSuccess: () => { closeModal(); confirmingSave.value = false; },
        });
    } else {
        form.post(route('budget.procurement.store'), {
            onSuccess: () => { closeModal(); confirmingSave.value = false; },
        });
    }
};

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingTransactionDeletion.value = true;
};

const deleteTransaction = () => {
    router.delete(route('budget.procurement.destroy', itemToDelete.value.id), {
        onSuccess: () => { confirmingTransactionDeletion.value = false; itemToDelete.value = null; },
    });
};

const closeModal = () => {
    managingTransaction.value = false;
    form.reset();
    editingTransaction.value = null;
    confirmingSave.value = false;
};

const showDepartment = computed(() => {
    const user = usePage().props.auth.user;
    return user.department && user.department.name === 'Admin';
});

const statusOptions = [
    { id: 'Planned', name: 'Planned' },
    { id: 'On Process', name: 'On Process' },
    { id: 'Awarded', name: 'Awarded' },
    { id: 'Completed', name: 'Completed' },
    { id: 'Cancelled', name: 'Cancelled' },
];

const searchableAppropriations = computed(() => {
    return props.appropriations.map(app => ({
        ...app,
        display_label: `[${app.fund_source?.name || 'N/A'} ${app.budget_year?.year || 'N/A'}] ${app.ppa_description}`
    }));
});
</script>

<template>
    <AppLayout title="Procurement Tracker">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Capital Outlay (Procurement Tracker)
                </h2>
                <div class="flex space-x-4">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search Transactions..."
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                    >
                    <PrimaryButton @click="openCreateModal">
                        Add Transaction
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100 uppercase text-[10px] font-bold text-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-left">Trans. No</th>
                                <th class="px-3 py-3 text-left">Item Description</th>
                                <th v-if="showDepartment" class="px-3 py-3 text-left">Dept.</th>
                                <th class="px-3 py-3 text-left">Fund Source</th>
                                <th class="px-3 py-3 text-center">Status</th>
                                <th class="px-3 py-3 text-right">Est. Cost</th>
                                <th class="px-3 py-3 text-right">Act. Cost</th>
                                <th class="px-3 py-3 text-left">Awarded</th>
                                <th class="px-3 py-3 text-left">Delivered</th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[10px]">
                            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-gray-50 transition">
                                <td class="px-3 py-4 font-semibold text-gray-900">{{ tx.transaction_no }}</td>
                                <td class="px-3 py-4 max-w-xs whitespace-normal">{{ tx.item_description }}</td>
                                <td v-if="showDepartment" class="px-3 py-4 whitespace-nowrap">{{ tx.department ? tx.department.name : '-' }}</td>
                                <td class="px-3 py-4">{{ tx.appropriation.fund_source.name }} ({{ tx.appropriation.budget_year.year }})</td>
                                <td class="px-3 py-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full border border-blue-200 bg-blue-50 text-blue-700 font-medium whitespace-nowrap uppercase text-[9px]">
                                        {{ tx.status || 'Planned' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-right">{{ formatCurrency(tx.estimated_cost) }}</td>
                                <td class="px-3 py-4 text-right font-medium">{{ formatCurrency(tx.actual_cost) }}</td>
                                <td class="px-3 py-4 whitespace-nowrap text-gray-500">{{ formatDate(tx.award_date) }}</td>
                                <td class="px-3 py-4 whitespace-nowrap text-gray-500">{{ formatDate(tx.delivery_date) }}</td>
                                <td class="px-3 py-4 whitespace-nowrap text-center space-x-2 text-xs">
                                    <button @click="openEditModal(tx)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button @click="confirmDeletion(tx)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center">
                    <Pagination :links="transactions.links" />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="managingTransaction" @close="closeModal">
            <template #title>
                {{ editingTransaction ? 'Edit Transaction' : 'Add New Transaction' }}
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <InputLabel for="appropriation" value="Link to Appropriation" />
                        <SearchableSelect 
                            v-model="form.appropriation_id" 
                            :options="searchableAppropriations" 
                            label="display_label"
                            placeholder="Select Appropriation (PPA)"
                            :error="form.errors.appropriation_id"
                        />
                        <InputError :message="form.errors.appropriation_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="transaction_no" value="Transaction No." />
                        <TextInput v-model="form.transaction_no" type="text" class="mt-1 block w-full" placeholder="e.g. 2024-001" />
                        <InputError :message="form.errors.transaction_no" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="status" value="Status" />
                        <SearchableSelect 
                            v-model="form.status" 
                            :options="statusOptions" 
                            placeholder="Select Status"
                            :error="form.errors.status"
                        />
                        <InputError :message="form.errors.status" class="mt-2" />
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

                    <div class="md:col-span-2">
                        <InputLabel for="item_description" value="Item Description" />
                        <textarea v-model="form.item_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3"></textarea>
                        <InputError :message="form.errors.item_description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="estimated_cost" value="Estimated Cost" />
                        <CurrencyInput v-model="form.estimated_cost" class="mt-1 block w-full" />
                        <InputError :message="form.errors.estimated_cost" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="actual_cost" value="Actual Cost" />
                        <CurrencyInput v-model="form.actual_cost" class="mt-1 block w-full" />
                        <InputError :message="form.errors.actual_cost" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="award_date" value="Award Date" />
                        <TextInput v-model="form.award_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.award_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="delivery_date" value="Delivery Date" />
                        <TextInput v-model="form.delivery_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.delivery_date" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="remarks" value="Remarks" />
                        <TextInput v-model="form.remarks" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.remarks" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveTransaction">
                    {{ editingTransaction ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Confirm Save Modal -->
        <ConfirmDialog
            :show="confirmingSave"
            type="info"
            title="Confirm Action"
            :content="editingTransaction ? 'Are you sure you want to update this transaction?' : 'Are you sure you want to create this transaction?'"
            :confirmText="editingTransaction ? 'Update' : 'Create'"
            @confirm="proceedSaveTransaction"
            @close="confirmingSave = false"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmDialog
            :show="confirmingTransactionDeletion"
            type="danger"
            title="Delete Transaction"
            content="Are you sure you want to delete this procurement transaction? This action cannot be undone."
            confirmText="Delete"
            @confirm="deleteTransaction"
            @close="confirmingTransactionDeletion = false"
        />
    </AppLayout>
</template>
