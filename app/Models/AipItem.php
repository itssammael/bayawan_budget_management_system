<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class AipItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'fund_source_id',
        'aip_reference_code',
        'ppa_description',
        'department_id',
        'start_date',
        'end_date',
        'expected_outputs',
        'amount_ps',
        'amount_mooe',
        'amount_fe',
        'amount_co',
    ];

    public function fundSource()
    {
        return $this->belongsTo(FundSource::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
