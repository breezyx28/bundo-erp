<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    tenant: { type: Object, required: true },
    shop: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

const whatsappUrl = computed(() => {
    const phone = props.shop.contact?.whatsapp || props.shop.contact?.phone;
    if (!phone) return null;
    const digits = phone.replace(/\D/g, '');
    const text = encodeURIComponent(props.shop.share_message || props.tenant.name);
    return `https://wa.me/${digits}?text=${text}`;
});

const phoneUrl = computed(() => {
    const phone = props.shop.contact?.phone;
    return phone ? `tel:${phone}` : null;
});

const styleVars = computed(() => ({
    '--shop-primary': props.tenant.primary_color || '#39C6A0',
    '--shop-secondary': props.tenant.secondary_color || '#228C70',
}));
</script>

<template>
    <div class="min-h-dvh bg-default pb-24" :style="styleVars">
        <Head>
            <title>{{ seo.title || tenant.name }}</title>
            <meta head-key="description" name="description" :content="seo.description" />
            <meta v-if="seo.image" head-key="og:image" property="og:image" :content="seo.image" />
        </Head>

        <header class="sticky top-0 z-20 border-b border-default bg-default/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <img v-if="tenant.logo" :src="tenant.logo" :alt="tenant.name" class="h-10 w-10 rounded-lg object-cover" />
                <div class="min-w-0">
                    <Link :href="route('shop.index', tenant.slug)" class="truncate text-lg font-semibold text-highlighted">{{ tenant.name }}</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <slot />
        </main>

        <footer
            v-if="whatsappUrl || phoneUrl"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-default bg-default/95 backdrop-blur"
        >
            <div class="mx-auto flex max-w-6xl gap-2 p-3">
                <a
                    v-if="whatsappUrl"
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[var(--shop-primary)] px-4 py-3 text-sm font-semibold text-white"
                >
                    <UIcon name="i-simple-icons-whatsapp" class="size-5" />
                    {{ t('shop.whatsapp') }}
                </a>
                <a
                    v-if="phoneUrl"
                    :href="phoneUrl"
                    class="flex items-center justify-center gap-2 rounded-xl border border-default px-4 py-3 text-sm font-medium"
                >
                    <UIcon name="i-heroicons-phone" class="size-5" />
                    {{ t('shop.call') }}
                </a>
            </div>
        </footer>
    </div>
</template>
