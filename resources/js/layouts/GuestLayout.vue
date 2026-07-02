<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDirection } from '@/composables/useDirection';

const page = usePage();
const { nuxtLocale } = useDirection();

const company = computed(() => page.props.branding?.company || 'Mazin Shoes');
</script>

<template>
    <UApp :locale="nuxtLocale">
        <div class="flex min-h-screen items-center justify-center bg-muted p-4">
            <div class="w-full max-w-md">
                <div class="mb-8 flex flex-col items-center gap-3">
                    <div
                        class="flex size-14 items-center justify-center rounded-lg bg-primary text-2xl font-bold text-white"
                    >
                        M
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-highlighted">
                        {{ company }}
                    </h1>
                </div>

                <UCard>
                    <slot />
                </UCard>
            </div>
        </div>
    </UApp>
</template>
