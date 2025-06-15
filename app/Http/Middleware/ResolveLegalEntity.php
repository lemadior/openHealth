<?php

namespace App\Http\Middleware;

use Redirect;
use Closure;
use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveLegalEntity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /* Try to get ID from URL */
        $legalEntityIdFromUrl = $request->route('legal_entity_id');

        /* If LegalEntity ID hasn't been founed in URL just continue */
        if (! $legalEntityIdFromUrl) {
            return $next($request);
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            Log::warning(__('ResolveLegalEntity middleware called without aunthenticated user for a protected route'));

            /**
             * It must be procceded by middleware 'auth' before
             * If we get this it is a security problem or wrong middleware order
             */
            return Redirect::route('login')->with('error', __('auth.login.error.legal_entity.auth_need'));
        }

        /* Get LegalEntity's ID choosen throught login (from session) */
        $loggedInLegalEntityId = session('selected_legal_entity_id');

        Log::info(__("ResolveLegalEntity Middleware: Requested ID = {$legalEntityIdFromUrl}, Session ID = {$loggedInLegalEntityId}"));

        if ($loggedInLegalEntityId) {
            /* Ensure that ID is equal with selected at the login */
            if ((string)$legalEntityIdFromUrl !== (string)$loggedInLegalEntityId) {
                Log::info(__("User {$user->id} attempted to change LegalEntity ID from URL ({$legalEntityIdFromUrl}) to different than session stored ({$loggedInLegalEntityId})"));

                /* Redirect it to the 404 URL */
                return redirect()->route('url.not-found', ['any' => 'page-not-found']);
            }
        } else {
            /*
             * Here is can be happens if session lost it selected_legal_entity_id value but user still logined.
             * In such case we must try to restor the context from the $user->legalEntity
             * (the 'ehealth' guard should initialize it for this moment) and redirect to it
             */
            if ($user->legalEntity) {
                $sessionFallbackId = $user->legalEntity->id;

                Log::warning(__("Session value for 'selected_legal_entity_id' missing for user {$user->id}. Falling back to the user->legalEntity ID {$sessionFallbackId}"));

                session()->put('selected_legal_entity_id', $sessionFallbackId);

                /* Check if the URL ID is corresponds this fallback */
                if ((string)$legalEntityIdFromUrl !== (string)$sessionFallbackId) {
                    /* Redirect it to the 404 URL */
                    return redirect()->route('url.not-found', ['any' => 'page-not-found']);
                }
            } else {
                Log::error(__("Severe error: Authenticated user {$user->id} has no associated LegalEntity in session or via guard's fallback!"));

                Auth::logout();

                return redirect()->route('login')->with('error', __('auth.login.error.legal_entity.invalid_session'));
            }
        }

        /* Find LegalEntity by ID from URL (at this moment we know that it is correct) */
        $legalEntity = LegalEntity::find($loggedInLegalEntityId);

        if (! $legalEntity) {
            Log::error("LegalEntity with ID {$legalEntityIdFromUrl} not found in the Database");

            Auth::logout();

            return redirect()->route('login')->with('error', __('auth.login.error.legal_entity.data_problem'));
        }

        if (! $user->hasAccessToLegalEntityByUuid($legalEntity->uuid)) {
            Log::critical(__("Security Alert: User {$user->id} has session LegalEntity ID {$loggedInLegalEntityId} but *LOST* DB access via Employee's relationship during request for {$legalEntityIdFromUrl}"));

            session()->forget('selected_legal_entity_id');

            Auth::logout();

            return redirect()->route('login')->with('error', __('auth.login.error.legal_entity.wrong_rights'));
        }

        /* Store LegalEntity in the request's attributes */
        $request->attributes->set('current_legal_entity', $legalEntity);

        return $next($request);
    }
}
