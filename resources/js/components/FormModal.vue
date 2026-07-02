<script setup>
const open = defineModel('open', { type: Boolean, default: false });

defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    // Tailwind width class for the dialog.
    width: { type: String, default: 'sm:max-w-lg' },
});
</script>

<template>
    <UModal
        v-model:open="open"
        :title="title"
        :description="description"
        :ui="{ content: width }"
    >
        <template #body>
            <slot />
        </template>

        <template v-if="$slots.footer" #footer>
            <div class="flex w-full items-center justify-end gap-2">
                <slot name="footer" :close="() => (open = false)" />
            </div>
        </template>
    </UModal>
</template>
