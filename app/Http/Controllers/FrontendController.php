<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrontendController extends Controller
{

    /**
     * Return welcome page
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function welcome(Request $request) {
        return view('welcome');
    }

    /**
     * Return OAuth Clients
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function clients(Request $request) {
        return view('clients');
    }
}
