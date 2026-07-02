<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.sign_in')" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-semibold text-highlighted">
                {{ t('auth.sign_in') }}
            </h2>
            <p class="mt-1 text-sm text-muted">
                {{ t('auth.sign_in_subtitle') }}
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <UFormField :label="t('auth.email')" :error="form.errors.email">
                <UInput
                    v-model="form.email"
                    type="email"
                    icon="i-heroicons-envelope"
                    autocomplete="email"
                    autofocus
                    class="w-full"
                />
            </UFormField>

            <UFormField
                :label="t('auth.password_label')"
                :error="form.errors.password"
            >
                <UInput
                    v-model="form.password"
                    type="password"
                    icon="i-heroicons-lock-closed"
                    autocomplete="current-password"
                    class="w-full"
                />
            </UFormField>

            <div class="flex items-center justify-between">
                <UCheckbox
                    v-model="form.remember"
                    :label="t('auth.remember_me')"
                />
            </div>

            <UButton
                type="submit"
                :label="t('auth.sign_in')"
                block
                :loading="form.processing"
            />
        </form>
    </GuestLayout>
</template>
