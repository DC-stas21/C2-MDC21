<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import * as echarts from 'echarts/core';
import { GaugeChart } from 'echarts/charts';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([GaugeChart, CanvasRenderer]);

const props = defineProps<{ score: number | null }>();
const chartRef = ref<HTMLDivElement>();
let chart: echarts.ECharts | null = null;

function scoreColor(s: number): string {
    if (s >= 80) return '#10b981';
    if (s >= 60) return '#3b82f6';
    if (s >= 40) return '#f59e0b';
    return '#ef4444';
}

const chartOption = computed(() => {
    const v = props.score ?? 0;
    return {
        backgroundColor: 'transparent',
        series: [{
            type: 'gauge',
            startAngle: 210,
            endAngle: -30,
            radius: '85%',
            center: ['50%', '58%'],
            min: 0,
            max: 100,
            progress: { show: true, width: 10, roundCap: true, itemStyle: { color: scoreColor(v) } },
            axisLine: { lineStyle: { width: 10, color: [[1, '#f4f4f5']] } },
            axisTick: { show: false },
            splitLine: { show: false },
            axisLabel: { show: false },
            pointer: { show: false },
            detail: {
                valueAnimation: true,
                fontSize: 26,
                fontWeight: '600',
                fontFamily: 'Inter',
                color: props.score !== null ? scoreColor(v) : '#d4d4d8',
                formatter: props.score !== null ? '{value}' : '—',
                offsetCenter: [0, '15%'],
            },
            data: [{ value: v }],
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
    <div ref="chartRef" class="h-44 w-full" />
</template>
