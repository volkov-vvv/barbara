<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ThemeController from '@/actions/App/Http/Controllers/Admin/ThemeController';
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
import { index as themesIndex } from '@/routes/admin/themes';

type Theme = {
    id: number;
    name: string;
    words_count: number;
};

defineProps<{
    themes: Theme[];
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'nav.themes', href: themesIndex() }],
    },
});

const editingId = ref<number | null>(null);
const createOpen = ref(false);
const createFormKey = ref(0);

function openCreate(): void {
    createFormKey.value += 1;
    createOpen.value = true;
}

function startEdit(theme: Theme): void {
    editingId.value = theme.id;
}

function cancelEdit(): void {
    editingId.value = null;
}

function destroyTheme(theme: Theme): void {
    if (!confirm(t('admin.deleteThemeConfirm', { name: theme.name }))) {
        return;
    }

    router.delete(ThemeController.destroy.url(theme.id));
}

function onThemeCreated(): void {
    createOpen.value = false;
}
</script>

<template>
    <Head :title="t('admin.themesTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="t('admin.themesTitle')"
                :description="t('admin.themesDescription')"
            />
            <Button @click="openCreate">
                <Plus class="size-4" />
                {{ t('admin.createTheme') }}
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.themesList') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-if="themes.length === 0"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('admin.noThemesYet') }}
                </div>

                <div
                    v-for="theme in themes"
                    :key="theme.id"
                    class="rounded-lg border p-4"
                >
                    <div
                        v-if="editingId !== theme.id"
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <p class="font-medium">{{ theme.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t('admin.themeWordsCount', {
                                        count: theme.words_count,
                                    })
                                }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="startEdit(theme)"
                            >
                                {{ t('common.edit') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="destroyTheme(theme)"
                            >
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>

                    <Form
                        v-else
                        v-bind="ThemeController.update.form(theme.id)"
                        class="space-y-3"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <div class="grid gap-2">
                            <Label :for="`theme-name-${theme.id}`">{{
                                t('admin.themeName')
                            }}</Label>
                            <Input
                                :id="`theme-name-${theme.id}`"
                                name="name"
                                :default-value="theme.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="processing">
                                {{ t('common.save') }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="cancelEdit"
                            >
                                {{ t('common.cancel') }}
                            </Button>
                        </div>
                    </Form>
                </div>
            </CardContent>
        </Card>

        <Dialog v-model:open="createOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.createTheme') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.createThemeDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="createFormKey"
                    v-bind="ThemeController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onThemeCreated"
                >
                    <div class="grid gap-2">
                        <Label for="create-theme-name">{{
                            t('admin.themeName')
                        }}</Label>
                        <Input id="create-theme-name" name="name" required />
                        <InputError :message="errors.name" />
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
