import { ref } from 'vue';

const SOUND_URL = '/sounds/notification.wav';
const unlocked = ref(false);
let audio = null;

function getAudio() {
    if (typeof window === 'undefined') {
        return null;
    }
    if (!audio) {
        audio = new Audio(SOUND_URL);
        audio.preload = 'auto';
    }
    return audio;
}

function unlockAudio() {
    if (unlocked.value) {
        return;
    }
    const el = getAudio();
    if (!el) {
        return;
    }
    el.volume = 0;
    el.play()
        .then(() => {
            el.pause();
            el.currentTime = 0;
            el.volume = 1;
            unlocked.value = true;
        })
        .catch(() => {});
}

if (typeof document !== 'undefined') {
    document.addEventListener('click', unlockAudio, { once: true, capture: true });
    document.addEventListener('keydown', unlockAudio, { once: true, capture: true });
}

/**
 * Play a short ringtone when unread notifications increase.
 *
 * @param {import('vue').Ref<boolean>} soundEnabled
 */
export function useNotificationSound(soundEnabled) {
    const lastUnread = ref(null);
    let initialised = false;

    function onUnreadChange(unread) {
        if (!initialised) {
            lastUnread.value = unread;
            initialised = true;
            return;
        }

        if (soundEnabled.value && unread > lastUnread.value) {
            play();
        }

        lastUnread.value = unread;
    }

    function play() {
        if (!soundEnabled.value) {
            return;
        }
        const el = getAudio();
        if (!el) {
            return;
        }
        el.currentTime = 0;
        el.play().catch(() => {});
    }

    return { onUnreadChange, play };
}
