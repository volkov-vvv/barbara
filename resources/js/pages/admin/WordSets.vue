<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import WordSetController from '@/actions/App/Http/Controllers/Admin/WordSetController';
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
import { index as wordSetsIndex } from '@/routes/admin/word-sets';

type Language = { id: number; code: string; name: string };
type WordSetRow = {
    id: number;
    title: string;
    description: string;
    language_id: number;
    language?: Language;
    words_count: number;
};
type PaginatedWordSets = {
    data: WordSetRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    wordSets: PaginatedWordSets;
    languages: Language[];
    filters: { language_id: number | null };
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'nav.dictionaries', href: wordSetsIndex() }],
    },
});

function filterByLanguage(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;

    router.get(
        wordSetsIndex.url(
            value ? { query: { language_id: value } } : undefined,
        ),
        {},
        { preserveState: true, replace: true },
    );
}

function destroySet(wordSet: WordSetRow): void {
    if (
        !confirm(
            t('admin.deleteDictionaryConfirm', { title: wordSet.title }),
        )
    ) {
        return;
    }

    router.delete(WordSetController.destroy.url(wordSet.id));
}
</script>

<template>
    <Head :title="t('admin.dictionariesTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('admin.dictionariesTitle')"
            :description="t('admin.dictionariesDescription')"
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>{{ t('admin.newDictionary') }}</CardTitle>
                    <CardDescription>{{
                        t('admin.newDictionaryHint')
                    }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="WordSetController.store.form()"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="language_id">{{
                                t('admin.language')
                            }}</Label>
                            <select
                                id="language_id"
                                name="language_id"
                                required
                                class="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            >
                                <option
                                    v-for="language in languages"
                                    :key="language.id"
                                    :value="language.id"
                                >
                                    {{ language.name }} ({{ language.code }})
                                </option>
                            </select>
                            <InputError :message="errors.language_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="title">{{ t('admin.title') }}</Label>
                            <Input id="title" name="title" required />
                            <InputError :message="errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">{{
                                t('admin.description')
                            }}</Label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                required
                                class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            {{ t('common.create') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card class="lg:col-span-3">
                <CardHeader class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <CardTitle>{{
                                t('admin.dictionariesList')
                            }}</CardTitle>
                            <CardDescription class="mt-1.5">
                                {{ t('admin.filterByLanguage') }}
                            </CardDescription>
                        </div>
                        <div class="grid gap-2">
                            <Label for="filter_language_id">{{
                                t('admin.language')
                            }}</Label>
                            <select
                                id="filter_language_id"
                                class="border-input h-9 min-w-48 rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                :value="filters.language_id ?? ''"
                                @change="filterByLanguage"
                            >
                                <option value="">
                                    {{ t('admin.allLanguages') }}
                                </option>
                                <option
                                    v-for="language in languages"
                                    :key="language.id"
                                    :value="language.id"
                                >
                                    {{ language.name }} ({{ language.code }})
                                </option>
                            </select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="!wordSets.data.length"
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        {{
                            filters.language_id
                                ? t('admin.noDictionariesForLanguage')
                                : t('admin.noDictionariesYet')
                        }}
                    </div>

                    <div
                        v-for="wordSet in wordSets.data"
                        :key="wordSet.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ wordSet.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ wordSet.language?.name }} ·
                                {{
                                    t('admin.wordsCount', {
                                        count: wordSet.words_count,
                                    })
                                }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button as-child size="sm" variant="outline">
                                <Link
                                    :href="
                                        WordSetController.show.url(wordSet.id)
                                    "
                                >
                                    {{ t('common.edit') }}
                                </Link>
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="destroySet(wordSet)"
                            >
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2">
                        <template
                            v-for="link in wordSets.links"
                            :key="link.label"
                        >
                            <Button
                                v-if="link.url"
                                as-child
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                            >
                                <Link :href="link.url" v-html="link.label" />
                            </Button>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
