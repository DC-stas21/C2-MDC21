<script setup lang="ts">
import HeroSection from '@/components/HeroSection.vue';
import ContentBlock from '@/components/ContentBlock.vue';
import FeaturesGrid from '@/components/FeaturesGrid.vue';
import FaqSection from '@/components/FaqSection.vue';
import CtaSection from '@/components/CtaSection.vue';
import CalculatorTool from '@/components/tools/CalculatorTool.vue';
import ComparatorTool from '@/components/tools/ComparatorTool.vue';
import CheckerTool from '@/components/tools/CheckerTool.vue';
import LeadFormTool from '@/components/tools/LeadFormTool.vue';

defineProps<{
    page: {
        slug: string;
        type: string;
        seo: { title: string; description: string };
        sections: Array<{ type: string; [key: string]: any }>;
    };
}>();

const componentMap: Record<string, any> = {
    hero: HeroSection,
    content: ContentBlock,
    features: FeaturesGrid,
    faq: FaqSection,
    cta: CtaSection,
    tool: CalculatorTool,
    comparator: ComparatorTool,
    checker: CheckerTool,
    lead_form: LeadFormTool,
};
</script>

<template>
    <div>
        <template v-for="(section, i) in page.sections" :key="i">
            <component
                :is="componentMap[section.tool_type || section.type]"
                v-if="componentMap[section.tool_type || section.type]"
                v-bind="section"
            />
            <component
                :is="componentMap[section.type]"
                v-else-if="componentMap[section.type]"
                v-bind="section"
            />
        </template>
    </div>
</template>
