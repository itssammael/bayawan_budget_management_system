<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    active_tab: String,
    fund_sources: Object,
    budget_years: Object,
    budget_categories: Object,
    activity_logs: Object,
    system_settings: Object,
    filters: Object,
});

const activeTab = ref(props.active_tab || 'fund_sources');
const searchCategory = ref(props.filters?.search_category || '');

watch(searchCategory, (value) => {
    router.get(route('settings.index'), { tab: activeTab.value, search_category: value }, {
        preserveState: true,
        replace: true
    });
});

const activeTabLabel = ref('');

// ... (logic for activeTabLabel could be added for better header display)

// Activity Log Details
const showingLogDetails = ref(false);
const selectedLog = ref(null);

const showLogDetails = (log) => {
    selectedLog.value = log;
    showingLogDetails.value = true;
};

watch(() => props.active_tab, (newTab) => {
    if (newTab) {
        activeTab.value = newTab;
    }
});

const switchTab = (tabId) => {
    activeTab.value = tabId;
    router.get(route('settings.index'), { tab: tabId }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Modals
const managingFundSource = ref(false);
const editingFundSource = ref(null);
const managingBudgetYear = ref(false);
const editingBudgetYear = ref(null);
const managingCategory = ref(false);
const editingCategory = ref(null);
const confirmingDeletion = ref(false);
const itemToDelete = ref(null);
const deleteType = ref(null); // 'fund_source', 'budget_year', 'category'

// Forms
const fundSourceForm = useForm({
    name: '',
    description: '',
});

const budgetYearForm = useForm({
    year: new Date().getFullYear(),
});

const categoryForm = useForm({
    name: '',
});

const appearanceForm = useForm({
    logo: null,
    icon: null,
    header_color: props.system_settings?.header_color || '#ffffff',
    header_text_color: props.system_settings?.header_text_color || '#374151',
    bg_color: props.system_settings?.bg_color || '#f3f4f6',
    accent_color: props.system_settings?.accent_color || '#4f46e5',
});

const appearancePreview = ref({
    logo: props.system_settings?.system_logo || null,
    icon: props.system_settings?.system_icon || null,
});

const onFileChange = (e, field) => {
    const file = e.target.files[0];
    if (file) {
        appearanceForm[field] = file;
        appearancePreview.value[field] = URL.createObjectURL(file);
    }
};

const saveAppearance = () => {
    appearanceForm.post(route('settings.appearance.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification or logic if needed
        }
    });
};

// Logic for Fund Sources
const openCreateFundSource = () => {
    editingFundSource.value = null;
    fundSourceForm.reset();
    managingFundSource.value = true;
};
const openEditFundSource = (item) => {
    editingFundSource.value = item;
    fundSourceForm.name = item.name;
    fundSourceForm.description = item.description;
    managingFundSource.value = true;
};
const saveFundSource = () => {
    if (editingFundSource.value) {
        fundSourceForm.put(route('settings.fund-sources.update', editingFundSource.value.id), { onSuccess: () => closeModal() });
    } else {
        fundSourceForm.post(route('settings.fund-sources.store'), { onSuccess: () => closeModal() });
    }
};

// Logic for Budget Years
const openCreateBudgetYear = () => {
    editingBudgetYear.value = null;
    budgetYearForm.reset();
    managingBudgetYear.value = true;
};
const openEditBudgetYear = (item) => {
    editingBudgetYear.value = item;
    budgetYearForm.year = item.year;
    managingBudgetYear.value = true;
};
const saveBudgetYear = () => {
    if (editingBudgetYear.value) {
        budgetYearForm.put(route('settings.budget-years.update', editingBudgetYear.value.id), { onSuccess: () => closeModal() });
    } else {
        budgetYearForm.post(route('settings.budget-years.store'), { onSuccess: () => closeModal() });
    }
};

// Logic for Categories
const openCreateCategory = () => {
    editingCategory.value = null;
    categoryForm.reset();
    managingCategory.value = true;
};
const openEditCategory = (item) => {
    editingCategory.value = item;
    categoryForm.name = item.name;
    managingCategory.value = true;
};
const saveCategory = () => {
    if (editingCategory.value) {
        categoryForm.put(route('settings.budget-categories.update', editingCategory.value.id), { onSuccess: () => closeModal() });
    } else {
        categoryForm.post(route('settings.budget-categories.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (item, type) => {
    itemToDelete.value = item;
    deleteType.value = type;
    confirmingDeletion.value = true;
};

const performDelete = () => {
    const routes = {
        fund_source: 'settings.fund-sources.destroy',
        budget_year: 'settings.budget-years.destroy',
        category: 'settings.budget-categories.destroy',
    };
    router.delete(route(routes[deleteType.value], itemToDelete.value.id), {
        onSuccess: () => (confirmingDeletion.value = false),
    });
};

const closeModal = () => {
    managingFundSource.value = false;
    managingBudgetYear.value = false;
    managingCategory.value = false;
    fundSourceForm.reset();
    budgetYearForm.reset();
    categoryForm.reset();
    editingFundSource.value = null;
    editingBudgetYear.value = null;
    editingCategory.value = null;
};
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Application Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg flex min-h-[60vh]">
                    <!-- Sidebar Tabs -->
                    <div class="w-64 border-r border-gray-200 bg-gray-50 py-6">
                        <nav class="space-y-1">
                            <button
                                v-for="tab in [
                                    {id:'fund_sources', label:'Fund Sources'},
                                    {id:'budget_years', label:'Budget Years'},
                                    {id:'categories', label:'Appropriation Categories'},
                                    {id:'appearance', label:'System Appearance'},
                                    {id:'user_logs', label:'User Logs'}
                                ]"
                                :key="tab.id"
                                @click="switchTab(tab.id)"
                                :class="[activeTab === tab.id ? 'bg-[var(--accent-color-light)] border-[var(--accent-color)] text-[var(--accent-color-dark)]' : 'border-transparent text-gray-600 hover:bg-gray-100', 'group flex items-center px-6 py-3 text-sm font-medium border-l-4 w-full text-left transition']"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- Content Area -->
                    <div class="flex-1 p-8">
                        <!-- Fund Sources Tab -->
                        <div v-if="activeTab === 'fund_sources'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Manage Fund Sources</h3>
                                <PrimaryButton @click="openCreateFundSource">Add Fund Source</PrimaryButton>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 uppercase text-[10px] font-semibold text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Name</th>
                                        <th class="px-6 py-3 text-left">Description</th>
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-xs">
                                    <tr v-for="fs in fund_sources.data" :key="fs.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium">{{ fs.name }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ fs.description || 'N/A' }}</td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditFundSource(fs)" class="text-[var(--accent-color-dark)] hover:text-[var(--accent-color)] font-semibold transition">Edit</button>
                                            <button @click="confirmDelete(fs, 'fund_source')" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-6">
                                <Pagination :links="fund_sources.links" />
                            </div>
                        </div>

                        <!-- Budget Years Tab -->
                        <div v-if="activeTab === 'budget_years'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">Manage Budget Years</h3>
                                <PrimaryButton @click="openCreateBudgetYear">Add Year</PrimaryButton>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 uppercase text-[10px] font-semibold text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Year</th>
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-xs">
                                    <tr v-for="by in budget_years.data" :key="by.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium">{{ by.year }}</td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditBudgetYear(by)" class="text-[var(--accent-color)] hover:opacity-80 font-semibold">Edit</button>
                                            <button @click="confirmDelete(by, 'budget_year')" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-6">
                                <Pagination :links="budget_years.links" />
                            </div>
                        </div>

                        <!-- Categories Tab -->
                        <div v-if="activeTab === 'categories'">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
                                <h3 class="text-lg font-medium text-gray-900">Manage Appropriation Categories</h3>
                                <div class="flex space-x-3 w-full sm:w-auto">
                                    <input
                                        v-model="searchCategory"
                                        type="search"
                                        placeholder="Search Categories..."
                                        class="border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)] rounded-md shadow-sm text-sm w-full sm:w-64"
                                    >
                                    <PrimaryButton @click="openCreateCategory" class="whitespace-nowrap">Add Category</PrimaryButton>
                                </div>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 uppercase text-[10px] font-semibold text-gray-600">
                                    <tr>
                                        <th class="px-6 py-3 text-left">Category Name</th>
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 text-xs text-xs">
                                    <tr v-for="cat in budget_categories.data" :key="cat.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium">{{ cat.name }}</td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditCategory(cat)" class="text-[var(--accent-color-dark)] hover:text-[var(--accent-color)] font-semibold transition">Edit</button>
                                            <button @click="confirmDelete(cat, 'category')" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-6">
                                <Pagination :links="budget_categories.links" />
                            </div>
                        </div>

                        <!-- System Appearance Tab -->
                        <div v-if="activeTab === 'appearance'">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900">System Appearance</h3>
                                <p class="text-sm text-gray-500">Customize the system logo and icon.</p>
                            </div>

                            <form @submit.prevent="saveAppearance" class="space-y-8 max-w-2xl">
                                <!-- Logo Selection -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-bold text-gray-700 uppercase mb-4">System Logo</h4>
                                    <div class="flex items-start space-x-6">
                                        <div class="flex-shrink-0">
                                            <div class="h-24 w-48 bg-white border border-gray-300 rounded flex items-center justify-center overflow-hidden">
                                                <img v-if="appearancePreview.logo" :src="appearancePreview.logo" class="max-h-full max-w-full object-contain" />
                                                <span v-else class="text-gray-400 text-xs">No Logo</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <input 
                                                type="file" 
                                                @change="(e) => onFileChange(e, 'logo')" 
                                                class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-[var(--accent-color-light)] file:text-[var(--accent-color-dark)] hover:file:bg-opacity-20 transition cursor-pointer"
                                                accept="image/*"
                                            />
                                            <p class="mt-2 text-xs text-gray-400">Recommended: PNG or SVG with transparent background (Max 2MB).</p>
                                            <InputError :message="appearanceForm.errors.logo" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Icon Selection -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-bold text-gray-700 uppercase mb-4">Favicon / System Icon</h4>
                                    <div class="flex items-start space-x-6">
                                        <div class="flex-shrink-0">
                                            <div class="h-16 w-16 bg-white border border-gray-300 rounded flex items-center justify-center overflow-hidden">
                                                <img v-if="appearancePreview.icon" :src="appearancePreview.icon" class="max-h-full max-w-full object-contain" />
                                                <span v-else class="text-gray-400 text-xs text-center px-1">No Icon</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <input 
                                                type="file" 
                                                @change="(e) => onFileChange(e, 'icon')" 
                                                class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-[var(--accent-color-light)] file:text-[var(--accent-color-dark)] hover:file:bg-opacity-20 transition cursor-pointer"
                                                accept="image/*"
                                            />
                                            <p class="mt-2 text-xs text-gray-400">Recommended: Square PNG or ICO (32x32 or 64x64) (Max 1MB).</p>
                                            <InputError :message="appearanceForm.errors.icon" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Theme Colors Selection -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-bold text-gray-700 uppercase mb-4">Theme Colors</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Header Color -->
                                        <div>
                                            <InputLabel for="header_color" value="Header Color" />
                                            <div class="mt-2 flex items-center space-x-3">
                                                <input 
                                                    id="header_color"
                                                    type="color" 
                                                    v-model="appearanceForm.header_color"
                                                    class="h-10 w-20 p-1 border border-gray-300 rounded cursor-pointer"
                                                />
                                                <span class="text-sm font-mono uppercase text-gray-600">{{ appearanceForm.header_color }}</span>
                                            </div>
                                            <InputError :message="appearanceForm.errors.header_color" class="mt-2" />
                                        </div>

                                        <!-- Header Text Color -->
                                        <div>
                                            <InputLabel for="header_text_color" value="Header Text Color" />
                                            <div class="mt-2 flex items-center space-x-3">
                                                <input 
                                                    id="header_text_color"
                                                    type="color" 
                                                    v-model="appearanceForm.header_text_color"
                                                    class="h-10 w-20 p-1 border border-gray-300 rounded cursor-pointer"
                                                />
                                                <span class="text-sm font-mono uppercase text-gray-600">{{ appearanceForm.header_text_color }}</span>
                                            </div>
                                            <InputError :message="appearanceForm.errors.header_text_color" class="mt-2" />
                                        </div>

                                        <!-- Background Color -->
                                        <div>
                                            <InputLabel for="bg_color" value="Background Color" />
                                            <div class="mt-2 flex items-center space-x-3">
                                                <input 
                                                    id="bg_color"
                                                    type="color" 
                                                    v-model="appearanceForm.bg_color"
                                                    class="h-10 w-20 p-1 border border-gray-300 rounded cursor-pointer"
                                                />
                                                <span class="text-sm font-mono uppercase text-gray-600">{{ appearanceForm.bg_color }}</span>
                                            </div>
                                            <InputError :message="appearanceForm.errors.bg_color" class="mt-2" />
                                        </div>

                                        <!-- Accent Color -->
                                        <div>
                                            <InputLabel for="accent_color" value="Accent Color" />
                                            <div class="mt-2 flex items-center space-x-3">
                                                <input 
                                                    id="accent_color"
                                                    type="color" 
                                                    v-model="appearanceForm.accent_color"
                                                    class="h-10 w-20 p-1 border border-gray-300 rounded cursor-pointer"
                                                />
                                                <span class="text-sm font-mono uppercase text-gray-600">{{ appearanceForm.accent_color }}</span>
                                            </div>
                                            <InputError :message="appearanceForm.errors.accent_color" class="mt-2" />
                                        </div>
                                    </div>
                                    <p class="mt-4 text-xs text-gray-400 italic">Note: These colors will be applied throughout the system. Ensure contrast is maintained for readability.</p>
                                </div>

                                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-100">
                                    <div v-if="appearanceForm.recentlySuccessful" class="text-sm text-green-600 font-medium">
                                        Saved successfully.
                                    </div>
                                    <PrimaryButton :disabled="appearanceForm.processing">
                                        Update Appearance
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                        <!-- User Logs Tab -->
                        <div v-if="activeTab === 'user_logs'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900">User Activity Logs</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 uppercase text-[10px] font-semibold text-gray-600">
                                        <tr>
                                            <th class="px-6 py-3 text-left">Date & Time</th>
                                            <th class="px-6 py-3 text-left">User</th>
                                            <th class="px-6 py-3 text-left">Action</th>
                                            <th class="px-6 py-3 text-left">Description</th>
                                            <th class="px-6 py-3 text-right">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200 text-xs">
                                        <tr v-for="log in activity_logs.data" :key="log.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ new Date(log.created_at).toLocaleString() }}</td>
                                            <td class="px-6 py-4">{{ log.user ? log.user.name : 'System' }}</td>
                                            <td class="px-6 py-4">
                                                <span :class="[
                                                    log.event === 'created' ? 'bg-green-100 text-green-800' : 
                                                    log.event === 'updated' ? 'bg-blue-100 text-blue-800' : 
                                                    log.event === 'deleted' ? 'bg-red-100 text-red-800' : 
                                                    'bg-gray-100 text-gray-800',
                                                    'px-2 py-1 rounded-full text-[10px] font-bold uppercase'
                                                ]">
                                                    {{ log.event }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">{{ log.description }}</td>
                                            <td class="px-6 py-4 text-right">
                                                <button @click="showLogDetails(log)" class="text-[var(--accent-color-dark)] hover:text-[var(--accent-color)] font-semibold transition">View</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                <Pagination :links="activity_logs.links" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Log Details Modal -->
        <DialogModal :show="showingLogDetails" @close="showingLogDetails = false">
            <template #title>Activity Details</template>
            <template #content v-if="selectedLog">
                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                        <div>
                            <span class="block text-gray-500 text-[10px] font-bold uppercase">Timestamp</span>
                            <span>{{ new Date(selectedLog.created_at).toLocaleString() }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-[10px] font-bold uppercase">IP Address</span>
                            <span>{{ selectedLog.ip_address || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-[10px] font-bold uppercase">Action</span>
                            <span class="capitalize">{{ selectedLog.event }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-[10px] font-bold uppercase">Model</span>
                            <span>{{ selectedLog.log_name.toUpperCase() }} #{{ selectedLog.subject_id }}</span>
                        </div>
                    </div>

                    <div v-if="selectedLog.properties && selectedLog.properties.attributes" class="mt-4">
                        <span class="block text-gray-500 text-[10px] font-bold uppercase mb-2">Data Changes</span>
                        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-xs overflow-x-auto">
                            <div v-if="selectedLog.properties.old" class="mb-4">
                                <div class="text-red-400 mb-1 font-bold">// PREVIOUS STATE</div>
                                <pre>{{ JSON.stringify(selectedLog.properties.old, null, 2) }}</pre>
                            </div>
                            <div>
                                <div class="text-green-400 mb-1 font-bold">// NEW STATE</div>
                                <pre>{{ JSON.stringify(selectedLog.properties.attributes, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10px] text-gray-400 font-mono break-all mt-4 border-t pt-2">
                        {{ selectedLog.user_agent }}
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showingLogDetails = false">Close</SecondaryButton>
            </template>
        </DialogModal>

        <!-- Fund Source Modal -->
        <DialogModal :show="managingFundSource" @close="closeModal">
            <template #title>{{ editingFundSource ? 'Edit Fund Source' : 'Add New Fund Source' }}</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Fund Source Name" />
                        <TextInput v-model="fundSourceForm.name" type="text" class="mt-1 block w-full" />
                        <InputError :message="fundSourceForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Description" />
                        <textarea v-model="fundSourceForm.description" class="w-full mt-1 border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)] rounded-md shadow-sm" rows="3"></textarea>
                        <InputError :message="fundSourceForm.errors.description" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :disabled="fundSourceForm.processing" @click="saveFundSource">Save</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Budget Year Modal -->
        <DialogModal :show="managingBudgetYear" @close="closeModal">
            <template #title>{{ editingBudgetYear ? 'Edit Budget Year' : 'Add New Budget Year' }}</template>
            <template #content>
                <div>
                    <InputLabel value="Year" />
                    <TextInput v-model="budgetYearForm.year" type="number" class="mt-1 block w-full" />
                    <InputError :message="budgetYearForm.errors.year" class="mt-2" />
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :disabled="budgetYearForm.processing" @click="saveBudgetYear">Save</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Category Modal -->
        <DialogModal :show="managingCategory" @close="closeModal">
            <template #title>{{ editingCategory ? 'Edit Category' : 'Add New Category' }}</template>
            <template #content>
                <div>
                    <InputLabel value="Category Name" />
                    <TextInput v-model="categoryForm.name" type="text" class="mt-1 block w-full" />
                    <InputError :message="categoryForm.errors.name" class="mt-2" />
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :disabled="categoryForm.processing" @click="saveCategory">Save</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Confirmation -->
        <DialogModal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <template #title>Confirm Deletion</template>
            <template #content>Are you sure you want to delete this item? This action cannot be undone.</template>
            <template #footer>
                <SecondaryButton @click="confirmingDeletion = false">Cancel</SecondaryButton>
                <button class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-900 focus:outline-none focus:shadow-outline-red disabled:opacity-25 transition" @click="performDelete">
                    Delete
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
