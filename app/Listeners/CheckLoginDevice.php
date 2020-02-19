<?php

namespace App\Listeners;

use App\User;
use App\Device;
use Jenssegers\Agent\Agent;
use App\Events\UserLogin;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
            // FIXME: Replace beautymail (Remove dependency, shift to html5 template)
            // $beautymail = app()->make('Snowfire\Beautymail\Beautymail');

            if (!$device) {
                // Send unknown device email
                // $beautymail->send(
                //     'emails.unknown-device',
                //     [
                //         'title' => 'Unknown Device Login',
                //         'agent' => $agent
                //     ],
                //     function($message) use ($user) {
                //         $message->from('it@nygmarosebeauty.com', 'NR Flow')
                //             ->to($user->email, $user->username)
                //             ->subject('NR Flow unknown device login');
                //     }
                // );

                // Save device
                Device::create($agent);
                return;
            }

            // Send known device email
            $beautymail->send('emails.unknown-device', ['agent' => $agent], function($message) use ($user) {
                $message->from('mua@nygmarosebeauty.com', 'NR Flow')
                    ->to($user->email, $user->username)
                    ->subject('NR Flow new login');
            });
        }
    }
}
