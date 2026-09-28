<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
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

type ChildSummary = {
    id: number;
    name: string;
    email: string;
    relation: string | null;
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
    children: ChildSummary[];
    dailyActivity: ActivityPoint[];
    weakTopics: WeakTopic[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Parent dashboard', href: analyticsIndex() }],
    },
});

const totals = computed(() => ({
    due: props.children.reduce((sum, child) => sum + child.due_count, 0),
    correct: props.children.reduce((sum, child) => sum + child.total_correct, 0),
    wrong: props.children.reduce((sum, child) => sum + child.total_wrong, 0),
}));
</script>

<template>
    <Head title="Parent dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Children overview"
            description="Track review regularity, weak topics, and daily goals."
        />

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Due cards</CardDescription>
                    <CardTitle class="text-3xl">{{ totals.due }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Total correct</CardDescription>
                    <CardTitle class="text-3xl">{{ totals.correct }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Total wrong</CardDescription>
                    <CardTitle class="text-3xl">{{ totals.wrong }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Review regularity</CardTitle>
                    <CardDescription>Last 14 days across linked children</CardDescription>
                </CardHeader>
                <CardContent>
                    <ActivityChart :activity="dailyActivity" />
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Weak topics</CardTitle>
                    <CardDescription>Highest wrong answers by word set</CardDescription>
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
                                {{ topic.total_wrong }} wrong /
                                {{ topic.total_correct }} correct
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No weak topics yet.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4">
            <Heading
                variant="small"
                title="Children"
                description="Open a child for detailed analytics or adjust the daily goal."
            />

            <div
                v-if="!children.length"
                class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                No linked children yet. Ask an admin to create the parent-student link.
            </div>

            <Card v-for="child in children" :key="child.id">
                <CardHeader class="flex flex-row items-start justify-between gap-4 space-y-0">
                    <div>
                        <CardTitle>{{ child.name }}</CardTitle>
                        <CardDescription>
                            {{ child.relation ?? 'Linked' }} ·
                            {{ child.reviews_today }}/{{ child.daily_word_goal }}
                            reviews today · {{ child.due_count }} due
                        </CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm">
                        <Link
                            :href="
                                AnalyticsController.show.url(child.id)
                            "
                        >
                            Details
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="
                            AnalyticsController.updateGoal.form(child.id)
                        "
                        class="flex flex-wrap items-end gap-3"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label :for="`goal-${child.id}`">Daily word goal</Label>
                            <Input
                                :id="`goal-${child.id}`"
                                type="number"
                                min="1"
                                max="500"
                                name="daily_word_goal"
                                class="w-32"
                                :default-value="child.daily_word_goal"
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
    </div>
</template>
