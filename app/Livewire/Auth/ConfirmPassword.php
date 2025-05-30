<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConfirmPassword extends Component
{
    public string $password = '';

    /**
     * Url to redirect after authentication
     *
     * @var string|null $intendedUrl
     */
    public ?string $intendedUrl = null;

    public function mount()
    {
        /* Checking if the confirmation timeout is still valid */
        $passwordConfirmed = (time() - session('auth.password_confirmed_at', 0)) < config('auth.password_timeout', config('auth.password_timeout'));

        /* Retrieving the intended URL (but not deleting one!) */
        $this->intendedUrl = session()->get('url.intended', route('dashboard', absolute: false));

        Log::info("ConfirmPassword [url intended]:  {$this->intendedUrl}");

        /*
         * If the password has already been confirmed
         * We use window.location.replace() for client-side redirection
         * and to replace the history entry, regardless of whether it's a Livewire request or a full page refresh.
         * This will work for both Ctrl+R and "Back" button navigation (if it was the last entry before confirm-password).
         */
        if ($passwordConfirmed) {
            $this->js('window.location.replace("' . $this->intendedUrl . '");');

            return;
        }
    }

    /**
     * Confirm the current user's password
     *
     * @return void
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            $this->addError('password', __('auth.password'));

            return;
        }

        session(['auth.password_confirmed_at' => time()]);

        session()->forget('url.intended');

        /* Using $this->js() for calling JavaScript on the client's side */
        $this->js('window.location.replace("' . $this->intendedUrl . '");');
    }
}
