<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class AipItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'budget_year_id',
        'budget_classification_id',
        'fund_source_id',
        'ppsa_id',
        'aip_reference_code',
        'ppa_description',
        'department_id',
        'start_date',
        'end_date',
        'expected_outputs',
        'amount',
    ];

    public function fundSource()
    {
        return $this->belongsTo(FundSource::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetYear()
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function budgetClassification()
    {
        return $this->belongsTo(BudgetClassification::class);
    }

    public function ppsa()
    {
        return $this->belongsTo(Ppsa::class);
    }

    public function implementingDepartments()
    {
        return $this->hasMany(AipImplementingDepartment::class);
    }
}
