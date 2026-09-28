<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ActivityChart from '@/components/charts/ActivityChart.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as studentProgressIndex } from '@/routes/admin/student-progress';

type StudentInfo = {
    id: number;
    name: string;
    email: string;
    daily_word_goal: number;
    reviews_today: number;
};

type Performance = {
    tracked_words: number;
    total_correct: number;
    total_wrong: number;
    avg_ease_factor: number;
};

type WeakTopic = {
    id: number;
    title: string;
    total_wrong: number | string;
    total_correct: number | string;
};

type ActivityPoint = {
    date: string;
    reviews: number;
    correct: number;
    wrong: number;
};

type Session = {
    id: number;
    started_at: string | null;
    finished_at: string | null;
    correct_count: number;
    wrong_count: number;
    is_open?: boolean;
};

const props = defineProps<{
    student: StudentInfo;
    performance: Performance;
    weakTopics: WeakTopic[];
    recentSessions: Session[];
    dailyActivity: ActivityPoint[];
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.studentProgress', href: studentProgressIndex() },
            { title: 'common.results' },
        ],
    },
});

const sessions = computed(() =>
    Array.isArray(props.recentSessions) ? props.recentSessions : [],
);

function formatSessionDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}
</script>

<template>
    <Head :title="`${student.name} results`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="student.name"
                :description="
                    t('admin.reviewsToday', {
                        done: student.reviews_today,
                        goal: student.daily_word_goal,
                        email: student.email,
                    })
                "
            />
            <Button as-child variant="outline">
                <Link :href="studentProgressIndex()">{{
                    t('common.back')
                }}</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{
                        t('admin.trackedWords')
                    }}</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.tracked_words
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('common.correct') }}</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.total_correct
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('common.wrong') }}</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.total_wrong
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{
                        t('admin.avgEaseFactor')
                    }}</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.avg_ease_factor
                    }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.regularity') }}</CardTitle>
                <CardDescription>{{ t('admin.last14Days') }}</CardDescription>
            </CardHeader>
            <CardContent>
                <ActivityChart :activity="dailyActivity" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.weakTopics') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <ul v-if="weakTopics.length" class="space-y-3">
                    <li
                        v-for="topic in weakTopics"
                        :key="topic.id"
                        class="flex justify-between gap-3 text-sm"
                    >
                        <span class="font-medium">{{ topic.title }}</span>
                        <span class="text-muted-foreground">
                            {{ topic.total_wrong }} {{ t('common.wrong') }} /
                            {{ topic.total_correct }} {{ t('common.correct') }}
                        </span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('admin.noWeakTopics') }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.recentSessions') }}</CardTitle>
                <CardDescription>
                    {{
                        t('admin.recentSessionsDescription', {
                            count: sessions.length,
                        })
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="sessions.length" class="overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-left text-sm">
                        <thead class="border-b text-muted-foreground">
                            <tr>
                                <th class="px-2 py-2 font-medium">{{
                                    t('admin.started')
                                }}</th>
                                <th class="px-2 py-2 font-medium">{{
                                    t('common.status')
                                }}</th>
                                <th class="px-2 py-2 font-medium">{{
                                    t('common.correct')
                                }}</th>
                                <th class="px-2 py-2 font-medium">{{
                                    t('common.wrong')
                                }}</th>
                                <th class="px-2 py-2 font-medium">{{
                                    t('common.total')
                                }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="session in sessions"
                                :key="session.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-2 py-3">
                                    {{ formatSessionDate(session.started_at) }}
                                </td>
                                <td class="px-2 py-3">
                                    <span
                                        class="rounded-md px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            session.is_open ||
                                            !session.finished_at
                                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'
                                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                        "
                                    >
                                        {{
                                            session.is_open ||
                                            !session.finished_at
                                                ? t('admin.inProgress')
                                                : t('admin.finished')
                                        }}
                                    </span>
                                </td>
                                <td class="px-2 py-3">
                                    {{ session.correct_count }}
                                </td>
                                <td class="px-2 py-3">
                                    {{ session.wrong_count }}
                                </td>
                                <td class="px-2 py-3 font-medium">
                                    {{
                                        session.correct_count +
                                        session.wrong_count
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('admin.noSessions') }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
