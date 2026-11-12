<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Columns3,
    Eye,
    Filter,
    Pencil,
    Plus,
    RotateCcw,
    Settings2,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import WordController from '@/actions/App/Http/Controllers/Student/WordController';
import WordSetController from '@/actions/App/Http/Controllers/Student/WordSetController';
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
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPaginationLabel } from '@/lib/pagination';
import { index as wordSetsIndex } from '@/routes/student/word-sets';

type ColumnKey =
    | 'id'
    | 'image'
    | 'text'
    | 'translation'
    | 'example_sentence'
    | 'created_at';

type ColumnDefinition = {
    key: ColumnKey;
    label: string;
    default: boolean;
    sortable: boolean;
};

type Language = { id: number; code: string; name: string };

type WordRow = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
    image_path: string | null;
    image_url: string | null;
    created_at: string | null;
};

type PaginatedWords = {
    data: WordRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Filters = {
    search: string | null;
    has_example: string | null;
};

type SortState = {
    column: string;
    direction: 'asc' | 'desc';
};

type WordSet = {
    id: number;
    title: string;
    description: string;
    language_id: number;
    language: Language | null;
};

const COLUMNS_STORAGE_KEY = 'student.wordSetShow.visibleColumns';

const props = defineProps<{
    wordSet: WordSet;
    words: PaginatedWords;
    languages: Language[];
    filters: Filters;
    sort: SortState;
    columns: ColumnDefinition[];
}>();

const { t, locale } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.myWordSets', href: wordSetsIndex() },
            { title: 'admin.viewDictionary' },
        ],
    },
});

const localFilters = ref({
    search: props.filters.search ?? '',
    has_example: props.filters.has_example ?? '',
});

watch(
    () => props.filters,
    (value) => {
        localFilters.value = {
            search: value.search ?? '',
            has_example: value.has_example ?? '',
        };
    },
    { deep: true },
);

function loadVisibleColumns(): ColumnKey[] {
    const defaults = props.columns
        .filter((column) => column.default)
        .map((column) => column.key);

    try {
        const raw = localStorage.getItem(COLUMNS_STORAGE_KEY);

        if (!raw) {
            return defaults;
        }

        const parsed = JSON.parse(raw) as string[];
        const allowed = new Set(props.columns.map((column) => column.key));
        const valid = parsed.filter((key): key is ColumnKey =>
            allowed.has(key as ColumnKey),
        );

        return valid.length > 0 ? valid : defaults;
    } catch {
        return defaults;
    }
}

const visibleColumns = ref<ColumnKey[]>(loadVisibleColumns());

watch(
    visibleColumns,
    (value) => {
        localStorage.setItem(COLUMNS_STORAGE_KEY, JSON.stringify(value));
    },
    { deep: true },
);

const detailsOpen = ref(false);
const detailsFormKey = ref(0);
const addWordOpen = ref(false);
const addWordFormKey = ref(0);
const editingWord = ref<WordRow | null>(null);
const editWordFormKey = ref(0);

const activeFilterCount = computed(() =>
    Object.values(props.filters).filter((value) => !!value).length,
);

const orderedVisibleColumns = computed(() =>
    props.columns.filter((column) => isColumnVisible(column.key)),
);

function columnLabel(key: ColumnKey): string {
    return t(`admin.wordFields.${key}`);
}

function isColumnVisible(key: ColumnKey): boolean {
    return visibleColumns.value.includes(key);
}

function toggleColumn(
    key: ColumnKey,
    checked: boolean | 'indeterminate',
): void {
    const nextChecked = checked === true;

    if (nextChecked) {
        if (!visibleColumns.value.includes(key)) {
            visibleColumns.value = [...visibleColumns.value, key];
        }

        return;
    }

    if (visibleColumns.value.length === 1) {
        return;
    }

    visibleColumns.value = visibleColumns.value.filter(
        (column) => column !== key,
    );
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

function cellValue(word: WordRow, key: ColumnKey): string {
    switch (key) {
        case 'id':
            return String(word.id);
        case 'image':
            return word.image_url ? t('admin.image') : '—';
        case 'example_sentence':
            return word.example_sentence?.trim() || '—';
        case 'created_at':
            return formatDate(word.created_at);
        default:
            return word[key] || '—';
    }
}

function buildQuery(
    overrides: Partial<Record<string, string>> = {},
): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(localFilters.value)) {
        const trimmed = value.trim();

        if (trimmed !== '') {
            query[key] = trimmed;
        }
    }

    const sortColumn = overrides.sort ?? props.sort.column;
    const sortDirection = overrides.direction ?? props.sort.direction;

    if (sortColumn !== 'id' || sortDirection !== 'desc') {
        query.sort = sortColumn;
        query.direction = sortDirection;
    }

    for (const [key, value] of Object.entries(overrides)) {
        if (key === 'sort' || key === 'direction') {
            continue;
        }

        if (value) {
            query[key] = value;
        } else {
            delete query[key];
        }
    }

    return query;
}

function navigateWithQuery(query: Record<string, string>): void {
    router.get(
        WordSetController.show.url(
            props.wordSet.id,
            Object.keys(query).length > 0 ? { query } : undefined,
        ),
        {},
        { preserveState: true, replace: true },
    );
}

function applyFilters(): void {
    navigateWithQuery(buildQuery());
}

function resetFilters(): void {
    localFilters.value = {
        search: '',
        has_example: '',
    };

    const query: Record<string, string> = {};

    if (props.sort.column !== 'id' || props.sort.direction !== 'desc') {
        query.sort = props.sort.column;
        query.direction = props.sort.direction;
    }

    navigateWithQuery(query);
}

function sortBy(column: ColumnKey): void {
    const definition = props.columns.find((item) => item.key === column);

    if (!definition?.sortable) {
        return;
    }

    let direction: 'asc' | 'desc' = 'asc';

    if (props.sort.column === column) {
        direction = props.sort.direction === 'asc' ? 'desc' : 'asc';
    }

    navigateWithQuery(
        buildQuery({
            sort: column,
            direction,
        }),
    );
}

function isSorted(column: ColumnKey): boolean {
    return props.sort.column === column;
}

function openDetails(): void {
    detailsFormKey.value += 1;
    detailsOpen.value = true;
}

function openAddWord(): void {
    addWordFormKey.value += 1;
    addWordOpen.value = true;
}

function openEditWord(word: WordRow): void {
    editingWord.value = word;
    editWordFormKey.value += 1;
}

function closeEditWord(): void {
    editingWord.value = null;
}

function destroyWord(word: WordRow): void {
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

function onDictionaryUpdated(): void {
    detailsOpen.value = false;
}

function onWordCreated(): void {
    addWordOpen.value = false;
}

function onWordUpdated(): void {
    closeEditWord();
}
</script>

<template>
    <Head
        :title="t('admin.viewDictionaryTitle', { title: wordSet.title })"
    />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="wordSet.title"
                :description="
                    t('admin.dictionaryMeta', {
                        language: wordSet.language?.name ?? t('admin.language'),
                        count: words.total,
                    })
                "
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" @click="openDetails">
                    <Settings2 class="size-4" />
                    {{ t('admin.dictionaryDetails') }}
                </Button>
                <Button as-child variant="outline">
                    <Link :href="wordSetsIndex()">{{ t('common.back') }}</Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader class="gap-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Filter class="size-4" />
                        {{ t('admin.filters') }}
                        <span
                            v-if="activeFilterCount > 0"
                            class="rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </CardTitle>
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="activeFilterCount === 0"
                        @click="resetFilters"
                    >
                        <RotateCcw class="size-4" />
                        {{ t('admin.resetFilters') }}
                    </Button>
                </div>

                <form
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"
                    @submit.prevent="applyFilters"
                >
                    <div class="space-y-1.5 xl:col-span-2">
                        <Label for="filter-search">{{
                            t('admin.search')
                        }}</Label>
                        <Input
                            id="filter-search"
                            v-model="localFilters.search"
                            :placeholder="t('admin.wordsSearchPlaceholder')"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-has-example">{{
                            t('admin.hasExample')
                        }}</Label>
                        <select
                            id="filter-has-example"
                            v-model="localFilters.has_example"
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">{{ t('common.all') }}</option>
                            <option value="1">
                                {{ t('admin.withExample') }}
                            </option>
                            <option value="0">
                                {{ t('admin.withoutExample') }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <Button type="submit">{{
                            t('admin.applyFilters')
                        }}</Button>
                    </div>
                </form>
            </CardHeader>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>
                        {{ t('admin.words') }}
                        <span
                            class="ml-2 text-sm font-normal text-muted-foreground"
                        >
                            {{ words.total }}
                        </span>
                    </CardTitle>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button size="sm" @click="openAddWord">
                            <Plus class="size-4" />
                            {{ t('admin.addWord') }}
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm">
                                    <Columns3 class="size-4" />
                                    {{ t('admin.columns') }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>{{
                                    t('admin.configureColumns')
                                }}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuCheckboxItem
                                    v-for="column in columns"
                                    :key="column.key"
                                    :model-value="isColumnVisible(column.key)"
                                    @select.prevent
                                    @update:model-value="
                                        (checked) =>
                                            toggleColumn(column.key, checked)
                                    "
                                >
                                    {{ columnLabel(column.key) }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-muted/60 text-muted-foreground">
                            <tr>
                                <th
                                    v-for="column in orderedVisibleColumns"
                                    :key="column.key"
                                    class="whitespace-nowrap px-3 py-3.5 font-medium"
                                    :class="
                                        column.sortable
                                            ? 'cursor-pointer select-none hover:text-foreground'
                                            : ''
                                    "
                                    @click="
                                        column.sortable
                                            ? sortBy(column.key)
                                            : undefined
                                    "
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        {{ columnLabel(column.key) }}
                                        <template v-if="column.sortable">
                                            <ArrowUp
                                                v-if="
                                                    isSorted(column.key) &&
                                                    sort.direction === 'asc'
                                                "
                                                class="size-3.5 text-foreground"
                                            />
                                            <ArrowDown
                                                v-else-if="
                                                    isSorted(column.key) &&
                                                    sort.direction === 'desc'
                                                "
                                                class="size-3.5 text-foreground"
                                            />
                                            <ArrowUpDown
                                                v-else
                                                class="size-3.5 opacity-40"
                                            />
                                        </template>
                                    </span>
                                </th>
                                <th
                                    class="whitespace-nowrap px-3 py-3.5 text-right font-medium"
                                >
                                    {{ t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="word in words.data"
                                :key="word.id"
                                class="border-t transition-colors hover:bg-muted/60"
                            >
                                <td
                                    v-for="column in orderedVisibleColumns"
                                    :key="`${word.id}-${column.key}`"
                                    class="max-w-md px-3 py-2 align-middle"
                                    :class="
                                        column.key === 'example_sentence'
                                            ? 'text-muted-foreground'
                                            : column.key === 'text'
                                              ? 'font-medium'
                                              : ''
                                    "
                                >
                                    <div
                                        v-if="column.key === 'image'"
                                        class="flex items-center"
                                    >
                                        <img
                                            v-if="word.image_url"
                                            :src="word.image_url"
                                            :alt="word.text"
                                            class="size-10 rounded-md object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                        >
                                            —
                                        </span>
                                    </div>
                                    <template v-else>
                                        {{ cellValue(word, column.key) }}
                                    </template>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <div
                                        class="flex flex-wrap justify-end gap-1"
                                    >
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            as-child
                                            :title="t('common.view')"
                                            :aria-label="t('common.view')"
                                        >
                                            <Link
                                                :href="
                                                    WordController.show.url({
                                                        wordSet: wordSet.id,
                                                        word: word.id,
                                                    })
                                                "
                                            >
                                                <Eye class="size-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            :title="t('common.edit')"
                                            :aria-label="t('common.edit')"
                                            @click="openEditWord(word)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="destructive"
                                            :title="t('common.delete')"
                                            :aria-label="t('common.delete')"
                                            @click="destroyWord(word)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="words.data.length === 0">
                                <td
                                    :colspan="orderedVisibleColumns.length + 1"
                                    class="px-3 py-8 text-center text-muted-foreground"
                                >
                                    {{ t('admin.noWordsFound') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-2">
                    <template v-for="link in words.links" :key="link.label">
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

        <Dialog v-model:open="detailsOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            t('admin.editDictionary', { title: wordSet.title })
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ t('student.dictionaryDetailsDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="detailsFormKey"
                    v-bind="WordSetController.update.form(wordSet.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onDictionaryUpdated"
                >
                    <div class="grid gap-2">
                        <Label for="edit-language_id">{{
                            t('admin.language')
                        }}</Label>
                        <select
                            id="edit-language_id"
                            name="language_id"
                            required
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            :value="wordSet.language_id"
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
                        <Label for="edit-title">{{ t('admin.title') }}</Label>
                        <Input
                            id="edit-title"
                            name="title"
                            required
                            :default-value="wordSet.title"
                        />
                        <InputError :message="errors.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-description">{{
                            t('admin.description')
                        }}</Label>
                        <textarea
                            id="edit-description"
                            name="description"
                            rows="3"
                            required
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            :value="wordSet.description"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="detailsOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.saveDetails') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="addWordOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.addWord') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.addWordDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="addWordFormKey"
                    v-bind="WordController.store.form(wordSet.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onWordCreated"
                >
                    <div class="grid gap-2">
                        <Label for="add-text">{{ t('admin.text') }}</Label>
                        <Input id="add-text" name="text" required />
                        <InputError :message="errors.text" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-translation">{{
                            t('admin.translation')
                        }}</Label>
                        <Input
                            id="add-translation"
                            name="translation"
                            required
                        />
                        <InputError :message="errors.translation" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-example">{{
                            t('admin.exampleOptional')
                        }}</Label>
                        <textarea
                            id="add-example"
                            name="example_sentence"
                            rows="2"
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-audio">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input id="add-audio" name="audio_url" />
                        <InputError :message="errors.audio_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-image">{{
                            t('admin.imageOptional')
                        }}</Label>
                        <Input
                            id="add-image"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        />
                        <InputError :message="errors.image" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="addWordOpen = false"
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

        <Dialog
            :open="editingWord !== null"
            @update:open="(open) => !open && closeEditWord()"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.editWord') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('student.editWordDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-if="editingWord"
                    :key="editWordFormKey"
                    v-bind="
                        WordController.update.form({
                            wordSet: wordSet.id,
                            word: editingWord.id,
                        })
                    "
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onWordUpdated"
                >
                    <div class="grid gap-2">
                        <Label for="edit-word-text">{{
                            t('admin.text')
                        }}</Label>
                        <Input
                            id="edit-word-text"
                            name="text"
                            required
                            :default-value="editingWord.text"
                        />
                        <InputError :message="errors.text" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-word-translation">{{
                            t('admin.translation')
                        }}</Label>
                        <Input
                            id="edit-word-translation"
                            name="translation"
                            required
                            :default-value="editingWord.translation"
                        />
                        <InputError :message="errors.translation" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-word-example">{{
                            t('admin.exampleOptional')
                        }}</Label>
                        <textarea
                            id="edit-word-example"
                            name="example_sentence"
                            rows="2"
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            :value="editingWord.example_sentence"
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-word-audio">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input
                            id="edit-word-audio"
                            name="audio_url"
                            :default-value="editingWord.audio_url ?? ''"
                        />
                        <InputError :message="errors.audio_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-word-image">{{
                            t('admin.imageOptional')
                        }}</Label>
                        <div
                            v-if="editingWord.image_url"
                            class="flex flex-wrap items-center gap-3"
                        >
                            <img
                                :src="editingWord.image_url"
                                :alt="editingWord.text"
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
                            id="edit-word-image"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                        />
                        <InputError :message="errors.image" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeEditWord"
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
