<script setup>
import { computed, ref, watch } from 'vue';
import { useTrans } from '@/composables/useTrans';
import { useDirection } from '@/composables/useDirection';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    // Full header list [{ key, label, align? }].
    headers: { type: Array, required: true },
    // Rows to print (already-formatted display values).
    rows: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open']);

const { t } = useTrans();
const { dir } = useDirection();

// Which columns to include in the print output; default to all.
const selected = ref(props.headers.map((h) => h.key));

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selected.value = props.headers.map((h) => h.key);
        }
    },
);

const chosenHeaders = computed(() =>
    props.headers.filter((h) => selected.value.includes(h.key)),
);

function toggle(key) {
    if (selected.value.includes(key)) {
        selected.value = selected.value.filter((k) => k !== key);
    } else {
        selected.value = [...selected.value, key];
    }
}

function cellValue(row, header) {
    const value = row[header.key];
    return value === null || value === undefined ? '' : String(value);
}

function printNow() {
    const docDir = dir.value;
    const headCells = chosenHeaders.value
        .map((h) => `<th style="text-align:${h.align === 'end' ? 'end' : 'start'}">${escapeHtml(h.label)}</th>`)
        .join('');

    const bodyRows = props.rows
        .map((row) => {
            const cells = chosenHeaders.value
                .map((h) => `<td style="text-align:${h.align === 'end' ? 'end' : 'start'}">${escapeHtml(cellValue(row, h))}</td>`)
                .join('');
            return `<tr>${cells}</tr>`;
        })
        .join('');

    const html = `<!doctype html><html dir="${docDir}"><head><meta charset="utf-8"><title>${escapeHtml(props.title)}</title>
        <style>
            body { font-family: system-ui, "Segoe UI", Tahoma, sans-serif; padding: 24px; color: #111; }
            h1 { font-size: 18px; margin: 0 0 16px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #ccc; padding: 6px 8px; }
            thead th { background: #f3f4f6; }
            @media print { body { padding: 0; } }
        </style></head>
        <body><h1>${escapeHtml(props.title)}</h1>
        <table><thead><tr>${headCells}</tr></thead><tbody>${bodyRows}</tbody></table>
        <script>window.onload=function(){window.print();}<\/script>
        </body></html>`;

    const win = window.open('', '_blank');
    if (win) {
        win.document.write(html);
        win.document.close();
    }
    emit('update:open', false);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

<template>
    <UModal :open="open" :title="t('common.print')" @update:open="emit('update:open', $event)">
        <template #body>
            <p class="mb-3 text-sm text-muted">{{ t('common.print_columns_hint') }}</p>
            <div class="grid gap-2 sm:grid-cols-2">
                <UCheckbox
                    v-for="header in headers"
                    :key="header.key"
                    :model-value="selected.includes(header.key)"
                    :label="header.label"
                    @update:model-value="toggle(header.key)"
                />
            </div>
        </template>
        <template #footer>
            <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="emit('update:open', false)" />
            <UButton icon="i-heroicons-printer" :label="t('common.print')" :disabled="!chosenHeaders.length" @click="printNow" />
        </template>
    </UModal>
</template>
