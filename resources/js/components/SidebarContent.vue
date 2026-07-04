<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    nav: { type: Array, required: true },
    branding: { type: Object, required: true },
    navIcon: { type: Function, required: true },
});
</script>

<template>
    <div class="flex h-full min-h-0 flex-col bg-default">
        <!-- Brand header -->
        <div
            class="flex h-14 shrink-0 items-center gap-2.5 border-b border-default px-3 lg:h-[68px] lg:px-4"
        >
            <img
                v-if="branding.logo"
                :src="branding.logo"
                alt=""
                class="size-9 rounded-lg object-cover"
            />
            <div
                v-else
                class="flex size-9 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white"
            >
                {{ (branding.company || 'M').charAt(0) }}
            </div>
            <span class="truncate text-sm font-semibold tracking-tight">
                {{ branding.company }}
            </span>
        </div>

        <!-- Primary navigation -->
        <nav class="min-h-0 flex-1 overflow-y-auto p-2">
            <ul class="flex flex-col gap-0.5">
                <li v-for="item in nav" :key="item.label">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            item.active
                                ? 'bg-primary/10 text-primary'
                                : 'text-toned hover:bg-elevated'
                        "
                    >
                        <UIcon
                            :name="navIcon(item.icon)"
                            class="size-5 shrink-0"
                            :class="item.active ? 'text-primary' : 'opacity-70'"
                        />
                        <span class="grow truncate">{{ item.label }}</span>
                        <UBadge
                            v-if="item.badge?.count"
                            :label="String(item.badge.count)"
                            :color="item.badge.tone"
                            size="sm"
                            variant="solid"
                        />
                    </Link>
                </li>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="shrink-0 border-t border-default p-3 text-[10px] text-dimmed">
            {{ branding.company }} · v0.1
        </div>
    </div>
</template>
