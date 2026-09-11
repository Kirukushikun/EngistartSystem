<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Access Hub
    |--------------------------------------------------------------------------
    |
    | The hub is one central list of people and their org role(s). Syncing from
    | it is optional -- User Management works fully without it. See
    | essentials/hub-integration-guide.md for the contract this implements.
    |
    | base_url and project_key are not secrets in the "never write them down"
    | sense, but project_key is still per-environment and only ever used once,
    | during enrollment -- so both are env-driven rather than hardcoded here.
    | The actual connection credentials (client id / secret) returned by
    | enrollment are never config or .env -- they live encrypted in the
    | hub_connections table. See App\Models\HubConnection.
    |
    */

    'base_url' => env('HUB_BASE_URL', ''),

    /*
    | Issued once by the hub admin when this project is registered on the
    | hub. Used only for the one-time POST /api/v1/enroll call -- everyday
    | sync calls authenticate with the stored client id/secret instead.
    */
    'project_key' => env('HUB_PROJECT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Role mapping
    |--------------------------------------------------------------------------
    |
    | Hub role => this system's role. The hub vocabulary is fixed at four
    | values: manager, division_head, vp, user.
    |
    | A hub role absent from this map grants nothing -- a silent no-op, not an
    | error. Re-check this file whenever the hub repo's docs/api.md changes its
    | role list (it renamed `requestor` -> `manager` once already).
    |
    | `user` is intentionally absent: a plain hub user with no management role
    | gets no login here.
    |
    | dh_gen_services, ed_manager, it_admin, engineer and guest have no hub
    | equivalent -- those accounts stay manually managed (source = manual).
    |
    | When the hub sends a person with more than one role, the highest-privilege
    | mapped role wins. That ordering lives in App\Support\HubRoleResolver, not
    | here -- this table stays a flat, logic-free lookup.
    */

    'role_map' => [
        'division_head' => 'division_head',
        'vp' => 'vp_gen_services',
        'manager' => 'farm_manager',
    ],

];
