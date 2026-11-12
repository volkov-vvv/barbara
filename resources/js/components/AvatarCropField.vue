<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';

const props = withDefaults(
    defineProps<{
        name?: string;
        removeName?: string;
        currentAvatar?: string | null;
        alt?: string;
        error?: string;
        label?: string;
        optional?: boolean;
        inputId?: string;
    }>(),
    {
        name: 'avatar',
        removeName: 'remove_avatar',
        currentAvatar: null,
        alt: '',
        error: undefined,
        label: undefined,
        optional: false,
        inputId: 'avatar',
    },
);

const { t } = useI18n();
const { getInitials } = useInitials();

const cropperRef = ref<InstanceType<typeof Cropper> | null>(null);
const pickerRef = ref<HTMLInputElement | null>(null);
const hiddenFileRef = ref<HTMLInputElement | null>(null);
const removeRef = ref<HTMLInputElement | null>(null);

const cropOpen = ref(false);
const sourceUrl = ref<string | null>(null);
const previewUrl = ref<string | null>(null);
const applying = ref(false);
const hasCroppedFile = ref(false);

const displayAvatar = ref<string | null>(props.currentAvatar ?? null);

watch(
    () => props.currentAvatar,
    (value) => {
        if (!previewUrl.value) {
            displayAvatar.value = value ?? null;
        }
    },
);

function revokeUrl(url: string | null): void {
    if (url && url.startsWith('blob:')) {
        URL.revokeObjectURL(url);
    }
}

function resetPicker(): void {
    if (pickerRef.value) {
        pickerRef.value.value = '';
    }
}

function clearPendingCrop(): void {
    revokeUrl(sourceUrl.value);
    sourceUrl.value = null;
    cropOpen.value = false;
    resetPicker();
}

function onFilePicked(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    revokeUrl(sourceUrl.value);
    sourceUrl.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropOpenChange(open: boolean): void {
    if (!open && !applying.value) {
        clearPendingCrop();
    } else {
        cropOpen.value = open;
    }
}

function cancelCrop(): void {
    clearPendingCrop();
}

async function applyCrop(): Promise<void> {
    const cropper = cropperRef.value;

    if (!cropper || !hiddenFileRef.value) {
        return;
    }

    const { canvas } = cropper.getResult();

    if (!canvas) {
        return;
    }

    applying.value = true;

    try {
        const blob = await new Promise<Blob | null>((resolve) => {
            canvas.toBlob((result) => resolve(result), 'image/jpeg', 0.92);
        });

        if (!blob) {
            return;
        }

        const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        hiddenFileRef.value.files = transfer.files;
        hasCroppedFile.value = true;

        revokeUrl(previewUrl.value);
        previewUrl.value = URL.createObjectURL(file);
        displayAvatar.value = previewUrl.value;

        if (removeRef.value) {
            removeRef.value.checked = false;
        }

        clearPendingCrop();
    } finally {
        applying.value = false;
        await nextTick();
    }
}

onBeforeUnmount(() => {
    revokeUrl(sourceUrl.value);
    revokeUrl(previewUrl.value);
});
</script>

<template>
    <div class="grid gap-3">
        <Label :for="inputId">
            {{
                label ??
                (optional ? t('common.avatarOptional') : t('common.avatar'))
            }}
        </Label>

        <div class="flex flex-wrap items-center gap-4">
            <Avatar class="size-16 overflow-hidden rounded-lg">
                <AvatarImage
                    v-if="displayAvatar"
                    :src="displayAvatar"
                    :alt="alt || t('common.avatar')"
                />
                <AvatarFallback
                    class="rounded-lg text-base text-black dark:text-white"
                >
                    {{ getInitials(alt || t('common.avatar')) }}
                </AvatarFallback>
            </Avatar>

            <div class="grid min-w-56 flex-1 gap-2">
                <input
                    :id="inputId"
                    ref="pickerRef"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                    class="border-input file:text-foreground h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                    @change="onFilePicked"
                />
                <input
                    ref="hiddenFileRef"
                    type="file"
                    :name="hasCroppedFile ? name : undefined"
                    class="hidden"
                    tabindex="-1"
                    aria-hidden="true"
                />
                <label
                    v-if="currentAvatar"
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <input
                        ref="removeRef"
                        type="checkbox"
                        :name="removeName"
                        value="1"
                        class="size-4 rounded border"
                    />
                    {{ t('common.removeAvatar') }}
                </label>
                <p class="text-xs text-muted-foreground">
                    {{ t('common.avatarCropHint') }}
                </p>
            </div>
        </div>

        <InputError :message="error" />

        <Dialog :open="cropOpen" @update:open="onCropOpenChange">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{{ t('common.avatarCropTitle') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('common.avatarCropDescription') }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="overflow-hidden rounded-lg border border-sidebar-border/70 bg-muted/30"
                >
                    <Cropper
                        v-if="sourceUrl"
                        ref="cropperRef"
                        class="h-72 w-full sm:h-96"
                        :src="sourceUrl"
                        :stencil-props="{
                            aspectRatio: 1,
                        }"
                        :canvas="{
                            width: 512,
                            height: 512,
                        }"
                    />
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="applying"
                        @click="cancelCrop"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="applying"
                        @click="applyCrop"
                    >
                        {{ t('common.avatarCropApply') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
