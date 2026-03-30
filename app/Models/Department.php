<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'shortname',
        'code',
        'department_head',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function procurementTransactions()
    {
        return $this->hasMany(ProcurementTransaction::class);
    }

    public function appropriations()
    {
        return $this->hasMany(Appropriation::class);
    }

    public function aipImplementingDepartments()
    {
        return $this->hasMany(AipImplementingDepartment::class);
    }
}
