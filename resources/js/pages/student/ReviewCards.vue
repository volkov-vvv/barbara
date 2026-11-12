<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Rocket, Volume2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ReviewController from '@/actions/App/Http/Controllers/Student/ReviewController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    languageCodeToBcp47,
    useSpeech,
} from '@/composables/useSpeech';
import { index as reviewsIndex } from '@/routes/student/reviews';

type Language = { code: string; name: string };
type WordSet = { title: string; language?: Language | null };
type Word = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
    word_set?: WordSet | null;
};
type ReviewCard = {
    id: number;
    word_id: number;
    word: Word;
};

type ReviewStats = {
    total_cards: number;
    not_started_cards: number;
    easy_cards: number;
    hard_cards: number;
    last_reviewed_at: string | null;
};

const props = defineProps<{
    cards: ReviewCard[];
    dueCount: number;
    availableNewCount: number;
    stats: ReviewStats;
}>();

const { t, locale } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'nav.reviews', href: reviewsIndex() }],
    },
});

const { speak, stop } = useSpeech();
const queue = ref<ReviewCard[]>([...props.cards]);
const started = ref(false);
const flipped = ref(false);
const submitting = ref(false);
const requestingMore = ref(false);

watch(
    () => props.cards,
    (cards) => {
        queue.value = [...cards];
        flipped.value = false;

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
    flipped.value = false;
}

function formatLastReview(value: string | null): string {
    if (!value) {
        return t('student.statsLastReviewNever');
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

const qualityEmojis: Record<number, string> = {
    0: '😭',
    1: '😢',
    2: '😕',
    3: '🙂',
    4: '😊',
    5: '🤩',
};

const qualityOptions = computed(() =>
    ([0, 1, 2, 3, 4, 5] as const).map((value) => ({
        value,
        emoji: qualityEmojis[value],
        label: t(`student.quality.${value}`),
    })),
);

function playAudio(event?: Event): void {
    event?.stopPropagation();

    if (!current.value) {
        return;
    }

    const word = current.value.word;

    if (word.audio_url) {
        const audio = new Audio(word.audio_url);
        void audio.play();

        return;
    }

    speak(
        word.text,
        languageCodeToBcp47(word.word_set?.language?.code),
    );
}

function flipCard(): void {
    flipped.value = !flipped.value;
}

function onCardKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        flipCard();
    }
}

function requestMoreCards(): void {
    if (!canRequestMore.value) {
        return;
    }

    requestingMore.value = true;

    router.post(
        ReviewController.requestMore.url(),
        { count: 10 },
        {
            preserveScroll: true,
            onFinish: () => {
                requestingMore.value = false;
            },
        },
    );
}

function submitQuality(quality: number): void {
    if (!current.value || submitting.value) {
        return;
    }

    if (!flipped.value) {
        flipped.value = true;
    }

    submitting.value = true;
    stop();

    const answeredId = current.value.id;

    router.post(
        ReviewController.store.url(),
        {
            word_id: current.value.word_id,
            quality,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                queue.value = queue.value.filter((card) => card.id !== answeredId);
                flipped.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('student.reviewsTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex w-full flex-wrap items-end justify-between gap-3">
            <div class="min-w-0 flex-1 [&_header]:mb-0">
                <Heading
                    :title="t('student.reviewsTitle')"
                    :description="t('student.reviewsDescription')"
                >
                    <template #after-title>
                        <Badge
                            variant="secondary"
                            class="rounded-md px-3 py-1 font-normal whitespace-normal"
                        >
                            <span class="text-muted-foreground">
                                {{ t('student.statsLastReview') }}:
                            </span>
                            {{ formatLastReview(stats.last_reviewed_at) }}
                        </Badge>
                    </template>
                </Heading>
            </div>
            <div
                v-if="started"
                class="flex flex-wrap items-center gap-3"
            >
                <p class="text-sm text-muted-foreground">
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
        <template v-if="!started">
            <dl
                class="grid w-full grid-cols-2 gap-3 text-left sm:grid-cols-4"
            >
                <div class="rounded-lg border bg-background px-3 py-2">
                    <dt class="text-xs text-muted-foreground">
                        {{ t('student.statsTotalCards') }}
                    </dt>
                    <dd class="mt-1 text-xl font-semibold">
                        {{ stats.total_cards }}
                    </dd>
                </div>
                <div class="rounded-lg border bg-background px-3 py-2">
                    <dt class="text-xs text-muted-foreground">
                        {{ t('student.statsNotStartedCards') }}
                    </dt>
                    <dd class="mt-1 text-xl font-semibold">
                        {{ stats.not_started_cards }}
                    </dd>
                </div>
                <div class="rounded-lg border bg-background px-3 py-2">
                    <dt class="text-xs text-muted-foreground">
                        {{ t('student.statsEasyCards') }}
                    </dt>
                    <dd class="mt-1 text-xl font-semibold">
                        {{ stats.easy_cards }}
                    </dd>
                </div>
                <div class="rounded-lg border bg-background px-3 py-2">
                    <dt class="text-xs text-muted-foreground">
                        {{ t('student.statsHardCards') }}
                    </dt>
                    <dd class="mt-1 text-xl font-semibold">
                        {{ stats.hard_cards }}
                    </dd>
                </div>
            </dl>

            <div
                class="flex min-h-[28rem] flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/80 bg-muted/30 px-6 text-center"
            >
                <p class="text-lg font-medium">
                    {{
                        queue.length > 0
                            ? t('student.readyCount', { count: queue.length })
                            : t('student.noCards')
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
        </template>

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

        <div v-else class="mx-auto mt-6 w-full max-w-2xl">
            <div
                class="review-scene group w-full cursor-pointer"
                role="button"
                tabindex="0"
                :aria-pressed="flipped"
                @click="flipCard"
                @keydown="onCardKeydown"
            >
                <div
                    class="review-card relative h-80 w-full md:h-96"
                    :class="{ 'is-flipped': flipped }"
                >
                    <div
                        class="review-face review-front absolute inset-0 flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-background p-6 shadow-sm md:p-8"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span
                                class="rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground"
                            >
                                {{
                                    current.word.word_set?.title ??
                                    t('nav.myWordSets')
                                }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{
                                t('student.flip')
                            }}</span>
                        </div>
                        <div class="flex flex-1 flex-col items-center justify-center gap-4 text-center">
                            <p
                                class="text-3xl font-semibold tracking-tight md:text-4xl"
                            >
                                {{ current.word.text }}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="playAudio"
                            >
                                <Volume2 class="size-4" />
                                Listen
                            </Button>
                        </div>
                    </div>

                    <div
                        class="review-face review-back absolute inset-0 flex flex-col justify-between rounded-2xl border border-sidebar-border/70 bg-primary/5 p-6 shadow-sm md:p-8"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span
                                class="rounded-md bg-background/80 px-2 py-1 text-xs font-medium text-muted-foreground"
                            >
                                Translation
                            </span>
                            <span class="text-xs text-muted-foreground">{{
                                t('student.flip')
                            }}</span>
                        </div>
                        <div class="flex flex-1 flex-col items-center justify-center gap-4 text-center">
                            <p
                                class="text-3xl font-semibold tracking-tight md:text-4xl"
                            >
                                {{ current.word.translation }}
                            </p>
                            <p
                                v-if="current.word.example_sentence"
                                class="max-w-lg text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ current.word.example_sentence }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-6">
                <Button
                    v-for="option in qualityOptions"
                    :key="option.value"
                    type="button"
                    variant="outline"
                    class="h-auto flex-col gap-0.5 py-3"
                    :disabled="submitting"
                    @click="submitQuality(option.value)"
                >
                    <span class="text-2xl leading-none" aria-hidden="true">{{
                        option.emoji
                    }}</span>
                    <span class="text-[11px] font-medium">{{
                        option.label
                    }}</span>
                </Button>
            </div>
        </div>
        </div>
    </div>
</template>

<style scoped>
.review-scene {
    perspective: 1200px;
}

.review-card {
    transform-style: preserve-3d;
    transition: transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}

.review-card.is-flipped {
    transform: rotateY(180deg);
}

.review-face {
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.review-back {
    transform: rotateY(180deg);
}
</style>
