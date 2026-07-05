<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import { useQuickActions } from '@/composables/useQuickActions';

const open = defineModel('open', { type: Boolean, default: false });

const page = usePage();
const { t } = useTrans();
const { actions } = useQuickActions();

const groups = computed(() => {
    const pages = (page.props.nav ?? [])
        .filter((item) => item.href)
        .map((item) => ({
            label: item.label,
            icon: item.icon?.startsWith('i-') ? item.icon : `i-heroicons-${item.icon}`,
            suffix: item.badge?.count ? String(item.badge.count) : undefined,
            onSelect: () => go(item.href),
        }));

    const quick = actions.value.map((action) => ({
        label: action.label,
        icon: action.icon,
        onSelect: () => go(action.href),
    }));

    return [
        { id: 'pages', label: t('links.pages'), items: pages },
        ...(quick.length ? [{ id: 'actions', label: t('links.actions'), items: quick }] : []),
    ];
});

function go(href) {
    open.value = false;
    router.visit(href);
}

function onKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <UModal
        v-model:open="open"
        :ui="{ overlay: 'bg-neutral-950/50 backdrop-blur-sm', content: 'sm:max-w-lg' }"
    >
        <template #content>
            <UCommandPalette
                :groups="groups"
                :placeholder="t('links.command_placeholder')"
                close
                class="h-96"
                @update:open="open = $event"
            />
        </template>
    </UModal>
</template>
