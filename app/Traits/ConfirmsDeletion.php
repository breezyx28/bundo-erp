<?php

namespace App\Traits;

trait ConfirmsDeletion
{
    public bool $confirmDelete = false;

    public mixed $deleteId = null;

    public function confirmDelete(int|string $id): void
    {
        $this->deleteId = $id;
        $this->confirmDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmDelete = false;
        $this->deleteId = null;
    }
}
