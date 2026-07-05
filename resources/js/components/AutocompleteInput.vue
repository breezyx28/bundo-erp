<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    // Whitelisted suggestion field name (see SuggestionController).
    field: { type: String, required: true },
    placeholder: { type: String, default: '' },
});

const model = defineModel({ type: String, default: '' });

const suggestions = ref([]);
const openList = ref(false);
let timer = null;
let blurTimer = null;

async function fetchSuggestions() {
    try {
        const url = route('suggestions', { field: props.field, q: model.value ?? '' });
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        suggestions.value = Array.isArray(data)
            ? data.filter((v) => v !== model.value)
            : [];
        openList.value = suggestions.value.length > 0;
    } catch {
        suggestions.value = [];
        openList.value = false;
    }
}

function onInput() {
    clearTimeout(timer);
    timer = setTimeout(fetchSuggestions, 250);
}

function onFocus() {
    clearTimeout(blurTimer);
    fetchSuggestions();
}

function onBlur() {
    // Delay so a click on a suggestion registers before the list closes.
    blurTimer = setTimeout(() => {
        openList.value = false;
    }, 150);
}

function pick(value) {
    model.value = value;
    openList.value = false;
}

onBeforeUnmount(() => {
    clearTimeout(timer);
    clearTimeout(blurTimer);
});
</script>

<template>
    <div class="relative w-full">
        <UInput
            v-model="model"
            :placeholder="placeholder"
            class="w-full"
            autocomplete="off"
            @input="onInput"
            @focus="onFocus"
            @blur="onBlur"
        />
        <div
            v-if="openList"
            class="absolute inset-x-0 top-full z-50 mt-1 max-h-48 overflow-y-auto rounded-md border border-default bg-default shadow-lg"
        >
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                type="button"
                class="block w-full truncate px-3 py-2 text-start text-sm hover:bg-elevated"
                @mousedown.prevent="pick(suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>
    </div>
</template>
