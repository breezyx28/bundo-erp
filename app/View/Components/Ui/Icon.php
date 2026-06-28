<?php

namespace App\View\Components\Ui;

use App\Support\TablerIcons;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Icon extends Component
{
    public string $uuid;

    public function __construct(
        public string $name,
        public ?string $id = null,
        public ?string $label = null
    ) {
        $this->uuid = 'ui'.md5(serialize($this)).$id;
    }

    public function iconClass(): string
    {
        return TablerIcons::resolve($this->name);
    }

    public function labelClasses(): ?string
    {
        return Str::replaceMatches('/(w-\w*)|(h-\w*)/', '', $this->attributes->get('class') ?? '');
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                @if(strlen($label ?? '') > 0)
                    <div class="inline-flex items-center gap-1">
                @endif
                    <span
                        {{ $attributes->class([$iconClass(), 'inline-block shrink-0', 'w-5 h-5' => !Str::contains($attributes->get('class') ?? '', ['w-', 'h-']) ]) }}
                        aria-hidden="true"
                    ></span>

                @if(strlen($label ?? '') > 0)
                        <div class="{{ $labelClasses() }}" {{ $attributes->whereStartsWith('@') }}>
                            {{ $label }}
                        </div>
                    </div>
                @endif
            BLADE;
    }
}
