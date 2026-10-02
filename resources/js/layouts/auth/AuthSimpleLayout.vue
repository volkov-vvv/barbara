<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { home } from '@/routes';

const props = defineProps<{
    title?: string;
    description?: string;
}>();

const { t, te } = useI18n();

const resolvedTitle = computed(() => {
    if (!props.title) {
        return '';
    }

    return te(props.title) ? t(props.title) : props.title;
});

const resolvedDescription = computed(() => {
    if (!props.description) {
        return '';
    }

    return te(props.description) ? t(props.description) : props.description;
});
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col overflow-hidden bg-[radial-gradient(ellipse_at_top,_#e8f1ff_0%,_#f7f8fb_45%,_#eef2f7_100%)] text-slate-900"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgb(148_163_184/0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgb(148_163_184/0.08)_1px,transparent_1px)] bg-size-[28px_28px]"
        />
        <div
            class="pointer-events-none absolute -top-24 right-[-10%] h-72 w-72 rounded-full bg-sky-300/30 blur-3xl"
        />
        <div
            class="pointer-events-none absolute bottom-[-8%] left-[-8%] h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl"
        />

        <div
            class="relative mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-10 sm:px-6 lg:py-16"
        >
            <div class="mb-8 flex items-center justify-between gap-4">
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2.5 text-slate-900 transition hover:opacity-80"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm shadow-sky-900/20"
                    >
                        <BookOpen class="size-5" stroke-width="2.25" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">
                        Barbara
                    </span>
                </Link>
                <p class="text-xs tracking-[0.18em] text-slate-500 uppercase">
                    Обучение
                </p>
            </div>

            <div
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur sm:p-8"
            >
                <div class="mb-8 space-y-2">
                    <h1
                        class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl"
                    >
                        {{ resolvedTitle }}
                    </h1>
                    <p
                        v-if="resolvedDescription"
                        class="text-sm leading-relaxed text-slate-600"
                    >
                        {{ resolvedDescription }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
