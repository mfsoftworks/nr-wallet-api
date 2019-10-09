<?php

namespace App\Http\Controllers;

use App\BudgetList;
use Illuminate\Http\Request;

class BudgetListController extends Controller
{
    /**
     * TODO: Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return BudgetList::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        return BudgetList::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $list = BudgetList::findOrFail($id);
        $list->fill($request->all());
        return $list;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        return BudgetList::destroy($id);
    }
}
