{{-- Product image upload modal. Expects $product. Opened with
     $dispatch('open-product-image'). --}}
<x-atoms.modal name="product-image" title="Tambah Gambar Produk">
    <form action="{{ route('master.products.images.store', $product) }}" method="POST" enctype="multipart/form-data"
        class="space-y-4" x-data="{ imageName: null, imagePreviewUrl: null }">
        @csrf

        <div class="relative">
            <label for="image-upload"
                class="flex h-32 w-full cursor-pointer items-center justify-center rounded-md border border-dashed border-gray-300 px-2 text-sm text-gray-500 dark:border-gray-500 dark:text-gray-400">
                <template x-if="imagePreviewUrl">
                    <img :src="imagePreviewUrl" class="h-full max-w-full rounded object-contain">
                </template>
                <div x-show="!imagePreviewUrl" class="text-center">
                    <x-heroicon-o-photo class="mx-auto h-8 w-8 text-gray-400" />
                    <span x-text="imageName ?? 'Pilih file gambar...'"></span>
                </div>
            </label>
            <input type="file" name="image" id="image-upload" accept="image/*" required class="sr-only"
                x-on:change="imageName = $event.target.files[0]?.name ?? null;
                             imagePreviewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
            @error('image')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_primary" value="1"
                class="h-4 w-4 rounded text-persada-primary focus:ring-persada-primary/50 dark:border-gray-600 dark:bg-gray-700">
            <span class="ml-2">Jadikan gambar utama</span>
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" x-on:click="isOpen = false"
                class="rounded bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                Batal
            </button>
            <button type="submit"
                class="inline-flex items-center rounded bg-persada-primary px-4 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                <x-heroicon-o-arrow-up-tray class="mr-2 h-4 w-4" />
                Upload
            </button>
        </div>
    </form>
</x-atoms.modal>
