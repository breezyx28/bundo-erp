import { onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useNotificationSound } from '@/composables/useNotificationSound';

const POLL_MS = 30000;

/**
 * Visibility-aware JSON polling for the topbar notification bell.
 */
export function useNotificationPoll() {
    const page = usePage();

    const initial = page.props.notifications ?? { unread: 0, items: [] };
    const initialPrefs = page.props.notificationPrefs ?? { sound: true };

    const notifications = ref({
        unread: initial.unread ?? 0,
        items: initial.items ?? [],
    });
    const soundEnabled = ref(initialPrefs.sound !== false);

    const { onUnreadChange } = useNotificationSound(soundEnabled);

    let timer = null;
    let fetching = false;

    async function fetchSummary() {
        if (fetching || document.visibilityState !== 'visible') {
            return;
        }
        fetching = true;
        try {
            const response = await fetch(route('notifications.summary'), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            notifications.value = {
                unread: data.unread ?? 0,
                items: data.items ?? [],
            };
            if (typeof data.sound === 'boolean') {
                soundEnabled.value = data.sound;
            }
            onUnreadChange(notifications.value.unread);
        } catch {
            // Network errors are ignored; next poll will retry.
        } finally {
            fetching = false;
        }
    }

    function startTimer() {
        stopTimer();
        if (document.visibilityState === 'visible') {
            timer = window.setInterval(fetchSummary, POLL_MS);
        }
    }

    function stopTimer() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function onVisibilityChange() {
        if (document.visibilityState === 'visible') {
            fetchSummary();
            startTimer();
        } else {
            stopTimer();
        }
    }

    watch(
        () => page.props.notifications,
        (next) => {
            if (!next) {
                return;
            }
            notifications.value = {
                unread: next.unread ?? 0,
                items: next.items ?? [],
            };
            onUnreadChange(notifications.value.unread);
        },
        { deep: true },
    );

    watch(
        () => page.props.notificationPrefs?.sound,
        (sound) => {
            if (typeof sound === 'boolean') {
                soundEnabled.value = sound;
            }
        },
    );

    onMounted(() => {
        onUnreadChange(notifications.value.unread);
        startTimer();
        document.addEventListener('visibilitychange', onVisibilityChange);
    });

    onUnmounted(() => {
        stopTimer();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    });

    return {
        notifications,
        soundEnabled,
        refresh: fetchSummary,
    };
}
