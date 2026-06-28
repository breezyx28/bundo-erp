<?php

namespace App\Traits;

trait ConfirmsVoid
{
    public bool $confirmVoid = false;

    public ?int $voidId = null;

    public function confirmVoid(int $id): void
    {
        $this->voidId = $id;
        $this->confirmVoid = true;
    }

    public function cancelVoid(): void
    {
        $this->confirmVoid = false;
        $this->voidId = null;
    }
}
