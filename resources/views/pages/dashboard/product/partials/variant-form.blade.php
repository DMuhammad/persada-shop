{{-- Product variant modal. Expects $product. Opened with
     $dispatch('open-product-variant'). --}}
@php
    $reopen = $errors->any() && old('_form') === 'product_variant';
@endphp

<x-atoms.modal name="product-variant" title="Tambah Varian Produk" :open="$reopen" width="max-w-2xl">
    <form action="{{ route('master.products.variants.store', $product) }}" method="POST">
        @csrf
        <input type="hidden" name="_form" value="product_variant">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="variant-name" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama
                    Varian</label>
                <input type="text" id="variant-name" name="name" value="{{ old('name') }}" placeholder="e.g., Merah, XL"
                    required
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
            </div>
            <div>
                <label for="variant-price"
                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Harga</label>
                <input type="number" id="variant-price" name="price" value="{{ old('price') }}" placeholder="Rp 150000"
                    required
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
            </div>
            <div>
                <label for="variant-stock"
                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Stok</label>
                <input type="number" id="variant-stock" name="stock" value="{{ old('stock', 0) }}" min="0" required
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Stok awal, tercatat sebagai saldo awal.
                    Perubahan berikutnya lewat mutasi stok.</p>
            </div>
            <div>
                <label for="variant-sku" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">SKU
                    (Opsional)</label>
                <input type="text" id="variant-sku" name="sku" value="{{ old('sku') }}" placeholder="SKU-MERAH-XL"
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
            </div>
            <div>
                <label for="variant-moq"
                    class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">MOQ</label>
                <input type="number" id="variant-moq" name="moq" value="{{ old('moq', 1) }}" required
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
            </div>
            <div class="sm:col-span-2">
                <label for="variant-weight" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Berat
                    (gram)</label>
                <input type="number" id="variant-weight" name="weight_in_grams" value="{{ old('weight_in_grams') }}"
                    placeholder="e.g., 250"
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Dimensi P x L x T
                    (cm)</label>
                <div class="flex gap-2">
                    <input type="number" name="length_in_cm" value="{{ old('length_in_cm') }}" placeholder="P"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                    <input type="number" name="width_in_cm" value="{{ old('width_in_cm') }}" placeholder="L"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                    <input type="number" name="height_in_cm" value="{{ old('height_in_cm') }}" placeholder="T"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 focus:border-persada-primary focus:ring-persada-primary">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
            <button type="button" x-on:click="isOpen = false"
                class="rounded bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                Batal
            </button>
            <button type="submit"
                class="inline-flex items-center rounded bg-persada-primary px-4 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                <x-heroicon-o-plus class="mr-2 h-4 w-4" />
                Tambah Varian
            </button>
        </div>
    </form>
</x-atoms.modal>
