<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof \App\Models\Product ? $product->id : $product;
        return [
            'name'        => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($productId, 'id')],
            'slug'        => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation()
    {
        $mergeData = [];
        $value = trim((string) $this->input('category_id'));

        if ($value !== '' && !Str::isUuid($value)) {
            $category = \App\Models\Category::firstOrCreate(['name' => Str::limit($value, 255, '')]);
            $mergeData['category_id'] = $category->id;
        }

        if ($this->has('name')) {
            $mergeData['slug'] = Str::slug($this->name);
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }
}
