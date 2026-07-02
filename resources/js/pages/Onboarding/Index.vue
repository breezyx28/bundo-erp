<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    defaults: { type: Object, required: true },
    modules: { type: Array, default: () => [] },
});

const { t } = useTrans();

const step = ref(1);

const form = useForm({
    company_name: props.defaults.company_name,
    primary_color: props.defaults.primary_color,
    secondary_color: props.defaults.secondary_color,
    locale: props.defaults.locale,
    timezone: props.defaults.timezone,
    currency: props.defaults.currency,
    exchange_rate: props.defaults.exchange_rate,
    branch_name: props.defaults.branch_name,
    branch_code: props.defaults.branch_code,
    branch_phone: props.defaults.branch_phone,
    branch_address: props.defaults.branch_address,
    logo: null,
    moduleToggles: props.modules.map((m) => ({ key: m.key, enabled: m.enabled })),
});

const localeOptions = [
    { value: 'ar', label: 'العربية' },
    { value: 'en', label: 'English' },
];
const currencyOptions = [
    { value: 'SDG', label: 'SDG' },
    { value: 'USD', label: 'USD' },
];

function onLogo(e) {
    form.logo = e.target.files?.[0] ?? null;
}

function next() {
    step.value = Math.min(5, step.value + 1);
}
function back() {
    step.value = Math.max(1, step.value - 1);
}

const canProceed = computed(() => {
    if (step.value === 2) return form.company_name.trim().length > 0;
    if (step.value === 3) return form.branch_name.trim().length > 0 && form.branch_code.trim().length > 0;
    if (step.value === 4) return form.timezone.trim().length > 0 && String(form.exchange_rate).length > 0;
    return true;
});

function finish() {
    form.transform((data) => ({
        ...data,
        moduleToggles: data.moduleToggles.map((m) => ({ key: m.key, enabled: m.enabled ? 1 : 0 })),
    })).post(route('onboarding.finish'), { forceFormData: true });
}
</script>

<template>
    <AppLayout :title="t('onboarding.title')">
        <Head :title="t('onboarding.title')" />

        <div class="mx-auto max-w-3xl space-y-6">
            <UCard :ui="{ body: 'p-8 sm:p-8' }">
                <!-- Step 1: welcome / locale -->
                <div v-if="step === 1" class="space-y-4 text-center">
                    <h1 class="text-2xl font-bold text-highlighted">{{ t('onboarding.welcome') }}</h1>
                    <p class="text-muted">{{ t('onboarding.welcome_sub') }}</p>
                    <UFormField :label="t('settings.locale')" class="mx-auto max-w-xs">
                        <USelectMenu v-model="form.locale" :items="localeOptions" value-key="value" label-key="label" class="w-full" />
                    </UFormField>
                </div>

                <!-- Step 2: business -->
                <div v-else-if="step === 2" class="space-y-4">
                    <h2 class="text-xl font-semibold text-highlighted">{{ t('onboarding.business_title') }}</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <UFormField :label="t('settings.company_name')" class="md:col-span-2" :error="form.errors.company_name">
                            <UInput v-model="form.company_name" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('onboarding.primary_color')" :error="form.errors.primary_color">
                            <UInput v-model="form.primary_color" type="color" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('onboarding.secondary_color')" :error="form.errors.secondary_color">
                            <UInput v-model="form.secondary_color" type="color" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('settings.logo')" class="md:col-span-2" :error="form.errors.logo">
                            <input type="file" accept="image/*" class="block w-full text-sm text-muted file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-white" @change="onLogo" />
                        </UFormField>
                    </div>
                </div>

                <!-- Step 3: branch -->
                <div v-else-if="step === 3" class="space-y-4">
                    <h2 class="text-xl font-semibold text-highlighted">{{ t('onboarding.branch_title') }}</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <UFormField :label="t('fields.name')" :error="form.errors.branch_name">
                            <UInput v-model="form.branch_name" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('branches.code')" :error="form.errors.branch_code">
                            <UInput v-model="form.branch_code" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('branches.phone')" :error="form.errors.branch_phone">
                            <UInput v-model="form.branch_phone" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('branches.address')" class="md:col-span-2" :error="form.errors.branch_address">
                            <UTextarea v-model="form.branch_address" class="w-full" />
                        </UFormField>
                    </div>
                </div>

                <!-- Step 4: system -->
                <div v-else-if="step === 4" class="space-y-4">
                    <h2 class="text-xl font-semibold text-highlighted">{{ t('onboarding.system_title') }}</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <UFormField :label="t('onboarding.timezone')" :error="form.errors.timezone">
                            <UInput v-model="form.timezone" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('onboarding.currency')" :error="form.errors.currency">
                            <USelectMenu v-model="form.currency" :items="currencyOptions" value-key="value" label-key="label" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('onboarding.exchange_rate')" :error="form.errors.exchange_rate">
                            <UInput v-model="form.exchange_rate" type="number" step="0.01" class="w-full" />
                        </UFormField>
                    </div>
                </div>

                <!-- Step 5: modules -->
                <div v-else class="space-y-4">
                    <h2 class="text-xl font-semibold text-highlighted">{{ t('onboarding.modules_title') }}</h2>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="mod in form.moduleToggles"
                            :key="mod.key"
                            class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-default bg-elevated/30 px-3 py-2.5"
                        >
                            <span class="text-sm font-medium capitalize">{{ mod.key }}</span>
                            <USwitch v-model="mod.enabled" />
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between border-t border-default pt-6">
                    <UButton v-if="step > 1" :label="t('onboarding.back')" color="neutral" variant="ghost" @click="back" />
                    <span v-else></span>

                    <UButton v-if="step < 5" :label="t('onboarding.next')" :disabled="!canProceed" @click="next" />
                    <UButton v-else :label="t('onboarding.finish')" :loading="form.processing" @click="finish" />
                </div>
            </UCard>

            <div class="flex justify-center gap-2">
                <span
                    v-for="i in 5"
                    :key="i"
                    class="size-2 rounded-full transition-colors"
                    :class="i <= step ? 'bg-primary' : 'bg-elevated'"
                />
            </div>
        </div>
    </AppLayout>
</template>
