{{--
    Blade component: <x-autofill-detector />
    Purpose: Force Chrome to "reveal" autofill values (e.g. after Ctrl+R).

    How it works:
    1. After window.load, we delay 150ms to let Chrome finish autofill
    2. Find the first visible input field (email, text, password, etc.)
    3. Focus the input to trigger Chrome autofill rendering
    4. After 50ms, check .value — if present, add .not-empty class and trigger 'input' event
    5. Then blur the field to avoid leaving it focused
--}}

@once
    @push('scripts')
        <script>
            window.addEventListener('load', () => {
                /*
                 * In more complex cases (slow connection, Alpine, Livewire, SSO, devtools throttling),
                 * Chrome (and borwsers with Chrpmium engine) inserts autofill values only after load."
                 * This timeout need to pass this delay to ensure autofill has been applied
                 */
                setTimeout(() => {
                    /* List of prioritized selectors to scan for autofill */
                    const selectors = [
                        'input.input.peer', // Inputs styled with Tailwind peer classes
                        'input[type=email]',
                        'input[type=password]',
                        'input[type=text]'
                    ];

                    let autofillInput = null;

                    /* Find the first visible and enabled input field */
                    for (const selector of selectors) {
                        const candidates = Array.from(document.querySelectorAll(selector));

                        autofillInput = candidates.find(el =>
                            el.offsetParent !== null && // Here: check if the element visible and present in DOM
                            !el.disabled &&
                            !el.readOnly &&
                            el.type !== 'hidden'
                        );

                        if (autofillInput) break;
                    }

                    if (!autofillInput) return;

                    /* Chrome only makes autofill value visible after focus */
                    autofillInput.focus();

                    /* Wait 50ms to allow Chrome to populate .value */
                    setTimeout(() => {
                        if (autofillInput.value) {
                            /* Add .not-empty class to trigger floating label */
                            autofillInput.classList.add('not-empty');

                            /* Dispatch input event so Alpine/Livewire can react */
                            autofillInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }

                        /* Remove focus so user doesn't see blinking cursor */
                        autofillInput.blur();
                    }, 50);
                }, 150); // Initial delay after page load
            });
        </script>
    @endpush
@endonce
