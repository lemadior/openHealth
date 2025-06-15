<?php

namespace App\Auth\EHealth\Guards;

use Exception;
use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Http\Request;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Session\Session;
use App\Auth\EHealth\Services\TokenStorage;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class EHealthGuard extends SessionGuard
{
    /**
     * @var TokenStorage
     */
    protected TokenStorage $tokenStorage;

    public function __construct(string $name, UserProvider $provider, Session $session, Request $request, TokenStorage $tokenStorage)
    {
        parent::__construct($name, $provider, $session, $request ?? request());

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get the currently authenticated user.
     * Depends on it's UUID
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if(!empty($this->user)) {
            return $this->user;
        }

        if ($this->user && !$this->tokenStorage->hasBearerToken()) {
            $this->logout();

            return null;
        }

        $uuid = $this->session->get($this->getName());

        if ($uuid) {
            $this->user = $this->provider->retrieveById($uuid);

            if ($this->user instanceof User) {
                $selectedLegalEntityId = session('selected_legal_entity_id');

                if($selectedLegalEntityId) {
                    $legalEntity = LegalEntity::find($selectedLegalEntityId);

                    if ($legalEntity && $this->user->hasAccessToLegalEntityByUuid($legalEntity->uuid)) {
                        $this->user->setLegalEntity($legalEntity);
                    } else {
                        Log::warning(__("Selected LegalEntity ID {$selectedLegalEntityId} from session is invalid or user has no access"));

                        session()->forget('selected_legal_entity_id');

                        $this->logout();

                        return null;
                    }
                } else {
                    Log::warning(__("No selected LegalEntity ID in session and no accessible LegalEntities for user {$this->user->id}"));

                    session()->forget('selected_legal_entity_id');

                    $this->logout();

                    return null;
                }
            }
        }

        return $this->user;
    }

    public function isLoggedIn(): bool
    {
        return $this->tokenStorage->hasBearerToken() && $this->tokenStorage->getExpiresAt();
    }

    public function getUserUUID(Authenticatable $user): ?string
    {
        return $user->uuid;
    }

    /**
     * Log a user into the application.
     * Add additional checks for Bearer token presents
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        if (! $this->tokenStorage->hasBearerToken()) {
            Log::error(__('Bearer token missing in session', [], 'en'));

            throw new Exception(__('Bearer token missing in session'));
        }

        $this->updateSession($this->getUserUUID($user));

        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);

        if ($user instanceof User) {
            $legalEntity = $user->legalEntity;

            if ($legalEntity) {
                session()->put('selected_legal_entity_id',  $legalEntity->id);
            } else {
                Log::error(__("LegalEntity was not properly set for user {$user->id} before EHealthGuard::login()"));

                $this->logout();

                throw new Exception(__('Selected LegalEntity invalid or inavailable'));
            }
        }
    }

    public function logout()
    {
        parent::logout();

        $this->tokenStorage->clear();

        session()->forget('selected_legal_entity_id');
    }
}
