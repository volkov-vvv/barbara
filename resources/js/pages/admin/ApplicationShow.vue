<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ApplicationController from '@/actions/App/Http/Controllers/Admin/ApplicationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as applicationsIndex } from '@/routes/admin/applications';

type ApplicationDetails = {
    id: number;
    full_name: string;
    email: string;
    phone: string;
    region: string;
    course: string;
    level: string;
    comment: string | null;
    document_path: string;
    document_name: string;
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    application: ApplicationDetails;
    regions: string[];
    courses: string[];
    levels: string[];
}>();

const { t, locale } = useI18n();
const editing = ref(false);
const editFormKey = ref(0);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.applications', href: applicationsIndex() },
            { title: 'admin.applicationDetails' },
        ],
    },
});

const selectClass =
    'border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

const fields: Array<{ key: keyof ApplicationDetails; label: string }> = [
    { key: 'id', label: 'id' },
    { key: 'full_name', label: 'full_name' },
    { key: 'email', label: 'email' },
    { key: 'phone', label: 'phone' },
    { key: 'region', label: 'region' },
    { key: 'course', label: 'course' },
    { key: 'level', label: 'level' },
    { key: 'comment', label: 'comment' },
    { key: 'document_name', label: 'document' },
    { key: 'created_at', label: 'created_at' },
    { key: 'updated_at', label: 'updated_at' },
];

function fieldValue(key: keyof ApplicationDetails): string {
    const value = props.application[key];

    if (key === 'created_at' || key === 'updated_at') {
        return formatDate(value as string | null);
    }

    if (typeof value === 'string') {
        return value.trim() || '—';
    }

    if (value === null || value === undefined) {
        return '—';
    }

    return String(value);
}

function startEdit(): void {
    editFormKey.value += 1;
    editing.value = true;
}

function onEditSuccess(): void {
    editing.value = false;
}

function destroyApplication(): void {
    if (
        !confirm(
            t('admin.deleteApplicationConfirm', {
                name: props.application.full_name,
            }),
        )
    ) {
        return;
    }

    router.delete(ApplicationController.destroy.url(props.application.id));
}
</script>

<template>
    <Head :title="t('admin.applicationDetails')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="t('admin.applicationDetails')"
                :description="application.full_name"
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="applicationsIndex()">
                        {{ t('common.back') }}
                    </Link>
                </Button>
                <Button as-child>
                    <a
                        :href="
                            ApplicationController.download.url(application.id)
                        "
                    >
                        {{ t('admin.downloadDocument') }}
                    </a>
                </Button>
                <Button
                    v-if="!editing"
                    variant="outline"
                    @click="startEdit"
                >
                    <Pencil class="size-4" />
                    {{ t('common.edit') }}
                </Button>
                <Button variant="destructive" @click="destroyApplication">
                    <Trash2 class="size-4" />
                    {{ t('common.delete') }}
                </Button>
            </div>
        </div>

        <Card v-if="!editing">
            <CardHeader>
                <CardTitle>{{ t('admin.applicationFieldsTitle') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="field in fields"
                        :key="field.key"
                        class="space-y-1 rounded-lg border p-3"
                        :class="
                            field.key === 'comment' ||
                            field.key === 'document_name'
                                ? 'sm:col-span-2'
                                : ''
                        "
                    >
                        <dt class="text-xs text-muted-foreground">
                            {{ t(`admin.applicationFields.${field.label}`) }}
                        </dt>
                        <dd class="text-sm font-medium whitespace-pre-wrap">
                            <a
                                v-if="field.key === 'document_name'"
                                :href="
                                    ApplicationController.download.url(
                                        application.id,
                                    )
                                "
                                class="text-primary underline-offset-2 hover:underline"
                            >
                                {{ application.document_name }}
                            </a>
                            <template v-else>
                                {{ fieldValue(field.key) }}
                            </template>
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader>
                <CardTitle>{{ t('admin.editApplication') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    :key="editFormKey"
                    :action="ApplicationController.update.url(application.id)"
                    method="put"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onEditSuccess"
                >
                    <div class="grid gap-2">
                        <Label for="show-full_name">{{
                            t('admin.applicationFields.full_name')
                        }}</Label>
                        <Input
                            id="show-full_name"
                            name="full_name"
                            :default-value="application.full_name"
                            required
                        />
                        <InputError :message="errors.full_name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="show-email">{{
                                t('admin.applicationFields.email')
                            }}</Label>
                            <Input
                                id="show-email"
                                type="email"
                                name="email"
                                :default-value="application.email"
                                required
                            />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="show-phone">{{
                                t('admin.applicationFields.phone')
                            }}</Label>
                            <Input
                                id="show-phone"
                                name="phone"
                                :default-value="application.phone"
                                required
                            />
                            <InputError :message="errors.phone" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="show-region">{{
                            t('admin.applicationFields.region')
                        }}</Label>
                        <select
                            id="show-region"
                            name="region"
                            required
                            :class="selectClass"
                            :value="application.region"
                        >
                            <option
                                v-for="region in regions"
                                :key="region"
                                :value="region"
                            >
                                {{ region }}
                            </option>
                        </select>
                        <InputError :message="errors.region" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="show-course">{{
                                t('admin.applicationFields.course')
                            }}</Label>
                            <select
                                id="show-course"
                                name="course"
                                required
                                :class="selectClass"
                                :value="application.course"
                            >
                                <option
                                    v-for="course in courses"
                                    :key="course"
                                    :value="course"
                                >
                                    {{ course }}
                                </option>
                            </select>
                            <InputError :message="errors.course" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="show-level">{{
                                t('admin.applicationFields.level')
                            }}</Label>
                            <select
                                id="show-level"
                                name="level"
                                required
                                :class="selectClass"
                                :value="application.level"
                            >
                                <option
                                    v-for="level in levels"
                                    :key="level"
                                    :value="level"
                                >
                                    {{ level }}
                                </option>
                            </select>
                            <InputError :message="errors.level" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="show-comment">{{
                            t('admin.applicationFields.comment')
                        }}</Label>
                        <textarea
                            id="show-comment"
                            name="comment"
                            rows="4"
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            :value="application.comment ?? ''"
                        />
                        <InputError :message="errors.comment" />
                    </div>

                    <div class="grid gap-2">
                        <p class="text-xs text-muted-foreground">
                            {{ t('admin.currentDocument') }}:
                            <a
                                :href="
                                    ApplicationController.download.url(
                                        application.id,
                                    )
                                "
                                class="text-primary underline-offset-2 hover:underline"
                            >
                                {{ application.document_name }}
                            </a>
                        </p>
                        <Label for="show-document">{{
                            t('admin.replaceDocumentOptional')
                        }}</Label>
                        <Input
                            id="show-document"
                            type="file"
                            name="document"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        />
                        <InputError :message="errors.document" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" :disabled="processing">
                            {{ t('common.save') }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editing = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
