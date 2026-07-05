<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    // Nav-shaped items: { label, icon, href, active, badge }.
    items: { type: Array, required: true },
    // Compact renders smaller icons (used inside the launcher modal).
    compact: { type: Boolean, default: false },
});

function navIcon(icon) {
    return icon?.startsWith('i-') ? icon : `i-heroicons-${icon}`;
}
</script>

<template>
    <div
        class="grid gap-4"
        :class="compact
            ? 'grid-cols-4 sm:grid-cols-5 md:grid-cols-6'
            : 'grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8'"
    >
        <Link
            v-for="item in items"
            :key="item.route || item.label"
            :href="item.href"
            class="group flex flex-col items-center gap-2 rounded-xl p-2 text-center outline-none focus-visible:ring-2 focus-visible:ring-primary"
        >
            <UChip
                :show="Boolean(item.badge?.count)"
                :text="item.badge?.count ? String(item.badge.count) : ''"
                :color="item.badge?.tone || 'error'"
                size="3xl"
            >
                <span
                    class="flex items-center justify-center rounded-full transition-all group-hover:scale-105 group-hover:shadow-lg"
                    :class="[
                        compact ? 'size-14' : 'size-16 sm:size-18',
                        item.active
                            ? 'bg-primary text-white shadow-md'
                            : 'bg-elevated text-toned group-hover:bg-primary/10 group-hover:text-primary',
                    ]"
                >
                    <UIcon :name="navIcon(item.icon)" :class="compact ? 'size-6' : 'size-7'" />
                </span>
            </UChip>
            <span
                class="line-clamp-2 text-xs font-medium"
                :class="item.active ? 'text-primary' : 'text-toned'"
            >
                {{ item.label }}
            </span>
        </Link>
    </div>
</template>
