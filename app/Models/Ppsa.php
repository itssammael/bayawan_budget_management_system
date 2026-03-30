<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Ppsa extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name'];

    public function appropriations()
    {
        return $this->hasMany(Appropriation::class);
    }
}
