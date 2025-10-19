<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class WorkUnit extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = ['name', 'description'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
