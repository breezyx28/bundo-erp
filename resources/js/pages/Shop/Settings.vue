<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

const props = defineProps({
    settings: { type: Object, required: true },
    public_url: { type: String, required: true },
    slug: { type: String, required: true },
    shop_enabled: { type: Boolean, default: false },
});

const { t } = useTrans();
const copied = ref(false);

const form = useForm({
    enabled: props.settings.enabled ?? false,
    show_prices: props.settings.show_prices ?? true,
    hero_title: props.settings.hero_title ?? '',
    hero_subtitle: props.settings.hero_subtitle ?? '',
    hero_image: null,
    share_message: props.settings.share_message ?? '',
    contact: {
        phone: props.settings.contact?.phone ?? '',
        whatsapp: props.settings.contact?.whatsapp ?? '',
        instagram: props.settings.contact?.instagram ?? '',
        facebook: props.settings.contact?.facebook ?? '',
        tiktok: props.settings.contact?.tiktok ?? '',
        address: props.settings.contact?.address ?? '',
        email: props.settings.contact?.email ?? '',
    },
    banners: (props.settings.banners?.length ? props.settings.banners : [{ title: '', link: '', image: null }]).map((b) => ({
        title: b.title ?? '',
        link: b.link ?? '',
        image: null,
        existing_image: b.image ?? null,
    })),
});

const shopDraft = useFormDraft({
    key: 'shop.settings',
    label: t('shop.settings'),
    routeName: 'shop.settings',
    form,
    getSnapshot: () => ({
        enabled: form.enabled,
        show_prices: form.show_prices,
        hero_title: form.hero_title,
        hero_subtitle: form.hero_subtitle,
        share_message: form.share_message,
        contact: { ...form.contact },
        banners: form.banners.map((b) => ({
            title: b.title,
            link: b.link,
            existing_image: b.existing_image,
        })),
    }),
    onApply: (data) => {
        form.enabled = data.enabled ?? false;
        form.show_prices = data.show_prices ?? true;
        form.hero_title = data.hero_title ?? '';
        form.hero_subtitle = data.hero_subtitle ?? '';
        form.share_message = data.share_message ?? '';
        form.contact = { ...form.contact, ...(data.contact ?? {}) };
        if (data.banners?.length) {
            form.banners = data.banners.map((b) => ({
                title: b.title ?? '',
                link: b.link ?? '',
                image: null,
                existing_image: b.existing_image ?? null,
            }));
        }
    },
});

useDraftQueryRestore('shop', () => {
    shopDraft.restoreDraft(true);
});

const shopStatusMessage = computed(() => {
    if (props.shop_enabled) {
        return t('shop.shop_live');
    }
    return t('shop.shop_preview_only');
});

const shopStatusColor = computed(() => (props.shop_enabled ? 'success' : 'warning'));

function fieldError(key) {
    return form.errors[key] ?? null;
}

function bannerError(index, field) {
    return form.errors[`banners.${index}.${field}`] ?? null;
}

function addBanner() {
    if (form.banners.length >= 6) return;
    form.banners.push({ title: '', link: '', image: null, existing_image: null });
}

function save() {
    form.post(route('shop.settings.save'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => shopDraft.clearDraft(),
    });
}

async function copyLink() {
    await navigator.clipboard.writeText(props.public_url);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}
</script>

<template>
    <AppLayout :title="t('shop.settings')">
        <Head :title="t('shop.settings')" />

        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-xl font-semibold text-highlighted">{{ t('shop.settings') }}</h1>

            <UAlert
                :color="shopStatusColor"
                variant="subtle"
                :title="shopStatusMessage"
                :description="!shop_enabled ? t('shop.shop_disabled_hint') : undefined"
            />

            <UCard>
                <div class="space-y-3">
                    <p class="text-sm text-dimmed">{{ t('shop.public_link') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="public_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded bg-elevated px-3 py-2 text-sm text-primary hover:underline"
                        >{{ public_url }}</a>
                        <UButton size="sm" variant="soft" icon="i-heroicons-arrow-top-right-on-square" :label="t('shop.open_shop')" :to="public_url" target="_blank" />
                        <UButton size="sm" variant="soft" :label="copied ? t('shop.link_copied') : t('shop.copy_link')" @click="copyLink" />
                    </div>
                </div>
            </UCard>

            <UCard>
                <form class="grid gap-4" @submit.prevent="save">
                    <UAlert
                        v-if="Object.keys(form.errors).length"
                        color="error"
                        variant="subtle"
                        :title="t('common.validation_errors')"
                    />

                    <UCheckbox v-model="form.enabled" :label="t('shop.enabled')" />
                    <UCheckbox v-model="form.show_prices" :label="t('shop.show_prices')" />

                    <UFormField :label="t('shop.hero_title')" :error="fieldError('hero_title')" required>
                        <UInput v-model="form.hero_title" class="w-full" :required="form.enabled" />
                    </UFormField>
                    <UFormField :label="t('shop.hero_subtitle')" :error="fieldError('hero_subtitle')">
                        <UTextarea v-model="form.hero_subtitle" :rows="2" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shop.hero_image')" :error="fieldError('hero_image')">
                        <input type="file" accept="image/*" class="block w-full text-sm" @change="form.hero_image = $event.target.files[0]" />
                    </UFormField>
                    <UFormField :label="t('shop.share_message')" :error="fieldError('share_message')">
                        <UTextarea v-model="form.share_message" :rows="2" class="w-full" />
                    </UFormField>

                    <div class="border-t border-default pt-4">
                        <h2 class="mb-3 text-sm font-semibold">{{ t('shop.contact_info') }}</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <UFormField :label="t('shop.call')" :error="fieldError('contact.phone')">
                                <UInput v-model="form.contact.phone" type="tel" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('shop.whatsapp')" :error="fieldError('contact.whatsapp')">
                                <UInput v-model="form.contact.whatsapp" type="tel" class="w-full" />
                            </UFormField>
                            <UFormField label="Instagram" :error="fieldError('contact.instagram')">
                                <UInput v-model="form.contact.instagram" class="w-full" />
                            </UFormField>
                            <UFormField label="Facebook" :error="fieldError('contact.facebook')">
                                <UInput v-model="form.contact.facebook" class="w-full" />
                            </UFormField>
                            <UFormField label="TikTok" :error="fieldError('contact.tiktok')">
                                <UInput v-model="form.contact.tiktok" class="w-full" />
                            </UFormField>
                            <UFormField label="Email" :error="fieldError('contact.email')">
                                <UInput v-model="form.contact.email" type="email" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('fields.address')" class="sm:col-span-2" :error="fieldError('contact.address')">
                                <UTextarea v-model="form.contact.address" :rows="2" class="w-full" />
                            </UFormField>
                        </div>
                    </div>

                    <div class="border-t border-default pt-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold">{{ t('shop.banners') }}</h2>
                            <UButton size="xs" variant="ghost" type="button" :label="t('shop.add_banner')" @click="addBanner" />
                        </div>
                        <p v-if="fieldError('banners')" class="mb-2 text-sm text-error">{{ fieldError('banners') }}</p>
                        <div v-for="(banner, index) in form.banners" :key="index" class="mb-4 grid gap-2 rounded-lg border border-default p-3">
                            <UFormField :label="t('shop.banner_title')" :error="bannerError(index, 'title')">
                                <UInput v-model="banner.title" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('shop.banner_link')" :error="bannerError(index, 'link')">
                                <UInput v-model="banner.link" type="url" placeholder="https://" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('shop.banner_image')" :error="bannerError(index, 'image')">
                                <img v-if="banner.existing_image && !banner.image" :src="`/storage/${banner.existing_image}`" alt="" class="mb-2 h-20 rounded object-cover" />
                                <input type="file" accept="image/*" class="block w-full text-sm" @change="banner.image = $event.target.files[0]" />
                            </UFormField>
                        </div>
                    </div>

                    <div class="flex justify-center pt-2">
                        <UButton type="submit" class="min-w-32 justify-center" :loading="form.processing" :label="t('common.save')" />
                    </div>
                </form>
            </UCard>
        </div>
    </AppLayout>
</template>
