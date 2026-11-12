<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

const { t } = useI18n();

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'auth.registerTitle',
        description: 'auth.registerDescription',
    },
});
</script>

<template>
    <Head :title="t('auth.register')" />

    <Form
        :action="store.url()"
        method="post"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="last_name">{{ t('common.lastName') }}</Label>
                <Input
                    id="last_name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="family-name"
                    name="last_name"
                    :placeholder="t('common.lastName')"
                />
                <InputError :message="errors.last_name" />
            </div>

            <div class="grid gap-2">
                <Label for="first_name">{{ t('common.firstName') }}</Label>
                <Input
                    id="first_name"
                    type="text"
                    required
                    :tabindex="2"
                    autocomplete="given-name"
                    name="first_name"
                    :placeholder="t('common.firstName')"
                />
                <InputError :message="errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="patronymic">{{ t('common.patronymic') }}</Label>
                <Input
                    id="patronymic"
                    type="text"
                    :tabindex="3"
                    autocomplete="additional-name"
                    name="patronymic"
                    :placeholder="t('common.patronymic')"
                />
                <InputError :message="errors.patronymic" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">{{ t('common.phone') }}</Label>
                <Input
                    id="phone"
                    type="tel"
                    :tabindex="4"
                    autocomplete="tel"
                    name="phone"
                    :placeholder="t('common.phone')"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('auth.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="5"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('auth.password') }}</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password"
                    :placeholder="t('auth.password')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('auth.confirmPassword')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="7"
                    autocomplete="new-password"
                    name="password_confirmation"
                    :placeholder="t('auth.confirmPassword')"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-1 w-full"
                size="lg"
                :tabindex="8"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                {{ t('auth.createAccount') }}
            </Button>
        </div>

        <div
            class="border-t border-slate-200 pt-5 text-center text-sm text-slate-600"
        >
            {{ t('auth.haveAccount') }}
            <TextLink
                :href="login()"
                class="text-sky-700 hover:text-sky-800"
                :tabindex="9"
            >
                {{ t('auth.login') }}
            </TextLink>
        </div>
    </Form>
</template>
