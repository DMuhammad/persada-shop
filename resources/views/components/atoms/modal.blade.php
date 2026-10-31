{{-- Modal shell opened with $dispatch('open-<name>'). Pass $open to reopen it
     after a failed submit. --}}
@props([
    'name',
    'title',
    'open' => false,
    'width' => 'max-w-lg',
])

<div x-data="{ isOpen: @js((bool) $open) }" x-on:open-{{ $name }}.window="isOpen = true"
    x-on:keydown.esc.window="isOpen = false">
    <div x-show="isOpen" x-cloak x-on:click.self="isOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div x-show="isOpen" x-transition
            class="flex max-h-[90vh] w-full {{ $width }} flex-col rounded-lg bg-white p-6 dark:bg-gray-800">
            <div class="mb-4 flex items-start justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                <button type="button" x-on:click="isOpen = false" aria-label="Tutup"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-heroicon-o-x-mark class="h-5 w-5" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto pr-1">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
