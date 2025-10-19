<?php

namespace App\Models;

use App\Enums\IncomingLetterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class IncomingLetter extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'letter_number',
        'letter_date',
        'received_date',
        'sender',
        'subject',
        'summary',
        'primary_file',
        'archive_external_id',
        'status',
        'last_disposition',
        'user_id',
        'classification_code',
        'security_level',
        'speed_level',
        'origin_agency',
        'physical_location',
        'disposed_at',
        'completed_at',
        'archived_at',
        'disposition_count',
        'file_hash',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'received_date' => 'date',
        'disposed_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'status' => IncomingLetterStatus::class,
    ];

    protected array $auditExclude = [
        // large file hash changes could be noisy if desired exclude later
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dispositions()
    {
        return $this->hasMany(Disposition::class);
    }
}
