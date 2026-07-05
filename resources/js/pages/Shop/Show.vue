<script setup>
import { computed } from 'vue';
import { route } from 'ziggy-js';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    tenant: { type: Object, required: true },
    shop: { type: Object, required: true },
    product: { type: Object, required: true },
    seo: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

const activeImage = computed(() => props.product.images?.[0] ?? null);

const whatsappProductUrl = computed(() => {
    const phone = props.shop.contact?.whatsapp || props.shop.contact?.phone;
    if (!phone) return null;
    const digits = phone.replace(/\D/g, '');
    const text = encodeURIComponent(
        props.shop.share_message
            ? `${props.shop.share_message} — ${props.product.name}`
            : `${props.product.name} — ${props.tenant.name}`,
    );
    return `https://wa.me/${digits}?text=${text}`;
});
</script>

<template>
    <ShopLayout :tenant="tenant" :shop="shop" :seo="seo">
        <a :href="route('shop.index', tenant.slug)" class="mb-4 inline-flex items-center gap-1 text-sm text-dimmed hover:text-highlighted">
            <UIcon name="i-heroicons-arrow-left" class="size-4" />
            {{ tenant.name }}
        </a>

        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                <div class="aspect-square overflow-hidden rounded-2xl bg-muted">
                    <img v-if="activeImage" :src="activeImage" :alt="product.name" class="size-full object-cover" />
                </div>
                <div v-if="product.images?.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                    <img
                        v-for="(img, i) in product.images"
                        :key="i"
                        :src="img"
                        alt=""
                        class="size-16 rounded-lg object-cover"
                    />
                </div>
            </div>

            <div>
                <p v-if="product.category" class="text-sm text-dimmed">{{ product.category }}</p>
                <h1 class="text-2xl font-bold text-highlighted">{{ product.name }}</h1>
                <p v-if="product.brand" class="mt-1 text-sm text-muted">{{ product.brand }}</p>
                <p v-if="product.price" class="mt-4 text-xl font-bold text-[var(--shop-primary)]">{{ product.price }}</p>

                <p v-if="product.description" class="mt-6 whitespace-pre-line text-sm leading-relaxed text-muted">{{ product.description }}</p>

                <a
                    v-if="whatsappProductUrl"
                    :href="whatsappProductUrl"
                    target="_blank"
                    rel="noopener"
                    class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[var(--shop-primary)] px-4 py-3 text-sm font-semibold text-white sm:w-auto"
                >
                    <UIcon name="i-simple-icons-whatsapp" class="size-5" />
                    {{ t('shop.contact_seller') }}
                </a>

                <div v-if="product.variants?.length" class="mt-10">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-dimmed">{{ t('shop.specifications') }}</h2>
                    <div class="overflow-x-auto rounded-xl border border-default">
                        <table class="min-w-full text-sm">
                            <thead class="bg-elevated text-dimmed">
                                <tr>
                                    <th class="px-3 py-2 text-start">{{ t('shop.size') }}</th>
                                    <th class="px-3 py-2 text-start">{{ t('shop.color') }}</th>
                                    <th class="px-3 py-2 text-start">{{ t('shop.sku') }}</th>
                                    <th v-if="shop.show_prices" class="px-3 py-2 text-end">{{ t('shop.price') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-default">
                                <tr v-for="(variant, i) in product.variants" :key="i">
                                    <td class="px-3 py-2">{{ variant.size || '—' }}</td>
                                    <td class="px-3 py-2">{{ variant.color || '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ variant.sku }}</td>
                                    <td v-if="shop.show_prices" class="px-3 py-2 text-end tabular-nums">{{ variant.price || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </ShopLayout>
</template>
