@once
    <script>
        window.createSignaturePadVisuals = function () {
            const shell = document.getElementById('signaturePadShell');
            const placeholder = document.getElementById('signaturePadPlaceholder');
            const readyState = document.getElementById('signaturePadReadyState');
            const errorState = document.getElementById('signaturePadErrorState');

            const removeClasses = (element, classes) => {
                if (! element) {
                    return;
                }

                element.classList.remove(...classes);
            };

            const addClasses = (element, classes) => {
                if (! element) {
                    return;
                }

                element.classList.add(...classes);
            };

            const setShellTone = (classes) => {
                removeClasses(shell, [
                    'border-slate-300',
                    'border-blue-300',
                    'border-emerald-300',
                    'border-rose-400',
                    'bg-slate-50',
                    'bg-blue-50/40',
                    'bg-emerald-50/40',
                    'bg-rose-50/40',
                    'shadow-[0_0_0_4px_rgba(59,130,246,0.08)]',
                    'shadow-[0_0_0_4px_rgba(16,185,129,0.08)]',
                    'shadow-[0_0_0_4px_rgba(244,63,94,0.10)]',
                ]);
                addClasses(shell, classes);
            };

            const hide = (element) => {
                element?.classList.add('hidden');
            };

            const show = (element) => {
                element?.classList.remove('hidden');
            };

            return {
                idle() {
                    setShellTone(['border-slate-300', 'bg-slate-50']);
                    show(placeholder);
                    hide(readyState);
                    hide(errorState);
                },
                active() {
                    setShellTone(['border-blue-300', 'bg-blue-50/40', 'shadow-[0_0_0_4px_rgba(59,130,246,0.08)]']);
                    hide(placeholder);
                    hide(readyState);
                    hide(errorState);
                },
                completed() {
                    setShellTone(['border-emerald-300', 'bg-emerald-50/40', 'shadow-[0_0_0_4px_rgba(16,185,129,0.08)]']);
                    hide(placeholder);
                    show(readyState);
                    hide(errorState);
                },
                error(message = 'Silakan tanda tangan terlebih dahulu.') {
                    setShellTone(['border-rose-400', 'bg-rose-50/40', 'shadow-[0_0_0_4px_rgba(244,63,94,0.10)]']);
                    show(placeholder);
                    hide(readyState);

                    if (errorState) {
                        errorState.textContent = message;
                        show(errorState);
                    }
                },
            };
        };
    </script>
@endonce
