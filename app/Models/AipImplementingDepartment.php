<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class AipImplementingDepartment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'aip_implementing_department';

    protected $fillable = [
        'aip_item_id',
        'department_id',
        'amount',
    ];

    public function aipItem()
    {
        return $this->belongsTo(AipItem::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
