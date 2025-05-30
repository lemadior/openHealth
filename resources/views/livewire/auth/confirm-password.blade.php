@php
    $hasPasswordError = $errors->has('password');
@endphp

<div class="flex items-center justify-center flex-col flex-grow w-full h-full">
    <x-authentication-card>

        <h2 class="text-lg font-medium text-gray-900 text-center dark:text-gray-100">
            {{  __('forms.password_confirmation') }}
        </h2>

        <form
            autocomplete="off"
            wire:submit.prevent="confirmPassword"
        >
            <div class="form-group group mt-6">
                <input
                    required
                    type="password"
                    placeholder=" "
                    id="password"
                    wire:model="password"
                    aria-describedby="{{ $hasPasswordError ? 'hasPasswordErrorHelp' : '' }}"
                    class="input {{ $hasPasswordError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                />

                @if($hasPasswordError)
                    <p id="hasPasswordErrorHelp" class="text-error">
                        {{ $errors->first('password') }}
                    </p>
                @endif

                <p id="passwordConfirmationHelp" class="text-note">
                    {{ __('forms.secure_page_description')}}
                </p>

                <label for="password" class="label z-10">
                    {{ __('forms.password') }}
                </label>
            </div>

            <div class="flex items-center justify-end mt-6">
                <button
                    type="submit"
                    id="submitButton"
                    class="special-button cursor-pointer w-full"
                    wire:click="confirmPassword"
                >
                    {{ __('forms.confirm')  }}
                </button>
            </div>
        </form>
    </x-authentication-card>
</div>
