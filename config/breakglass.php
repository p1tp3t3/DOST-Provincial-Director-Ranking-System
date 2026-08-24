<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin Break-glass Login Password
    |--------------------------------------------------------------------------
    |
    | Bcrypt hash of the password that unlocks /admin/login/{password}, the
    | maintenance-mode bypass for super admins. This is a hash, not the
    | plaintext — it is only ever compared with Hash::check(). Manage it from
    | Super Admin > Maintenance in the app, which rewrites this env value.
    |
    */

    'password_hash' => env('SUPER_ADMIN_BREAKGLASS_PASSWORD_HASH'),

];
