{{-- Product add and edit modal. Lives inside the productCrud() scope of the
     index page and expects $categories. --}}
<div x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div @click.away="closeModal()"
        class="w-full max-w-2xl max-h-[90vh] bg-white rounded-lg p-6 dark:bg-gray-800 flex flex-col">

        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white" x-text="modalTitle"></h3>

        <div class="overflow-y-auto pr-2 flex-1">
            <form id="productForm" :action="formAction" method="POST" class="space-y-4" @submit.prevent="submitForm">
                @csrf
                <template x-if="!isAddMode">
                    <input type="hidden" name="_method" value="PATCH">
                </template>

                <div>
                    <label class="block text-sm font-medium">Nama Produk</label>
                    <input type="text" name="name" x-model="formData.name"
                        class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700" required>
                </div>

                <div>
                    <label class="block text-sm font-medium">Kategori</label>
                    <select name="category_id" id="category" class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700"
                        required>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Deskripsi Produk</label>
                    <input id="description" type="hidden" name="description" x-model="formData.description">
                    <trix-editor input="description" x-ref="trixEditor"
                        class="prose dark:prose-invert max-w-none mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 focus:ring-persada-primary focus:border-persada-primary">
                    </trix-editor>
                </div>

                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <select name="status" x-model="formData.status"
                        class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="flex justify-end gap-2 pt-4 mt-4">
            <button type="button" @click="closeModal()"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                Batal
            </button>
            <button type="button" @click="submitForm()"
                class="px-4 py-2 bg-persada-primary text-white rounded hover:bg-persada-dark-hover"
                x-text="submitText"></button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        let categorySelect;

        document.addEventListener('DOMContentLoaded', () => {
            categorySelect = new Select('#category').tags().build();
        });

        function productCrud() {
            return {
                isOpen: false,
                isAddMode: true,
                modalTitle: '',
                submitText: '',
                formAction: '',
                formData: {
                    id: null,
                    name: '',
                    description: '',
                    status: 'active'
                },

                init() {
                    this.$refs.trixEditor.addEventListener('trix-change', () => {
                        this.formData.description = this.$refs.trixEditor.value;
                    });
                },

                openModal(mode, product = null) {
                    this.isOpen = true;

                    if (mode === 'add') {
                        this.isAddMode = true;
                        this.modalTitle = 'Tambah Produk Baru';
                        this.submitText = 'Simpan';
                        this.formAction = '{{ route('master.products.store') }}';
                        this.formData = {
                            id: null,
                            name: '',
                            description: '',
                            status: 'active'
                        };
                        categorySelect.clear();

                        this.$nextTick(() => {
                            this.$refs.trixEditor.editor.loadHTML('');
                        });

                    } else {
                        this.isAddMode = false;
                        this.modalTitle = 'Edit Produk';
                        this.submitText = 'Simpan';
                        this.formAction = '{{ route('master.products.update', ['product' => ':id']) }}'.replace(':id',
                            product
                            .id);
                        this.formData = {
                            id: product.id,
                            name: product.name,
                            description: product.description ?? '',
                            status: product.status
                        };
                        categorySelect.setSelected(product.category_id, product.category?.name ?? product.category_id);

                        this.$nextTick(() => {
                            this.$refs.trixEditor.editor.loadHTML(this.formData.description);
                        });
                    }
                },

                closeModal() {
                    this.isOpen = false;
                },

                submitForm() {
                    this.formData.description = this.$refs.trixEditor.value;
                    document.querySelector('#description').value = this.formData.description;
                    document.getElementById('productForm').submit();
                }
            }
        }
    </script>
@endpush
