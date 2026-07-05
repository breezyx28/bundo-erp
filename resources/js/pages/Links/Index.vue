<script setup>
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AppIconGrid from '@/components/AppIconGrid.vue';
import { useTrans } from '@/composables/useTrans';
import { useQuickActions } from '@/composables/useQuickActions';
import { useRecentPages } from '@/composables/useRecentPages';

defineProps({
    stats: { type: Array, default: null },
});

const page = usePage();
const { t } = useTrans();
const { actions } = useQuickActions();
const { recentNavItems } = useRecentPages();

const search = ref('');

const navItems = computed(() =>
    (page.props.nav ?? []).filter((item) => item.href),
);

const filteredItems = computed(() => {
    const term = search.value.trim().toLowerCase();
    return term
        ? navItems.value.filter((item) => item.label.toLowerCase().includes(term))
        : navItems.value;
});

const recentItems = computed(() => recentNavItems(navItems.value));

const statTone = {
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
    neutral: 'text-toned',
};
</script>

<template>
    <AppLayout :title="t('links.title')">
        <Head :title="t('links.title')" />

        <div class="mx-auto max-w-4xl space-y-8 py-4">
            <!-- Search -->
            <UInput
                v-model="search"
                icon="i-heroicons-magnifying-glass"
                :placeholder="t('links.search_placeholder')"
                size="xl"
                class="w-full"
            />

            <!-- Quick statistics -->
            <div v-if="stats" class="responsive-stat-grid gap-3">
                <div
                    v-for="stat in stats"
                    :key="stat.key"
                    class="rounded-xl border border-default bg-default p-4"
                >
                    <p class="truncate text-xs text-muted">{{ t(`links.${stat.key}`) }}</p>
                    <p
                        class="mt-1 truncate text-lg font-semibold tabular-nums"
                        :class="statTone[stat.tone] || 'text-toned'"
                    >
                        {{ stat.value }}
                    </p>
                </div>
            </div>

            <!-- Quick access -->
            <div v-if="actions.length && !search">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">
                    {{ t('links.quick_access') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <UButton
                        v-for="action in actions"
                        :key="action.key"
                        :label="action.label"
                        :icon="action.icon"
                        color="neutral"
                        variant="soft"
                        :to="action.href"
                    />
                </div>
            </div>

            <!-- Recently used -->
            <div v-if="recentItems.length && !search">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">
                    {{ t('links.recent') }}
                </p>
                <AppIconGrid :items="recentItems" compact />
            </div>

            <!-- All pages -->
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">
                    {{ t('links.all_pages') }}
                </p>
                <AppIconGrid :items="filteredItems" />
                <div v-if="!filteredItems.length" class="py-10 text-center text-sm text-muted">
                    {{ t('links.no_results') }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
