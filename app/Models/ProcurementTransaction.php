<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDepartmentScope;
use App\Traits\LogsActivity;

class ProcurementTransaction extends Model
{
    use HasFactory, LogsActivity, HasDepartmentScope;

    protected $fillable = [
        'appropriation_id',
        'transaction_no',
        'item_description',
        'ppmp_no',
        'pr_no',
        'po_no',
        'status',
        'estimated_cost',
        'actual_cost',
        'started_at',
        'completed_at',
        'department_id',
    ];

    public function appropriation()
    {
        return $this->belongsTo(Appropriation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
