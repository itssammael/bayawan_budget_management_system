<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification_name',
    ];
}
