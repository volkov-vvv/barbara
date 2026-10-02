<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuthenticatedUser } from '@/composables/useAuth';
import { edit } from '@/routes/profile';

type LocaleOption = {
    value: string;
    label: string;
};

defineProps<{
    locales: LocaleOption[];
}>();

const { t } = useI18n();
const user = useAuthenticatedUser();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'settings.profileTitle',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('settings.profileTitle')" />

    <h1 class="sr-only">{{ t('settings.profileTitle') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('settings.profileHeading')"
            :description="t('settings.profileDescription')"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ t('common.name') }}</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    :placeholder="t('settings.fullName')"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('settings.emailAddress') }}</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    :placeholder="t('settings.emailAddress')"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="locale">{{ t('settings.interfaceLanguage') }}</Label>
                <select
                    id="locale"
                    name="locale"
                    required
                    class="border-input mt-1 h-9 w-full rounded-md border bg-transparent pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    :value="user.locale ?? 'en'"
                >
                    <option
                        v-for="locale in locales"
                        :key="locale.value"
                        :value="locale.value"
                    >
                        {{ t(`locales.${locale.value}`) }}
                    </option>
                </select>
                <p class="text-sm text-muted-foreground">
                    {{ t('settings.interfaceLanguageHelp') }}
                </p>
                <InputError class="mt-2" :message="errors.locale" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button">
                    {{ t('common.save') }}
                </Button>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
