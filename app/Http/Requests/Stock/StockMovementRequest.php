<?php

namespace App\Http\Requests\Stock;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'type' => ['required', 'integer', Rule::in($this->allowedTypes())],
            // A correction carries the counted balance, which may legitimately be zero.
            'quantity' => ['required', 'integer', $this->isAdjustment() ? 'min:0' : 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_variant_id' => 'varian produk',
            'type' => 'jenis mutasi',
            'quantity' => $this->isAdjustment() ? 'hasil hitung stok' : 'jumlah',
            'note' => 'catatan',
        ];
    }

    /**
     * Returns the submitted type as an enum instance.
     */
    public function movementType(): StockMovementType
    {
        return StockMovementType::from((int) $this->validated('type'));
    }

    /**
     * Tells whether the submitted type is a correction, before validation runs.
     */
    private function isAdjustment(): bool
    {
        return StockMovementType::tryFrom((int) $this->input('type'))?->isAdjustment() ?? false;
    }

    /**
     * Returns the type values an admin may submit, leaving the order driven
     * types out of reach.
     *
     * @return array<int, int>
     */
    private function allowedTypes(): array
    {
        return array_column(StockMovementType::manualCases(), 'value');
    }
}
