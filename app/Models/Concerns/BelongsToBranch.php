<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait BelongsToBranch
{
    protected static array $branchColumnCache = [];

    protected static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            $branchId = self::currentBranchId();
            if (self::modelHasBranchColumn($model) && empty($model->branch_id) && $branchId) {
                $model->branch_id = $branchId;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = self::currentBranchId();
            if ($branchId && self::modelHasBranchColumn($builder->getModel())) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
            }
        });
    }

    protected static function currentBranchId(): ?int
    {
        return Tenant::branchId();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function modelHasBranchColumn($model): bool
    {
        $table = $model->getTable();

        if (array_key_exists($table, self::$branchColumnCache)) {
            return self::$branchColumnCache[$table];
        }

        try {
            return self::$branchColumnCache[$table] = Schema::hasColumn($table, 'branch_id');
        } catch (Throwable $e) {
            return self::$branchColumnCache[$table] = false;
        }
    }
}
