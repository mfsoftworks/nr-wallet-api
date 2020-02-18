<?php

namespace App\Jobs\StripeWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use Illuminate\Foundation\Bus\Dispatchable;
use Spatie\WebhookClient\Models\WebhookCall;
use Illuminate\Support\Facades\Log;
use App\User;
use FCM;

class PaymentIntentPaymentFailed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // DEBUG: Log Call
        Log::alert(
            \json_encode($this->webhookCall, JSON_PRETTY_PRINT)
        );
        $data = $this->webhookCall->payload["data"]["object"];

        // Get User & FCM Token
        $sender = User::findOrFail($data["metadata"]["user_id"]);
        $receiver = User::findOrFail($data["metadata"]["for_user_id"]);

        // TODO: Format Each Currency Correctly
        $amount = $data["amount"]/100;
        $currency = strtoupper($data["currency"]);

        // Get Error
        $error = "Transaction failed with error: {$data["last_payment_error"]["message"]}" ?? "";

        // Create Notifications
        $notification = (new PayloadNotificationBuilder())
            ->setTitle("Failed Transfer \${$amount} {$currency} to {$receiver->username}")
            ->setBody($error)
            ->setChannelId('transfers')
            ->build();
        $options = (new OptionsBuilder())->setCollapseKey($data["id"]);

        // Send Notification
        $response = FCM::sendToGroup($sender->fcm_token, $options->build(), $notification, null);

        // TODO: Seperate into different job if needed
        // Cancel transaction
        // \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        // $intent = \Stripe\PaymentIntent::retrieve($data["id"]);
        // $intent->cancel();
    }
}
