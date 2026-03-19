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

const props = defineProps({
    items: Object,
    filters: Object,
    fund_sources: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const confirmingItemDeletion = ref(false);
const itemToDelete = ref(null);
const managingItem = ref(false);
const editingItem = ref(null);

const form = useForm({
    fund_source_id: '',
    aip_reference_code: '',
    ppa_description: '',
    department_id: '',
    start_date: '',
    end_date: '',
    expected_outputs: '',
    amount_ps: 0,
    amount_mooe: 0,
    amount_fe: 0,
    amount_co: 0,
});

watch(search, (value) => {
    router.get(route('budget.aip'), { search: value }, {
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
    form.fund_source_id = item.fund_source_id;
    form.aip_reference_code = item.aip_reference_code;
    form.ppa_description = item.ppa_description;
    form.department_id = item.department_id;
    form.start_date = item.start_date;
    form.end_date = item.end_date;
    form.expected_outputs = item.expected_outputs;
    form.amount_ps = item.amount_ps;
    form.amount_mooe = item.amount_mooe;
    form.amount_fe = item.amount_fe;
    form.amount_co = item.amount_co;
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
                                <th class="px-3 py-3 text-left">Dates</th>
                                <th class="px-3 py-3 text-right">PS</th>
                                <th class="px-3 py-3 text-right">MOOE</th>
                                <th class="px-3 py-3 text-right">FE</th>
                                <th class="px-3 py-3 text-right">CO</th>
                                <th class="px-3 py-3 text-right">Total</th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-[10px]">
                            <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50 transition">
                                <td class="px-3 py-4 font-semibold text-gray-900">{{ item.aip_reference_code }}</td>
                                <td class="px-3 py-4 max-w-xs whitespace-normal">{{ item.ppa_description }}</td>
                                <td v-if="showDepartment" class="px-3 py-4">{{ item.department ? item.department.name : '-' }}</td>
                                <td class="px-3 py-4 whitespace-nowrap text-gray-500">
                                    {{ formatDate(item.start_date) }} -<br>
                                    {{ formatDate(item.end_date) }}
                                </td>
                                <td class="px-3 py-4 text-right">{{ formatCurrency(item.amount_ps) }}</td>
                                <td class="px-3 py-4 text-right">{{ formatCurrency(item.amount_mooe) }}</td>
                                <td class="px-3 py-4 text-right">{{ formatCurrency(item.amount_fe) }}</td>
                                <td class="px-3 py-4 text-right">{{ formatCurrency(item.amount_co) }}</td>
                                <td class="px-3 py-4 text-right font-bold text-blue-600">
                                    {{ formatCurrency(Number(item.amount_ps) + Number(item.amount_mooe) + Number(item.amount_fe) + Number(item.amount_co)) }}
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
                        <InputLabel for="fund_source" value="Fund Source" />
                        <select v-model="form.fund_source_id" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Select Fund Source</option>
                            <option v-for="fs in fund_sources" :key="fs.id" :value="fs.id">{{ fs.name }}</option>
                        </select>
                        <InputError :message="form.errors.fund_source_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="aip_reference_code" value="AIP Reference Code" />
                        <TextInput v-model="form.aip_reference_code" type="text" class="mt-1 block w-full" placeholder="e.g. 1000-1-01-011" />
                        <InputError :message="form.errors.aip_reference_code" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="ppa_description" value="PPA Description" />
                        <textarea v-model="form.ppa_description" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2"></textarea>
                        <InputError :message="form.errors.ppa_description" class="mt-2" />
                    </div>

                    <div v-if="showDepartment" class="md:col-span-2">
                        <InputLabel for="department_id" value="Department" />
                        <select v-model="form.department_id" class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                        </select>
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

                    <div>
                        <InputLabel for="amount_ps" value="Amount (PS)" />
                        <CurrencyInput v-model="form.amount_ps" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount_ps" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="amount_mooe" value="Amount (MOOE)" />
                        <CurrencyInput v-model="form.amount_mooe" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount_mooe" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="amount_fe" value="Amount (FE)" />
                        <CurrencyInput v-model="form.amount_fe" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount_fe" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="amount_co" value="Amount (CO)" />
                        <CurrencyInput v-model="form.amount_co" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount_co" class="mt-2" />
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
