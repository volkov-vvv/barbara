<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import StudentProgressController from '@/actions/App/Http/Controllers/Admin/StudentProgressController';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AvatarCropField from '@/components/AvatarCropField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { useInitials } from '@/composables/useInitials';
import { formatPaginationLabel } from '@/lib/pagination';
import { index as usersIndex } from '@/routes/admin/users';

type ManagedUser = {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    patronymic: string | null;
    phone: string | null;
    avatar: string | null;
    email: string;
    role: string;
};

type PaginatedUsers = {
    data: ManagedUser[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: { role: string | null };
    roles: string[];
}>();

const { t, te } = useI18n();
const { getInitials } = useInitials();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'nav.users', href: usersIndex() }],
    },
});

const editingId = ref<number | null>(null);
const createOpen = ref(false);
const createFormKey = ref(0);

function roleLabel(role: string): string {
    const key = `roles.${role}`;

    return te(key) ? t(key) : role;
}

function openCreate(): void {
    createFormKey.value += 1;
    createOpen.value = true;
}

function filterByRole(role: string | null): void {
    router.get(
        usersIndex.url(role ? { query: { role } } : undefined),
        {},
        { preserveState: true, replace: true },
    );
}

function startEdit(user: ManagedUser): void {
    editingId.value = user.id;
}

function cancelEdit(): void {
    editingId.value = null;
}

function destroyUser(user: ManagedUser): void {
    if (!confirm(`Delete ${user.name}?`)) {
        return;
    }

    router.delete(UserController.destroy.url(user.id));
}

function onUserCreated(): void {
    createOpen.value = false;
}
</script>

<template>
    <Head :title="t('admin.usersTitle')" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                :title="t('admin.usersTitle')"
                :description="t('admin.usersDescription')"
            />
            <Button @click="openCreate">
                <Plus class="size-4" />
                {{ t('admin.createUser') }}
            </Button>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                size="sm"
                :variant="!filters.role ? 'default' : 'outline'"
                @click="filterByRole(null)"
            >
                {{ t('common.all') }}
            </Button>
            <Button
                v-for="role in roles"
                :key="role"
                size="sm"
                :variant="filters.role === role ? 'default' : 'outline'"
                @click="filterByRole(role)"
            >
                {{ roleLabel(role) }}
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('admin.usersList') }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="rounded-lg border p-4"
                >
                    <div
                        v-if="editingId !== user.id"
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <Avatar class="size-10 overflow-hidden rounded-lg">
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback class="rounded-lg text-black dark:text-white">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ user.email }} ·
                                    {{ roleLabel(user.role) }}
                                    <span v-if="user.phone">
                                        · {{ user.phone }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="user.role === 'student'"
                                as-child
                                size="sm"
                                variant="outline"
                            >
                                <Link
                                    :href="
                                        StudentProgressController.show.url(
                                            user.id,
                                        )
                                    "
                                >
                                    {{ t('common.results') }}
                                </Link>
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="startEdit(user)"
                            >
                                {{ t('common.edit') }}
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="destroyUser(user)"
                            >
                                {{ t('common.delete') }}
                            </Button>
                        </div>
                    </div>

                    <Form
                        v-else
                        v-bind="UserController.update.form(user.id)"
                        class="space-y-3"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <AvatarCropField
                            :input-id="`avatar-${user.id}`"
                            optional
                            :current-avatar="user.avatar"
                            :alt="user.name"
                            :error="errors.avatar"
                        />
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`last-name-${user.id}`">{{
                                    t('common.lastName')
                                }}</Label>
                                <Input
                                    :id="`last-name-${user.id}`"
                                    name="last_name"
                                    :default-value="user.last_name"
                                    required
                                />
                                <InputError :message="errors.last_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`first-name-${user.id}`">{{
                                    t('common.firstName')
                                }}</Label>
                                <Input
                                    :id="`first-name-${user.id}`"
                                    name="first_name"
                                    :default-value="user.first_name"
                                    required
                                />
                                <InputError :message="errors.first_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`patronymic-${user.id}`">{{
                                    t('common.patronymic')
                                }}</Label>
                                <Input
                                    :id="`patronymic-${user.id}`"
                                    name="patronymic"
                                    :default-value="user.patronymic ?? ''"
                                />
                                <InputError :message="errors.patronymic" />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`phone-${user.id}`">{{
                                    t('common.phone')
                                }}</Label>
                                <Input
                                    :id="`phone-${user.id}`"
                                    type="tel"
                                    name="phone"
                                    :default-value="user.phone ?? ''"
                                />
                                <InputError :message="errors.phone" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label :for="`email-${user.id}`">{{
                                    t('common.email')
                                }}</Label>
                                <Input
                                    :id="`email-${user.id}`"
                                    type="email"
                                    name="email"
                                    :default-value="user.email"
                                    required
                                />
                                <InputError :message="errors.email" />
                            </div>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`password-${user.id}`">{{
                                    t('admin.passwordOptional')
                                }}</Label>
                                <Input
                                    :id="`password-${user.id}`"
                                    type="password"
                                    name="password"
                                />
                                <InputError :message="errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`role-${user.id}`">{{
                                    t('common.role')
                                }}</Label>
                                <select
                                    :id="`role-${user.id}`"
                                    name="role"
                                    required
                                    class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    :value="user.role"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ roleLabel(role) }}
                                    </option>
                                </select>
                                <InputError :message="errors.role" />
                            </div>
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

                <div class="flex flex-wrap gap-2 pt-2">
                    <template v-for="link in users.links" :key="link.label">
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
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('admin.createUser') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('admin.createUserDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    :key="createFormKey"
                    v-bind="UserController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="onUserCreated"
                >
                    <AvatarCropField
                        input-id="create-avatar"
                        optional
                        :error="errors.avatar"
                    />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create-last-name">{{
                                t('common.lastName')
                            }}</Label>
                            <Input
                                id="create-last-name"
                                name="last_name"
                                required
                            />
                            <InputError :message="errors.last_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-first-name">{{
                                t('common.firstName')
                            }}</Label>
                            <Input
                                id="create-first-name"
                                name="first_name"
                                required
                            />
                            <InputError :message="errors.first_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-patronymic">{{
                                t('common.patronymic')
                            }}</Label>
                            <Input id="create-patronymic" name="patronymic" />
                            <InputError :message="errors.patronymic" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-phone">{{
                                t('common.phone')
                            }}</Label>
                            <Input
                                id="create-phone"
                                type="tel"
                                name="phone"
                            />
                            <InputError :message="errors.phone" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-email">{{
                            t('common.email')
                        }}</Label>
                        <Input
                            id="create-email"
                            type="email"
                            name="email"
                            required
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-password">{{
                            t('common.password')
                        }}</Label>
                        <Input
                            id="create-password"
                            type="password"
                            name="password"
                            required
                        />
                        <InputError :message="errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-role">{{ t('common.role') }}</Label>
                        <select
                            id="create-role"
                            name="role"
                            required
                            class="border-input h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ roleLabel(role) }}
                            </option>
                        </select>
                        <InputError :message="errors.role" />
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
