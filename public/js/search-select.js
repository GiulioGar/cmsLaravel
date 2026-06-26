(function () {
    function getOptions(select) {
        return Array.from(select.options).map(function (option, index) {
            return {
                value: option.value,
                text: option.text,
                isPlaceholder: index === 0 && option.value === ''
            };
        });
    }

    function initWrapper(wrapper) {
        if (!wrapper || wrapper.dataset.searchSelectReady === '1') {
            return;
        }

        var selectSelector = wrapper.dataset.targetSelect;
        var select = document.querySelector(selectSelector);

        if (!select) {
            return;
        }

        var toggle = wrapper.querySelector('.search-select-toggle');
        var label = wrapper.querySelector('.search-select-label');
        var dropdown = wrapper.querySelector('.search-select-dropdown');
        var input = wrapper.querySelector('.search-select-input');
        var optionsBox = wrapper.querySelector('.search-select-options');

        if (!toggle || !label || !dropdown || !input || !optionsBox) {
            return;
        }

        function getSelectedText() {
            var selectedOption = select.options[select.selectedIndex];
            return selectedOption ? selectedOption.text : '-- Seleziona --';
        }

        function updateLabel() {
            label.textContent = getSelectedText();
        }

        function renderOptions(searchTerm) {
            var term = (searchTerm || '').trim().toLowerCase();
            var options = getOptions(select);

            optionsBox.innerHTML = '';

            var filtered = options.filter(function (item) {
                if (item.isPlaceholder) {
                    return false;
                }

                if (term === '') {
                    return true;
                }

                return item.text.toLowerCase().indexOf(term) !== -1;
            });

            if (!filtered.length) {
                optionsBox.innerHTML = '<div class="search-select-empty">Nessun risultato trovato</div>';
                return;
            }

            filtered.forEach(function (item) {
                var optionEl = document.createElement('div');
                optionEl.className = 'search-select-option';

                if (String(select.value) === String(item.value)) {
                    optionEl.classList.add('is-selected');
                }

                optionEl.textContent = item.text;
                optionEl.dataset.value = item.value;
                optionEl.addEventListener('click', function () {
                    select.value = item.value;
                    updateLabel();
                    closeDropdown();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                optionsBox.appendChild(optionEl);
            });
        }

        function openDropdown() {
            dropdown.style.display = 'block';
            renderOptions(input.value);
            setTimeout(function () {
                input.focus();
            }, 0);
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            input.value = '';
        }

        function isOpen() {
            return dropdown.style.display !== 'none';
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (isOpen()) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        input.addEventListener('input', function () {
            renderOptions(this.value);
        });

        input.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                closeDropdown();
            }
        });

        select.addEventListener('change', function () {
            updateLabel();
            if (isOpen()) {
                renderOptions(input.value);
            }
        });

        wrapper.addEventListener('search-select:refresh', function () {
            updateLabel();
            if (isOpen()) {
                renderOptions(input.value);
            } else {
                renderOptions('');
            }
        });

        wrapper.dataset.searchSelectReady = '1';
        updateLabel();
        renderOptions('');
    }

    function initSearchSelects(root) {
        var scope = root || document;
        var wrappers = scope.querySelectorAll('.search-select');

        wrappers.forEach(function (wrapper) {
            initWrapper(wrapper);
        });
    }

    window.initSearchSelects = initSearchSelects;

    document.addEventListener('DOMContentLoaded', function () {
        initSearchSelects(document);
    });
})();
