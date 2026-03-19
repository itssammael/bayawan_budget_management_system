<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    years: Array,
    summary: Array,
    mooe_data: Array,
    co_data: Array,
});

import MooeChart from '@/Components/Charts/MooeChart.vue';
import CapitalOutlayChart from '@/Components/Charts/CapitalOutlayChart.vue';
import BudgetOverviewChart from '@/Components/Charts/BudgetOverviewChart.vue';

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const totalAppropriation = computed(() => props.summary.reduce((acc, item) => acc + item.total_appropriation, 0));
const totalAllotment = computed(() => props.summary.reduce((acc, item) => acc + item.total_allotment, 0));
const totalObligation = computed(() => props.summary.reduce((acc, item) => acc + item.total_obligation, 0));
const totalBalance = computed(() => props.summary.reduce((acc, item) => acc + item.total_balance, 0));

const overviewData = computed(() => [
    { category: 'Total Appropriation', value: totalAppropriation.value },
    { category: 'Total Allotment', value: totalAllotment.value },
    { category: 'Total Obligation', value: totalObligation.value },
    { category: 'Overall Balance', value: totalBalance.value },
]);

const consumptionRate = computed(() => {
    if (totalAllotment.value === 0) return 0;
    return (totalObligation.value / totalAllotment.value) * 100;
});

</script>

<template>
    <AppLayout title="Budget Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Budget Consumption & Accomplishment Monitoring
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-9xl mx-auto sm:px-6 lg:px-8">
                <!-- Top Section: Summary & Main Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                    <!-- Summary Cards -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-blue-500">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Appropriation</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ formatCurrency(totalAppropriation) }}</div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-green-500">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Allotment</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ formatCurrency(totalAllotment) }}</div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-yellow-500">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Obligation</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ formatCurrency(totalObligation) }}</div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-red-500">
                            <div class="text-sm font-medium text-gray-500 truncate">Overall Balance</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ formatCurrency(totalBalance) }}</div>
                        </div>
                    </div>

                    <!-- Main Overview Chart -->
                    <div class="lg:col-span-2">
                        <BudgetOverviewChart :data="overviewData" />
                    </div>
                </div>

                <!-- Charts Section (Sub-categories) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <MooeChart :data="mooe_data" />
                    <CapitalOutlayChart :data="co_data" />
                </div>
                
                <!-- Main Summary Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Fund Source Summary</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fund Source</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Appropriation</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Allotment</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Obligation</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in summary" :key="item.fund_source">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.fund_source }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ formatCurrency(item.total_appropriation) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ formatCurrency(item.total_allotment) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">{{ formatCurrency(item.total_obligation) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 font-semibold">{{ formatCurrency(item.total_balance) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 max-w-[100px] mx-auto">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: (item.total_obligation / item.total_allotment * 100) + '%' }"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ (item.total_obligation / item.total_allotment * 100).toFixed(1) }}%</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <Link :href="route('budget.appropriations')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:shadow-outline-gray disabled:opacity-25 transition">
                        View Detailed Appropriations
                    </Link>
                    <Link :href="route('budget.procurement')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:shadow-outline-indigo disabled:opacity-25 transition">
                        Procurement Tracker
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
