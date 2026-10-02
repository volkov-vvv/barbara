<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, GraduationCap, Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create as applyCreate } from '@/routes/apply';
import { dashboard, login } from '@/routes';

const highlights = [
    {
        icon: BookOpen,
        title: 'Практические курсы',
        description:
            'Frontend, Backend и QA — программы с упором на реальные задачи.',
    },
    {
        icon: GraduationCap,
        title: 'Разный уровень подготовки',
        description:
            'Подходит и новичкам, и тем, у кого уже есть база или junior-опыт.',
    },
    {
        icon: Sparkles,
        title: 'Быстрый старт',
        description:
            'Оставьте заявку — мы свяжемся и поможем выбрать подходящий курс.',
    },
];
</script>

<template>
    <Head title="Главная" />

    <div
        class="relative min-h-screen overflow-hidden bg-[radial-gradient(ellipse_at_top,_#e8f1ff_0%,_#f7f8fb_45%,_#eef2f7_100%)] text-slate-900"
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

        <div class="relative mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:py-16">
            <div class="mb-8 flex items-center justify-between gap-4">
                <Link
                    href="/"
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
                <div class="flex items-center gap-3">
                    <p
                        class="hidden text-xs tracking-[0.18em] text-slate-500 uppercase sm:block"
                    >
                        Обучение
                    </p>
                    <Button v-if="$page.props.auth.user" as-child variant="outline">
                        <Link :href="dashboard()">Кабинет</Link>
                    </Button>
                    <Button v-else as-child variant="outline">
                        <Link :href="login()">Войти</Link>
                    </Button>
                </div>
            </div>

            <div
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur sm:p-10"
            >
                <div class="max-w-2xl space-y-4">
                    <p
                        class="text-xs font-medium tracking-[0.18em] text-sky-700 uppercase"
                    >
                        Платформа обучения
                    </p>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-5xl"
                    >
                        Начните обучение с заявки за пару минут
                    </h1>
                    <p
                        class="text-base leading-relaxed text-slate-600 sm:text-lg"
                    >
                        Выберите курс, укажите уровень подготовки и прикрепите
                        документ — мы рассмотрим заявку и свяжемся с вами.
                    </p>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Button as-child size="lg" class="min-w-48">
                        <Link :href="applyCreate()">
                            Подать заявку
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                    <p class="text-sm text-slate-500">
                        Без регистрации. Ответ обычно в течение 1–2 рабочих
                        дней.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div
                    v-for="item in highlights"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm shadow-slate-900/5 backdrop-blur"
                >
                    <div
                        class="mb-3 flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100"
                    >
                        <component :is="item.icon" class="size-5" />
                    </div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        {{ item.title }}
                    </h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        {{ item.description }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
