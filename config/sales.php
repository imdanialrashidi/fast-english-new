<?php

// S8 sales switch (scope §25: real prices block public sale).
//
// When off, the plan list shows «در دست آماده‌سازی» and no payment
// request can be created (server-side gate in PaymentRequestController).
// Default is OFF in every environment — the owner turns it on only with
// real prices, a real destination, and approved terms/privacy in place.

return [
    'enabled' => (bool) env('SALES_ENABLED', false),
];
