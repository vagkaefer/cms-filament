<?php

namespace VagKaefer\CmsFilament\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'pivot',
    ];

    protected $fillable = [
        'name',
        'guard_name',
    ];
}
