<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import WordSetController from '@/actions/App/Http/Controllers/Admin/WordSetController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPaginationLabel } from '@/lib/pagination';
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

const createOpen = ref(false);
const createFormKey = ref(0);

function openCreate(): void {
    createFormKey.value += 1;
    createOpen.value = true;
}

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

function onDictionaryCreated(): void {
    createOpen.value = false;
}
</script>

<template>
    <Head :title="t('admin.dictionariesTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="t('admin.dictionariesTitle')"
                :description="t('admin.dictionariesDescription')"
            />
            <Button @click="openCreate">
                <Plus class="size-4" />
                {{ t('admin.createDictionary') }}
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="grid gap-2">
                <Label for="filter_language_id">{{
                    t('admin.language')
                }}</Label>
                <select
                    id="filter_language_id"
                    class="border-input h-9 min-w-48 rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
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

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.dictionariesList') }}</CardTitle>
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
                            <Link :href="link.url">
                                {{ formatPaginationLabel(link.label, t) }}
                            </Link>
                        </Button>
                    </template>
                </div>
            </CardContent>
        </Card>

        <Dialog v-model:open="createOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        t('admin.createDictionary')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.createDictionaryDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="createFormKey"
                    v-bind="WordSetController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onDictionaryCreated"
                >
                    <div class="grid gap-2">
                        <Label for="create-language_id">{{
                            t('admin.language')
                        }}</Label>
                        <select
                            id="create-language_id"
                            name="language_id"
                            required
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
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
                        <Label for="create-title">{{
                            t('admin.title')
                        }}</Label>
                        <Input id="create-title" name="title" required />
                        <InputError :message="errors.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-description">{{
                            t('admin.description')
                        }}</Label>
                        <textarea
                            id="create-description"
                            name="description"
                            rows="3"
                            required
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('common.create') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
