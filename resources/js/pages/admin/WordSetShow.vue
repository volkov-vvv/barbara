<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import WordController from '@/actions/App/Http/Controllers/Admin/WordController';
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
type Word = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
};
type WordSet = {
    id: number;
    title: string;
    description: string;
    language_id: number;
    language?: Language;
    words: Word[];
};

const props = defineProps<{
    wordSet: WordSet;
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.dictionaries', href: wordSetsIndex() },
            { title: 'admin.editor' },
        ],
    },
});

const editingWordId = ref<number | null>(null);

function destroyWord(word: Word): void {
    if (!confirm(t('admin.deleteWordConfirm', { text: word.text }))) {
        return;
    }

    router.delete(
        WordController.destroy.url({
            wordSet: props.wordSet.id,
            word: word.id,
        }),
    );
}
</script>

<template>
    <Head
        :title="t('admin.editDictionary', { title: wordSet.title })"
    />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="wordSet.title"
                :description="
                    t('admin.dictionaryMeta', {
                        language: wordSet.language?.name ?? t('admin.language'),
                        count: wordSet.words.length,
                    })
                "
            />
            <Button as-child variant="outline">
                <Link :href="wordSetsIndex()">{{ t('common.back') }}</Link>
            </Button>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('admin.dictionaryDetails') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="WordSetController.update.form(wordSet.id)"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="language_id"
                            :value="wordSet.language_id"
                        />
                        <div class="grid gap-2">
                            <Label for="title">{{ t('admin.title') }}</Label>
                            <Input
                                id="title"
                                name="title"
                                :default-value="wordSet.title"
                                required
                            />
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
                                :value="wordSet.description"
                            />
                            <InputError :message="errors.description" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.saveDetails') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('admin.batchImport') }}</CardTitle>
                    <CardDescription>
                        {{ t('admin.batchImportHint') }}
                        <code class="text-xs">text|translation|example</code>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="WordController.importMethod.form(wordSet.id)"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="words">{{ t('admin.words') }}</Label>
                            <textarea
                                id="words"
                                name="words"
                                rows="8"
                                required
                                placeholder="hello|привет|Hello, world!"
                                class="border-input w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                            <InputError :message="errors.words" />
                        </div>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.import') }}
                        </Button>
                    </Form>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.addWord') }}</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="WordController.store.form(wordSet.id)"
                    class="grid gap-4 md:grid-cols-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="text">{{ t('admin.text') }}</Label>
                        <Input id="text" name="text" required />
                        <InputError :message="errors.text" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="translation">{{
                            t('admin.translation')
                        }}</Label>
                        <Input id="translation" name="translation" required />
                        <InputError :message="errors.translation" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="example_sentence">{{
                            t('admin.example')
                        }}</Label>
                        <Input
                            id="example_sentence"
                            name="example_sentence"
                            required
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="audio_url">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input id="audio_url" name="audio_url" />
                        <InputError :message="errors.audio_url" />
                    </div>
                    <div class="md:col-span-2">
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.addWord') }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.words') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-if="!wordSet.words.length"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('admin.noWordsYet') }}
                </div>

                <div
                    v-for="word in wordSet.words"
                    :key="word.id"
                    class="rounded-lg border p-4"
                >
                    <div
                        v-if="editingWordId !== word.id"
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <p class="font-medium">
                                {{ word.text }}
                                <span class="text-muted-foreground">→</span>
                                {{ word.translation }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ word.example_sentence }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="editingWordId = word.id"
                            >
                                {{ t('common.edit') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="destroyWord(word)"
                            >
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>

                    <Form
                        v-else
                        v-bind="
                            WordController.update.form({
                                wordSet: wordSet.id,
                                word: word.id,
                            })
                        "
                        class="grid gap-3 md:grid-cols-2"
                        v-slot="{ errors, processing }"
                        @success="editingWordId = null"
                    >
                        <div class="grid gap-2">
                            <Label>{{ t('admin.text') }}</Label>
                            <Input
                                name="text"
                                :default-value="word.text"
                                required
                            />
                            <InputError :message="errors.text" />
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ t('admin.translation') }}</Label>
                            <Input
                                name="translation"
                                :default-value="word.translation"
                                required
                            />
                            <InputError :message="errors.translation" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label>{{ t('admin.example') }}</Label>
                            <Input
                                name="example_sentence"
                                :default-value="word.example_sentence"
                                required
                            />
                            <InputError :message="errors.example_sentence" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label>{{ t('admin.audioUrl') }}</Label>
                            <Input
                                name="audio_url"
                                :default-value="word.audio_url ?? ''"
                            />
                            <InputError :message="errors.audio_url" />
                        </div>
                        <div class="flex gap-2 md:col-span-2">
                            <Button type="submit" :disabled="processing">
                                {{ t('common.save') }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="editingWordId = null"
                            >
                                {{ t('common.cancel') }}
                            </Button>
                        </div>
                    </Form>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
