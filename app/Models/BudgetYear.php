<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class BudgetYear extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['year'];

    public function appropriations()
    {
        return $this->hasMany(Appropriation::class);
    }
}
