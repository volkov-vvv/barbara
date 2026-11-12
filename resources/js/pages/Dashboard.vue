<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes';

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'nav.dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('dashboard.title')"
            :description="t('dashboard.description')"
        />
    </div>
</template>
