document.addEventListener('DOMContentLoaded', () => {
    const instances = [];

    function closeAll(except = null) {
        document.querySelectorAll('.sv-ms.open').forEach((ms) => {
            if (except && ms === except) {
                return;
            }

            ms.classList.remove('open');
        });
    }

    function initMultiSelect(ms) {
        const trigger = ms.querySelector('.sv-ms-trigger');
        const search = ms.querySelector('[data-ms-search]');
        const list = ms.querySelector('[data-ms-list]');
        const chipsEl = ms.querySelector('[data-ms-chips]');
        const countEl = ms.querySelector('[data-ms-count]');
        const btnClear = ms.querySelector('[data-ms-clear]');
        const btnClose = ms.querySelector('[data-ms-close]');
        const hiddenSelect = ms.querySelector('select.sv-hidden-select');

        if (!hiddenSelect || !list) {
            return;
        }

        function syncToSelect() {
            const map = new Map();

            list.querySelectorAll('.sv-ms-item').forEach((item) => {
                map.set(item.dataset.value, item.querySelector('input').checked);
            });

            Array.from(hiddenSelect.options).forEach((opt) => {
                opt.selected = !!map.get(opt.value);
            });

            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function syncFromSelect() {
            list.querySelectorAll('.sv-ms-item').forEach((item) => {
                const checkbox = item.querySelector('input');
                const option = Array.from(hiddenSelect.options).find((opt) => opt.value === item.dataset.value);

                if (checkbox && option) {
                    checkbox.checked = option.selected;
                }
            });

            renderChips();
            updateCount();
        }

        function updateCount() {
            const selectedCount = hiddenSelect.selectedOptions.length;

            if (countEl) {
                countEl.textContent = String(selectedCount);
            }
        }

        function renderChips() {
            if (!chipsEl) {
                return;
            }

            chipsEl.innerHTML = '';

            Array.from(hiddenSelect.selectedOptions).forEach((opt) => {
                const chip = document.createElement('span');
                chip.className = 'sv-chip';
                chip.innerHTML = `
                    <span>${opt.text}</span>
                    <button type="button" aria-label="Rimuovi">×</button>
                `;

                chip.querySelector('button').addEventListener('click', () => {
                    opt.selected = false;

                    const safeValue = window.CSS && typeof window.CSS.escape === 'function'
                        ? window.CSS.escape(opt.value)
                        : opt.value;

                    const item = list.querySelector(`.sv-ms-item[data-value="${safeValue}"]`);

                    if (item) {
                        const checkbox = item.querySelector('input');
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    }

                    hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    renderChips();
                    updateCount();
                });

                chipsEl.appendChild(chip);
            });
        }

        function buildList() {
            list.innerHTML = '';

            Array.from(hiddenSelect.options).forEach((opt) => {
                const item = document.createElement('div');
                item.className = 'sv-ms-item';
                item.dataset.value = opt.value;
                item.dataset.label = opt.text;

                item.innerHTML = `<input type="checkbox"><div>${opt.text}</div>`;

                const checkbox = item.querySelector('input');
                checkbox.checked = opt.selected;

                item.addEventListener('click', (e) => {
                    if (e.target.tagName.toLowerCase() !== 'input') {
                        checkbox.checked = !checkbox.checked;
                    }

                    syncToSelect();
                    renderChips();
                    updateCount();
                });

                checkbox.addEventListener('change', () => {
                    syncToSelect();
                    renderChips();
                    updateCount();
                });

                list.appendChild(item);
            });
        }

        function applySearch() {
            const query = (search?.value || '').trim().toLowerCase();

            list.querySelectorAll('.sv-ms-item').forEach((item) => {
                const label = (item.dataset.label || '').toLowerCase();
                item.style.display = (!query || label.includes(query)) ? '' : 'none';
            });
        }

        function toggleOpen() {
            const isOpen = ms.classList.contains('open');

            closeAll(ms);
            ms.classList.toggle('open', !isOpen);

            if (!isOpen && search) {
                search.value = '';
                applySearch();

                setTimeout(() => {
                    search.focus();
                }, 0);
            }
        }

        function clearSelection() {
            Array.from(hiddenSelect.options).forEach((opt) => {
                opt.selected = false;
            });

            syncFromSelect();
            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function resetUi() {
            if (search) {
                search.value = '';
            }

            applySearch();
            syncFromSelect();
            ms.classList.remove('open');
        }

        if (trigger) {
            trigger.addEventListener('click', toggleOpen);

            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleOpen();
                }
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', () => {
                ms.classList.remove('open');
            });
        }

        if (btnClear) {
            btnClear.addEventListener('click', () => {
                clearSelection();
            });
        }

        if (search) {
            search.addEventListener('input', applySearch);
        }

        hiddenSelect.addEventListener('change', () => {
            syncFromSelect();
        });

        document.addEventListener('mousedown', (e) => {
            if (!ms.contains(e.target)) {
                ms.classList.remove('open');
            }
        }, true);

        buildList();
        syncFromSelect();

        instances.push({
            root: ms,
            resetUi: resetUi,
            clearSelection: clearSelection,
            syncFromSelect: syncFromSelect
        });
    }

    document.querySelectorAll('.sv-ms').forEach(initMultiSelect);

    window.SvMultiSelect = {
        resetAll() {
            instances.forEach((instance) => instance.resetUi());
        },
        clearAll() {
            instances.forEach((instance) => instance.clearSelection());
        },
        syncAll() {
            instances.forEach((instance) => instance.syncFromSelect());
        }
    };

    if (window.feather) {
        feather.replace();
    }
});
