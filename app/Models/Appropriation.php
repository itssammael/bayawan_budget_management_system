<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDepartmentScope;
use App\Traits\LogsActivity;

class Appropriation extends Model
{
    use HasFactory, LogsActivity, HasDepartmentScope;

    protected $fillable = [
        'fund_source_id',
        'budget_year_id',
        'ppsa_id',
        'account_code',
        'ppa_description',
        'appropriation_type',
        'appropriated_amount',
        'allotment',
        'obligation',
        'remarks',
        'department_id',
    ];

    public function fundSource()
    {
        return $this->belongsTo(FundSource::class);
    }

    public function budgetYear()
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function ppsa()
    {
        return $this->belongsTo(Ppsa::class);
    }

    public function procurementTransactions()
    {
        return $this->hasMany(ProcurementTransaction::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
