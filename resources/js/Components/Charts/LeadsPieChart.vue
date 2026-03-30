<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import * as echarts from 'echarts/core';
import { PieChart } from 'echarts/charts';
import { TooltipComponent, LegendComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([PieChart, TooltipComponent, LegendComponent, CanvasRenderer]);

const props = defineProps<{
    data: Record<string, number>;
}>();

const chartRef = ref<HTMLDivElement>();
let chart: echarts.ECharts | null = null;

const labels: Record<string, string> = {
    new: 'Nuevos',
    qualified: 'Cualificados',
    sent: 'Enviados',
    discarded: 'Descartados',
};

const colors: Record<string, string> = {
    new: '#6366f1',
    qualified: '#10b981',
    sent: '#3b82f6',
    discarded: '#d4d4d8',
};

const chartOption = computed(() => {
    const items = Object.entries(props.data).map(([key, value]) => ({
        name: labels[key] ?? key,
        value,
        itemStyle: { color: colors[key] ?? '#d4d4d8' },
    }));

    return {
        backgroundColor: 'transparent',
        tooltip: {
            trigger: 'item',
            backgroundColor: '#fff',
            borderColor: '#e4e4e7',
            borderWidth: 1,
            textStyle: { color: '#09090b', fontSize: 12, fontFamily: 'Inter' },
            shadowColor: 'rgba(0,0,0,0.04)',
            shadowBlur: 12,
            formatter: '{b}: {c} ({d}%)',
        },
        legend: {
            bottom: 0,
            textStyle: { color: '#a1a1aa', fontSize: 11, fontFamily: 'Inter' },
            itemWidth: 6,
            itemHeight: 6,
            itemGap: 12,
            icon: 'circle',
        },
        series: [{
            type: 'pie',
            radius: ['55%', '75%'],
            center: ['50%', '42%'],
            avoidLabelOverlap: true,
            label: { show: false },
            emphasis: {
                scaleSize: 4,
            },
            data: items,
        }],
    };
});

onMounted(() => {
    if (chartRef.value) {
        chart = echarts.init(chartRef.value);
        chart.setOption(chartOption.value);
        new ResizeObserver(() => chart?.resize()).observe(chartRef.value);
    }
});

watch(chartOption, (opt) => chart?.setOption(opt));
</script>

<template>
    <div v-if="Object.keys(data).length > 0" ref="chartRef" class="h-44 w-full" />
    <div v-else class="flex h-44 items-center justify-center text-[13px] text-[#a1a1aa]">Sin leads</div>
</template>
