<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locale = document.documentElement.lang || document.documentElement.dataset.locale || 'fr';
        const isArabic = locale === 'ar';
        const arabicKeys = ['ض','ص','ث','ق','ف','غ','ع','ه','خ','ح','ج','د','ش','س','ي','ب','ل','ا','ت','ن','م','ك','ط','ئ','ء','ؤ','ر','لا','ى','ة','و','ز','ظ','ذ'];
        const technicalNames = ['phone', 'telephone', 'licence', 'number', 'nina', 'date', 'photo', 'certificate', 'grade', 'status'];
        const isMobileViewport = window.matchMedia('(max-width: 768px), (pointer: coarse)').matches;
        let activeField = null;

        function isTextField(field) {
            if (!field) return false;
            if (field.tagName === 'TEXTAREA') return true;
            if (field.tagName !== 'INPUT') return false;
            return ['text', 'search', ''].includes((field.type || '').toLowerCase());
        }

        function isArabicTarget(field) {
            if (!isTextField(field)) return false;
            const name = `${field.name || ''} ${field.id || ''}`.toLowerCase();
            return !technicalNames.some((key) => name.includes(key));
        }

        function applyDirection(field) {
            if (!isTextField(field)) return;
            const target = isArabic && isArabicTarget(field);
            field.lang = target ? 'ar' : locale;
            field.dir = target ? 'rtl' : 'ltr';
            if (target) {
                field.setAttribute('inputmode', 'text');
                field.setAttribute('autocapitalize', 'off');
                field.setAttribute('autocorrect', 'off');
            }
            field.classList.toggle('arabic-keyboard-target', target);
        }

        function ensureKeyboard() {
            let keyboard = document.getElementById('arabicVirtualKeyboard');
            if (keyboard) return keyboard;

            keyboard = document.createElement('div');
            keyboard.id = 'arabicVirtualKeyboard';
            keyboard.className = 'arabic-virtual-keyboard d-none';
            keyboard.innerHTML = `
                <div class="arabic-keyboard-panel">
                    <div class="arabic-keyboard-header">
                        <strong>{{ __('messages.cards.arabic_keyboard') }}</strong>
                        <button type="button" class="arabic-keyboard-close" aria-label="{{ __('messages.cancel') }}">&times;</button>
                    </div>
                    <div class="arabic-keyboard-keys" aria-label="{{ __('messages.cards.arabic_keyboard') }}"></div>
                    <div class="arabic-keyboard-actions">
                        <button type="button" class="arabic-action-key arabic-space-key" data-keyboard-action="space">{{ __('messages.cards.space') }}</button>
                        <button type="button" class="arabic-action-key" data-keyboard-action="backspace">⌫</button>
                    </div>
                </div>
            `;

            const keysWrap = keyboard.querySelector('.arabic-keyboard-keys');
            arabicKeys.forEach((key) => {
                if (key === ' ') return;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'arabic-key';
                button.textContent = key;
                button.dataset.key = key;
                keysWrap.appendChild(button);
            });

            document.body.appendChild(keyboard);
            return keyboard;
        }

        function insertAtCursor(field, value) {
            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;
            field.value = field.value.slice(0, start) + value + field.value.slice(end);
            const position = start + value.length;
            field.setSelectionRange(position, position);
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.focus();
        }

        function showKeyboard(field) {
            applyDirection(field);
            if (!field.classList.contains('arabic-keyboard-target')) return;
            activeField = field;
            const keyboard = ensureKeyboard();
            keyboard.classList.remove('d-none');
            document.body.classList.add('arabic-keyboard-open');
            if (isMobileViewport) {
                window.setTimeout(() => {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 80);
            }
        }

        function hideKeyboard() {
            const keyboard = document.getElementById('arabicVirtualKeyboard');
            if (keyboard) keyboard.classList.add('d-none');
            document.body.classList.remove('arabic-keyboard-open');
        }

        document.querySelectorAll('input, textarea').forEach(applyDirection);

        if (!isArabic) return;

        document.addEventListener('focusin', function (event) {
            applyDirection(event.target);
            if (event.target.classList?.contains('arabic-keyboard-target')) {
                showKeyboard(event.target);
            }
        });

        document.addEventListener('click', function (event) {
            const keyButton = event.target.closest('.arabic-key');
            if (keyButton && activeField) {
                insertAtCursor(activeField, keyButton.dataset.key);
                return;
            }

            const action = event.target.closest('[data-keyboard-action]');
            if (action && activeField) {
                if (action.dataset.keyboardAction === 'space') {
                    insertAtCursor(activeField, ' ');
                }
                if (action.dataset.keyboardAction === 'backspace') {
                    const start = activeField.selectionStart ?? activeField.value.length;
                    const end = activeField.selectionEnd ?? activeField.value.length;
                    if (start !== end) {
                        activeField.value = activeField.value.slice(0, start) + activeField.value.slice(end);
                        activeField.setSelectionRange(start, start);
                    } else if (start > 0) {
                        activeField.value = activeField.value.slice(0, start - 1) + activeField.value.slice(end);
                        activeField.setSelectionRange(start - 1, start - 1);
                    }
                    activeField.dispatchEvent(new Event('input', { bubbles: true }));
                    activeField.focus();
                }
                return;
            }

            if (event.target.closest('.arabic-keyboard-close')) {
                hideKeyboard();
            }
        });
    });
</script>

<style>
    [dir="rtl"] .arabic-keyboard-target {
        text-align: right;
    }

    body.arabic-keyboard-open {
        padding-bottom: 190px;
    }

    .arabic-virtual-keyboard {
        position: fixed;
        left: 50%;
        bottom: 8px;
        transform: translateX(-50%);
        width: min(680px, calc(100vw - 16px));
        max-height: 42vh;
        z-index: 3000;
    }

    .arabic-keyboard-panel {
        background: linear-gradient(180deg, var(--card-bg, #fff) 0%, #f6f8fc 100%);
        border: 1px solid rgba(64, 96, 160, .24);
        border-radius: 8px;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .30);
        padding: 10px;
        max-height: inherit;
        overflow-y: auto;
    }

    .arabic-keyboard-header,
    .arabic-keyboard-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-bottom: 8px;
    }

    .arabic-keyboard-header {
        justify-content: space-between;
        color: var(--body-text, #1f2937);
    }

    .arabic-keyboard-close {
        border: 0;
        background: transparent;
        font-size: 20px;
        line-height: 1;
        color: inherit;
    }

    .arabic-keyboard-keys {
        display: grid;
        grid-template-columns: repeat(11, minmax(38px, 1fr));
        gap: 5px;
        direction: rtl;
    }

    .arabic-key {
        min-height: 38px;
        border: 1px solid rgba(64, 96, 160, .22);
        border-radius: 6px;
        background: #ffffff;
        color: var(--body-text, #1f2937);
        font-weight: 800;
        font-size: 20px;
        box-shadow: 0 2px 0 rgba(64, 96, 160, .16);
    }

    .arabic-key:active,
    .arabic-action-key:active {
        transform: translateY(1px);
        box-shadow: none;
    }

    .arabic-keyboard-actions {
        margin: 8px 0 0;
    }

    .arabic-action-key {
        min-height: 36px;
        min-width: 74px;
        border: 1px solid rgba(64, 96, 160, .24);
        border-radius: 6px;
        background: rgba(64, 96, 160, .10);
        color: var(--body-text, #1f2937);
        font-weight: 700;
    }

    .arabic-space-key {
        width: min(360px, 56vw);
    }

    @media (max-width: 768px) {
        body.arabic-keyboard-open {
            padding-bottom: 150px;
        }

        .arabic-virtual-keyboard {
            bottom: 6px;
            max-height: 36vh;
        }

        .arabic-keyboard-panel {
            padding: 8px;
        }

        .arabic-keyboard-header {
            font-size: 13px;
            margin-bottom: 6px;
        }

        .arabic-keyboard-keys {
            grid-template-columns: repeat(9, minmax(28px, 1fr));
            gap: 4px;
        }

        .arabic-key {
            min-height: 32px;
            font-size: 18px;
        }

        .arabic-action-key {
            min-height: 32px;
            font-size: 13px;
        }
    }
</style>
