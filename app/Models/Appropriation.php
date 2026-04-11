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
        'aip_item_id',
        'budget_year_id',
        'account_code',
        'allotment',
        'obligation',
        'remarks',
        'department_id',
    ];

    protected $appends = [
        'fund_source_id',
        'appropriation_type',
        'ppsa_id',
        'ppa_description',
        'appropriated_amount',
        'balance',
        'fund_source',
        'ppsa'
    ];

    public function getFundSourceIdAttribute()
    {
        return $this->aipItem?->fund_source_id;
    }

    public function getPpsaIdAttribute()
    {
        return $this->aipItem?->ppsa_id;
    }

    public function getPpaDescriptionAttribute()
    {
        return $this->aipItem?->ppa_description;
    }

    public function getAppropriatedAmountAttribute()
    {
        return $this->aipItem?->amount;
    }

    public function getAppropriationTypeAttribute()
    {
        return $this->aipItem?->budgetClassification?->classification_name;
    }

    public function getBalanceAttribute()
    {
        return ($this->aipItem?->amount ?? 0) - ($this->obligation ?? 0);
    }

    public function getFundSourceAttribute()
    {
        return $this->aipItem?->fundSource;
    }

    public function getPpsaAttribute()
    {
        return $this->aipItem?->ppsa;
    }

    public function aipItem()
    {
        return $this->belongsTo(AipItem::class);
    }

    public function budgetYear()
    {
        return $this->belongsTo(BudgetYear::class);
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
