<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class FundSource extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'description'];

    public function appropriations()
    {
        return $this->hasMany(Appropriation::class);
    }

    public function aipItems()
    {
        return $this->hasMany(AipItem::class);
    }
}
