<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StudentProgressController from '@/actions/App/Http/Controllers/Admin/StudentProgressController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as studentProgressIndex } from '@/routes/admin/student-progress';

type StudentSummary = {
    id: number;
    name: string;
    email: string;
    due_count: number;
    total_correct: number;
    total_wrong: number;
    daily_word_goal: number;
    reviews_today: number;
    sessions_last_7_days: number;
};

type ActivityPoint = {
    date: string;
    reviews: number | string;
    correct: number | string;
    wrong: number | string;
};

type WeakTopic = {
    id: number;
    title: string;
    total_wrong: number | string;
    total_correct: number | string;
};

const props = defineProps<{
    students: StudentSummary[];
    dailyActivity: ActivityPoint[];
    weakTopics: WeakTopic[];
    filters: { search: string | null };
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.studentProgress', href: studentProgressIndex() },
        ],
    },
});

function searchStudents(event: Event): void {
    const value = (event.target as HTMLInputElement).value.trim();

    router.get(
        studentProgressIndex.url(
            value ? { query: { search: value } } : undefined,
        ),
        {},
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head :title="t('admin.studentProgressTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('admin.studentProgressTitle')"
            :description="t('admin.studentProgressDescription')"
        />

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('admin.students') }}</CardDescription>
                    <CardTitle class="text-3xl">{{ students.length }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{ t('admin.dueCards') }}</CardDescription>
                    <CardTitle class="text-3xl">
                        {{
                            students.reduce(
                                (sum, student) => sum + student.due_count,
                                0,
                            )
                        }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>{{
                        t('admin.correctAnswers')
                    }}</CardDescription>
                    <CardTitle class="text-3xl">
                        {{
                            students.reduce(
                                (sum, student) => sum + student.total_correct,
                                0,
                            )
                        }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>{{ t('admin.reviewRegularity') }}</CardTitle>
                    <CardDescription>{{
                        t('admin.last14DaysAll')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ActivityChart :activity="dailyActivity" />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('admin.weakTopics') }}</CardTitle>
                    <CardDescription>{{
                        t('admin.weakTopicsHint')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ul v-if="weakTopics.length" class="space-y-3">
                        <li
                            v-for="topic in weakTopics"
                            :key="topic.id"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="font-medium">{{ topic.title }}</span>
                            <span class="text-muted-foreground">
                                {{ topic.total_wrong }} {{ t('common.wrong') }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        {{ t('admin.noWeakTopics') }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <CardTitle>{{ t('admin.students') }}</CardTitle>
                        <CardDescription class="mt-1.5">
                            {{ t('admin.studentsListHint') }}
                        </CardDescription>
                    </div>
                    <div class="grid gap-2">
                        <Label for="student-search">{{
                            t('admin.search')
                        }}</Label>
                        <Input
                            id="student-search"
                            type="search"
                            class="w-64"
                            :placeholder="t('admin.searchPlaceholder')"
                            :default-value="filters.search ?? ''"
                            @change="searchStudents"
                        />
                    </div>
                </div>
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-if="!students.length"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('admin.noStudentsFound') }}
                </div>

                <div
                    v-for="student in students"
                    :key="student.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
                >
                    <div>
                        <p class="font-medium">{{ student.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ student.email }} ·
                            {{
                                t('admin.studentSummary', {
                                    done: student.reviews_today,
                                    goal: student.daily_word_goal,
                                    due: student.due_count,
                                    correct: student.total_correct,
                                    wrong: student.total_wrong,
                                })
                            }}
                        </p>
                    </div>
                    <Button as-child size="sm" variant="outline">
                        <Link
                            :href="
                                StudentProgressController.show.url(student.id)
                            "
                        >
                            {{ t('common.results') }}
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
