/**
 * Select — pengganti Select2 untuk Tailwind (vanilla JS, tanpa dependency).
 *
 * Cara kerjanya sama seperti Select2: baca <select> native yang sudah ada
 * (termasuk <option> hasil @foreach dari Blade), sembunyikan elemen aslinya,
 * lalu render UI custom di sebelahnya. <select> native tetap jadi pemegang
 * nilai form, jadi name, required, dan submit biasa tetap jalan apa adanya.
 *
 *   new Select('#category')
 *       .placeholder('-- Pilih Kategori --')
 *       .tags()
 *       .onChange((e, id, item) => console.log(id, item))
 *       .build();
 *
 *   new Select('#product', { multiple: true })
 *       .viaAjax('/api/products', { minLength: 2, params: () => ({ status: 'active' }) })
 *       .build();
 *
 * Nilai diubah dari luar? Pakai idiom yang sama dengan Select2:
 *   el.value = id; el.dispatchEvent(new Event('change'));
 * atau langsung instance-nya: instance.setSelected(id, text).
 */

const same = (a, b) => String(a) === String(b);

class Select {
    /**
     * @param {string|HTMLSelectElement} selector
     * @param {object} config - { multiple, tags, placeholder, allowClear, minLength, limit, width }
     */
    constructor(selector, config = {}) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) throw new Error(`Select: element not found (${selector})`);

        this.el = el;
        this.options = {
            width: '100%',
            multiple: el.multiple,
            tags: false,
            allowClear: true,
            placeholder: 'Pilih opsi...',
            minLength: 0,
            limit: 15,
            ...config,
        };

        // Placeholder eksplisit (config/chaining) menang atas teks <option value="">
        this.placeholderLocked = 'placeholder' in config;
        this.ajaxConfig = null;
        this.onChangeCallback = null;
        this.initialData = null;
        this.staticOptions = [];
        this.visible = [];
        this.syncing = false;
        this.abortController = null;

        this.state = {
            options: [],
            selected: [],
            activeIndex: -1,
            isOpen: false,
            isDisabled: el.disabled,
        };
    }

    // ==========================================
    // CHAINING API
    // ==========================================

    /**
     * @param {string} url
     * @param {object} config - { minLength, limit, params, data, processResults, delay }
     *                          params boleh object atau function (dievaluasi tiap request)
     */
    viaAjax(url, config = {}) {
        this.ajaxConfig = {
            url,
            params: config.params ?? {},
            limit: config.limit ?? this.options.limit,
            data: config.data ?? null,
            processResults: config.processResults ?? null,
            delay: config.delay ?? 400,
        };
        if (config.minLength !== undefined) this.options.minLength = config.minLength;
        return this;
    }

    /** Izinkan user mengetik teks bebas jadi pilihan baru. Tidak memaksa multiple. */
    tags(enabled = true) {
        this.options.tags = enabled;
        return this;
    }

    multiple(enabled = true) {
        this.options.multiple = enabled;
        this.el.multiple = enabled;
        return this;
    }

    placeholder(text) {
        this.options.placeholder = text;
        this.placeholderLocked = true;
        if (this.ui) this.renderControl();
        return this;
    }

    allowClear(enabled = true) {
        this.options.allowClear = enabled;
        if (this.ui) this.renderControl();
        return this;
    }

    minLength(n) {
        this.options.minLength = n;
        return this;
    }

    onChange(callback) {
        this.onChangeCallback = callback;
        return this;
    }

    /** setSelected(id, text) atau setSelected([{ id, text }, ...]) */
    setSelected(id, text) {
        this.initialData = Array.isArray(id) ? id : [{ id, text: text ?? id }];
        if (this.ui) this.applyInitialData();
        return this;
    }

    build() {
        this.el._select?.destroy();
        this.el._select = this;

        // Selalu baca HTML, termasuk saat mode ajax — ini yang bikin <option selected>
        // kiriman server (form edit) tetap muncul sebagai pilihan awal.
        this.extractFromHTML();

        // Teknik yang sama dengan .select2-hidden-accessible: elemen asli tetap
        // ter-render (constraint validation `required` butuh itu), cuma tak terlihat.
        this.el.classList.add('sr-only', 'pointer-events-none');

        this.renderWrapper();
        this.bindEvents();
        this.setDisabled(this.state.isDisabled);

        if (this.initialData) this.applyInitialData();
        this.renderControl();
        return this;
    }

    // ==========================================
    // RUNTIME API
    // ==========================================

    clear() {
        this.state.selected = [];
        this.renderControl();
        this.syncNative();
        this.emit(null);
        return this;
    }

    setDisabled(isDisabled = true) {
        this.state.isDisabled = isDisabled;
        this.el.disabled = isDisabled;
        this.wrapper?.classList.toggle('opacity-60', isDisabled);
        this.wrapper?.classList.toggle('pointer-events-none', isDisabled);
        return this;
    }

    /** string untuk single, array untuk multiple */
    getValue() {
        return this.options.multiple
            ? this.state.selected.map((s) => s.id)
            : this.state.selected[0]?.id ?? '';
    }

    getSelected() {
        return this.state.selected.slice();
    }

    /** Baca ulang <select> native ke UI, kalau value diubah dari luar tanpa event. */
    refresh() {
        this.syncFromNative();
        return this;
    }

    destroy() {
        document.removeEventListener('click', this.onDocClick);
        this.el.removeEventListener('change', this.onNativeChange);
        this.abortController?.abort();
        this.wrapper?.remove();
        this.el.classList.remove('sr-only', 'pointer-events-none');
        delete this.el._select;
    }

    // ==========================================
    // SINKRONISASI DENGAN <select> NATIVE
    // ==========================================

    extractFromHTML() {
        this.staticOptions = [];
        this.state.selected = [];

        Array.from(this.el.options).forEach((o) => {
            if (o.value === '') {
                // <option value="">-- Pilih --</option> dipakai jadi placeholder
                if (!this.placeholderLocked && o.text.trim()) this.options.placeholder = o.text.trim();
                return;
            }
            const item = { id: o.value, text: o.text };
            this.staticOptions.push(item);
            if (o.selected) this.state.selected.push(item);
        });

        this.state.options = this.staticOptions.slice();
    }

    /** state.selected -> <option> native. Satu-satunya tempat yang menulis ke DOM select. */
    syncNative() {
        const pool = this.staticOptions.slice();
        this.state.selected.forEach((s) => {
            if (!pool.some((o) => same(o.id, s.id))) pool.push(s);
        });

        const frag = document.createDocumentFragment();
        if (!this.options.multiple) frag.appendChild(new Option(this.options.placeholder, ''));
        pool.forEach((o) => {
            const sel = this.isSelected(o.id);
            frag.appendChild(new Option(o.text, o.id, sel, sel));
        });

        this.syncing = true;
        this.el.replaceChildren(frag);
        this.syncing = false;

        this.el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /** <select> native -> state.selected. Dipakai saat value diubah dari luar. */
    syncFromNative() {
        this.state.selected = Array.from(this.el.selectedOptions)
            .filter((o) => o.value !== '')
            .map((o) => ({ id: o.value, text: o.text }));
        this.renderControl();
    }

    applyInitialData() {
        this.state.selected = (this.initialData || []).filter((i) => i?.id != null && i.id !== '');
        this.renderControl();
        this.syncNative();
    }

    isSelected(id) {
        return this.state.selected.some((s) => same(s.id, id));
    }

    emit(item) {
        if (!this.onChangeCallback) return;
        this.onChangeCallback({ params: { data: item } }, item?.id ?? null, item);
    }

    // ==========================================
    // RENDER
    // ==========================================

    renderWrapper() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'relative text-sm mt-1 select-wrapper';
        this.wrapper.style.width = this.options.width;

        // ponytail: dropdown absolute — bisa ke-clip di parent overflow-hidden.
        // Pindah ke popover/anchor positioning kalau kejadian.
        this.wrapper.innerHTML = `
            <div class="select-trigger flex items-center justify-between gap-2 p-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg cursor-text min-h-[42px] focus-within:ring-2 focus-within:ring-indigo-500 shadow-sm transition-all">
                <div class="flex flex-wrap items-center gap-1 flex-1">
                    <div class="select-tags flex flex-wrap items-center gap-1"></div>
                    <input type="text" class="select-input flex-1 min-w-[60px] border-none bg-transparent focus:ring-0 p-0 text-sm dark:text-white" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list">
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="select-clear hidden text-gray-400 hover:text-red-500 font-bold leading-none" aria-label="Hapus pilihan">&times;</button>
                    <svg class="select-arrow w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
            <div class="select-dropdown hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                <div class="select-msg hidden p-3 text-sm text-gray-500 text-center"></div>
                <div class="select-options" role="listbox"></div>
            </div>
        `;

        this.el.after(this.wrapper);

        this.ui = {
            trigger: this.wrapper.querySelector('.select-trigger'),
            input: this.wrapper.querySelector('.select-input'),
            tagList: this.wrapper.querySelector('.select-tags'),
            dropdown: this.wrapper.querySelector('.select-dropdown'),
            options: this.wrapper.querySelector('.select-options'),
            msg: this.wrapper.querySelector('.select-msg'),
            clearBtn: this.wrapper.querySelector('.select-clear'),
            arrow: this.wrapper.querySelector('.select-arrow'),
        };
    }

    /** Render trigger: badge (multiple) atau label terpilih (single). */
    renderControl() {
        const picked = this.state.selected;
        this.ui.clearBtn.classList.toggle('hidden', !this.options.allowClear || picked.length === 0);

        if (!this.options.multiple) {
            this.ui.tagList.replaceChildren();
            this.ui.input.placeholder = picked[0]?.text || this.options.placeholder;
            // placeholder dipakai sebagai label nilai -> warnanya ikut teks normal
            this.ui.input.classList.toggle('placeholder:text-gray-900', picked.length > 0);
            this.ui.input.classList.toggle('dark:placeholder:text-white', picked.length > 0);
            return;
        }

        this.ui.input.placeholder = picked.length ? '' : this.options.placeholder;
        this.ui.tagList.replaceChildren(...picked.map((item) => this.renderBadge(item)));
    }

    renderBadge(item) {
        const badge = document.createElement('span');
        badge.className =
            'flex items-center gap-1 pl-2 pr-1 py-1 text-xs font-medium text-indigo-700 bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-md';

        // textContent, bukan innerHTML: di mode tags isinya teks bebas dari user.
        const label = document.createElement('span');
        label.textContent = item.text;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'px-0.5 hover:text-red-500 leading-none';
        remove.textContent = '×';
        remove.setAttribute('aria-label', `Hapus ${item.text}`);
        remove.addEventListener('click', (e) => {
            e.stopPropagation();
            this.deselect(item.id);
        });

        badge.append(label, remove);
        return badge;
    }

    renderDropdown(keyword = '') {
        const q = keyword.trim();
        const list = this.ajaxConfig
            ? this.state.options
            : this.state.options.filter((o) => o.text.toLowerCase().includes(q.toLowerCase()));

        // Mode tags: tawarkan teks yang sedang diketik sebagai pilihan baru.
        const canCreate = this.options.tags && q && !list.some((o) => o.text.toLowerCase() === q.toLowerCase());
        this.visible = canCreate ? [{ id: q, text: q, isNew: true }, ...list] : list;

        this.ui.options.replaceChildren(...this.visible.map((opt, i) => this.renderOption(opt, i)));
        this.ui.msg.classList.toggle('hidden', this.visible.length > 0);
        if (!this.visible.length) {
            this.ui.msg.textContent = this.options.tags ? 'Ketik untuk menambah pilihan baru' : 'Data tidak ditemukan';
        }

        if (this.state.activeIndex >= this.visible.length) this.state.activeIndex = -1;
        this.paintActive();
    }

    renderOption(opt, i) {
        const row = document.createElement('div');
        row.className =
            'px-4 py-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0';
        row.setAttribute('role', 'option');
        row.textContent = opt.isNew ? `Tambah "${opt.text}"` : opt.text;

        if (this.isSelected(opt.id)) {
            row.classList.add('text-indigo-600', 'dark:text-indigo-400', 'font-medium');
            row.setAttribute('aria-selected', 'true');
        }

        // mousedown + preventDefault: fokus tetap di input, dropdown tak keburu tertutup.
        row.addEventListener('mousedown', (e) => {
            e.preventDefault();
            this.selectItem(opt);
        });
        row.addEventListener('mouseenter', () => this.setActive(i, false));
        return row;
    }

    paintActive() {
        Array.from(this.ui.options.children).forEach((row, i) => {
            const on = i === this.state.activeIndex;
            row.classList.toggle('bg-indigo-50', on);
            row.classList.toggle('dark:bg-gray-700', on);
        });
    }

    setActive(i, scroll = true) {
        const total = this.ui.options.children.length;
        if (!total) return;
        this.state.activeIndex = ((i % total) + total) % total;
        this.paintActive();
        if (scroll) this.ui.options.children[this.state.activeIndex].scrollIntoView({ block: 'nearest' });
    }

    showMessage(text) {
        this.ui.msg.textContent = text;
        this.ui.msg.classList.remove('hidden');
        this.ui.options.replaceChildren();
        this.visible = [];
    }

    // ==========================================
    // INTERAKSI
    // ==========================================

    bindEvents() {
        this.onDocClick = (e) => {
            if (this.wrapper && !this.wrapper.contains(e.target)) this.close();
        };
        // Idiom Select2: el.value = x; el.dispatchEvent(new Event('change'))
        this.onNativeChange = () => {
            if (!this.syncing) this.syncFromNative();
        };

        document.addEventListener('click', this.onDocClick);
        this.el.addEventListener('change', this.onNativeChange);

        this.ui.trigger.addEventListener('click', () => {
            if (this.state.isDisabled) return;
            this.ui.input.focus();
            this.state.isOpen ? this.close() : this.open();
        });

        this.ui.clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.clear();
        });

        let typingTimer;
        this.ui.input.addEventListener('input', (e) => {
            if (!this.state.isOpen) this.open();
            const keyword = e.target.value.trim();
            clearTimeout(typingTimer);

            if (!this.ajaxConfig) return this.renderDropdown(keyword);

            if (keyword.length < this.options.minLength) {
                return this.showMessage(`Ketik minimal ${this.options.minLength} karakter...`);
            }
            typingTimer = setTimeout(() => this.fetchData(keyword), this.ajaxConfig.delay);
        });

        this.ui.input.addEventListener('keydown', (e) => this.onKeydown(e));
    }

    onKeydown(e) {
        const value = this.ui.input.value.trim();

        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowUp':
                e.preventDefault();
                if (!this.state.isOpen) return this.open();
                return this.setActive(this.state.activeIndex + (e.key === 'ArrowDown' ? 1 : -1));

            case 'Enter': {
                // Jangan biarkan Enter men-submit form saat dropdown sedang dipakai.
                e.preventDefault();
                const active = this.visible[this.state.activeIndex];
                if (active) return this.selectItem(active);
                if (this.options.tags && value) return this.selectItem({ id: value, text: value, isNew: true });
                return;
            }

            case 'Escape':
                return this.close();

            case ',':
                if (!this.options.tags || !value) return;
                e.preventDefault();
                return this.selectItem({ id: value, text: value, isNew: true });

            case 'Backspace':
                if (value || !this.state.selected.length) return;
                return this.deselect(this.state.selected.at(-1).id);
        }
    }

    open() {
        this.state.isOpen = true;
        this.ui.dropdown.classList.remove('hidden');
        this.ui.arrow.classList.add('rotate-180');
        this.ui.input.setAttribute('aria-expanded', 'true');

        if (!this.ajaxConfig || this.state.options.length) return this.renderDropdown(this.ui.input.value);

        this.options.minLength === 0
            ? this.fetchData('')
            : this.showMessage(`Ketik minimal ${this.options.minLength} karakter...`);
    }

    close() {
        this.state.isOpen = false;
        this.state.activeIndex = -1;
        this.ui.dropdown.classList.add('hidden');
        this.ui.arrow.classList.remove('rotate-180');
        this.ui.input.setAttribute('aria-expanded', 'false');
        this.ui.input.value = '';
        this.renderControl();
    }

    selectItem(item) {
        const picked = { id: item.id, text: item.text };

        if (!this.options.multiple) {
            this.state.selected = [picked];
        } else if (!this.isSelected(picked.id)) {
            this.state.selected.push(picked);
        }

        // Tag baru ikut masuk daftar supaya tetap muncul di dropdown & <option>.
        if (item.isNew && !this.staticOptions.some((o) => same(o.id, picked.id))) {
            this.staticOptions.push(picked);
            this.state.options.push(picked);
        }

        this.ui.input.value = '';
        this.state.activeIndex = -1;
        this.renderControl();
        this.syncNative();
        this.emit(picked);

        this.options.multiple ? this.renderDropdown('') : this.close();
    }

    deselect(id) {
        const removed = this.state.selected.find((s) => same(s.id, id));
        this.state.selected = this.state.selected.filter((s) => !same(s.id, id));
        this.renderControl();
        this.syncNative();
        if (this.state.isOpen) this.renderDropdown(this.ui.input.value);
        this.emit(removed ?? null);
    }

    async fetchData(keyword) {
        this.showMessage('Memuat data...');
        this.abortController?.abort();
        this.abortController = new AbortController();

        try {
            const termParams = { term: keyword, page: 1 };
            let queryData;

            if (this.ajaxConfig.data) {
                queryData = this.ajaxConfig.data(termParams);
            } else {
                const extra =
                    typeof this.ajaxConfig.params === 'function' ? this.ajaxConfig.params() : this.ajaxConfig.params;

                queryData = {
                    search: termParams.term || '',
                    page: termParams.page,
                    limit: this.ajaxConfig.limit,
                    ...extra,
                };
            }

            const url = new URL(this.ajaxConfig.url, window.location.origin);
            url.search = new URLSearchParams(queryData).toString();

            const response = await fetch(url, {
                signal: this.abortController.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const json = await response.json();
            const processed = this.ajaxConfig.processResults
                ? this.ajaxConfig.processResults(json, termParams)
                : { results: json.data || [] };

            this.state.options = (processed.results || []).map((item) => ({
                id: item.id ?? item.value,
                text: item.text ?? item.label,
            }));

            this.ui.msg.classList.add('hidden');
            this.renderDropdown(keyword);
        } catch (error) {
            if (error.name === 'AbortError') return;
            this.state.options = [];
            this.showMessage('Gagal memuat data');
        }
    }
}

export default Select;
