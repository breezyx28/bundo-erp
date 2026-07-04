<script setup>
defineProps({
    active: { type: Boolean, required: true },
    activeLabel: { type: String, required: true },
    inactiveLabel: { type: String, required: true },
});
</script>

<template>
    <UBadge
        :color="active ? 'success' : 'neutral'"
        variant="subtle"
        :label="active ? activeLabel : inactiveLabel"
    />
</template>
