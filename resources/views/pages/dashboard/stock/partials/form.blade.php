{{-- Stock movement modal. Expects $products (with variants) and $manualTypes.
     Opened with $dispatch('open-stock-movement', { productId, variantId }). --}}
@php
    $reopen = $errors->any() && old('_form') === 'stock_movement';
    $productOptions = $products
        ->map(
            fn($product) => [
                'id' => $product->id,
                'variants' => $product->variants
                    ->map(fn($variant) => ['id' => $variant->id, 'label' => $variant->name . ' (stok ' . $variant->stock . ')'])
                    ->values(),
            ],
        )
        ->values();
@endphp

<x-atoms.modal name="stock-movement" title="Catat Mutasi Stok" :open="$reopen">
    <div x-data="{
        products: @js($productOptions),
        adjustmentType: {{ App\Enums\StockMovementType::ADJUSTMENT->value }},
        productId: @js(old('product_id', '')),
        variantId: @js(old('product_variant_id', '')),
        type: @js((int) old('type', App\Enums\StockMovementType::PURCHASE->value)),
    
        get variants() {
            return this.products.find((product) => product.id === this.productId)?.variants ?? [];
        },
    
        get isAdjustment() {
            return this.type === this.adjustmentType;
        },
    
        preset(detail) {
            this.productId = detail?.productId ?? '';
            this.variantId = detail?.variantId ?? '';
        },
    }" x-on:open-stock-movement.window="preset($event.detail)">

        <form action="{{ route('master.stock.movements.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="stock_movement">

            <div>
                <label for="movement-product"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Produk</label>
                <select id="movement-product" name="product_id" x-model="productId" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                    <option value="">-- Pilih Produk --</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement-variant" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Varian
                </label>
                <select id="movement-variant" name="product_variant_id" required :disabled="!productId"
                    x-on:change="variantId = $event.target.value"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:disabled:bg-gray-900 focus:border-persada-primary focus:ring-persada-primary">
                    <option value="">-- Pilih Varian --</option>
                    <template x-for="variant in variants" :key="variant.id">
                        <option :value="variant.id" :selected="variant.id === variantId" x-text="variant.label"></option>
                    </template>
                </select>
                @error('product_variant_id')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="movement-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis
                    Mutasi</label>
                <select id="movement-type" name="type" x-model.number="type" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                    @foreach ($manualTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="movement-quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    x-text="isAdjustment ? 'Hasil Hitung Fisik' : 'Jumlah Masuk'"></label>
                <input id="movement-quantity" type="number" name="quantity" value="{{ old('quantity') }}" required
                    :min="isAdjustment ? 0 : 1"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    x-text="isAdjustment
                        ? 'Masukkan saldo hasil hitung fisik. Selisihnya dengan saldo tercatat dihitung sistem.'
                        : 'Jumlah yang ditambahkan ke saldo stok.'">
                </p>
                @error('quantity')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="movement-note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan
                    (opsional)</label>
                <input id="movement-note" type="text" name="note" maxlength="255" value="{{ old('note') }}"
                    placeholder="Nomor nota, nama supplier, alasan koreksi..."
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                @error('note')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" x-on:click="isOpen = false"
                    class="rounded bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                    Batal
                </button>
                <button type="submit"
                    class="rounded bg-persada-primary px-4 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</x-atoms.modal>
