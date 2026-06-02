<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_RESPONDED = 'responded';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_READ => 'Read',
        self::STATUS_RESPONDED => 'Responded',
    ];

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'inquiry_type',
        'message',
        'source_page',
        'ip_address',
        'user_agent',
        'status',
        'admin_note',
    ];
}
