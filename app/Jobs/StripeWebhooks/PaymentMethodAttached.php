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

class PaymentMethodAttached implements ShouldQueue
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
        $user = User::where('stripe_customer_id', $data["customer"])->first();

        // Create Notification
        $notification = (new PayloadNotificationBuilder())
            ->setTitle('New Payment Source')
            ->setChannelId('payment_methods');
        $options = (new OptionsBuilder())->setCollapseKey($data["id"]);

        switch ($data["type"]) {
            case "card":
            default:
                $notification->setBody("New Payment Source Ending in {$data["card"]["last4"]} Added to Your Account");
                break;
        }

        // Send Notification
        $response = FCM::sendToGroup($user->fcm_token, $options->build(), $notification->build(), null);
    }
}
