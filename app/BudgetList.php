<?php

namespace App;

class BudgetList extends CachableModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title'
    ];

    /**
     * The related attributes that should be returned
     */
    protected $with = [
        'budgetItems'
    ];

    /**
     * Get the budget list for this item.
     */
    public function budgetItems()
    {
        return $this->hasMany('App\BudgetItem');
    }
}
