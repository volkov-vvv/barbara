<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { BookOpen } from '@lucide/vue';

const name = usePage().props.name;
</script>

<template>
    <span
        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm shadow-sky-900/20"
    >
        <BookOpen class="size-5" stroke-width="2.25" />
    </span>
    <div
        class="ml-2 grid flex-1 text-left text-sm group-data-[collapsible=icon]:hidden"
    >
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
