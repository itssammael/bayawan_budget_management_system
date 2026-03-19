<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5percent from '@amcharts/amcharts5/percent';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps({
    data: {
        type: Array,
        required: true
    }
});

const chartDiv = ref(null);
let root = null;
let series = null;

const createChart = () => {
    if (root) {
        root.dispose();
    }

    let root_internal = am5.Root.new(chartDiv.value);
    root = root_internal;

    root_internal.setThemes([
        am5themes_Animated.new(root_internal)
    ]);

    let chart = root_internal.container.children.push(
        am5percent.PieChart.new(root_internal, {
            innerRadius: am5.percent(50),
            layout: root_internal.verticalLayout
        })
    );

    series = chart.series.push(
        am5percent.PieSeries.new(root_internal, {
            name: "Series",
            valueField: "value",
            categoryField: "category",
            alignLabels: true
        })
    );

    series.get("colors").set("colors", [
        am5.color(0x3B82F6), // Blue-500 (Appropriation)
        am5.color(0x10B981), // Green-500 (Allotment)
        am5.color(0xF59E0B), // Yellow-500 (Obligation)
        am5.color(0xEF4444)  // Red-500 (Balance)
    ]);

    series.labels.template.setAll({
        radius: 10,
        fontSize: 12,
        text: "{category}: {valuePercentTotal.formatNumber('0.00')}%"
    });

    series.ticks.template.setAll({
        forceHidden: false
    });

    series.slices.template.setAll({
        tooltipText: "[bold]{category}[/]\nAmount: [bold]P{value.formatNumber('#,###.00')}[/]\nPercentage: [bold]{valuePercentTotal.formatNumber('0.00')}%[/]",
        strokeOpacity: 0
    });

    series.data.setAll(props.data);

    // Add legend
    let legend = chart.children.push(am5.Legend.new(root_internal, {
        centerX: am5.p50,
        x: am5.p50,
        marginTop: 15,
        marginBottom: 15
    }));

    legend.data.setAll(series.dataItems);

    series.appear(1000, 100);
    chart.appear(1000, 100);
};

onMounted(() => {
    createChart();
});

watch(() => props.data, () => {
    if (series) {
        series.data.setAll(props.data);
    }
}, { deep: true });

onUnmounted(() => {
    if (root) {
        root.dispose();
    }
});
</script>

<template>
    <div class="bg-white p-6 shadow-xl sm:rounded-lg border border-gray-100 flex flex-col h-full">
        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-[var(--accent-color)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="Path 11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
            Budget Distribution Overview
        </h4>
        <div class="flex-grow flex items-center justify-center">
            <div ref="chartDiv" style="width: 100%; height: 400px;"></div>
        </div>
    </div>
</template>
