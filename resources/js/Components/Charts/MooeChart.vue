<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const props = defineProps({
    data: Array
});

const chartDiv = ref(null);
let root = null;

onMounted(() => {
    let root_internal = am5.Root.new(chartDiv.value);
    root = root_internal;

    root_internal.setThemes([
        am5themes_Animated.new(root_internal)
    ]);

    let chart = root_internal.container.children.push(
        am5xy.XYChart.new(root_internal, {
            panX: false,
            panY: false,
            wheelX: "none",
            wheelY: "none",
            layout: root_internal.verticalLayout
        })
    );

    // Create X-axis (Projects)
    let xRenderer = am5xy.AxisRendererX.new(root_internal, {
        minGridDistance: 30,
        cellStartLocation: 0.1,
        cellEndLocation: 0.9
    });

    xRenderer.labels.template.setAll({
        rotation: -45,
        centerY: am5.p50,
        centerX: am5.p100,
        paddingRight: 15,
        fontSize: 10
    });

    let xAxis = chart.xAxes.push(
        am5xy.CategoryAxis.new(root_internal, {
            categoryField: "category",
            renderer: xRenderer
        })
    );
    xAxis.data.setAll(props.data);

    // Create Y-axis (Values)
    let yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root_internal, {
            renderer: am5xy.AxisRendererY.new(root_internal, {})
        })
    );

    // Add legend
    let legend = chart.children.push(am5.Legend.new(root_internal, {
        centerX: am5.p50,
        x: am5.p50
    }));

    // Add series
    function createSeries(name, field, color, widthPercent) {
        let series = chart.series.push(
            am5xy.ColumnSeries.new(root_internal, {
                name: name,
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: field,
                categoryXField: "category",
                fill: am5.color(color),
                stroke: am5.color(color),
                clustered: false
            })
        );

        series.columns.template.setAll({
            tooltipText: "{name}: [bold]{valueY}[/]",
            width: am5.percent(widthPercent),
            centerX: am5.p50,
            tooltipY: 0
        });

        series.data.setAll(props.data);
        series.appear();

        legend.data.push(series);
    }

    createSeries("BUDGET", "budget", 0x93C5FD, 100); // Light Blue (Back)
    createSeries("OBLIGATED", "obligated", 0x3B82F6, 60); // Blue (Middle)
    createSeries("ACTUAL BALANCE", "balance", 0x10B981, 25); // Emerald/Green (Front)

    chart.appear(1000, 100);
});

onUnmounted(() => {
    if (root) {
        root.dispose();
    }
});
</script>

<template>
    <div class="bg-white p-4 shadow rounded-lg border border-gray-200">
        <h4 class="text-sm font-semibold text-gray-700 mb-2 uppercase">MOOE</h4>
        <div ref="chartDiv" style="width: 100%; height: 500px;"></div>
    </div>
</template>
