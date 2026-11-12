<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
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
import { index as wordSetsIndex } from '@/routes/admin/word-sets';

type Language = { id: number; code: string; name: string };
type ThemeOption = { id: number; name: string };

type Word = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
    image_path: string | null;
    image_url: string | null;
    themes: ThemeOption[];
    created_at: string | null;
    updated_at: string | null;
};

type WordSet = {
    id: number;
    title: string;
    language: Language | null;
};

const props = defineProps<{
    wordSet: WordSet;
    word: Word;
    themes: ThemeOption[];
}>();

const { t, locale } = useI18n();

defineOptions({
    layout: (pageProps: { wordSet: WordSet }) => ({
        breadcrumbs: [
            { title: 'nav.dictionaries', href: wordSetsIndex() },
            {
                title: 'admin.viewDictionary',
                href: WordSetController.view.url(pageProps.wordSet.id),
            },
            { title: 'admin.viewWord' },
        ],
    }),
});

const editOpen = ref(false);
const editFormKey = ref(0);

function wordHasTheme(themeId: number): boolean {
    return props.word.themes.some((theme) => theme.id === themeId);
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}

function openEdit(): void {
    editFormKey.value += 1;
    editOpen.value = true;
}

function onEditSuccess(): void {
    editOpen.value = false;
}

function destroyWord(): void {
    if (!confirm(t('admin.deleteWordConfirm', { text: props.word.text }))) {
        return;
    }

    router.delete(
        WordController.destroy.url({
            wordSet: props.wordSet.id,
            word: props.word.id,
        }),
    );
}
</script>

<template>
    <Head
        :title="
            t('admin.viewWordTitle', {
                text: word.text,
                translation: word.translation,
            })
        "
    />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="word.text"
                :description="
                    t('admin.viewWordMeta', {
                        translation: word.translation,
                        dictionary: wordSet.title,
                    })
                "
            />
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="WordSetController.view.url(wordSet.id)">
                        {{ t('common.back') }}
                    </Link>
                </Button>
                <Button variant="outline" @click="openEdit">
                    <Pencil class="size-4" />
                    {{ t('common.edit') }}
                </Button>
                <Button variant="destructive" @click="destroyWord">
                    <Trash2 class="size-4" />
                    {{ t('common.delete') }}
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,20rem)_1fr]">
            <Card>
                <CardContent class="flex items-center justify-center p-6">
                    <img
                        v-if="word.image_url"
                        :src="word.image_url"
                        :alt="word.text"
                        class="max-h-80 w-full rounded-lg object-contain"
                    />
                    <div
                        v-else
                        class="flex aspect-square w-full items-center justify-center rounded-lg border border-dashed text-sm text-muted-foreground"
                    >
                        {{ t('admin.noWordImage') }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('admin.wordDetails') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="text-xs text-muted-foreground">ID</dt>
                            <dd class="text-sm font-medium">{{ word.id }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.wordFields.created_at') }}
                            </dt>
                            <dd class="text-sm font-medium">
                                {{ formatDate(word.created_at) }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.text') }}
                            </dt>
                            <dd class="text-lg font-semibold">
                                {{ word.text }}
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.translation') }}
                            </dt>
                            <dd class="text-lg font-semibold">
                                {{ word.translation }}
                            </dd>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.example') }}
                            </dt>
                            <dd class="text-sm font-medium whitespace-pre-wrap">
                                {{ word.example_sentence?.trim() || '—' }}
                            </dd>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.themes') }}
                            </dt>
                            <dd class="flex flex-wrap gap-1.5">
                                <template v-if="word.themes.length">
                                    <span
                                        v-for="theme in word.themes"
                                        :key="theme.id"
                                        class="rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ theme.name }}
                                    </span>
                                </template>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground"
                                >
                                    —
                                </span>
                            </dd>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <dt class="text-xs text-muted-foreground">
                                {{ t('admin.audioUrl') }}
                            </dt>
                            <dd class="text-sm font-medium break-all">
                                <a
                                    v-if="word.audio_url"
                                    :href="word.audio_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary underline-offset-2 hover:underline"
                                >
                                    {{ word.audio_url }}
                                </a>
                                <span v-else>—</span>
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="editOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.editWord') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.editWordDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="editFormKey"
                    v-bind="
                        WordController.update.form({
                            wordSet: wordSet.id,
                            word: word.id,
                        })
                    "
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onEditSuccess"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="show-edit-text">{{
                                t('admin.text')
                            }}</Label>
                            <Input
                                id="show-edit-text"
                                name="text"
                                :default-value="word.text"
                                required
                            />
                            <InputError :message="errors.text" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="show-edit-translation">{{
                                t('admin.translation')
                            }}</Label>
                            <Input
                                id="show-edit-translation"
                                name="translation"
                                :default-value="word.translation"
                                required
                            />
                            <InputError :message="errors.translation" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="show-edit-example">{{
                            t('admin.exampleOptional')
                        }}</Label>
                        <Input
                            id="show-edit-example"
                            name="example_sentence"
                            :default-value="word.example_sentence"
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="show-edit-audio">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input
                            id="show-edit-audio"
                            name="audio_url"
                            :default-value="word.audio_url ?? ''"
                        />
                        <InputError :message="errors.audio_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="show-edit-image">{{
                            t('admin.imageOptional')
                        }}</Label>
                        <div
                            v-if="word.image_url"
                            class="flex flex-wrap items-center gap-3"
                        >
                            <img
                                :src="word.image_url"
                                :alt="word.text"
                                class="size-16 rounded-md object-cover"
                            />
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="remove_image"
                                    value="1"
                                    class="size-4 rounded border"
                                />
                                {{ t('admin.removeImage') }}
                            </label>
                        </div>
                        <Input
                            id="show-edit-image"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        />
                        <InputError :message="errors.image" />
                    </div>
                    <div class="grid gap-2">
                        <Label>{{ t('admin.themes') }}</Label>
                        <p class="text-xs text-muted-foreground">
                            {{ t('admin.themesOptionalHint') }}
                        </p>
                        <div
                            v-if="themes.length"
                            class="flex flex-wrap gap-3"
                        >
                            <label
                                v-for="theme in themes"
                                :key="`show-theme-${theme.id}`"
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="theme_ids[]"
                                    :value="theme.id"
                                    :checked="wordHasTheme(theme.id)"
                                    class="size-4 rounded border"
                                />
                                {{ theme.name }}
                            </label>
                        </div>
                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('admin.noThemesYet') }}
                        </p>
                        <InputError :message="errors.theme_ids" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="editOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('common.save') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
