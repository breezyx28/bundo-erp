<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, default: '' },
    iconClass: { type: String, default: 'text-muted' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <UCard>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm text-muted">{{ title }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-highlighted">
                    {{ value }}
                </p>
                <p v-if="hint" class="mt-1 text-xs text-dimmed">{{ hint }}</p>
            </div>
            <div
                v-if="icon"
                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-elevated"
            >
                <UIcon :name="icon" class="size-5" :class="iconClass" />
            </div>
        </div>
    </UCard>
</template>
