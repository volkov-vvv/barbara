<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'settings.appearanceTitle',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('settings.appearanceTitle')" />

    <h1 class="sr-only">{{ t('settings.appearanceTitle') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.appearanceTitle')"
            :description="t('settings.appearanceDescription')"
        />
        <AppearanceTabs />
    </div>
</template>
