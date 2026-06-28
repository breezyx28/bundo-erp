<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Allows a model to carry tenant-defined custom fields and their values.
 */
trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    /** Field definitions applicable to this model type. */
    public function customFieldDefinitions(): Collection
    {
        return CustomField::query()
            ->where('model_type', static::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getCustomField(string $key): mixed
    {
        $definition = $this->customFieldDefinitions()->firstWhere('key', $key);

        if (! $definition) {
            return null;
        }

        return $this->customFieldValues()
            ->where('custom_field_id', $definition->id)
            ->value('value');
    }

    public function setCustomField(string $key, mixed $value): void
    {
        $definition = CustomField::query()
            ->where('model_type', static::class)
            ->where('key', $key)
            ->first();

        if (! $definition) {
            return;
        }

        $this->customFieldValues()->updateOrCreate(
            ['custom_field_id' => $definition->id],
            ['value' => is_array($value) ? json_encode($value) : $value],
        );
    }
}
