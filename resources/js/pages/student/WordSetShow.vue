<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    wordSet: unknown;
}>();
</script>

<template>
    <Head title="Word Set" />
    <div class="p-6">
        <h1 class="text-2xl font-semibold">Personal word set</h1>
        <p class="mt-2 text-sm text-muted-foreground">Student stub — UI in stage 3.</p>
    </div>
</template>
