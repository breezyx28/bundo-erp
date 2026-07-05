<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

const props = defineProps({
    settings: { type: Object, required: true },
    invoiceDesigns: { type: Array, default: () => [] },
});

const { t } = useTrans();

const tab = ref('general');
const previewOpen = ref(false);
const previewDesign = ref(null);

const tabItems = [
    { value: 'general', label: t('settings.general') },
    { value: 'branding', label: t('settings.branding') },
    { value: 'currency', label: t('settings.currency') },
    { value: 'invoice', label: t('settings.invoice') },
];

const localeItems = [
    { label: 'العربية', value: 'ar' },
    { label: 'English', value: 'en' },
];

const currencyItems = [
    { label: 'SDG', value: 'SDG' },
    { label: 'USD', value: 'USD' },
];

const generalForm = useForm({
    company_name: props.settings.company_name,
    locale: props.settings.locale,
    timezone: props.settings.timezone,
});

const currencyForm = useForm({
    currency: props.settings.currency,
    exchange_rate: props.settings.exchange_rate,
});

const brandingForm = useForm({
    primary_color: props.settings.primary_color,
    secondary_color: props.settings.secondary_color,
    logo: null,
});

const invoiceForm = useForm({
    invoice_prefix: props.settings.invoice_prefix,
    invoice_footer: props.settings.invoice_footer,
    invoice_design: props.settings.invoice_design ?? 'classic',
});

const generalDraft = useFormDraft({
    key: 'settings.general',
    label: t('settings.general'),
    routeName: 'settings.index',
    form: generalForm,
    active: computed(() => tab.value === 'general'),
});

const currencyDraft = useFormDraft({
    key: 'settings.currency',
    label: t('settings.currency'),
    routeName: 'settings.index',
    form: currencyForm,
    active: computed(() => tab.value === 'currency'),
});

const brandingDraft = useFormDraft({
    key: 'settings.branding',
    label: t('settings.branding'),
    routeName: 'settings.index',
    form: brandingForm,
    active: computed(() => tab.value === 'branding'),
    getSnapshot: () => ({
        primary_color: brandingForm.primary_color,
        secondary_color: brandingForm.secondary_color,
    }),
    onApply: (data) => {
        brandingForm.primary_color = data.primary_color ?? props.settings.primary_color;
        brandingForm.secondary_color = data.secondary_color ?? props.settings.secondary_color;
    },
});

const invoiceDraft = useFormDraft({
    key: 'settings.invoice',
    label: t('settings.invoice'),
    routeName: 'settings.index',
    form: invoiceForm,
    active: computed(() => tab.value === 'invoice'),
});

useDraftQueryRestore('settings', (key) => {
    const tabMap = {
        'settings.general': 'general',
        'settings.currency': 'currency',
        'settings.branding': 'branding',
        'settings.invoice': 'invoice',
    };
    tab.value = tabMap[key] ?? 'general';
    const draft = { 'settings.general': generalDraft, 'settings.currency': currencyDraft, 'settings.branding': brandingDraft, 'settings.invoice': invoiceDraft }[key];
    draft?.restoreDraft(true);
});

function saveGeneral() {
    generalForm.put(route('settings.general'), {
        preserveScroll: true,
        onSuccess: () => generalDraft.clearDraft(),
    });
}

function saveCurrency() {
    currencyForm.put(route('settings.currency'), {
        preserveScroll: true,
        onSuccess: () => currencyDraft.clearDraft(),
    });
}

function saveBranding() {
    brandingForm.post(route('settings.branding'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => brandingDraft.clearDraft(),
    });
}

function saveInvoice() {
    invoiceForm.put(route('settings.invoice'), {
        preserveScroll: true,
        onSuccess: () => invoiceDraft.clearDraft(),
    });
}

function onLogoChange(event) {
    brandingForm.logo = event.target.files[0] ?? null;
}

function previewUrl(design) {
    return route('settings.invoice.preview', design);
}

function openPreview(design, event) {
    event?.preventDefault();
    event?.stopPropagation();
    previewDesign.value = design;
    previewOpen.value = true;
}
</script>

<template>
    <AppLayout :title="t('nav.settings')">
        <Head :title="t('nav.settings')" />

        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.settings') }}
                </h1>
                <UButton :to="route('shop.settings')" variant="soft" icon="i-heroicons-globe-alt" :label="t('shop.settings')" />
                <UButton :to="route('preferences.display')" variant="soft" icon="i-heroicons-adjustments-horizontal" :label="t('preferences.display_menu')" />
            </div>

            <UTabs v-model="tab" :items="tabItems" class="w-full">
                <template #content="{ item }">
                    <UCard class="mt-4">
                        <!-- General -->
                        <div v-if="item.value === 'general'" class="grid max-w-2xl gap-4">
                            <UFormField :label="t('settings.company_name')" :error="generalForm.errors.company_name">
                                <UInput v-model="generalForm.company_name" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.locale')" :error="generalForm.errors.locale">
                                <USelectMenu v-model="generalForm.locale" :items="localeItems" value-key="value" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.timezone')" :error="generalForm.errors.timezone">
                                <UInput v-model="generalForm.timezone" class="w-full" />
                            </UFormField>
                            <UButton :label="t('common.save')" class="w-fit" :loading="generalForm.processing" @click="saveGeneral" />
                        </div>

                        <!-- Branding -->
                        <div v-else-if="item.value === 'branding'" class="grid max-w-2xl gap-4">
                            <UFormField :label="t('settings.primary_color')" :error="brandingForm.errors.primary_color">
                                <UInput v-model="brandingForm.primary_color" type="color" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.secondary_color')" :error="brandingForm.errors.secondary_color">
                                <UInput v-model="brandingForm.secondary_color" type="color" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.logo')" :error="brandingForm.errors.logo">
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="block w-full text-sm text-muted file:me-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
                                    @change="onLogoChange"
                                />
                            </UFormField>
                            <UButton :label="t('common.save')" class="w-fit" :loading="brandingForm.processing" @click="saveBranding" />
                        </div>

                        <!-- Currency -->
                        <div v-else-if="item.value === 'currency'" class="grid max-w-2xl gap-4">
                            <UFormField :label="t('settings.default_currency')" :error="currencyForm.errors.currency">
                                <USelectMenu v-model="currencyForm.currency" :items="currencyItems" value-key="value" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.exchange_rate')" :error="currencyForm.errors.exchange_rate">
                                <UInput v-model="currencyForm.exchange_rate" type="number" step="0.01" class="w-full" />
                            </UFormField>
                            <UButton :label="t('common.save')" class="w-fit" :loading="currencyForm.processing" @click="saveCurrency" />
                        </div>

                        <!-- Invoice -->
                        <div v-else-if="item.value === 'invoice'" class="grid max-w-3xl gap-6">
                            <UFormField :label="t('settings.invoice_design')" :error="invoiceForm.errors.invoice_design">
                                <p class="mb-3 text-sm text-dimmed">{{ t('settings.invoice_design_help') }}</p>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div
                                        v-for="design in invoiceDesigns"
                                        :key="design.key"
                                        class="overflow-hidden rounded-lg border transition-colors"
                                        :class="invoiceForm.invoice_design === design.key ? 'border-primary ring-2 ring-primary/30' : 'border-default'"
                                    >
                                        <label class="block cursor-pointer">
                                            <input v-model="invoiceForm.invoice_design" type="radio" class="sr-only" :value="design.key" />
                                            <div class="border-b border-default bg-elevated px-3 py-2 text-sm font-medium">{{ design.label }}</div>
                                            <img
                                                :src="design.cover"
                                                :alt="design.label"
                                                class="h-40 w-full bg-white object-cover object-top"
                                            />
                                        </label>
                                        <div class="border-t border-default bg-default px-3 py-2">
                                            <UButton
                                                size="xs"
                                                color="neutral"
                                                variant="soft"
                                                icon="i-heroicons-eye"
                                                :label="t('settings.preview_example')"
                                                class="w-full justify-center"
                                                @click="openPreview(design.key, $event)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </UFormField>
                            <UFormField :label="t('settings.invoice_prefix')" :error="invoiceForm.errors.invoice_prefix">
                                <UInput v-model="invoiceForm.invoice_prefix" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.invoice_footer')" :error="invoiceForm.errors.invoice_footer">
                                <UTextarea v-model="invoiceForm.invoice_footer" class="w-full" />
                            </UFormField>
                            <UButton :label="t('common.save')" class="w-fit" :loading="invoiceForm.processing" @click="saveInvoice" />
                        </div>
                    </UCard>
                </template>
            </UTabs>

            <UModal v-model:open="previewOpen" :title="t('settings.preview_example')" :ui="{ content: 'sm:max-w-4xl' }">
                <template #body>
                    <iframe
                        v-if="previewDesign"
                        :src="previewUrl(previewDesign)"
                        class="h-[70vh] w-full rounded-md border border-default bg-white"
                        :title="t('settings.preview_example')"
                    />
                </template>
            </UModal>
        </div>
    </AppLayout>
</template>
