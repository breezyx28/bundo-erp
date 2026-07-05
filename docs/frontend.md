# Frontend Guide

Vue 3 + Inertia patterns used in this codebase.

---

## Entry point

`resources/js/inertia.js`

- Creates Vue app with `@inertiajs/vue3`
- Registers **Nuxt UI** (`UApp`, components)
- Registers **Ziggy** (`route()` in script)
- Registers **ApexCharts**
- Lazy-loads pages: `import.meta.glob('./pages/**/*.vue')`

---

## Page convention

One primary page per module:

```
resources/js/pages/{Module}/Index.vue
```

Props come from Laravel controllers via `Inertia::render()`.

---

## Layouts

| Layout | Use |
|--------|-----|
| `AppLayout.vue` | Authenticated ERP (sidebar or tablet) |
| `GuestLayout.vue` | Login |
| `ShopLayout.vue` | Public catalog |

Wrap pages:
```vue
<AppLayout :title="t('nav.sales')">
  …
</AppLayout>
```

---

## Translations

```js
import { useTrans } from '@/composables/useTrans';
const { t } = useTrans();
t('sales.new_sale');
t('debts.oldest', { days: 5 });
```

All PHP files in `lang/{locale}/` are shared as `translations` prop.

---

## Direction & RTL

```js
import { useDirection } from '@/composables/useDirection';
const { dir, locale, nuxtLocale } = useDirection();
```

`UApp :locale="nuxtLocale"` drives Nuxt UI RTL.

---

## Money & exchange rate

```js
import { usePage } from '@inertiajs/vue3';
const page = usePage();
const rate = page.props.money?.exchangeRate;
```

Or `useMoney()` composable for formatting.

---

## Standard CRUD modal pattern

```js
import { useResourceForm } from '@/composables/useResourceForm';

const form = useForm({ name: '', … });

const { modalOpen, openCreate, openEdit, submit, destroy } = useResourceForm(form, {
  resource: 'products',
  draftKey: 'products',
  draftLabel: t('nav.products'),
});
```

Handles modal state, submit to `{resource}.store/update`, optional **form drafts**.

---

## Form drafts (localStorage)

| Composable / component | Role |
|------------------------|------|
| `useFormDraftRegistry` | Read/write draft list per user+tenant |
| `useFormDraft` | Debounced save, restore, clear on success |
| `useFormDraftModal` | Shared modal open state |
| `FormDraftReminder` | Trigger (inline or floating FAB) |
| `FormDraftReminderModal` | Single modal instance in AppLayout |
| `useDraftQueryRestore` | Restore from `?draft=key` URL |

Storage key: `ms:drafts:{userId}:{tenantId}`

---

## Tables

- `DataTable` + `TableToolbar` + `useTableFilters` + `useTableColumns`
- Sort/filter state synced to URL query string
- `TablePrintModal` for print-friendly export

---

## Notifications polling

`useNotificationPoll()` in AppLayout:

- Polls `GET /notifications/summary` every 30s (visible tab only)
- Updates bell dropdown reactively
- Feeds `useNotificationSound()` on unread increase

---

## Complex forms (Sales, Purchases, …)

Manual `useForm` + `useFormDraft` when line items or multi-step logic exceeds `useResourceForm`.

---

## Styling

- Tailwind utility classes
- Nuxt UI semantic colors: `text-highlighted`, `bg-elevated`, `text-dimmed`
- Brand CSS variables from shared `branding` prop: `--ui-primary`

See also [STYLES_WORKFLOW.md](./STYLES_WORKFLOW.md).

---

## Build

```bash
npm run dev    # development
npm run build  # production assets → public/build
```

Vite config: `vite.config.js`
