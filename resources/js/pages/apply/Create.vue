<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, FileUp, LoaderCircle } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { home } from '@/routes';

const props = defineProps<{
    regions: string[];
    courses: string[];
    levels: string[];
}>();

const MAX_FILE_SIZE = 20 * 1024 * 1024;

const form = useForm({
    full_name: '',
    email: '',
    phone: '',
    region: '',
    document: null as File | null,
    course: '',
    level: '',
    comment: '',
    privacy_consent: false as boolean,
});

const localFileError = ref<string | null>(null);
const selectedFileName = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const fieldClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-10 w-full rounded-lg border pl-3 pr-10 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const documentError = computed(
    () => localFileError.value ?? form.errors.document ?? undefined,
);

function formatPhone(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.length === 0) {
        return '';
    }

    let normalized = digits;

    if (normalized.startsWith('8')) {
        normalized = `7${normalized.slice(1)}`;
    }

    if (!normalized.startsWith('7')) {
        normalized = `7${normalized}`;
    }

    normalized = normalized.slice(0, 11);

    const rest = normalized.slice(1);
    let result = '+7';

    if (rest.length > 0) {
        result += ` (${rest.slice(0, 3)}`;
    }

    if (rest.length >= 3) {
        result += ')';
    }

    if (rest.length > 3) {
        result += ` ${rest.slice(3, 6)}`;
    }

    if (rest.length > 6) {
        result += `-${rest.slice(6, 8)}`;
    }

    if (rest.length > 8) {
        result += `-${rest.slice(8, 10)}`;
    }

    return result;
}

function onPhoneInput(value: string | number): void {
    form.phone = formatPhone(String(value));
}

function onDocumentChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    localFileError.value = null;
    selectedFileName.value = null;
    form.document = null;
    form.clearErrors('document');

    if (!file) {
        return;
    }

    if (file.size > MAX_FILE_SIZE) {
        localFileError.value = 'Файл слишком большой (макс. 20 МБ)';
        target.value = '';
        return;
    }

    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

    if (
        !allowed.includes(file.type) &&
        !allowedExtensions.includes(extension)
    ) {
        localFileError.value = 'Допустимы только PDF, JPG или PNG';
        target.value = '';
        return;
    }

    form.document = file;
    selectedFileName.value = file.name;
}

function clearDocument(): void {
    form.document = null;
    selectedFileName.value = null;
    localFileError.value = null;
    form.clearErrors('document');

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function submit(): void {
    if (localFileError.value) {
        return;
    }

    form
        .transform((data) => ({
            ...data,
            privacy_consent: data.privacy_consent ? '1' : '0',
        }))
        .post('/apply', {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                form.reset();
                clearDocument();
            },
        });
}

function resetSuccessState(): void {
    form.wasSuccessful = false;
    form.recentlySuccessful = false;
}
</script>

<template>
    <Head title="Заявка на обучение" />

    <div
        class="relative min-h-screen overflow-hidden bg-[radial-gradient(ellipse_at_top,_#e8f1ff_0%,_#f7f8fb_45%,_#eef2f7_100%)] text-slate-900"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(148_163_184/0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148_163_184/0.08)_1px,transparent_1px)] bg-size-[28px_28px]"
        />
        <div
            class="pointer-events-none absolute -top-24 right-[-10%] h-72 w-72 rounded-full bg-sky-300/30 blur-3xl"
        />
        <div
            class="pointer-events-none absolute bottom-[-8%] left-[-8%] h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl"
        />

        <div class="relative mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:py-16">
            <div class="mb-8 flex items-center justify-between gap-4">
                <Link
                    :href="home()"
                    class="text-sm font-medium text-slate-600 transition hover:text-slate-900"
                >
                    ← На главную
                </Link>
                <p class="text-xs tracking-[0.18em] text-slate-500 uppercase">
                    Обучение
                </p>
            </div>

            <div
                v-if="form.wasSuccessful"
                class="animate-in fade-in zoom-in-95 rounded-3xl border border-emerald-200/80 bg-white/90 p-8 shadow-xl shadow-emerald-900/5 backdrop-blur duration-500 sm:p-12"
            >
                <div class="mx-auto flex max-w-md flex-col items-center text-center">
                    <div
                        class="mb-5 flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                    >
                        <CheckCircle2 class="size-8" />
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                        Заявка отправлена
                    </h1>
                    <p class="mt-3 text-base leading-relaxed text-slate-600">
                        Спасибо! Мы получили ваши данные и свяжемся с вами по
                        указанному email или телефону.
                    </p>
                    <Button class="mt-8" @click="resetSuccessState">
                        Отправить ещё одну заявку
                    </Button>
                </div>
            </div>

            <div
                v-else
                class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-xl shadow-slate-900/5 backdrop-blur sm:p-10"
            >
                <div class="mb-8 space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                        Заявка на обучение
                    </h1>
                    <p class="max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base">
                        Заполните форму, прикрепите документ и выберите курс.
                        Мы рассмотрим заявку и ответим в ближайшее время.
                    </p>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="full_name">ФИО</Label>
                            <Input
                                id="full_name"
                                v-model="form.full_name"
                                type="text"
                                autocomplete="name"
                                placeholder="Иванов Иван Иванович"
                                required
                                :aria-invalid="!!form.errors.full_name"
                            />
                            <InputError :message="form.errors.full_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                placeholder="name@example.com"
                                required
                                :aria-invalid="!!form.errors.email"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Телефон</Label>
                            <Input
                                id="phone"
                                :model-value="form.phone"
                                type="tel"
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="+7 (___) ___-__-__"
                                required
                                :aria-invalid="!!form.errors.phone"
                                @update:model-value="onPhoneInput"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="region">Регион РФ</Label>
                            <select
                                id="region"
                                v-model="form.region"
                                required
                                :class="fieldClass"
                                :aria-invalid="!!form.errors.region"
                            >
                                <option disabled value="">Выберите регион</option>
                                <option
                                    v-for="region in props.regions"
                                    :key="region"
                                    :value="region"
                                >
                                    {{ region }}
                                </option>
                            </select>
                            <InputError :message="form.errors.region" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="document">Документ</Label>
                            <div
                                class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-4 transition hover:border-sky-400 hover:bg-sky-50/40"
                            >
                                <input
                                    id="document"
                                    ref="fileInputRef"
                                    type="file"
                                    class="sr-only"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    required
                                    @change="onDocumentChange"
                                />
                                <label
                                    for="document"
                                    class="flex cursor-pointer flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <span class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-sky-600 shadow-sm ring-1 ring-slate-200"
                                        >
                                            <FileUp class="size-5" />
                                        </span>
                                        <span>
                                            <span
                                                class="block text-sm font-medium text-slate-800"
                                            >
                                                Загрузить PDF или изображение
                                            </span>
                                            <span
                                                class="mt-1 block text-xs text-slate-500"
                                            >
                                                .pdf, .jpg, .jpeg, .png · до 20 МБ
                                            </span>
                                        </span>
                                    </span>
                                    <span
                                        class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white"
                                    >
                                        Выбрать файл
                                    </span>
                                </label>

                                <div
                                    v-if="selectedFileName"
                                    class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm ring-1 ring-slate-200"
                                >
                                    <span class="truncate font-medium text-slate-700">
                                        {{ selectedFileName }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-xs font-medium text-red-600 hover:text-red-700"
                                        @click="clearDocument"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </div>
                            <InputError :message="documentError" />
                        </div>

                        <div class="space-y-2">
                            <Label for="course">Курс</Label>
                            <select
                                id="course"
                                v-model="form.course"
                                required
                                :class="fieldClass"
                                :aria-invalid="!!form.errors.course"
                            >
                                <option disabled value="">Выберите курс</option>
                                <option
                                    v-for="course in props.courses"
                                    :key="course"
                                    :value="course"
                                >
                                    {{ course }}
                                </option>
                            </select>
                            <InputError :message="form.errors.course" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label>Уровень подготовки</Label>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <label
                                    v-for="level in props.levels"
                                    :key="level"
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition has-checked:border-sky-500 has-checked:bg-sky-50 has-checked:ring-2 has-checked:ring-sky-200"
                                >
                                    <input
                                        v-model="form.level"
                                        type="radio"
                                        name="level"
                                        :value="level"
                                        class="size-4 accent-sky-600"
                                        required
                                    />
                                    <span class="text-sm font-medium text-slate-800">
                                        {{ level }}
                                    </span>
                                </label>
                            </div>
                            <InputError :message="form.errors.level" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="comment">Комментарий</Label>
                            <textarea
                                id="comment"
                                v-model="form.comment"
                                rows="4"
                                placeholder="Расскажите кратко о целях обучения (необязательно)"
                                class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                            />
                            <InputError :message="form.errors.comment" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <label class="flex items-start gap-3 text-sm text-slate-700">
                                <input
                                    v-model="form.privacy_consent"
                                    type="checkbox"
                                    class="mt-1 size-4 rounded border-slate-300 accent-sky-600"
                                    required
                                />
                                <span>
                                    Я согласен(а) с
                                    <a
                                        href="#"
                                        class="font-medium text-sky-700 underline underline-offset-2 hover:text-sky-800"
                                        @click.prevent
                                    >
                                        политикой конфиденциальности
                                    </a>
                                </span>
                            </label>
                            <InputError :message="form.errors.privacy_consent" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">
                            Все поля, кроме комментария, обязательны для заполнения.
                        </p>
                        <Button
                            type="submit"
                            class="min-w-44"
                            :disabled="form.processing || !!localFileError"
                        >
                            <LoaderCircle
                                v-if="form.processing"
                                class="size-4 animate-spin"
                            />
                            {{ form.processing ? 'Отправка…' : 'Отправить заявку' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
