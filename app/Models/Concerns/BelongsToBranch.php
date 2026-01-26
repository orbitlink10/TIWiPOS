<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            $branchId = self::currentBranchId();
            if (empty($model->branch_id) && $branchId) {
                $model->branch_id = $branchId;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = self::currentBranchId();
            if ($branchId) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
            }
        });
    }

    protected static function currentBranchId(): ?int
    {
        return session('branch_id') ?? Auth::user()?->branch_id;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
