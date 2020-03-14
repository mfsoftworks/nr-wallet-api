<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Jenssegers\Agent\Agent;
use App\User;
use App\Events\UserLogin;
use App\Mail\Welcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Validator;
use Mail;

class AuthController extends Controller
{
    /**
     * Handle user registration requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        // Validate new user info
        $validator = Validator::make($request->all(), [
            'username' => 'required|min:3|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Unable to create new account, check your details',
                'validator' => $validator->errors()
            ], 400);
        }

        // Create new user with details
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // Create customer TODO: Convert to Job
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $customer = \Stripe\Customer::create(["email" => $user->email]);
        $user->fill(['stripe_customer_id' => $customer->id])->save();

        // Email out welcome email
        Mail::to($user->email)
            ->queue(new Welcome($user));

        // FIXME: Replace beautymail (Remove dependency, shift to html5 template)
        // $beautymail = app()->make('Snowfire\Beautymail\Beautymail');
        // $beautymail->send('emails.welcome', [], function($message) use ($user) {
        //     $message->from('mua@nygmarosebeauty.com', 'NR Escape')
        //         ->to($user->email, $user->username)
        //         ->subject('Welcome to Escape');
        // });

        // Get passport token
        $token = $user->createToken(env('APP_NAME', 'NR Flow'), ['*'])->accessToken;

        // Signup success response
        return response()->json([
            'token' => $token,
            'profile' => $user->makeVisible([
                'name',
                'email',
                'fcm_token',
                'stripe_connect_id',
                'stripe_customer_id',
                'display_name',
                'settings'
            ])
        ], 201);
    }

    /**
     * Handle user login requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {
        // Create credentials object
        $credentials = [
            'username' => $request->username,
            'password' => $request->password
        ];

        // Attempt auth
        if (auth()->attempt($credentials)) {
            // Remove deactivated response from user
            $user = auth()->user();
            $user->deactivated = 0;
            $user->save();

            // If no customer create customer
            if (!auth()->user()->stripe_customer_id) {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                $customer = \Stripe\Customer::create(["email" => auth()->user()->email]);
                auth()->user()->fill(['stripe_customer_id' => $customer->id])->save();
            }

            // Save user default currency
            if ($user->stripe_connect_id && (!$user->default_currency || !$user->country || !$user->name)) {
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                $account = \Stripe\Account::retrieve($user->stripe_connect_id);
                Log::notice($account);
                $user->fill([
                    'default_currency' => $account->default_currency,
                    'country' => $account->country,
                    'name' => $account->business_profile->name
                ])->save();
            }

            // Create JWT for access
            $token = $user->createToken(env('APP_NAME', 'Laravel'), ['*'])->accessToken;

            // Dispatch login event
            $agent = new Agent();
            event(new UserLogin([
                'ip' => $request->ip(),
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'robot' => $agent->isRobot(),
                'user_id' => auth()->user()->id
            ]));

            // Return successful response
            return response()->json([
                'token' => $token,
                'profile' => auth()->user()->makeVisible([
                    'name',
                    'email',
                    'fcm_token',
                    'stripe_connect_id',
                    'stripe_customer_id',
                    'display_name',
                    'settings'
                ])
            ], 201);

        // If auth fails respond with error
        }

        return response()->json(['error' => 'Incorrect username or password'], 401);
    }

    /**
     * Return the authenticated users details
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request) {
        // Get authenticated user
        $user = auth()->user();

        // Return user with hidden data
        return response()->json($user->makeVisible([
            'name',
            'email',
            'fcm_token',
            'stripe_connect_id',
            'stripe_customer_id',
            'display_name',
            'settings'
        ]));
    }

    /**
     * Update user data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request) {
        // Instantiate user data
        $data = $request->all();

        // Only take relevent data
        $payload = [
            "username" => $data["username"],
            "email" => $data["email"],
            "settings" => $data["settings"],
            "display_name" => $data["displayName"],
            "password" => $data["password"] ?? null
        ];

        // If updating password, hash new password
        if (!$payload["password"]) {
            unset($payload["password"]);
        } else {
            $payload['password'] = Hash::make($data['password']);
        }

        // TODO: If profile pic exists then handle media

        // Get authorised user account
        $user = auth()->user()->fill($payload)->save();
        return $this->user($request);
    }

    /**
     * Deactivate user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivate(Request $request) {
        // Get authenticated user
        $user = auth()->user();

        // Set deactivated status
        $user->deactivated = 1;

        // Save updated object
        if ($user->save()) {
            return response()->json('success', 204);
        } else {
            return response()->json([
                'error' => 'Couldn\'t update user account'
            ], 500);
        }
    }
}
