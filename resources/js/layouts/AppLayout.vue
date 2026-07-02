<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useDirection } from '@/composables/useDirection';
import { useTrans } from '@/composables/useTrans';
import SidebarContent from '@/components/SidebarContent.vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const { t } = useTrans();
const { locale, dir, nuxtLocale } = useDirection();
const toast = useToast();

const nav = computed(() => page.props.nav || []);
const branding = computed(() => page.props.branding || {});
const user = computed(() => page.props.auth?.user || null);
const branchContext = computed(() => page.props.branchContext || null);
const notifications = computed(
    () => page.props.notifications || { unread: 0, items: [] },
);

const mobileNavOpen = ref(false);

const userInitials = computed(() => {
    const name = user.value?.name || 'G';
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
});

function navIcon(icon) {
    return `i-heroicons-${icon}`;
}

function selectBranch(id) {
    router.post(route('branch-context.update'), { branch: id }, {
        preserveScroll: true,
    });
}

function markNotificationRead(id) {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
}

function markAllNotificationsRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}

function logout() {
    router.post(route('logout'));
}

const notificationLevelClass = {
    alert: 'bg-error/10 text-error',
    reminder: 'bg-warning/10 text-warning',
    info: 'bg-info/10 text-info',
    success: 'bg-success/10 text-success',
};

// Bridge server flash toasts into Nuxt UI's toast system.
watch(
    () => page.props.flash?.toast,
    (flash) => {
        if (!flash) {
            return;
        }
        toast.add({
            title: flash.title,
            description: flash.description || undefined,
            color: flash.type || flash.color || 'info',
        });
    },
    { immediate: true, deep: true },
);

// Close the mobile drawer after navigating.
router.on('navigate', () => {
    mobileNavOpen.value = false;
});

// Poll notifications every 60s (parity with the old wire:poll.60s bell).
let pollTimer = null;
onMounted(() => {
    pollTimer = window.setInterval(() => {
        router.reload({ only: ['notifications'] });
    }, 60000);
});
onUnmounted(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
    }
});
</script>

<template>
    <UApp :locale="nuxtLocale">
        <div class="flex min-h-screen bg-muted">
            <!-- Desktop sidebar -->
            <aside
                class="hidden w-56 shrink-0 border-e border-default bg-default lg:sticky lg:top-0 lg:flex lg:h-svh lg:flex-col"
            >
                <SidebarContent
                    :nav="nav"
                    :branding="branding"
                    :nav-icon="navIcon"
                />
            </aside>

            <!-- Mobile sidebar -->
            <USlideover
                v-model:open="mobileNavOpen"
                :side="dir === 'rtl' ? 'right' : 'left'"
                :ui="{ content: 'w-64' }"
            >
                <template #content>
                    <SidebarContent
                        :nav="nav"
                        :branding="branding"
                        :nav-icon="navIcon"
                    />
                </template>
            </USlideover>

            <div class="flex min-w-0 flex-1 flex-col">
                <!-- Topbar -->
                <header
                    class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-2 border-b border-default bg-default/95 px-4 backdrop-blur sm:px-6 lg:h-[68px] lg:gap-4"
                >
                    <UButton
                        icon="i-heroicons-bars-3"
                        color="neutral"
                        variant="ghost"
                        class="lg:hidden"
                        :aria-label="t('nav.dashboard')"
                        @click="mobileNavOpen = true"
                    />

                    <h1
                        class="min-w-0 flex-1 truncate text-base font-semibold tracking-tight text-highlighted"
                    >
                        {{ title }}
                    </h1>

                    <div class="flex shrink-0 items-center gap-2">
                        <!-- Branch selector -->
                        <UPopover v-if="branchContext">
                            <UButton
                                color="neutral"
                                variant="soft"
                                size="sm"
                                icon="i-heroicons-building-storefront"
                                trailing-icon="i-heroicons-chevron-down"
                                :label="branchContext.current_label"
                                class="max-w-[10rem] truncate"
                            />
                            <template #content>
                                <div class="w-56 p-1">
                                    <button
                                        v-if="branchContext.can_view_all"
                                        type="button"
                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-elevated"
                                        @click="selectBranch('all')"
                                    >
                                        <UIcon name="i-heroicons-squares-2x2" class="size-4" />
                                        {{ t('nav.all_branches') }}
                                    </button>
                                    <USeparator v-if="branchContext.can_view_all" class="my-1" />
                                    <button
                                        v-for="branch in branchContext.branches"
                                        :key="branch.id"
                                        type="button"
                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-elevated"
                                        @click="selectBranch(branch.id)"
                                    >
                                        <UIcon name="i-heroicons-building-storefront" class="size-4" />
                                        <span class="truncate">{{ branch.name }}</span>
                                    </button>
                                </div>
                            </template>
                        </UPopover>

                        <!-- Locale switch (full reload updates <html dir>) -->
                        <div
                            class="flex items-center gap-0.5 rounded-lg border border-default bg-default p-0.5"
                        >
                            <a
                                :href="route('locale.switch', 'ar')"
                                class="rounded-md px-2.5 py-1 text-xs font-medium"
                                :class="
                                    locale === 'ar'
                                        ? 'bg-primary text-white'
                                        : 'text-muted hover:bg-elevated'
                                "
                            >AR</a>
                            <a
                                :href="route('locale.switch', 'en')"
                                class="rounded-md px-2.5 py-1 text-xs font-medium"
                                :class="
                                    locale === 'en'
                                        ? 'bg-primary text-white'
                                        : 'text-muted hover:bg-elevated'
                                "
                            >EN</a>
                        </div>

                        <!-- Notifications -->
                        <UPopover>
                            <UChip
                                :show="notifications.unread > 0"
                                color="error"
                                size="sm"
                            >
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-heroicons-bell"
                                    :aria-label="t('common.notifications')"
                                />
                            </UChip>
                            <template #content>
                                <div class="w-80">
                                    <div
                                        class="flex items-center justify-between px-4 py-2"
                                    >
                                        <p class="text-sm font-semibold">
                                            {{ t('notifications.title') }}
                                        </p>
                                        <button
                                            v-if="notifications.unread > 0"
                                            type="button"
                                            class="text-xs text-primary hover:underline"
                                            @click="markAllNotificationsRead"
                                        >
                                            {{ t('notifications.mark_all_read') }}
                                        </button>
                                    </div>
                                    <USeparator />
                                    <div class="max-h-96 overflow-y-auto">
                                        <button
                                            v-for="note in notifications.items"
                                            :key="note.id"
                                            type="button"
                                            class="flex w-full items-start gap-3 px-4 py-3 text-start hover:bg-elevated"
                                            :class="note.read_at ? 'opacity-60' : ''"
                                            @click="markNotificationRead(note.id)"
                                        >
                                            <span
                                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full"
                                                :class="
                                                    notificationLevelClass[
                                                        note.data?.level
                                                    ] || 'bg-info/10 text-info'
                                                "
                                            >
                                                <UIcon
                                                    name="i-heroicons-bell"
                                                    class="size-4"
                                                />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span
                                                    class="block truncate text-sm font-medium"
                                                >{{ note.data?.title }}</span>
                                                <span
                                                    class="block text-xs text-muted"
                                                >{{ note.data?.message }}</span>
                                                <span
                                                    class="block text-[10px] text-dimmed"
                                                >{{ note.created_at }}</span>
                                            </span>
                                        </button>
                                        <div
                                            v-if="!notifications.items.length"
                                            class="px-4 py-8 text-center text-sm text-muted"
                                        >
                                            {{ t('notifications.none') }}
                                        </div>
                                    </div>
                                    <USeparator />
                                    <Link
                                        :href="route('notifications.index')"
                                        class="block px-4 py-2 text-center text-sm text-primary hover:bg-elevated"
                                    >
                                        {{ t('notifications.view_all') }}
                                    </Link>
                                </div>
                            </template>
                        </UPopover>

                        <!-- Profile -->
                        <UPopover>
                            <button
                                type="button"
                                class="flex items-center"
                                :aria-label="t('auth.my_profile')"
                            >
                                <UAvatar :text="userInitials" size="md" />
                            </button>
                            <template #content>
                                <div class="w-56 p-1">
                                    <div class="px-3 py-2">
                                        <p class="text-sm font-semibold">
                                            {{ user?.name }}
                                        </p>
                                        <p class="text-xs text-muted">
                                            {{ user?.email }}
                                        </p>
                                    </div>
                                    <USeparator class="my-1" />
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-error hover:bg-elevated"
                                        @click="logout"
                                    >
                                        <UIcon
                                            name="i-heroicons-arrow-right-on-rectangle"
                                            class="size-4"
                                        />
                                        {{ t('auth.sign_out') }}
                                    </button>
                                </div>
                            </template>
                        </UPopover>
                    </div>
                </header>

                <!-- Page content -->
                <main id="main-content" class="flex-1 p-4 sm:p-6">
                    <div class="mx-auto w-full max-w-[1320px] min-w-0">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </UApp>
</template>
