<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class MenuItem extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $iconClasses = null,
        public ?string $spinner = null,
        public ?string $link = null,
        public ?string $route = null,
        public mixed $routeParams = null,
        public ?bool $external = false,
        public ?bool $noWireNavigate = false,
        public ?bool $exact = false,
        public ?bool $active = false,
        public ?bool $disabled = false,
        public ?bool $hidden = false,
        public ?string $badge = null,
        public ?string $badgeClasses = null,
    ) {
        $this->uuid = 'ui'.md5(serialize($this)).$id;
    }

    public function spinnerTarget(): ?string
    {
        if ($this->spinner == 1) {
            return $this->attributes->whereStartsWith('wire:click')->first();
        }

        return $this->spinner;
    }

    public function getHref(): ?string
    {
        if ($this->route) {
            return route($this->route, $this->routeParams);
        }

        return $this->link;
    }

    public function routeMatches(): bool
    {
        $href = $this->getHref();

        if ($href == null) {
            return false;
        }

        if ($this->route) {
            return request()->routeIs($this->route);
        }

        $link = url($this->link ?? '');
        $route = url(request()->url());

        if ($link == $route) {
            return true;
        }

        return ! $this->exact && $this->link != '/' && Str::startsWith($route, $link);
    }

    public function render(): View|Closure|string
    {
        if ($this->hidden === true) {
            return '';
        }

        return <<<'BLADE'
                @aware(['activateByRoute' => false])

                <li @class(['menu-disabled' => $disabled])>
                    @if($getHref())
                        <a
                            {{
                                $attributes->class([
                                    'dropdown-item',
                                    'menu-active' => ($active || ($activateByRoute && $routeMatches())),
                                ])
                            }}
                            href="{{ $getHref() }}"

                            @if($external)
                                target="_blank"
                            @endif

                            @if(!$external && !$noWireNavigate)
                                {{ $attributes->wire('navigate')->value() ? $attributes->wire('navigate') : 'wire:navigate' }}
                            @endif

                            @if($spinner)
                                wire:target="{{ $spinnerTarget() }}"
                                wire:loading.attr="disabled"
                            @endif
                        >
                    @else
                        <button
                            type="button"
                            {{
                                $attributes->class([
                                    'dropdown-item',
                                    'menu-active' => ($active || ($activateByRoute && $routeMatches())),
                                ])
                            }}

                            @if($spinner)
                                wire:target="{{ $spinnerTarget() }}"
                                wire:loading.attr="disabled"
                            @endif
                        >
                    @endif

                        @if($spinner)
                            <span class="loading loading-spinner loading-xs"></span>
                        @endif

                        @if($icon)
                            <x-ui-icon :name="$icon" @class(['size-4', 'opacity-70', $iconClasses]) />
                        @endif

                        @if($title || $slot->isNotEmpty())
                            <span>
                                @if($title)
                                    {{ $title }}

                                    @if($badge)
                                        <span class="{{ $badgeClasses }}">{{ $badge }}</span>
                                    @endif
                                @else
                                    {{ $slot }}
                                @endif
                            </span>
                        @endif

                    @if($getHref())
                        </a>
                    @else
                        </button>
                    @endif
                </li>
            BLADE;
    }
}
