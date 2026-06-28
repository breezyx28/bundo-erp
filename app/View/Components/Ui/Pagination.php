<?php

namespace App\View\Components\Ui;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    public string $uuid;

    public function __construct(
        public ArrayAccess|array $rows,
        public ?string $id = null,
        public ?array $perPageValues = [10, 20, 50, 100],
    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
    }

    public function modelName(): ?string
    {
        return $this->attributes->whereStartsWith('wire:model.live')->first();
    }

    public function isShowable(): bool
    {
        return ! empty($this->modelName()) && $this->rows instanceof LengthAwarePaginator && $this->rows->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
            <div {{ $attributes->class([]) }}>
                @if($isShowable())
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <div class="select select-sm w-auto">
                            <select id="{{ $uuid }}" wire:model.live="{{ $modelName() }}" class="grow">
                                @foreach ($perPageValues as $option)
                                    <option value="{{ $option }}" @selected($rows->perPage() === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                @if($rows instanceof LengthAwarePaginator)
                    {{ $rows->onEachSide(1)->links(data: ['scrollTo' => false]) }}
                @else
                    {{ $rows->links(data: ['scrollTo' => false]) }}
                @endif
            </div>
            HTML;
    }
}
