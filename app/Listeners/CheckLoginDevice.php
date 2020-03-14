<?php

namespace App\Listeners;

use App\User;
use App\Device;
use Jenssegers\Agent\Agent;
use App\Events\UserLogin;
use App\Mail\UnknownDeviceLogin;
use App\Mail\KnownDeviceLogin;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use Mail;

class CheckLoginDevice implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserLogin  $event
     * @return void
     */
    public function handle(UserLogin $event)
    {
        // Reference agent
        $agent = $event->request;

        // Log Request
        Log::info($agent);

        /**
         * Check user signin device against known devices
         *
         * For user check if:
         * - Device and IP (Nexus at 102.66.226.55)
         * - Device and platform and browser (Nexus Android 4.0 Chrome)
         *
         */
        if ($agent['robot'] !== true) {
            Log::alert('Checking login devices');

            // Check if any similar device
            $device = Device::where('user_id', $agent['user_id'])
                ->where('device', $agent['device'])
                ->where(function($q) use ($agent) {
                    $q->where('ip', $agent['ip'])
                        ->orWhere(function($r) use ($agent) {
                            $r->where('platform', $agent['platform'])
                                ->where('browser', $agent['browser']);
                        });
                })
                ->first();

            Log::alert("Similar Login Devices: " . \json_encode($device, JSON_PRETTY_PRINT));

            // Setup email
            $user = User::findOrFail($agent['user_id']);

            // Save device
            if (!$device) {
                Device::create($agent);
            }

            // Send email to known or unknown
            Mail::to($user->email)->queue(
                $device
                    ? new KnownDeviceLogin($agent)
                    : new UnknownDeviceLogin($agent)
            );
        }
    }
}
