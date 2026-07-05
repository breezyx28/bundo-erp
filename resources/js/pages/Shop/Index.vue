<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ShopLayout from '@/layouts/ShopLayout.vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    tenant: { type: Object, required: true },
    shop: { type: Object, required: true },
    featured: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    seo: { type: Object, default: () => ({}) },
});

const { t } = useTrans();

function filterCategory(id) {
    router.get(route('shop.index', props.tenant.slug), { category: id || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <ShopLayout :tenant="tenant" :shop="shop" :seo="seo">
        <section v-if="shop.hero_image || shop.hero_title" class="mb-8 overflow-hidden rounded-2xl bg-elevated">
            <div class="relative">
                <img v-if="shop.hero_image" :src="shop.hero_image" alt="" class="h-48 w-full object-cover sm:h-64" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                    <h1 class="text-2xl font-bold sm:text-3xl">{{ shop.hero_title || tenant.name }}</h1>
                    <p v-if="shop.hero_subtitle" class="mt-1 text-sm text-white/90">{{ shop.hero_subtitle }}</p>
                </div>
            </div>
        </section>

        <section v-if="shop.banners?.length" class="mb-8 grid gap-3 sm:grid-cols-2">
            <a
                v-for="(banner, index) in shop.banners"
                :key="index"
                :href="banner.link || '#'"
                class="overflow-hidden rounded-xl bg-elevated"
                :class="{ 'pointer-events-none': !banner.link }"
            >
                <img v-if="banner.image" :src="banner.image" :alt="banner.title" class="aspect-[2/1] w-full object-cover" />
                <p v-if="banner.title" class="p-3 text-sm font-medium">{{ banner.title }}</p>
            </a>
        </section>

        <section v-if="featured.length" class="mb-10">
            <h2 class="mb-4 text-lg font-semibold text-highlighted">{{ t('shop.featured') }}</h2>
            <div class="flex gap-4 overflow-x-auto pb-2">
                <a
                    v-for="item in featured"
                    :key="item.id"
                    :href="item.url"
                    class="w-40 shrink-0 overflow-hidden rounded-xl border border-default bg-elevated"
                >
                    <div class="aspect-square bg-muted">
                        <img v-if="item.image" :src="item.image" :alt="item.name" class="size-full object-cover" />
                    </div>
                    <div class="p-3">
                        <p class="truncate text-sm font-medium">{{ item.name }}</p>
                        <p v-if="item.price" class="text-xs font-semibold text-[var(--shop-primary)]">{{ item.price }}</p>
                    </div>
                </a>
            </div>
        </section>

        <section>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-highlighted">{{ t('shop.all_products') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <UButton size="xs" :variant="!filters.category ? 'solid' : 'ghost'" @click="filterCategory(null)">{{ t('common.all') }}</UButton>
                    <UButton
                        v-for="cat in categories"
                        :key="cat.id"
                        size="xs"
                        :variant="filters.category === cat.id ? 'solid' : 'ghost'"
                        @click="filterCategory(cat.id)"
                    >
                        {{ cat.name }}
                    </UButton>
                </div>
            </div>

            <div v-if="products.data?.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <a
                    v-for="item in products.data"
                    :key="item.id"
                    :href="item.url"
                    class="overflow-hidden rounded-xl border border-default bg-elevated transition hover:shadow-md"
                >
                    <div class="aspect-square bg-muted">
                        <img v-if="item.image" :src="item.image" :alt="item.name" class="size-full object-cover" />
                    </div>
                    <div class="p-3">
                        <p v-if="item.category" class="text-xs text-dimmed">{{ item.category }}</p>
                        <p class="truncate font-medium">{{ item.name }}</p>
                        <p v-if="item.price" class="mt-1 text-sm font-semibold text-[var(--shop-primary)]">{{ item.price }}</p>
                    </div>
                </a>
            </div>
            <p v-else class="py-12 text-center text-dimmed">{{ t('shop.no_products') }}</p>

            <div v-if="products.links?.length > 3" class="mt-8 flex justify-center gap-1">
                <template v-for="(link, i) in products.links" :key="i">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-[var(--shop-primary)] text-white' : 'text-dimmed hover:bg-elevated'"
                        v-html="link.label"
                    />
                </template>
            </div>
        </section>
    </ShopLayout>
</template>
