<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    settings: { type: Object, required: true },
});

const { t } = useTrans();

const tab = ref('general');

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
});

function saveGeneral() {
    generalForm.put(route('settings.general'), { preserveScroll: true });
}

function saveCurrency() {
    currencyForm.put(route('settings.currency'), { preserveScroll: true });
}

function saveBranding() {
    brandingForm.post(route('settings.branding'), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function saveInvoice() {
    invoiceForm.put(route('settings.invoice'), { preserveScroll: true });
}

function onLogoChange(event) {
    brandingForm.logo = event.target.files[0] ?? null;
}
</script>

<template>
    <AppLayout :title="t('nav.settings')">
        <Head :title="t('nav.settings')" />

        <div class="space-y-6">
            <h1 class="text-xl font-semibold text-highlighted">
                {{ t('nav.settings') }}
            </h1>

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
                        <div v-else-if="item.value === 'invoice'" class="grid max-w-2xl gap-4">
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
        </div>
    </AppLayout>
</template>
