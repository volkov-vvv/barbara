<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    GraduationCap,
    LayoutGrid,
    Users,
} from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/admin/applications';
import { index as studentProgressIndex } from '@/routes/admin/student-progress';
import { index as usersIndex } from '@/routes/admin/users';
import { index as wordSetsIndex } from '@/routes/admin/word-sets';
import { index as parentAnalyticsIndex } from '@/routes/parent/analytics';
import { index as reviewsIndex } from '@/routes/student/reviews';
import { index as studentWordSetsIndex } from '@/routes/student/word-sets';

type RecentApplication = {
    id: number;
    full_name: string;
    email: string;
    course: string;
    created_at: string | null;
};

type DashboardStats = {
    users: {
        total: number;
        admins: number;
        students: number;
        parents: number;
    };
    applications: {
        total: number;
        today: number;
        last_7_days: number;
        recent: RecentApplication[];
    };
    dictionaries: {
        system_sets: number;
        personal_sets: number;
        words: number;
        languages: number;
    };
    progress: {
        due_cards: number;
        students_with_due: number;
        reviews_today: number;
        sessions_last_7_days: number;
        total_correct: number;
        total_wrong: number;
    };
};

const props = defineProps<{
    stats: DashboardStats | null;
}>();

const { t, locale } = useI18n();
const { hasRole } = useAuth();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'nav.dashboard',
                href: dashboard(),
            },
        ],
    },
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale.value, {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('dashboard.title')"
            :description="t('dashboard.description')"
        />

        <template v-if="stats">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between gap-2">
                            <CardDescription>{{
                                t('dashboard.users')
                            }}</CardDescription>
                            <Users class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl">{{
                            stats.users.total
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{
                            t('dashboard.usersBreakdown', {
                                students: stats.users.students,
                                parents: stats.users.parents,
                                admins: stats.users.admins,
                            })
                        }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between gap-2">
                            <CardDescription>{{
                                t('dashboard.applications')
                            }}</CardDescription>
                            <ClipboardList
                                class="size-4 text-muted-foreground"
                            />
                        </div>
                        <CardTitle class="text-3xl">{{
                            stats.applications.total
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{
                            t('dashboard.applicationsBreakdown', {
                                today: stats.applications.today,
                                week: stats.applications.last_7_days,
                            })
                        }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between gap-2">
                            <CardDescription>{{
                                t('dashboard.dictionaries')
                            }}</CardDescription>
                            <BookOpen class="size-4 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl">{{
                            stats.dictionaries.system_sets
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{
                            t('dashboard.dictionariesBreakdown', {
                                words: stats.dictionaries.words,
                                languages: stats.dictionaries.languages,
                                personal: stats.dictionaries.personal_sets,
                            })
                        }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between gap-2">
                            <CardDescription>{{
                                t('dashboard.progress')
                            }}</CardDescription>
                            <GraduationCap
                                class="size-4 text-muted-foreground"
                            />
                        </div>
                        <CardTitle class="text-3xl">{{
                            stats.progress.due_cards
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground">
                        {{
                            t('dashboard.progressBreakdown', {
                                students: stats.progress.students_with_due,
                                today: stats.progress.reviews_today,
                            })
                        }}
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('dashboard.progressDetails') }}</CardTitle>
                        <CardDescription>{{
                            t('dashboard.progressDetailsHint')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-lg border bg-muted/30 px-3 py-2"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ t('admin.dueCards') }}
                                </dt>
                                <dd class="text-xl font-semibold">
                                    {{ stats.progress.due_cards }}
                                </dd>
                            </div>
                            <div
                                class="rounded-lg border bg-muted/30 px-3 py-2"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ t('dashboard.sessionsWeek') }}
                                </dt>
                                <dd class="text-xl font-semibold">
                                    {{ stats.progress.sessions_last_7_days }}
                                </dd>
                            </div>
                            <div
                                class="rounded-lg border bg-muted/30 px-3 py-2"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ t('admin.correctAnswers') }}
                                </dt>
                                <dd class="text-xl font-semibold">
                                    {{ stats.progress.total_correct }}
                                </dd>
                            </div>
                            <div
                                class="rounded-lg border bg-muted/30 px-3 py-2"
                            >
                                <dt class="text-xs text-muted-foreground">
                                    {{ t('common.wrong') }}
                                </dt>
                                <dd class="text-xl font-semibold">
                                    {{ stats.progress.total_wrong }}
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <Button as-child variant="outline" size="sm">
                                <Link :href="studentProgressIndex()">
                                    {{ t('dashboard.openProgress') }}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            t('dashboard.recentApplications')
                        }}</CardTitle>
                        <CardDescription>{{
                            t('dashboard.recentApplicationsHint')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul
                            v-if="stats.applications.recent.length"
                            class="divide-y rounded-lg border"
                        >
                            <li
                                v-for="application in stats.applications
                                    .recent"
                                :key="application.id"
                                class="flex items-start justify-between gap-3 px-3 py-2.5 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ application.full_name }}
                                    </p>
                                    <p
                                        class="truncate text-muted-foreground"
                                    >
                                        {{ application.course }} ·
                                        {{ application.email }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    {{ formatDate(application.created_at) }}
                                </span>
                            </li>
                        </ul>
                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('admin.noApplications') }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button as-child variant="outline" size="sm">
                                <Link :href="applicationsIndex()">
                                    {{ t('dashboard.openApplications') }}
                                </Link>
                            </Button>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="usersIndex()">
                                    {{ t('dashboard.openUsers') }}
                                </Link>
                            </Button>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="wordSetsIndex()">
                                    {{ t('dashboard.openDictionaries') }}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </template>

        <template v-else>
            <div class="grid gap-4 md:grid-cols-2">
                <Card v-if="hasRole('student')">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <LayoutGrid class="size-4 text-muted-foreground" />
                            <CardTitle>{{ t('student.reviewsTitle') }}</CardTitle>
                        </div>
                        <CardDescription>{{
                            t('dashboard.studentHint')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Button as-child>
                            <Link :href="reviewsIndex()">
                                {{ t('nav.reviews') }}
                            </Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="studentWordSetsIndex()">
                                {{ t('nav.myWordSets') }}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card v-if="hasRole('parent')">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Users class="size-4 text-muted-foreground" />
                            <CardTitle>{{
                                t('parent.dashboardTitle')
                            }}</CardTitle>
                        </div>
                        <CardDescription>{{
                            t('dashboard.parentHint')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button as-child>
                            <Link :href="parentAnalyticsIndex()">
                                {{ t('nav.children') }}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </template>
    </div>
</template>
