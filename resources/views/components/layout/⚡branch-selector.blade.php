<?php

use App\Services\Branch\BranchContext;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public function branches(): Collection
    {
        return app(BranchContext::class)->allowedBranches();
    }

    public function canViewAll(): bool
    {
        return app(BranchContext::class)->canViewAllBranches();
    }

    public function currentLabel(): string
    {
        $context = app(BranchContext::class);

        if ($context->isConsolidated()) {
            return __('nav.all_branches');
        }

        return $context->currentBranch()?->name ?? __('nav.all_branches');
    }

    public function select(string $branchId): void
    {
        app(BranchContext::class)->setBranch($branchId === 'all' ? 'all' : (int) $branchId);

        // Reload so every branch-scoped query reflects the new context.
        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: true);
    }
}; ?>

<div>
    <x-ui.dropdown right>
        <x-slot:trigger>
            <button type="button" class="dropdown-toggle btn btn-soft btn-sm h-9 max-w-[10rem] gap-1.5 px-2.5 sm:max-w-none sm:px-3">
                <x-ui.icon name="o-building-storefront" class="size-4 shrink-0 opacity-60 sm:size-5" />
                <span class="truncate font-medium">{{ $this->currentLabel() }}</span>
                <x-ui.icon name="o-chevron-down" class="size-4 opacity-50" />
            </button>
        </x-slot:trigger>

        @if ($this->canViewAll())
            <x-ui.menu-item :title="__('nav.all_branches')" icon="o-squares-2x2" wire:click="select('all')" />
            <x-ui.menu-separator />
        @endif

        @foreach ($this->branches() as $branch)
            <x-ui.menu-item :title="$branch->name" icon="o-building-storefront" wire:click="select('{{ $branch->id }}')" />
        @endforeach
    </x-ui.dropdown>
</div>
