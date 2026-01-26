<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::creating(function ($model) {
            if (empty($model->business_id) && ($businessId = Auth::user()?->business_id)) {
                $model->business_id = $businessId;
            }
        });

        static::addGlobalScope('business', function (Builder $builder) {
            $businessId = Auth::user()?->business_id;
            if ($businessId) {
                $builder->where($builder->getModel()->getTable() . '.business_id', $businessId);
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
