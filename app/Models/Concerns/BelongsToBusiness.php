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
            $user = Auth::user();
            if (empty($model->business_id) && $user && !$user->is_super_admin && ($businessId = $user->business_id)) {
                $model->business_id = $businessId;
            }
        });

        static::addGlobalScope('business', function (Builder $builder) {
            $user = Auth::user();
            $businessId = $user?->business_id;
            $isSuper = $user?->is_super_admin;
            if ($businessId && !$isSuper) {
                $builder->where($builder->getModel()->getTable() . '.business_id', $businessId);
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
