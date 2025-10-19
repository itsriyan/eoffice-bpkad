<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Employee extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'grade_id',
        'work_unit_id',
        'name',
        'nip',
        'position',
        'email',
        'phone_number',
        'status'
    ];

    protected array $auditExclude = [
        // add heavy computed or unchanged attributes here if needed
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }
}
