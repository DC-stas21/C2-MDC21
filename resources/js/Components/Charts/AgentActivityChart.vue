<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import * as echarts from 'echarts/core';
import { BarChart } from 'echarts/charts';
import { TooltipComponent, GridComponent, LegendComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([BarChart, TooltipComponent, GridComponent, LegendComponent, CanvasRenderer]);

const props = defineProps<{
    data: Array<{ date: string; agent_type: string; status: string; count: number }>;
}>();

const chartRef = ref<HTMLDivElement>();
let chart: echarts.ECharts | null = null;

const chartOption = computed(() => {
    const dates = [...new Set(props.data.map((d) => d.date))].sort();
    const statusConfig = [
        { key: 'completed', color: '#10b981', label: 'Completados' },
        { key: 'failed', color: '#ef4444', label: 'Fallidos' },
        { key: 'running', color: '#6366f1', label: 'Ejecutando' },
    ];

    return {
        backgroundColor: 'transparent',
        tooltip: {
            trigger: 'axis',
            backgroundColor: '#fff',
            borderColor: '#e4e4e7',
            borderWidth: 1,
            textStyle: { color: '#09090b', fontSize: 12, fontFamily: 'Inter' },
            shadowColor: 'rgba(0,0,0,0.04)',
            shadowBlur: 12,
            padding: [8, 12],
        },
        legend: {
            bottom: 0,
            textStyle: { color: '#a1a1aa', fontSize: 11, fontFamily: 'Inter' },
            itemWidth: 6,
            itemHeight: 6,
            itemGap: 20,
            icon: 'circle',
        },
        grid: { left: 32, right: 8, top: 4, bottom: 32 },
        xAxis: {
            type: 'category',
            data: dates.map((d) => {
                const dt = new Date(d);
                const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                return days[dt.getDay()];
            }),
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { color: '#a1a1aa', fontSize: 11, fontFamily: 'Inter' },
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: '#f4f4f5' } },
            axisLabel: { color: '#a1a1aa', fontSize: 11, fontFamily: 'Inter' },
            axisLine: { show: false },
            axisTick: { show: false },
        },
        series: statusConfig.map((s) => ({
            name: s.label,
            type: 'bar',
            stack: 'total',
            barWidth: '40%',
            itemStyle: {
                color: s.color,
                borderRadius: s.key === 'completed' ? [3, 3, 0, 0] : 0,
            },
            data: dates.map((date) =>
                props.data
                    .filter((d) => d.date === date && d.status === s.key)
                    .reduce((sum, d) => sum + d.count, 0)
            ),
        })),
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
    <div v-if="data.length > 0" ref="chartRef" class="h-56 w-full" />
    <div v-else class="flex h-56 items-center justify-center text-[13px] text-[#a1a1aa]">
        Sin actividad reciente
    </div>
</template>
