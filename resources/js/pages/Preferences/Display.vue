<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { applyDisplayPreferences } from '@/composables/useDisplayPreferences';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    scale: { type: String, default: 'md' },
    textBody: { type: String, default: null },
    textMuted: { type: String, default: null },
    highContrast: { type: Boolean, default: false },
});

const { t } = useTrans();

const scaleOptions = [
    { value: 'sm', label: 'scale_sm' },
    { value: 'md', label: 'scale_md' },
    { value: 'lg', label: 'scale_lg' },
    { value: 'xl', label: 'scale_xl' },
];

const form = useForm({
    scale: props.scale,
    textBody: props.textBody ?? '',
    textMuted: props.textMuted ?? '',
    highContrast: props.highContrast,
});

const previewPrefs = computed(() => ({
    scale: form.scale,
    textBody: form.highContrast || !form.textBody ? null : form.textBody,
    textMuted: form.highContrast || !form.textMuted ? null : form.textMuted,
    highContrast: form.highContrast,
}));

watch(previewPrefs, (prefs) => applyDisplayPreferences(prefs), { deep: true, immediate: true });

function save() {
    form
        .transform((data) => ({
            ...data,
            textBody: data.textBody || null,
            textMuted: data.textMuted || null,
        }))
        .post(route('preferences.display.save'), { preserveScroll: true });
}

function resetDefaults() {
    form.scale = 'md';
    form.textBody = '';
    form.textMuted = '';
    form.highContrast = false;
}
</script>

<template>
    <AppLayout :title="t('preferences.display_title')">
        <Head :title="t('preferences.display_title')" />

        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-highlighted">{{ t('preferences.display_title') }}</h1>
                <p class="text-sm text-muted">{{ t('preferences.display_subtitle') }}</p>
            </div>

            <UCard>
                <form class="space-y-6" @submit.prevent="save">
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-highlighted">{{ t('preferences.scale_label') }}</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button
                                v-for="option in scaleOptions"
                                :key="option.value"
                                type="button"
                                class="rounded-lg border px-3 py-3 text-sm transition"
                                :class="form.scale === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-default hover:bg-elevated'"
                                @click="form.scale = option.value"
                            >
                                {{ t(`preferences.${option.label}`) }}
                            </button>
                        </div>
                        <div class="rounded-lg border border-dashed border-default p-4">
                            <p class="text-sm" :class="`preview-scale-${form.scale}`">
                                {{ t('preferences.preview_text') }}
                            </p>
                            <UButton class="mt-2" size="sm" :label="t('preferences.preview_button')" />
                        </div>
                    </div>

                    <USeparator />

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-highlighted">{{ t('preferences.high_contrast') }}</p>
                                <p class="text-xs text-muted">{{ t('preferences.high_contrast_hint') }}</p>
                            </div>
                            <USwitch v-model="form.highContrast" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2" :class="form.highContrast ? 'pointer-events-none opacity-50' : ''">
                            <UFormField :label="t('preferences.text_body')">
                                <div class="flex items-center gap-2">
                                    <input v-model="form.textBody" type="color" class="size-10 cursor-pointer rounded border border-default bg-default" />
                                    <UInput v-model="form.textBody" class="flex-1 font-mono" />
                                </div>
                            </UFormField>
                            <UFormField :label="t('preferences.text_muted')">
                                <div class="flex items-center gap-2">
                                    <input v-model="form.textMuted" type="color" class="size-10 cursor-pointer rounded border border-default bg-default" />
                                    <UInput v-model="form.textMuted" class="flex-1 font-mono" />
                                </div>
                            </UFormField>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                        <UButton type="button" color="neutral" variant="ghost" :label="t('preferences.reset')" @click="resetDefaults" />
                        <UButton type="submit" class="min-w-32 justify-center" :loading="form.processing" :label="t('common.save')" />
                    </div>
                </form>
            </UCard>
        </div>
    </AppLayout>
</template>
