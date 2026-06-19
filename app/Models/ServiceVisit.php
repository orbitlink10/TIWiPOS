<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceVisit extends Model
{
    use HasFactory, BelongsToBusiness, BelongsToBranch;

    public const STATUS_BOOKED = 'booked';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'business_id',
        'branch_id',
        'customer_id',
        'service_id',
        'service_worker_id',
        'recorded_by_user_id',
        'customer_name',
        'customer_phone',
        'delivery_location',
        'delivery_reference',
        'service_date',
        'service_time',
        'price',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'completed_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_BOOKED => 'Booked',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function worker()
    {
        return $this->belongsTo(ServiceWorker::class, 'service_worker_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
