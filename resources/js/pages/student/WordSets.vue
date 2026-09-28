<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    wordSets: unknown;
    languages: unknown;
}>();
</script>

<template>
    <Head title="My Word Sets" />
    <div class="p-6">
        <h1 class="text-2xl font-semibold">Personal word sets</h1>
        <p class="mt-2 text-sm text-muted-foreground">Student stub — UI in stage 3.</p>
    </div>
</template>
