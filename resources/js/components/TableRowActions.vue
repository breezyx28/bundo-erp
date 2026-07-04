<script setup>
defineProps({
    showEdit: { type: Boolean, default: true },
    showDelete: { type: Boolean, default: true },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <UButton
        v-if="showEdit"
        icon="i-heroicons-pencil-square"
        color="neutral"
        variant="ghost"
        size="sm"
        @click="$emit('edit')"
    />
    <UButton
        v-if="showDelete"
        icon="i-heroicons-trash"
        color="error"
        variant="ghost"
        size="sm"
        @click="$emit('delete')"
    />
</template>
