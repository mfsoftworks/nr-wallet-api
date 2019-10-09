<?php

namespace App\Http\Controllers;

use App\PaymentRequest;
use Illuminate\Http\Request;

class PaymentRequestController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['from_user_id'] = auth()->user()->id;

        $payRequest = PaymentRequest::create($data);
        
        // TODO: Send request notification
        return $payRequest;
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return PaymentRequest::findOrFail($id);
    }
}
