<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
    tool_config: {
        calculator_type: string;
        fields: Array<{ name: string; label: string; type: string; min?: number; max?: number; step?: number; default?: number }>;
        formula: string;
        output_fields: string[];
        disclaimer?: string;
    };
}>();

const values = ref<Record<string, number>>({});

// Initialize with defaults
props.tool_config.fields.forEach((f) => {
    values.value[f.name] = f.default ?? f.min ?? 0;
});

const result = computed(() => {
    const v = values.value;

    if (props.tool_config.formula === 'french_amortization') {
        const principal = v.amount ?? 150000;
        const years = v.years ?? 25;
        const rate = (v.rate ?? 3.5) / 100 / 12;
        const months = years * 12;
        if (rate === 0) return { monthly_payment: principal / months, total_interest: 0, total_cost: principal };
        const payment = principal * (rate * Math.pow(1 + rate, months)) / (Math.pow(1 + rate, months) - 1);
        const totalCost = payment * months;
        return { monthly_payment: payment, total_interest: totalCost - principal, total_cost: totalCost };
    }

    if (props.tool_config.formula === 'energy_savings') {
        const consumption = v.consumption ?? 3500;
        const currentPrice = v.current_price ?? 0.15;
        const newPrice = v.new_price ?? 0.12;
        const savings = (currentPrice - newPrice) * consumption;
        return { annual_savings: savings, monthly_savings: savings / 12 };
    }

    if (props.tool_config.formula === 'loan_tae') {
        const amount = v.amount ?? 10000;
        const months = v.months ?? 36;
        const tin = (v.tin ?? 7) / 100 / 12;
        if (tin === 0) return { monthly_payment: amount / months, total_interest: 0, tae: 0 };
        const payment = amount * (tin * Math.pow(1 + tin, months)) / (Math.pow(1 + tin, months) - 1);
        const totalCost = payment * months;
        const tae = (Math.pow(1 + tin, 12) - 1) * 100;
        return { monthly_payment: payment, total_interest: totalCost - amount, tae };
    }

    if (props.tool_config.formula === 'solar_roi') {
        const cost = v.install_cost ?? 6000;
        const annualSavings = v.annual_savings ?? 900;
        const subsidy = v.subsidy_pct ?? 30;
        const netCost = cost * (1 - subsidy / 100);
        const roiYears = annualSavings > 0 ? netCost / annualSavings : 0;
        return { net_cost: netCost, roi_years: roiYears, savings_25y: annualSavings * 25 - netCost };
    }

    return {};
});

function formatNumber(n: number): string {
    return n.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const outputLabels: Record<string, string> = {
    monthly_payment: 'Cuota mensual',
    total_interest: 'Intereses totales',
    total_cost: 'Coste total',
    annual_savings: 'Ahorro anual',
    monthly_savings: 'Ahorro mensual',
    tae: 'TAE',
    net_cost: 'Coste neto',
    roi_years: 'Amortización (años)',
    savings_25y: 'Ahorro en 25 años',
};
</script>

<template>
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            <div class="overflow-hidden rounded-2xl shadow-lg" style="background-color: var(--site-color-background); border: 1px solid var(--site-color-surface)">
                <!-- Inputs -->
                <div class="p-6 sm:p-8">
                    <div class="space-y-5">
                        <div v-for="field in tool_config.fields" :key="field.name">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium" style="color: var(--site-color-text)">{{ field.label }}</label>
                                <span class="font-mono text-sm font-semibold" style="color: var(--site-color-primary)">
                                    {{ field.type === 'currency' ? `${formatNumber(values[field.name])} €` : values[field.name] }}
                                    {{ field.type === 'number' && field.name.includes('rate') ? '%' : '' }}
                                </span>
                            </div>
                            <input
                                v-model.number="values[field.name]"
                                type="range"
                                :min="field.min"
                                :max="field.max"
                                :step="field.step ?? 1"
                                class="mt-2 w-full accent-[var(--site-color-primary)]"
                            />
                            <div class="mt-1 flex justify-between text-xs" style="color: var(--site-color-text_muted)">
                                <span>{{ field.min }}</span>
                                <span>{{ field.max }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results -->
                <div class="p-6 sm:p-8" style="background-color: var(--site-color-surface)">
                    <div class="grid gap-4" :class="Object.keys(result).length > 2 ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2'">
                        <div v-for="key in tool_config.output_fields" :key="key" class="rounded-xl p-4 text-center" style="background-color: var(--site-color-background)">
                            <p class="text-xs font-medium uppercase tracking-wide" style="color: var(--site-color-text_muted)">{{ outputLabels[key] ?? key }}</p>
                            <p class="mt-1 font-mono text-2xl font-bold" style="color: var(--site-color-primary)">
                                {{ key === 'tae' ? `${formatNumber(result[key] ?? 0)}%` : `${formatNumber(result[key] ?? 0)} €` }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div v-if="tool_config.disclaimer" class="border-t px-6 py-3" style="border-color: var(--site-color-surface)">
                    <p class="text-xs" style="color: var(--site-color-text_muted)">{{ tool_config.disclaimer }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
