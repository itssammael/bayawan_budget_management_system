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
const showingSelectionDialog = ref(false);
const isBulkCreate = ref(false);
const bulkItems = ref([]);

const form = useForm({
    appropriation_id: '',
    transaction_no: '',
    item_description: '',
    ppmp_no: '',
    pr_no: '',
    po_no: '',
    status: 'Planned',
    estimated_cost: 0,
    actual_cost: 0,
    started_at: '',
    completed_at: '',
    department_id: '',
});

const bulkForm = useForm({
    items: [],
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

const getStatusColor = (status) => {
    const s = status?.toLowerCase() || '';
    if (s.includes('completed') || s.includes('delivered')) return 'text-green-600 bg-green-50 border-green-200';
    if (s.includes('awarded')) return 'text-emerald-600 bg-emerald-50 border-emerald-200';
    if (s.includes('on process') || s.includes('ongoing')) return 'text-blue-600 bg-blue-50 border-blue-200';
    if (s.includes('cancelled')) return 'text-red-600 bg-red-50 border-red-200';
    return 'text-gray-600 bg-gray-50 border-gray-200';
};

const openCreateModal = () => {
    showingSelectionDialog.value = true;
};

const selectSingleCreate = () => {
    showingSelectionDialog.value = false;
    isBulkCreate.value = false;
    editingTransaction.value = null;
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }

    managingTransaction.value = true;
};

const selectBulkCreate = () => {
    showingSelectionDialog.value = false;
    isBulkCreate.value = true;
    editingTransaction.value = null;
    bulkItems.value = [];
    form.reset();
    
    const user = usePage().props.auth.user;
    if (user.department && user.department.name !== 'Admin') {
        form.department_id = user.department_id;
    }

    managingTransaction.value = true;
};

const openEditModal = (item) => {
    editingTransaction.value = item;
    isBulkCreate.value = false;
    form.appropriation_id = item.appropriation_id || '';
    form.transaction_no = item.transaction_no || '';
    form.item_description = item.item_description || '';
    form.ppmp_no = item.ppmp_no || '';
    form.pr_no = item.pr_no || '';
    form.po_no = item.po_no || '';
    form.status = item.status || 'Planned';
    form.estimated_cost = Number(item.estimated_cost || 0);
    form.actual_cost = Number(item.actual_cost || 0);
    form.started_at = item.started_at || '';
    form.completed_at = item.completed_at || '';
    form.department_id = item.department_id || '';
    managingTransaction.value = true;
};

const addToBulkList = () => {
    if (!form.appropriation_id || !form.transaction_no || !form.item_description || !form.status) {
        alert('Please fill in all required fields (Appropriation, Transaction No., Item Description, and Status)');
        return;
    }
    
    if (bulkItems.value.some(item => item.transaction_no === form.transaction_no)) {
        alert('A transaction with this Transaction Number is already added to the batch list.');
        return;
    }

    const app = props.appropriations.find(a => a.id === form.appropriation_id);

    const item = {
        appropriation_id: form.appropriation_id,
        appropriation_label: app ? `[${app.fund_source?.name || 'N/A'}] ${app.ppa_description}` : '',
        transaction_no: form.transaction_no,
        item_description: form.item_description,
        ppmp_no: form.ppmp_no || null,
        pr_no: form.pr_no || null,
        po_no: form.po_no || null,
        status: form.status,
        estimated_cost: Number(form.estimated_cost || 0),
        actual_cost: Number(form.actual_cost || 0),
        started_at: form.started_at || null,
        completed_at: form.completed_at || null,
        department_id: form.department_id || null,
        department_name: props.departments.find(d => d.id === form.department_id)?.name || 'N/A',
    };

    bulkItems.value.push(item);

    form.transaction_no = '';
    form.item_description = '';
    form.ppmp_no = '';
    form.pr_no = '';
    form.po_no = '';
    form.status = 'Planned';
    form.estimated_cost = 0;
    form.actual_cost = 0;
    form.started_at = '';
    form.completed_at = '';
};

const removeBulkItem = (index) => {
    bulkItems.value.splice(index, 1);
};

const saveTransaction = () => {
    if (isBulkCreate.value && bulkItems.value.length === 0) {
        alert('Please add at least one transaction to the batch list.');
        return;
    }
    confirmingSave.value = true;
};

const proceedSaveTransaction = () => {
    if (isBulkCreate.value) {
        bulkForm.items = bulkItems.value;
        bulkForm.post(route('budget.procurement.bulk-store'), {
            onSuccess: () => { 
                closeModal(); 
                confirmingSave.value = false; 
            },
        });
    } else {
        if (editingTransaction.value) {
            form.put(route('budget.procurement.update', editingTransaction.value.id), {
                onSuccess: () => { 
                    closeModal(); 
                    confirmingSave.value = false; 
                },
            });
        } else {
            form.post(route('budget.procurement.store'), {
                onSuccess: () => { 
                    closeModal(); 
                    confirmingSave.value = false; 
                },
            });
        }
    }
};

const confirmDeletion = (item) => {
    itemToDelete.value = item;
    confirmingTransactionDeletion.value = true;
};

const deleteTransaction = () => {
    router.delete(route('budget.procurement.destroy', itemToDelete.value.id), {
        onSuccess: () => { 
            confirmingTransactionDeletion.value = false; 
            itemToDelete.value = null; 
        },
    });
};

const closeModal = () => {
    managingTransaction.value = false;
    form.reset();
    editingTransaction.value = null;
    confirmingSave.value = false;
    bulkItems.value = [];
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

const selectedAppropriation = computed(() => {
    return props.appropriations.find(app => app.id === form.appropriation_id) || null;
});

const bulkTotalEstimatedCost = computed(() => {
    return bulkItems.value.reduce((sum, item) => sum + Number(item.estimated_cost || 0), 0);
});
</script>

<template>
    <AppLayout title="Procurement Tracker">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Procurement Tracker
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
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg overflow-x-auto border border-gray-150">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 uppercase text-xs font-semibold text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Year</th>
                                <th class="px-4 py-3 text-left">Trans. No</th>
                                <th class="px-4 py-3 text-left">Item Description</th>
                                <th v-if="showDepartment" class="px-4 py-3 text-left">Dept.</th>
                                <th class="px-4 py-3 text-left">Appropriation (PPA) / Fund</th>
                                <th class="px-4 py-3 text-left">PPMP / PR / PO</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Est. Cost</th>
                                <th class="px-4 py-3 text-right">Act. Cost</th>
                                <th class="px-4 py-3 text-left">Awarded/Started</th>
                                <th class="px-4 py-3 text-left">Delivered/Completed</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-gray-50 transition text-xs">
                                <td class="px-4 py-4 whitespace-nowrap">{{ tx.appropriation?.budget_year?.year || '-' }}</td>
                                <td class="px-4 py-4 font-mono font-semibold text-gray-900 whitespace-nowrap">{{ tx.transaction_no }}</td>
                                <td class="px-4 py-4 max-w-xs whitespace-normal" :title="tx.item_description">{{ tx.item_description }}</td>
                                <td v-if="showDepartment" class="px-4 py-4 whitespace-nowrap">{{ tx.department ? tx.department.name : '-' }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-700 truncate max-w-xs" :title="tx.appropriation?.ppa_description">{{ tx.appropriation?.ppa_description }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ tx.appropriation?.fund_source?.name }}</div>
                                </td>
                                <td class="px-4 py-4 font-mono text-[10px] whitespace-nowrap">
                                    <div v-if="tx.ppmp_no"><span class="text-gray-400">PPMP:</span> {{ tx.ppmp_no }}</div>
                                    <div v-if="tx.pr_no"><span class="text-gray-400">PR:</span> {{ tx.pr_no }}</div>
                                    <div v-if="tx.po_no"><span class="text-gray-400">PO:</span> {{ tx.po_no }}</div>
                                    <div v-if="!tx.ppmp_no && !tx.pr_no && !tx.po_no" class="text-gray-400">-</div>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span :class="['px-2 py-0.5 rounded-full border text-[10px] font-semibold uppercase', getStatusColor(tx.status)]">
                                        {{ tx.status || 'Planned' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-medium text-gray-700 whitespace-nowrap">{{ formatCurrency(tx.estimated_cost) }}</td>
                                <td class="px-4 py-4 text-right font-semibold text-emerald-700 whitespace-nowrap">{{ formatCurrency(tx.actual_cost) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-gray-500">{{ formatDate(tx.started_at) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-gray-500">{{ formatDate(tx.completed_at) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-center space-x-2">
                                    <button @click="openEditModal(tx)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                                    <button @click="confirmDeletion(tx)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="12" class="px-4 py-8 text-center text-gray-500">No procurement transactions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center">
                    <Pagination :links="transactions.links" />
                </div>
            </div>
        </div>

        <!-- Selection Dialog Modal -->
        <DialogModal :show="showingSelectionDialog" @close="showingSelectionDialog = false" max-width="md">
            <template #title>
                Select Creation Mode
            </template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 py-2">
                    <button 
                        @click="selectSingleCreate" 
                        class="flex flex-col items-center p-4 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 rounded-lg transition text-left group"
                    >
                        <span class="font-bold text-gray-800 group-hover:text-indigo-900 text-sm">Single Transaction</span>
                        <span class="text-xs text-gray-500 mt-1">Create a single detailed procurement transaction item.</span>
                    </button>

                    <button 
                        @click="selectBulkCreate" 
                        class="flex flex-col items-center p-4 bg-gray-50 hover:bg-indigo-50 border border-gray-200 hover:border-indigo-300 rounded-lg transition text-left group"
                    >
                        <span class="font-bold text-gray-800 group-hover:text-indigo-900 text-sm">Bulk Batch Creation</span>
                        <span class="text-xs text-gray-500 mt-1">Add multiple items to a list and create them all at once.</span>
                    </button>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showingSelectionDialog = false">Cancel</SecondaryButton>
            </template>
        </DialogModal>

        <!-- Create/Edit Modal -->
        <DialogModal :show="managingTransaction" @close="closeModal" :max-width="isBulkCreate ? '7xl' : '2xl'">
            <template #title>
                <span v-if="isBulkCreate">Bulk Add Procurement Transactions</span>
                <span v-else>{{ editingTransaction ? 'Edit Procurement Transaction' : 'Add New Procurement Transaction' }}</span>
            </template>

            <template #content>
                <!-- Bulk Create Layout -->
                <div v-if="isBulkCreate" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Form Input Block -->
                    <div class="lg:col-span-5 border-r border-gray-200 pr-4 space-y-4">
                        <div>
                            <InputLabel for="appropriation" value="Link to Appropriation" />
                            <SearchableSelect 
                                v-model="form.appropriation_id" 
                                :options="searchableAppropriations" 
                                label="display_label"
                                placeholder="Select Appropriation (PPA)"
                                :error="form.errors.appropriation_id"
                            />
                        </div>

                        <!-- Derived Appropriation details -->
                        <div v-if="selectedAppropriation" class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-950 mt-1">
                            <h4 class="font-bold text-indigo-900 mb-1.5 uppercase tracking-wide">Appropriation Details</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div><span class="text-indigo-600 block">Fund Source:</span> <span class="font-semibold">{{ selectedAppropriation.fund_source?.name || 'N/A' }}</span></div>
                                <div><span class="text-indigo-600 block">Budget Year:</span> <span class="font-semibold">{{ selectedAppropriation.budget_year?.year || 'N/A' }}</span></div>
                                <div><span class="text-indigo-600 block">Allotment:</span> <span class="font-semibold text-emerald-700">{{ formatCurrency(selectedAppropriation.allotment) }}</span></div>
                                <div><span class="text-indigo-600 block">Obligation:</span> <span class="font-semibold text-red-600">{{ formatCurrency(selectedAppropriation.obligation) }}</span></div>
                                <div class="col-span-2 border-t border-indigo-200 pt-1 mt-1"><span class="text-indigo-600 block">Available Balance:</span> <span class="font-semibold text-indigo-900">{{ formatCurrency(selectedAppropriation.allotment - selectedAppropriation.obligation) }}</span></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="transaction_no" value="Transaction No." />
                                <TextInput v-model="form.transaction_no" type="text" class="mt-1 block w-full text-xs" placeholder="e.g. 2026-DRRM-001" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Status" />
                                <SearchableSelect 
                                    v-model="form.status" 
                                    :options="statusOptions" 
                                    placeholder="Select Status"
                                    class="text-xs"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <InputLabel for="ppmp_no" value="PPMP No." />
                                <TextInput v-model="form.ppmp_no" type="text" class="mt-1 block w-full text-xs" placeholder="PPMP-2026-X" />
                            </div>
                            <div>
                                <InputLabel for="pr_no" value="PR No." />
                                <TextInput v-model="form.pr_no" type="text" class="mt-1 block w-full text-xs" placeholder="100-26-XX-XX" />
                            </div>
                            <div>
                                <InputLabel for="po_no" value="PO No." />
                                <TextInput v-model="form.po_no" type="text" class="mt-1 block w-full text-xs" placeholder="PO-26-XX-XX" />
                            </div>
                        </div>

                        <div v-if="showDepartment">
                            <InputLabel for="department_id" value="Department" />
                            <SearchableSelect 
                                v-model="form.department_id" 
                                :options="departments" 
                                placeholder="Select Department"
                            />
                        </div>

                        <div>
                            <InputLabel for="item_description" value="Item Description" />
                            <textarea v-model="form.item_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs" rows="2" placeholder="Item description/activity details..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="estimated_cost" value="Estimated Cost" />
                                <CurrencyInput v-model="form.estimated_cost" class="mt-1 block w-full text-xs" />
                            </div>

                            <div>
                                <InputLabel for="actual_cost" value="Actual Cost" />
                                <CurrencyInput v-model="form.actual_cost" class="mt-1 block w-full text-xs" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="started_at" value="Awarded/Started At" />
                                <TextInput v-model="form.started_at" type="date" class="mt-1 block w-full text-xs" />
                            </div>

                            <div>
                                <InputLabel for="completed_at" value="Delivered/Completed At" />
                                <TextInput v-model="form.completed_at" type="date" class="mt-1 block w-full text-xs" />
                            </div>
                        </div>

                        <div class="pt-2">
                            <SecondaryButton @click="addToBulkList" class="w-full justify-center">
                                Add to Batch List
                            </SecondaryButton>
                        </div>
                    </div>

                    <!-- Batch List Column -->
                    <div class="lg:col-span-7 flex flex-col h-[550px]">
                        <div class="font-semibold text-sm text-gray-700 mb-2 flex justify-between items-center">
                            <span>Batch List Items</span>
                            <span class="text-xs font-normal text-gray-500">Total Estimated: <span class="font-semibold text-indigo-600">{{ formatCurrency(bulkTotalEstimatedCost) }}</span></span>
                        </div>
                        <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg bg-gray-50 p-2">
                            <div v-for="(item, idx) in bulkItems" :key="idx" class="bg-white border border-gray-150 rounded-lg p-3 mb-2 shadow-sm flex justify-between items-start gap-4">
                                <div class="text-xs space-y-1 flex-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold font-mono text-gray-800">{{ item.transaction_no }}</span>
                                        <span :class="['px-1.5 py-0.5 rounded border text-[9px] font-semibold uppercase', getStatusColor(item.status)]">{{ item.status }}</span>
                                    </div>
                                    <div class="font-medium text-gray-700 truncate max-w-sm" :title="item.item_description">{{ item.item_description }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono truncate max-w-xs">{{ item.appropriation_label }}</div>
                                    <div class="flex space-x-4 pt-1 font-mono text-[10px]">
                                        <span v-if="item.pr_no"><span class="text-gray-400">PR:</span> {{ item.pr_no }}</span>
                                        <span><span class="text-gray-400">Est:</span> {{ formatCurrency(item.estimated_cost) }}</span>
                                    </div>
                                </div>
                                <button @click="removeBulkItem(idx)" class="text-red-500 hover:text-red-700 font-semibold text-xs py-1">Remove</button>
                            </div>
                            <div v-if="bulkItems.length === 0" class="h-full flex items-center justify-center text-gray-400 text-xs">
                                No items added yet. Fill in the form on the left and click "Add to Batch List".
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Single Create / Edit Layout -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                    <!-- Derived Appropriation details -->
                    <div v-if="selectedAppropriation" class="md:col-span-2 bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-xs text-indigo-950 mt-1">
                        <h4 class="font-bold text-indigo-900 mb-1.5 uppercase tracking-wide">Appropriation Details</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <div><span class="text-indigo-600 block">Fund Source:</span> <span class="font-semibold">{{ selectedAppropriation.fund_source?.name || 'N/A' }}</span></div>
                            <div><span class="text-indigo-600 block">Budget Year:</span> <span class="font-semibold">{{ selectedAppropriation.budget_year?.year || 'N/A' }}</span></div>
                            <div><span class="text-indigo-600 block">Allotment:</span> <span class="font-semibold text-emerald-700">{{ formatCurrency(selectedAppropriation.allotment) }}</span></div>
                            <div><span class="text-indigo-600 block">Obligation:</span> <span class="font-semibold text-red-600">{{ formatCurrency(selectedAppropriation.obligation) }}</span></div>
                        </div>
                        <div class="border-t border-indigo-200 pt-1 mt-1.5"><span class="text-indigo-600 block">Available Balance:</span> <span class="font-semibold text-indigo-900">{{ formatCurrency(selectedAppropriation.allotment - selectedAppropriation.obligation) }}</span></div>
                    </div>

                    <div>
                        <InputLabel for="transaction_no" value="Transaction No." />
                        <TextInput v-model="form.transaction_no" type="text" class="mt-1 block w-full" placeholder="e.g. 2026-DRRM-001" />
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

                    <div>
                        <InputLabel for="ppmp_no" value="PPMP No." />
                        <TextInput v-model="form.ppmp_no" type="text" class="mt-1 block w-full" placeholder="PPMP-2026-X" />
                        <InputError :message="form.errors.ppmp_no" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="pr_no" value="PR No." />
                        <TextInput v-model="form.pr_no" type="text" class="mt-1 block w-full" placeholder="100-26-XX-XX" />
                        <InputError :message="form.errors.pr_no" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="po_no" value="PO No." />
                        <TextInput v-model="form.po_no" type="text" class="mt-1 block w-full" placeholder="PO-26-XX-XX" />
                        <InputError :message="form.errors.po_no" class="mt-2" />
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

                    <div class="md:col-span-2">
                        <InputLabel for="item_description" value="Item Description" />
                        <textarea v-model="form.item_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3" placeholder="Detailed item description..."></textarea>
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
                        <InputLabel for="started_at" value="Awarded/Started At" />
                        <TextInput v-model="form.started_at" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.started_at" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="completed_at" value="Delivered/Completed At" />
                        <TextInput v-model="form.completed_at" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.completed_at" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton 
                    class="ml-3" 
                    :class="{ 'opacity-25': form.processing || bulkForm.processing }" 
                    :disabled="form.processing || bulkForm.processing" 
                    @click="saveTransaction"
                >
                    <span v-if="isBulkCreate">Submit Batch ({{ bulkItems.length }})</span>
                    <span v-else>{{ editingTransaction ? 'Update' : 'Create' }}</span>
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Confirm Save Modal -->
        <ConfirmDialog
            :show="confirmingSave"
            type="info"
            title="Confirm Action"
            :content="isBulkCreate ? `Are you sure you want to create these ${bulkItems.length} transactions?` : (editingTransaction ? 'Are you sure you want to update this transaction?' : 'Are you sure you want to create this transaction?')"
            :confirmText="isBulkCreate ? 'Submit Batch' : (editingTransaction ? 'Update' : 'Create')"
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
