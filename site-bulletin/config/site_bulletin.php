<?php

return [
    'demo_login_enabled' => env('SITE_BULLETIN_DEMO_LOGIN_ENABLED'),
    'demo_simulation_enabled' => (bool) env('SITE_BULLETIN_DEMO_SIMULATION_ENABLED', true),
    'prototype_footer_enabled' => (bool) env('SITE_BULLETIN_PROTOTYPE_FOOTER_ENABLED', false),
];
