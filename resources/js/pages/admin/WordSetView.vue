<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Columns3,
    Eye,
    FileDown,
    FileSpreadsheet,
    Filter,
    ListPlus,
    Pencil,
    Plus,
    RotateCcw,
    Settings2,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

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
import { index as wordSetsIndex } from '@/routes/admin/word-sets';

type ColumnKey =
    | 'id'
    | 'image'
    | 'text'
    | 'translation'
    | 'example_sentence'
    | 'themes'
    | 'created_at';

type ColumnDefinition = {
    key: ColumnKey;
    label: string;
    default: boolean;
    sortable: boolean;
};

type Language = { id: number; code: string; name: string };
type ThemeOption = { id: number; name: string };
type DictionaryWord = {
    id: number;
    text: string;
    translation: string;
    theme_ids: number[];
};

type WordRow = {
    id: number;
    text: string;
    translation: string;
    example_sentence: string;
    audio_url: string | null;
    image_path: string | null;
    image_url: string | null;
    themes: ThemeOption[];
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
    theme_id: string | null;
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

const COLUMNS_STORAGE_KEY = 'admin.wordSetView.visibleColumns';

const props = defineProps<{
    wordSet: WordSet;
    words: PaginatedWords;
    themes: ThemeOption[];
    filters: Filters;
    sort: SortState;
    columns: ColumnDefinition[];
}>();

const { t, locale } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.dictionaries', href: wordSetsIndex() },
            { title: 'admin.viewDictionary' },
        ],
    },
});

const localFilters = ref({
    search: props.filters.search ?? '',
    has_example: props.filters.has_example ?? '',
    theme_id: props.filters.theme_id ?? '',
});

watch(
    () => props.filters,
    (value) => {
        localFilters.value = {
            search: value.search ?? '',
            has_example: value.has_example ?? '',
            theme_id: value.theme_id ?? '',
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

const editingWord = ref<WordRow | null>(null);
const editOpen = ref(false);
const editFormKey = ref(0);

const detailsOpen = ref(false);
const detailsFormKey = ref(0);
const excelImportOpen = ref(false);
const excelImportFormKey = ref(0);
const batchImportOpen = ref(false);
const batchImportFormKey = ref(0);
const addWordOpen = ref(false);
const addWordFormKey = ref(0);

const dictionaryExportOpen = ref(false);
const dictionaryExportLoading = ref(false);
const dictionaryExportError = ref<string | null>(null);
const dictionaryExportWords = ref<DictionaryWord[]>([]);
const selectedWordIds = ref<number[]>([]);
const exportThemeId = ref('');
const exportRandom = ref(false);
const exportWordsOnly = ref(false);
const exportTranslationOnly = ref(false);
const exportWithoutExample = ref(true);

const filteredExportWords = computed(() => {
    if (exportThemeId.value === '') {
        return dictionaryExportWords.value;
    }

    const themeId = Number(exportThemeId.value);

    return dictionaryExportWords.value.filter((word) =>
        word.theme_ids.includes(themeId),
    );
});

const selectedCount = computed(
    () =>
        filteredExportWords.value.filter((word) =>
            selectedWordIds.value.includes(word.id),
        ).length,
);
const allSelected = computed(
    () =>
        filteredExportWords.value.length > 0 &&
        selectedCount.value === filteredExportWords.value.length,
);

const activeFilterCount = computed(() =>
    Object.values(props.filters).filter((value) => !!value).length,
);

function columnLabel(key: ColumnKey): string {
    return t(`admin.wordFields.${key}`);
}

function isColumnVisible(key: ColumnKey): boolean {
    return visibleColumns.value.includes(key);
}

function toggleColumn(key: ColumnKey, checked: boolean | 'indeterminate'): void {
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

const orderedVisibleColumns = computed(() =>
    props.columns.filter((column) => isColumnVisible(column.key)),
);

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
        case 'themes':
            return word.themes.map((theme) => theme.name).join(', ') || '—';
        case 'created_at':
            return formatDate(word.created_at);
        default:
            return word[key] || '—';
    }
}

function wordHasTheme(word: WordRow, themeId: number): boolean {
    return word.themes.some((theme) => theme.id === themeId);
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
        WordSetController.view.url(
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
        theme_id: '',
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

function exportQuery(): Record<string, string> {
    return buildQuery();
}

function exportExcelUrl(): string {
    const query = exportQuery();

    return WordSetController.viewExportExcel.url(
        props.wordSet.id,
        Object.keys(query).length > 0 ? { query } : undefined,
    );
}

function exportPdfUrl(): string {
    const query = exportQuery();

    return WordSetController.viewExportPdf.url(
        props.wordSet.id,
        Object.keys(query).length > 0 ? { query } : undefined,
    );
}

function openEdit(word: WordRow): void {
    editingWord.value = word;
    editFormKey.value += 1;
    editOpen.value = true;
}

function onEditSuccess(): void {
    editOpen.value = false;
    editingWord.value = null;
}

function openDetails(): void {
    detailsFormKey.value += 1;
    detailsOpen.value = true;
}

function openExcelImport(): void {
    excelImportFormKey.value += 1;
    excelImportOpen.value = true;
}

function openBatchImport(): void {
    batchImportFormKey.value += 1;
    batchImportOpen.value = true;
}

function openAddWord(): void {
    addWordFormKey.value += 1;
    addWordOpen.value = true;
}

function onDetailsSaved(): void {
    detailsOpen.value = false;
}

function onExcelImported(): void {
    excelImportOpen.value = false;
}

function onBatchImported(): void {
    batchImportOpen.value = false;
}

function onWordCreated(): void {
    addWordOpen.value = false;
}

async function fetchDictionaryWords(
    wordSetId: number,
): Promise<DictionaryWord[]> {
    const response = await fetch(
        WordSetController.exportOptions.url(wordSetId),
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        },
    );

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    const payload = (await response.json()) as {
        words: DictionaryWord[];
    };

    return payload.words;
}

function isWordSelected(wordId: number): boolean {
    return selectedWordIds.value.includes(wordId);
}

function toggleWord(wordId: number, event: Event): void {
    const checked = (event.target as HTMLInputElement).checked;

    if (checked) {
        if (!selectedWordIds.value.includes(wordId)) {
            selectedWordIds.value = [...selectedWordIds.value, wordId];
        }

        return;
    }

    selectedWordIds.value = selectedWordIds.value.filter((id) => id !== wordId);
}

function selectAllWords(): void {
    const visibleIds = filteredExportWords.value.map((word) => word.id);
    selectedWordIds.value = [
        ...new Set([...selectedWordIds.value, ...visibleIds]),
    ];
}

function clearWordSelection(): void {
    const visibleIds = new Set(
        filteredExportWords.value.map((word) => word.id),
    );

    selectedWordIds.value = selectedWordIds.value.filter(
        (id) => !visibleIds.has(id),
    );
}

function applyExportThemeFilter(): void {
    selectedWordIds.value = filteredExportWords.value.map((word) => word.id);
}

async function openDictionaryExport(): Promise<void> {
    dictionaryExportOpen.value = true;
    dictionaryExportLoading.value = true;
    dictionaryExportError.value = null;
    dictionaryExportWords.value = [];
    selectedWordIds.value = [];
    exportThemeId.value = '';
    exportRandom.value = false;
    exportWordsOnly.value = false;
    exportTranslationOnly.value = false;
    exportWithoutExample.value = true;

    try {
        dictionaryExportWords.value = await fetchDictionaryWords(
            props.wordSet.id,
        );
        selectedWordIds.value = dictionaryExportWords.value.map(
            (word) => word.id,
        );
    } catch {
        dictionaryExportError.value = t('admin.exportLoadError');
    } finally {
        dictionaryExportLoading.value = false;
    }
}

function submitDictionaryExport(): void {
    const exportIds = filteredExportWords.value
        .map((word) => word.id)
        .filter((id) => selectedWordIds.value.includes(id));

    if (exportIds.length === 0) {
        return;
    }

    const url = new URL(
        WordSetController.exportPdf.url(props.wordSet.id),
        window.location.origin,
    );

    for (const id of exportIds) {
        url.searchParams.append('word_ids[]', String(id));
    }

    url.searchParams.set('random', exportRandom.value ? '1' : '0');
    url.searchParams.set('words_only', exportWordsOnly.value ? '1' : '0');
    url.searchParams.set(
        'translation_only',
        exportTranslationOnly.value ? '1' : '0',
    );
    url.searchParams.set(
        'without_example',
        exportWithoutExample.value ? '1' : '0',
    );

    window.location.assign(url.toString());
    dictionaryExportOpen.value = false;
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
                <Button variant="outline" @click="openExcelImport">
                    <FileSpreadsheet class="size-4" />
                    {{ t('admin.excelImport') }}
                </Button>
                <Button variant="outline" @click="openBatchImport">
                    <ListPlus class="size-4" />
                    {{ t('admin.batchImport') }}
                </Button>
                <Button variant="outline" @click="openDictionaryExport">
                    <FileDown class="size-4" />
                    {{ t('admin.export') }}
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
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-5"
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
                        <Label for="filter-theme">{{
                            t('admin.themes')
                        }}</Label>
                        <select
                            id="filter-theme"
                            v-model="localFilters.theme_id"
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">{{ t('common.all') }}</option>
                            <option
                                v-for="theme in themes"
                                :key="theme.id"
                                :value="String(theme.id)"
                            >
                                {{ theme.name }}
                            </option>
                        </select>
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
                        <Button variant="outline" size="sm" as-child>
                            <a :href="exportExcelUrl()">
                                <FileSpreadsheet class="size-4" />
                                {{ t('admin.exportExcel') }}
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" as-child>
                            <a :href="exportPdfUrl()">
                                <FileDown class="size-4" />
                                {{ t('admin.exportPdf') }}
                            </a>
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
                                        v-if="column.key === 'themes'"
                                        class="flex flex-wrap gap-1.5"
                                    >
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
                                            class="text-muted-foreground"
                                        >
                                            —
                                        </span>
                                    </div>
                                    <div
                                        v-else-if="column.key === 'image'"
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
                                            @click="openEdit(word)"
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

        <Dialog v-model:open="editOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.editWord') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.editWordDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-if="editingWord"
                    :key="editFormKey"
                    v-bind="
                        WordController.update.form({
                            wordSet: wordSet.id,
                            word: editingWord.id,
                        })
                    "
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onEditSuccess"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-text">{{ t('admin.text') }}</Label>
                            <Input
                                id="edit-text"
                                name="text"
                                :default-value="editingWord.text"
                                required
                            />
                            <InputError :message="errors.text" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-translation">{{
                                t('admin.translation')
                            }}</Label>
                            <Input
                                id="edit-translation"
                                name="translation"
                                :default-value="editingWord.translation"
                                required
                            />
                            <InputError :message="errors.translation" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-example">{{
                            t('admin.exampleOptional')
                        }}</Label>
                        <Input
                            id="edit-example"
                            name="example_sentence"
                            :default-value="editingWord.example_sentence"
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-audio">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input
                            id="edit-audio"
                            name="audio_url"
                            :default-value="editingWord.audio_url ?? ''"
                        />
                        <InputError :message="errors.audio_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-image">{{
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
                            id="edit-image"
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
                                :key="`edit-theme-${theme.id}`"
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="theme_ids[]"
                                    :value="theme.id"
                                    :checked="
                                        wordHasTheme(editingWord, theme.id)
                                    "
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

        <Dialog v-model:open="detailsOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.dictionaryDetails') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.dictionaryDetailsDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="detailsFormKey"
                    v-bind="WordSetController.update.form(wordSet.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onDetailsSaved"
                >
                    <input
                        type="hidden"
                        name="language_id"
                        :value="wordSet.language_id"
                    />
                    <div class="grid gap-2">
                        <Label for="details-title">{{
                            t('admin.title')
                        }}</Label>
                        <Input
                            id="details-title"
                            name="title"
                            :default-value="wordSet.title"
                            required
                        />
                        <InputError :message="errors.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="details-description">{{
                            t('admin.description')
                        }}</Label>
                        <textarea
                            id="details-description"
                            name="description"
                            rows="4"
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

        <Dialog v-model:open="excelImportOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.excelImport') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.excelImportHint') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="excelImportFormKey"
                    v-bind="WordController.importMethod.form(wordSet.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onExcelImported"
                >
                    <div class="grid gap-2">
                        <Label for="excel-file">{{
                            t('admin.excelFile')
                        }}</Label>
                        <Input
                            id="excel-file"
                            type="file"
                            name="file"
                            accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv"
                            required
                        />
                        <InputError :message="errors.file" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="excelImportOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.importExcel') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="batchImportOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.batchImport') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.batchImportHint') }}
                        <code class="text-xs">text|translation|example</code>
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="batchImportFormKey"
                    v-bind="WordController.importMethod.form(wordSet.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onBatchImported"
                >
                    <div class="grid gap-2">
                        <Label for="batch-words">{{ t('admin.words') }}</Label>
                        <textarea
                            id="batch-words"
                            name="words"
                            rows="8"
                            required
                            placeholder="hello|привет|Hello, world!"
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 font-mono text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        />
                        <InputError :message="errors.words" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="batchImportOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.import') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="dictionaryExportOpen">
            <DialogContent class="max-h-[90vh] overflow-hidden sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.exportDictionary') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t('admin.exportDictionaryDescription', {
                                title: wordSet.title,
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div
                        v-if="dictionaryExportLoading"
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('common.loading') }}
                    </div>

                    <div
                        v-else-if="dictionaryExportError"
                        class="rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {{ dictionaryExportError }}
                    </div>

                    <template v-else>
                        <div class="space-y-1.5">
                            <Label for="export-theme">{{
                                t('admin.themes')
                            }}</Label>
                            <select
                                id="export-theme"
                                v-model="exportThemeId"
                                class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                @change="applyExportThemeFilter"
                            >
                                <option value="">{{ t('common.all') }}</option>
                                <option
                                    v-for="theme in themes"
                                    :key="theme.id"
                                    :value="String(theme.id)"
                                >
                                    {{ theme.name }}
                                </option>
                            </select>
                        </div>

                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t('admin.exportSelectedCount', {
                                        selected: selectedCount,
                                        total: filteredExportWords.length,
                                    })
                                }}
                            </p>
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    :disabled="allSelected"
                                    @click="selectAllWords"
                                >
                                    {{ t('admin.selectAll') }}
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    :disabled="selectedCount === 0"
                                    @click="clearWordSelection"
                                >
                                    {{ t('admin.clearSelection') }}
                                </Button>
                            </div>
                        </div>

                        <div
                            class="max-h-72 space-y-2 overflow-y-auto rounded-lg border p-3"
                        >
                            <div
                                v-if="!filteredExportWords.length"
                                class="py-6 text-center text-sm text-muted-foreground"
                            >
                                {{
                                    dictionaryExportWords.length === 0
                                        ? t('admin.noWordsYet')
                                        : t('admin.noWordsFound')
                                }}
                            </div>

                            <label
                                v-for="word in filteredExportWords"
                                :key="word.id"
                                class="flex cursor-pointer items-start gap-3 rounded-md px-2 py-1.5 hover:bg-muted/50"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 size-4 shrink-0 rounded border"
                                    :checked="isWordSelected(word.id)"
                                    @change="toggleWord(word.id, $event)"
                                />
                                <span class="min-w-0 text-sm">
                                    <span class="font-medium">{{
                                        word.text
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        → {{ word.translation }}
                                    </span>
                                </span>
                            </label>
                        </div>

                        <p
                            v-if="exportWordsOnly && exportTranslationOnly"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('admin.exportColumnModesHint') }}
                        </p>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                            >
                                <input
                                    v-model="exportWordsOnly"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded border"
                                />
                                <span class="text-sm font-medium">{{
                                    t('admin.exportWordsOnly')
                                }}</span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                            >
                                <input
                                    v-model="exportTranslationOnly"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded border"
                                />
                                <span class="text-sm font-medium">{{
                                    t('admin.exportTranslationOnly')
                                }}</span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                            >
                                <input
                                    v-model="exportWithoutExample"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded border"
                                />
                                <span class="text-sm font-medium">{{
                                    t('admin.exportWithoutExample')
                                }}</span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                            >
                                <input
                                    v-model="exportRandom"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded border"
                                />
                                <span>
                                    <span class="block text-sm font-medium">{{
                                        t('admin.exportRandom')
                                    }}</span>
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        {{ t('admin.exportRandomHint') }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </template>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="dictionaryExportOpen = false"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="
                            dictionaryExportLoading ||
                            !!dictionaryExportError ||
                            selectedCount === 0
                        "
                        @click="submitDictionaryExport"
                    >
                        <FileDown class="size-4" />
                        {{ t('admin.export') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="addWordOpen">
            <DialogContent class="sm:max-w-lg">
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
                    <div class="grid gap-4 sm:grid-cols-2">
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
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-example_sentence">{{
                            t('admin.exampleOptional')
                        }}</Label>
                        <Input
                            id="add-example_sentence"
                            name="example_sentence"
                        />
                        <InputError :message="errors.example_sentence" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="add-audio_url">{{
                            t('admin.audioUrlOptional')
                        }}</Label>
                        <Input id="add-audio_url" name="audio_url" />
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
                                :key="`add-theme-${theme.id}`"
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="theme_ids[]"
                                    :value="theme.id"
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
                            @click="addWordOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ t('admin.addWord') }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
