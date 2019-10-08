<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Format query
        $query = $this->formatQuery($request->input('query'));
        $type = isset($request->type) ? $request->type : '%';

        // Select user by ID
        return response()->json(
            User::where('username', 'like', $query)
                ->where('account_type', 'like', $type)
                ->limit(45)
                ->get()
        );
    }

    /**
     * Format search query
     *
     * @param string $query
     * @return string
     */
    private function formatQuery($query) {
        return '%'.str_replace(' ', '%', $query).'%';
    }
}
