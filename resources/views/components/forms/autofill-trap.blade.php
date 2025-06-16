{{--
    This component serves to prevent unwanted browser autofill (especially Chrome),
    by confusing it with hidden decoy fields.

    Use it at the beginning of your form.

    The primary "username" decoy field is always added, as it proved to be critical.
    You can add extra fields by passing an array to the `additionalTypes` prop,
    e.g.: `additionalTypes="['password', 'email']"`.
--}}

@props([
    /*
     *Types of additional fields to include, besides the main "username" decoy field.
     * By default, this is empty, as "username" is mandatory and most effective.
     */
    'additionalTypes' => [],
    'usernameName' => 'username', // Keep fixed name "username"
    'passwordName' => 'password_autofill_dummy_' . Str::random(8),
    'emailName' => 'email_autofill_dummy_' . Str::random(8),
])

<div style="display:none;" aria-hidden="true" tabindex="-1">

    {{-- The primary "username" decoy field, which proved effective --}}
    <input type="text" name="{{ $usernameName }}" autocomplete="username" tabindex="-1">

    @if(in_array('password', $additionalTypes))
        {{-- Additional password field, if explicitly specified --}}
        <input type="password" name="{{ $passwordName }}" autocomplete="new-password" tabindex="-1">
    @endif

    @if(in_array('email', $additionalTypes))
        {{-- Additional email field, if explicitly specified --}}
        <input type="email" name="{{ $emailName }}" autocomplete="off" tabindex="-1">
    @endif
</div>
