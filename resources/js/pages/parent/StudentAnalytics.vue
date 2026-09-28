<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AnalyticsController from '@/actions/App/Http/Controllers/Parent/AnalyticsController';
import ActivityChart from '@/components/charts/ActivityChart.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index as analyticsIndex } from '@/routes/parent/analytics';

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
    started_at: string;
    finished_at: string | null;
    correct_count: number;
    wrong_count: number;
};

const props = defineProps<{
    student: StudentInfo;
    performance: Performance;
    weakTopics: WeakTopic[];
    recentSessions: Session[];
    dailyActivity: ActivityPoint[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Parent dashboard', href: analyticsIndex() },
            { title: 'Student analytics' },
        ],
    },
});
</script>

<template>
    <Head :title="`${student.name} analytics`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="student.name"
                :description="`${student.reviews_today}/${student.daily_word_goal} reviews today · ${student.email}`"
            />
            <Button as-child variant="outline">
                <Link :href="analyticsIndex()">Back</Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Tracked words</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.tracked_words
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Correct</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.total_correct
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Wrong</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.total_wrong
                    }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Avg ease factor</CardDescription>
                    <CardTitle class="text-3xl">{{
                        performance.avg_ease_factor
                    }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Regularity</CardTitle>
                    <CardDescription>Last 14 days</CardDescription>
                </CardHeader>
                <CardContent>
                    <ActivityChart :activity="dailyActivity" />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Daily goal</CardTitle>
                    <CardDescription>Words to review each day</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="
                            AnalyticsController.updateGoal.form(student.id)
                        "
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="daily_word_goal">Goal</Label>
                            <Input
                                id="daily_word_goal"
                                type="number"
                                min="1"
                                max="500"
                                name="daily_word_goal"
                                :default-value="student.daily_word_goal"
                            />
                            <InputError :message="errors.daily_word_goal" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            Save goal
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Weak topics</CardTitle>
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
                                {{ topic.total_wrong }} wrong
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No weak topics yet.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Recent sessions</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul v-if="recentSessions.length" class="space-y-3">
                        <li
                            v-for="session in recentSessions"
                            :key="session.id"
                            class="flex justify-between gap-3 text-sm"
                        >
                            <span>{{
                                new Date(session.started_at).toLocaleString()
                            }}</span>
                            <span class="text-muted-foreground">
                                {{ session.correct_count }} /
                                {{ session.wrong_count }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No sessions yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
