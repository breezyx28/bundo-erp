<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    notifications: { type: Object, required: true },
    filter: { type: String, default: 'all' },
    emailAlerts: { type: Boolean, default: false },
    soundAlerts: { type: Boolean, default: true },
});

const { t } = useTrans();

const levelClass = (level) => ({
    alert: 'bg-error/10 text-error',
    reminder: 'bg-warning/10 text-warning',
    info: 'bg-info/10 text-info',
    success: 'bg-success/10 text-success',
}[level] ?? 'bg-elevated text-muted');

function setFilter(value) {
    router.get(route('notifications.index'), { filter: value }, { preserveState: true, preserveScroll: true, replace: true });
}

function markRead(id) {
    router.post(route('notifications.mark-read', id), {}, { preserveScroll: true });
}

function markAllRead() {
    router.post(route('notifications.mark-all'), {}, { preserveScroll: true });
}

const prefForm = useForm({ emailAlerts: props.emailAlerts, soundAlerts: props.soundAlerts });
function savePreferences() {
    prefForm.post(route('notifications.preferences'), { preserveScroll: true });
}

function goToPage(page) {
    router.get(route('notifications.index'), { filter: props.filter, page }, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <AppLayout :title="t('notifications.title')">
        <Head :title="t('notifications.title')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-highlighted">{{ t('notifications.title') }}</h1>
                    <p class="text-sm text-muted">{{ t('notifications.subtitle') }}</p>
                </div>
                <UButton :label="t('notifications.mark_all_read')" icon="i-heroicons-check" color="neutral" variant="ghost" @click="markAllRead" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div class="flex gap-2">
                        <UButton :label="t('notifications.all')" size="sm" :color="filter === 'all' ? 'primary' : 'neutral'" :variant="filter === 'all' ? 'solid' : 'ghost'" @click="setFilter('all')" />
                        <UButton :label="t('notifications.unread')" size="sm" :color="filter === 'unread' ? 'primary' : 'neutral'" :variant="filter === 'unread' ? 'solid' : 'ghost'" @click="setFilter('unread')" />
                    </div>

                    <UCard>
                        <div class="divide-y divide-default">
                            <div
                                v-for="note in notifications.data"
                                :key="note.id"
                                class="flex items-start gap-3 py-3"
                                :class="note.read ? 'opacity-60' : ''"
                            >
                                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full" :class="levelClass(note.level)">
                                    <UIcon :name="note.icon || 'i-heroicons-bell'" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ note.title }}</span>
                                        <UBadge v-if="!note.read" color="primary" variant="subtle" size="sm" :label="t('notifications.level_' + note.level)" />
                                    </div>
                                    <p class="text-sm text-toned">{{ note.message }}</p>
                                    <p class="text-xs text-dimmed">{{ note.created_at }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <UButton v-if="note.url" :label="t('notifications.view')" color="neutral" variant="ghost" size="xs" :to="note.url" as="a" />
                                    <UButton v-if="!note.read" icon="i-heroicons-check" color="neutral" variant="ghost" size="xs" @click="markRead(note.id)" />
                                </div>
                            </div>
                            <div v-if="!notifications.data.length" class="py-10 text-center text-muted">{{ t('notifications.none') }}</div>
                        </div>

                        <div v-if="notifications.last_page > 1" class="mt-4 flex justify-end border-t border-default pt-4">
                            <UPagination
                                :page="notifications.current_page"
                                :items-per-page="notifications.per_page"
                                :total="notifications.total"
                                @update:page="goToPage"
                            />
                        </div>
                    </UCard>
                </div>

                <div>
                    <UCard>
                        <template #header><span class="font-medium">{{ t('notifications.preferences') }}</span></template>
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-default bg-elevated/30 px-3 py-2.5">
                            <div>
                                <span class="text-sm font-medium">{{ t('notifications.email_alerts') }}</span>
                                <p class="text-xs text-muted">{{ t('notifications.email_alerts_hint') }}</p>
                            </div>
                            <USwitch v-model="prefForm.emailAlerts" />
                        </label>
                        <label class="mt-3 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-default bg-elevated/30 px-3 py-2.5">
                            <div>
                                <span class="text-sm font-medium">{{ t('notifications.sound_alerts') }}</span>
                                <p class="text-xs text-muted">{{ t('notifications.sound_alerts_hint') }}</p>
                            </div>
                            <USwitch v-model="prefForm.soundAlerts" />
                        </label>
                        <template #footer>
                            <div class="flex justify-end">
                                <UButton :label="t('common.save')" icon="i-heroicons-check" size="sm" :loading="prefForm.processing" @click="savePreferences" />
                            </div>
                        </template>
                    </UCard>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
