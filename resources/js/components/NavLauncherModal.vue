<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppIconGrid from '@/components/AppIconGrid.vue';
import { useTrans } from '@/composables/useTrans';
import { useQuickActions } from '@/composables/useQuickActions';

const open = defineModel('open', { type: Boolean, default: false });

const page = usePage();
const { t } = useTrans();
const { actions } = useQuickActions();

const search = ref('');

const items = computed(() => {
    const nav = page.props.nav ?? [];
    const home = {
        label: t('links.title'),
        icon: 'i-heroicons-squares-2x2',
        route: 'links.index',
        href: route('links.index'),
        active: false,
        badge: null,
    };
    const all = [home, ...nav.filter((item) => item.href)];
    const term = search.value.trim().toLowerCase();

    return term
        ? all.filter((item) => item.label.toLowerCase().includes(term))
        : all;
});

// Close after navigating from within the launcher.
router.on('navigate', () => {
    open.value = false;
    search.value = '';
});
</script>

<template>
    <UModal
        v-model:open="open"
        :ui="{
            overlay: 'bg-neutral-950/60 backdrop-blur-md',
            content: 'sm:max-w-3xl',
        }"
    >
        <template #content>
            <div class="max-h-[80vh] space-y-6 overflow-y-auto p-6">
                <UInput
                    v-model="search"
                    icon="i-heroicons-magnifying-glass"
                    :placeholder="t('links.search_placeholder')"
                    size="lg"
                    class="w-full"
                    autofocus
                />

                <AppIconGrid :items="items" compact />

                <div v-if="!items.length" class="py-8 text-center text-sm text-muted">
                    {{ t('links.no_results') }}
                </div>

                <div v-if="actions.length" class="border-t border-default pt-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">
                        {{ t('links.actions') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <UButton
                            v-for="action in actions"
                            :key="action.key"
                            :label="action.label"
                            :icon="action.icon"
                            color="neutral"
                            variant="soft"
                            size="sm"
                            :to="action.href"
                        />
                    </div>
                </div>
            </div>
        </template>
    </UModal>
</template>
