<?php

return [

    /*
     * Stripe will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://dashboard.stripe.com/account/webhooks.
     */
    'signing_secret_connect' => env('STRIPE_WEBHOOK_SECRET_CONNECT'),
    'signing_secret_account' => env('STRIPE_WEBHOOK_SECRET_ACCOUNT'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Stripe event type with the `.` replaced by a `_`.
     *
     * You can find a list of Stripe webhook types here:
     * https://stripe.com/docs/api#event_types.
     */
    'jobs' => [
        'payout_created' => \App\Jobs\StripeWebhooks\PayoutCreated::class,
        'payout_failed' => \App\Jobs\StripeWebhooks\PayoutFailed::class,
        'payout_paid' => \App\Jobs\StripeWebhooks\PayoutPaid::class,
        'payment_intent_payment_failed' => \App\Jobs\StripeWebhooks\PaymentIntentPaymentFailed::class,
        'payment_intent_succeeded' => \App\Jobs\StripeWebhooks\PaymentIntentSucceeded::class,
        // 'payment_intent_created' => \App\Jobs\StripeWebhooks\PaymentIntentCreated::class,
        'payment_method_attached' => \App\Jobs\StripeWebhooks\PaymentMethodAttached::class,
        'payment_method_detached' => \App\Jobs\StripeWebhooks\PaymentMethodDetached::class,
        'transfer_failed' => \App\Jobs\StripeWebhooks\TransferFailed::class,
        'transfer_paid' => \App\Jobs\StripeWebhooks\TransferPaid::class
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\StripeWebhooks\ProcessStripeWebhookJob.
     */
    'model' => \Spatie\StripeWebhooks\ProcessStripeWebhookJob::class,
];
