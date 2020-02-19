<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Log;
use App\Transaction;
use App\User;

class Nonce
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, string $model)
    {
        // Check for nonce existence
        if (!$request->nonce) {
            return response()->json([
                'error' => [
                    'message' => 'Request requires a nonce key.'
                ]
            ], 400);
        }

        // Check for nonce use in scope (Authenticated User + Model)
        switch (strtolower($model)) {
            case 'transaction':

                $use = Transaction::where('nonce', $request->nonce)
                    ->where('from_user_id', auth()->user()->id)
                    ->first();

                if ($use) {
                    Log::alert("Nonce {$nonce} in use by: " . \json_encode($use, JSON_PRETTY_PRINT));
                    return response()->json([
                        'error' => [
                            'message' => 'This nonce has already been allocted.'
                        ]
                    ], 400);
                }
                break;
        }

        // Proceed with Request
        return $next($request);
    }
}
