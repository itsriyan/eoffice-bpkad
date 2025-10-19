<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Disposition extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'incoming_letter_id',
        'from_user_id',
        'from_name',
        'from_nip',
        'from_phone',
        'to_user_id',
        'to_unit_id',
        'to_name',
        'to_nip',
        'to_phone',
        'to_unit_name',
        'instruction',
        'status',
        'rejection_reason',
        'template_code',
        'sequence',
        'claimed_by_user_id',
        'claimed_at',
        'channel',
        'whatsapp_message_id',
        'whatsapp_sent_at',
        'received_at',
        'rejected_at',
        'followed_up_at',
        'completed_at',
    ];

    protected $casts = [
        'whatsapp_sent_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
        'followed_up_at' => 'datetime',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
        'status' => DispositionStatus::class,
    ];

    protected array $auditExclude = [
        // exclude template_code if unchanged noise, adjust later if needed
    ];

    public function letter()
    {
        return $this->belongsTo(IncomingLetter::class, 'incoming_letter_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function claimedByUser()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }
}
