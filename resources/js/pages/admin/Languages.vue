<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    languages: unknown;
}>();
</script>

<template>
    <Head title="Languages" />
    <div class="p-6">
        <h1 class="text-2xl font-semibold">Languages</h1>
        <p class="mt-2 text-sm text-muted-foreground">Admin stub — UI in stage 3.</p>
    </div>
</template>
