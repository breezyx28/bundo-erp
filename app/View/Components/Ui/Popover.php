<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Popover extends Component
{
    public string $uuid;

    public string $panelClass;

    public function __construct(
        public ?string $id = null,
        public int $offset = 5,
        public string $position = 'top',
    ) {
        $this->uuid = 'ui'.md5(serialize($this)).$id;
        $this->panelClass = match (true) {
            str_starts_with($position, 'bottom') => 'top-full mt-2 start-0',
            str_ends_with($position, 'end') => 'bottom-full mb-2 end-0',
            str_ends_with($position, 'start') => 'bottom-full mb-2 start-0',
            default => 'bottom-full mb-2 start-1/2 -translate-x-1/2',
        };
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
            <div
                wire:key="{{ $uuid }}"
                x-data="{ open: false }"
                class="relative inline-flex"
                @click.outside="open = false"
            >
                <div @click="open = !open" {{ $trigger->attributes->class(['inline-flex cursor-pointer']) }}>
                    {{ $trigger }}
                </div>

                <div
                    x-cloak
                    x-show="open"
                    x-transition
                    {{ $content->attributes->class([
                        'absolute z-50 w-fit max-w-xs rounded-md border border-base-300 bg-base-100 p-3 text-sm shadow-md',
                        $panelClass,
                    ]) }}
                >
                    {{ $content }}
                </div>
            </div>
            HTML;
    }
}
