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

class TransferPaid implements ShouldQueue
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
        $sender = array_key_exists("user_id", $data["metadata"]) ? User::find($data["metadata"]["user_id"]) : null;
        $receiver = User::find($data["metadata"]["for_user_id"]);

        // Get Transactions
        $transaction = Transaction::where('stripe_transaction_id', $data["id"])->first();

        // TODO: Format Each Currency Correctly
        $amount = $data["amount"]/100;
        $currency = strtoupper($data["currency"]);

        // Build notification extras
        $note = $transaction->description ? "Note: {$description}" : "";
        $senderName = $sender->username ?? "Anonymous";
        $options = (new OptionsBuilder())->setCollapseKey($data["id"]);

        // Create Notifications
        if ($sender) {
            $sNotification = (new PayloadNotificationBuilder())
                ->setTitle('Transfer Complete')
                ->setBody("Completed transferring \${$amount} {$currency} to {$receiver->username}")
                ->setChannelId('transfers')
                ->build();
            // Send Notification
            FCM::sendToGroup($sender->fcm_token, $options->build(), $sNotification, null);
        }

        $rNotification = (new PayloadNotificationBuilder())
            ->setTitle("{$senderName} sent \${$amount} {$currency}")
            ->setBody($note)
            ->setChannelId('transfers')
            ->build();

        // Send Notification
        FCM::sendToGroup($receiver->fcm_token, $options->build(), $rNotification, null);

        // TODO: Update Transaction
    }
}
