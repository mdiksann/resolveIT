<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Support\Facades\DB;

trait NormalizesConfigurationName
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => mb_strtolower(trim($this->input('name')))]);
        }
    }

    protected function nameRules(string $table, ?int $ignoreId = null): array
    {
        return ['bail', 'required', 'string', 'min:2', 'max:60', function (string $attribute, string $value, Closure $fail) use ($table, $ignoreId): void {
            // Also catches pre-existing mixed-case names; new writes use a canonical lowercase name.
            if (DB::table($table)->whereRaw('LOWER(name) = ?', [$value])->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
                $fail('This name is already in use.');
            }
        }];
    }
}
