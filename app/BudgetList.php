<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BudgetList extends Model
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
