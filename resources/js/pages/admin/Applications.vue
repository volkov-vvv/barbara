<script setup lang="ts">
import { Head, Form, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Columns3, Eye, FileDown, FileSpreadsheet, Filter, Pencil, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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
import { index as applicationsIndex } from '@/routes/admin/applications';

type ColumnKey =
    | 'id'
    | 'full_name'
    | 'email'
    | 'phone'
    | 'region'
    | 'course'
    | 'level'
    | 'comment'
    | 'document'
    | 'created_at';

type ColumnDefinition = {
    key: ColumnKey;
    label: string;
    default: boolean;
    sortable: boolean;
};

type ApplicationRow = {
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
};

type PaginatedApplications = {
    data: ApplicationRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Filters = {
    search: string | null;
    region: string | null;
    course: string | null;
    level: string | null;
    date_from: string | null;
    date_to: string | null;
};

type SortState = {
    column: string;
    direction: 'asc' | 'desc';
};

const COLUMNS_STORAGE_KEY = 'admin.applications.visibleColumns';

const props = defineProps<{
    applications: PaginatedApplications;
    filters: Filters;
    sort: SortState;
    regions: string[];
    courses: string[];
    levels: string[];
    columns: ColumnDefinition[];
}>();

const { t, locale } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'nav.applications', href: applicationsIndex() },
        ],
    },
});

const localFilters = ref({
    search: props.filters.search ?? '',
    region: props.filters.region ?? '',
    course: props.filters.course ?? '',
    level: props.filters.level ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

watch(
    () => props.filters,
    (value) => {
        localFilters.value = {
            search: value.search ?? '',
            region: value.region ?? '',
            course: value.course ?? '',
            level: value.level ?? '',
            date_from: value.date_from ?? '',
            date_to: value.date_to ?? '',
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

const selectedApplication = ref<ApplicationRow | null>(null);
const detailsOpen = ref(false);
const editingApplication = ref<ApplicationRow | null>(null);
const editOpen = ref(false);
const editFormKey = ref(0);

const selectClass =
    'border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const activeFilterCount = computed(() =>
    Object.values(props.filters).filter((value) => !!value).length,
);

function columnLabel(key: ColumnKey): string {
    return t(`admin.applicationFields.${key}`);
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

function cellValue(application: ApplicationRow, key: ColumnKey): string {
    switch (key) {
        case 'id':
            return String(application.id);
        case 'comment':
            return application.comment?.trim() || '—';
        case 'document':
            return application.document_name;
        case 'created_at':
            return formatDate(application.created_at);
        default:
            return application[key] || '—';
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

    if (sortColumn !== 'created_at' || sortDirection !== 'desc') {
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
        applicationsIndex.url(
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
        region: '',
        course: '',
        level: '',
        date_from: '',
        date_to: '',
    };

    const query: Record<string, string> = {};

    if (props.sort.column !== 'created_at' || props.sort.direction !== 'desc') {
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

function openDetails(application: ApplicationRow): void {
    selectedApplication.value = application;
    detailsOpen.value = true;
}

function openEdit(application: ApplicationRow): void {
    editingApplication.value = application;
    editFormKey.value += 1;
    editOpen.value = true;
}

function onEditSuccess(): void {
    editOpen.value = false;
    editingApplication.value = null;
}

function destroyApplication(application: ApplicationRow): void {
    if (
        !confirm(
            t('admin.deleteApplicationConfirm', {
                name: application.full_name,
            }),
        )
    ) {
        return;
    }

    router.delete(ApplicationController.destroy.url(application.id));
}

function documentUrl(application: ApplicationRow): string {
    return ApplicationController.download.url(application.id);
}

function exportQuery(): Record<string, string> {
    return buildQuery();
}

function exportExcelUrl(): string {
    const query = exportQuery();

    return ApplicationController.exportExcel.url(
        Object.keys(query).length > 0 ? { query } : undefined,
    );
}

function exportPdfUrl(): string {
    const query = exportQuery();

    return ApplicationController.exportPdf.url(
        Object.keys(query).length > 0 ? { query } : undefined,
    );
}
</script>

<template>
    <Head :title="t('admin.applicationsTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('admin.applicationsTitle')"
            :description="t('admin.applicationsDescription')"
        />

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
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-6"
                    @submit.prevent="applyFilters"
                >
                    <div class="space-y-1.5 xl:col-span-2">
                        <Label for="filter-search">{{
                            t('admin.search')
                        }}</Label>
                        <Input
                            id="filter-search"
                            v-model="localFilters.search"
                            :placeholder="t('admin.applicationsSearchPlaceholder')"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-region">{{
                            t('admin.applicationFields.region')
                        }}</Label>
                        <select
                            id="filter-region"
                            v-model="localFilters.region"
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">{{ t('common.all') }}</option>
                            <option
                                v-for="region in regions"
                                :key="region"
                                :value="region"
                            >
                                {{ region }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-course">{{
                            t('admin.applicationFields.course')
                        }}</Label>
                        <select
                            id="filter-course"
                            v-model="localFilters.course"
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">{{ t('common.all') }}</option>
                            <option
                                v-for="course in courses"
                                :key="course"
                                :value="course"
                            >
                                {{ course }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-level">{{
                            t('admin.applicationFields.level')
                        }}</Label>
                        <select
                            id="filter-level"
                            v-model="localFilters.level"
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">{{ t('common.all') }}</option>
                            <option
                                v-for="level in levels"
                                :key="level"
                                :value="level"
                            >
                                {{ level }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-date-from">{{
                            t('admin.dateFrom')
                        }}</Label>
                        <Input
                            id="filter-date-from"
                            v-model="localFilters.date_from"
                            type="date"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="filter-date-to">{{
                            t('admin.dateTo')
                        }}</Label>
                        <Input
                            id="filter-date-to"
                            v-model="localFilters.date_to"
                            type="date"
                        />
                    </div>
                    <div class="flex items-end xl:col-span-6">
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
                        {{ t('admin.applicationsList') }}
                        <span
                            class="ml-2 text-sm font-normal text-muted-foreground"
                        >
                            {{ applications.total }}
                        </span>
                    </CardTitle>
                    <div class="flex flex-wrap items-center gap-2">
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
                                    <span class="inline-flex items-center gap-1.5">
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
                                v-for="application in applications.data"
                                :key="application.id"
                                class="border-t transition-colors hover:bg-muted/60"
                            >
                                <td
                                    v-for="column in orderedVisibleColumns"
                                    :key="`${application.id}-${column.key}`"
                                    class="max-w-64 truncate px-3 py-2 align-middle"
                                >
                                    <a
                                        v-if="column.key === 'document'"
                                        :href="documentUrl(application)"
                                        class="text-primary underline-offset-2 hover:underline"
                                    >
                                        {{ application.document_name }}
                                    </a>
                                    <template v-else>
                                        {{
                                            cellValue(application, column.key)
                                        }}
                                    </template>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="openDetails(application)"
                                        >
                                            <Eye class="size-4" />
                                            {{ t('common.view') }}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="openEdit(application)"
                                        >
                                            <Pencil class="size-4" />
                                            {{ t('common.edit') }}
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="
                                                destroyApplication(application)
                                            "
                                        >
                                            <Trash2 class="size-4" />
                                            {{ t('common.delete') }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="applications.data.length === 0">
                                <td
                                    :colspan="orderedVisibleColumns.length + 1"
                                    class="px-3 py-8 text-center text-muted-foreground"
                                >
                                    {{ t('admin.noApplications') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-2">
                    <template
                        v-for="link in applications.links"
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

        <Dialog v-model:open="detailsOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{
                        t('admin.applicationDetails')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.applicationDetailsDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <dl
                    v-if="selectedApplication"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">ID</dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.id }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.created_at') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ formatDate(selectedApplication.created_at) }}
                        </dd>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.full_name') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.full_name }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.email') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.email }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.phone') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.phone }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.region') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.region }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.course') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.course }}
                        </dd>
                    </div>
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.level') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ selectedApplication.level }}
                        </dd>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.document') }}
                        </dt>
                        <dd class="text-sm font-medium">
                            <a
                                :href="documentUrl(selectedApplication)"
                                class="text-primary underline-offset-2 hover:underline"
                            >
                                {{ selectedApplication.document_name }}
                            </a>
                        </dd>
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <dt class="text-xs text-muted-foreground">
                            {{ t('admin.applicationFields.comment') }}
                        </dt>
                        <dd class="text-sm font-medium whitespace-pre-wrap">
                            {{ selectedApplication.comment?.trim() || '—' }}
                        </dd>
                    </div>
                </dl>

                <DialogFooter
                    v-if="selectedApplication"
                    class="gap-2 sm:justify-between"
                >
                    <Button
                        variant="outline"
                        as-child
                    >
                        <Link
                            :href="
                                ApplicationController.show.url(
                                    selectedApplication.id,
                                )
                            "
                        >
                            {{ t('admin.openPage') }}
                        </Link>
                    </Button>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            @click="
                                openEdit(selectedApplication);
                                detailsOpen = false;
                            "
                        >
                            <Pencil class="size-4" />
                            {{ t('common.edit') }}
                        </Button>
                        <Button
                            variant="destructive"
                            @click="destroyApplication(selectedApplication)"
                        >
                            <Trash2 class="size-4" />
                            {{ t('common.delete') }}
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.editApplication') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.editApplicationDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    v-if="editingApplication"
                    :key="editFormKey"
                    :action="
                        ApplicationController.update.url(editingApplication.id)
                    "
                    method="put"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onEditSuccess"
                >
                    <div class="grid gap-2">
                        <Label for="edit-full_name">{{
                            t('admin.applicationFields.full_name')
                        }}</Label>
                        <Input
                            id="edit-full_name"
                            name="full_name"
                            :default-value="editingApplication.full_name"
                            required
                        />
                        <InputError :message="errors.full_name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-email">{{
                                t('admin.applicationFields.email')
                            }}</Label>
                            <Input
                                id="edit-email"
                                type="email"
                                name="email"
                                :default-value="editingApplication.email"
                                required
                            />
                            <InputError :message="errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-phone">{{
                                t('admin.applicationFields.phone')
                            }}</Label>
                            <Input
                                id="edit-phone"
                                name="phone"
                                :default-value="editingApplication.phone"
                                required
                            />
                            <InputError :message="errors.phone" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-region">{{
                            t('admin.applicationFields.region')
                        }}</Label>
                        <select
                            id="edit-region"
                            name="region"
                            required
                            :class="selectClass"
                            :value="editingApplication.region"
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
                            <Label for="edit-course">{{
                                t('admin.applicationFields.course')
                            }}</Label>
                            <select
                                id="edit-course"
                                name="course"
                                required
                                :class="selectClass"
                                :value="editingApplication.course"
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
                            <Label for="edit-level">{{
                                t('admin.applicationFields.level')
                            }}</Label>
                            <select
                                id="edit-level"
                                name="level"
                                required
                                :class="selectClass"
                                :value="editingApplication.level"
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
                        <Label for="edit-comment">{{
                            t('admin.applicationFields.comment')
                        }}</Label>
                        <textarea
                            id="edit-comment"
                            name="comment"
                            rows="3"
                            class="border-input w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            :value="editingApplication.comment ?? ''"
                        />
                        <InputError :message="errors.comment" />
                    </div>

                    <div class="grid gap-2">
                        <p class="text-xs text-muted-foreground">
                            {{ t('admin.currentDocument') }}:
                            <a
                                :href="documentUrl(editingApplication)"
                                class="text-primary underline-offset-2 hover:underline"
                            >
                                {{ editingApplication.document_name }}
                            </a>
                        </p>
                        <Label for="edit-document">{{
                            t('admin.replaceDocumentOptional')
                        }}</Label>
                        <Input
                            id="edit-document"
                            type="file"
                            name="document"
                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        />
                        <InputError :message="errors.document" />
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
