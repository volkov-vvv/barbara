<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://w3.org"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M21 4H15.5C13.57 4 12 5.57 12 7.5V20C12 18.57 13.57 17 15.5 17H21V4ZM3 4H8.5C10.43 4 12 5.57 12 7.5V20C12 18.57 10.43 17 8.5 17H3V4ZM10 7.5V15H8.5C7.3 15 6 15.5 5 16V6C6 5.5 7.3 5 8.5 5C9.5 5 10 6 10 7.5ZM19 16C18 15.5 16.7 15 15.5 15H14V7.5C14 6 14.5 5 15.5 5C16.7 5 18 5.5 19 6V16Z"
        />
    </svg>
</template>

