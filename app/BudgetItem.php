<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'ammount'
    ];

    /**
     * Get the budget list for this item.
     */
    public function budgetList()
    {
        return $this->belongsTo('App\BudgetList');
    }
}
