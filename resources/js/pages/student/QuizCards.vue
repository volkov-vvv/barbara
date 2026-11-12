<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Rocket } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import QuizController from '@/actions/App/Http/Controllers/Student/QuizController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index as quizzesIndex } from '@/routes/student/quizzes';

type Language = { code: string; name: string };
type WordSet = { title: string; language?: Language | null };
type Word = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
    image_url: string | null;
    word_set?: WordSet | null;
};
type QuizOption = {
    id: number;
    label: string;
};
type QuizCard = {
    id: number;
    word_id: number;
    word: Word;
    options: QuizOption[];
};

const props = defineProps<{
    cards: QuizCard[];
    dueCount: number;
    availableNewCount: number;
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'nav.quiz', href: quizzesIndex() }],
    },
});

const queue = ref<QuizCard[]>([...props.cards]);
const started = ref(false);
const submitting = ref(false);
const requestingMore = ref(false);
const selectedOptionId = ref<number | null>(null);
const revealed = ref(false);

watch(
    () => props.cards,
    (cards) => {
        if (revealed.value) {
            return;
        }

        queue.value = [...cards];
        selectedOptionId.value = null;
        revealed.value = false;

        if (cards.length === 0) {
            started.value = false;
        }
    },
);

const current = computed(() =>
    started.value ? (queue.value[0] ?? null) : null,
);
const remaining = computed(() => queue.value.length);
const canRequestMore = computed(
    () => props.availableNewCount > 0 && !requestingMore.value,
);
const canStart = computed(() => queue.value.length > 0 && !started.value);

function startSession(): void {
    if (!canStart.value) {
        return;
    }

    started.value = true;
    selectedOptionId.value = null;
    revealed.value = false;
}

function optionClass(optionId: number): string {
    if (!revealed.value || selectedOptionId.value === null || !current.value) {
        return 'border-sidebar-border/70 hover:bg-muted/40';
    }

    const isCorrect = optionId === current.value.word_id;
    const isSelected = optionId === selectedOptionId.value;

    if (isCorrect) {
        return 'border-green-500 bg-green-500/10 text-green-800 dark:text-green-300';
    }

    if (isSelected) {
        return 'border-red-500 bg-red-500/10 text-red-800 dark:text-red-300';
    }

    return 'border-sidebar-border/40 opacity-60';
}

function requestMoreCards(): void {
    if (!canRequestMore.value) {
        return;
    }

    requestingMore.value = true;

    router.post(
        QuizController.requestMore.url(),
        { count: 10 },
        {
            preserveScroll: true,
            onFinish: () => {
                requestingMore.value = false;
            },
        },
    );
}

function selectOption(optionId: number): void {
    if (!current.value || submitting.value || revealed.value) {
        return;
    }

    revealed.value = true;
    selectedOptionId.value = optionId;
    submitting.value = true;

    const answeredId = current.value.id;

    router.post(
        QuizController.store.url(),
        {
            word_id: current.value.word_id,
            selected_option_id: optionId,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                window.setTimeout(() => {
                    queue.value = props.cards.filter(
                        (card) => card.id !== answeredId,
                    );
                    selectedOptionId.value = null;
                    revealed.value = false;
                    submitting.value = false;
                }, 800);
            },
            onError: () => {
                selectedOptionId.value = null;
                revealed.value = false;
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('student.quizTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex w-full flex-wrap items-end justify-between gap-3">
            <div class="min-w-0 flex-1 [&_header]:mb-0">
                <Heading
                    :title="t('student.quizTitle')"
                    :description="t('student.quizDescription')"
                />
            </div>
            <div
                v-if="started"
                class="flex flex-wrap items-center gap-3"
            >
                <p
                    v-if="current"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('student.remaining', { count: remaining }) }}
                </p>
                <Button
                    type="button"
                    :disabled="!canRequestMore"
                    @click="requestMoreCards"
                >
                    {{
                        requestingMore
                            ? t('common.loading')
                            : t('student.getMoreCards')
                    }}
                </Button>
            </div>
        </div>

        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <div
            v-if="!started"
            class="flex min-h-[28rem] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-muted/30 px-6 text-center"
        >
            <p class="text-lg font-medium">
                {{
                    queue.length > 0
                        ? t('student.readyCount', { count: queue.length })
                        : t('student.noQuizCards')
                }}
            </p>
            <p
                v-if="queue.length > 0"
                class="mt-2 text-sm text-muted-foreground"
            >
                {{ t('student.startHint') }}
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <Button
                    type="button"
                    size="lg"
                    :disabled="!canStart"
                    @click="startSession"
                >
                    <Rocket class="size-4" />
                    {{ t('student.start') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    :disabled="!canRequestMore"
                    @click="requestMoreCards"
                >
                    {{
                        requestingMore
                            ? t('common.loading')
                            : t('student.getMoreCards')
                    }}
                </Button>
            </div>
        </div>

        <div
            v-else-if="!current"
            class="flex min-h-[28rem] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-muted/30 px-6 text-center"
        >
            <p class="text-lg font-medium">{{ t('student.sessionDone') }}</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <Button type="button" @click="started = false">
                    {{ t('common.back') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="!canRequestMore"
                    @click="requestMoreCards"
                >
                    {{
                        requestingMore
                            ? t('common.loading')
                            : t('student.getMoreCards')
                    }}
                </Button>
            </div>
        </div>

        <div v-else class="mx-auto w-full max-w-2xl space-y-6">
            <div
                class="rounded-2xl border border-sidebar-border/70 bg-background p-6 shadow-sm md:p-8"
            >
                <div class="mb-6 flex items-start justify-between gap-3">
                    <span
                        class="rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground"
                    >
                        {{
                            current.word.word_set?.title ??
                            t('nav.myWordSets')
                        }}
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ t('student.quizPickAnswer') }}
                    </span>
                </div>

                <div
                    class="flex flex-col items-center justify-center gap-4 text-center"
                >
                    <img
                        v-if="current.word.image_url"
                        :src="current.word.image_url"
                        :alt="current.word.text"
                        class="max-h-40 rounded-lg object-contain"
                    />
                    <p
                        class="text-3xl font-semibold tracking-tight md:text-4xl"
                    >
                        {{ current.word.text }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button
                    v-for="option in current.options"
                    :key="option.id"
                    type="button"
                    class="min-h-16 rounded-xl border px-4 py-4 text-left text-base font-medium transition-colors disabled:cursor-default"
                    :class="optionClass(option.id)"
                    :disabled="revealed || submitting"
                    @click="selectOption(option.id)"
                >
                    {{ option.label }}
                </button>
            </div>

            <p
                v-if="revealed && selectedOptionId !== null"
                class="text-center text-sm font-medium"
                :class="
                    selectedOptionId === current.word_id
                        ? 'text-green-700 dark:text-green-400'
                        : 'text-red-700 dark:text-red-400'
                "
            >
                {{
                    selectedOptionId === current.word_id
                        ? t('student.quizCorrect')
                        : t('student.quizIncorrect')
                }}
            </p>
        </div>
        </div>
    </div>
</template>
