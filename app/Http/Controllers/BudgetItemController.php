<?php

namespace App\Http\Controllers;

use App\BudgetItem;
use Illuminate\Http\Request;

class BudgetItemController extends Controller
{
    /**
     * TODO: Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return BudgetItem::create($request->all());
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
        return BudgetItem::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BudgetItem  $budgetItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = BudgetItem::findOrFail($id);
        $item->fill($request->all());
        return $item;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\BudgetItem  $budgetItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        return BudgetList::destroy($id);
    }
}
