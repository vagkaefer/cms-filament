<?php

namespace VagKaefer\CmsFilament\Filament\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasResourcePermissions
{
    /**
     * Get the permission prefix for this resource
     * For example: NewsResource -> 'news', NoticiaResource -> 'noticia'
     * Removes 'Resource' suffix and converts to snake_case
     */
    protected static function getPermissionPrefix(): string
    {
        $className = class_basename(static::class);
        // Remove 'Resource' suffix
        $modelName = Str::beforeLast($className, 'Resource');

        return Str::snake($modelName);
    }

    /**
     * Se o usuário atual tem o cargo administrativo (passa em tudo).
     */
    protected static function isAdmin(): bool
    {
        return cms_filament_is_admin();
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("view_{$prefix}") ?? false;
    }

    /**
     * Check if user can view a specific record
     */
    public static function canView(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("view_{$prefix}") ?? false;
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("create_{$prefix}") ?? false;
    }

    /**
     * Check if user can edit a specific record
     */
    public static function canEdit(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("edit_{$prefix}") ?? false;
    }

    /**
     * Check if user can delete a specific record
     */
    public static function canDelete(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("delete_{$prefix}") ?? false;
    }

    /**
     * Check if user can delete multiple records
     */
    public static function canDeleteAny(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("delete_{$prefix}") ?? false;
    }

    /**
     * Check if user can force delete a specific record
     */
    public static function canForceDelete(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("delete_{$prefix}") ?? false;
    }

    /**
     * Check if user can force delete multiple records
     */
    public static function canForceDeleteAny(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("delete_{$prefix}") ?? false;
    }

    /**
     * Check if user can restore a specific record
     */
    public static function canRestore(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("edit_{$prefix}") ?? false;
    }

    /**
     * Check if user can restore multiple records
     */
    public static function canRestoreAny(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("edit_{$prefix}") ?? false;
    }

    /**
     * Check if user can replicate a specific record
     */
    public static function canReplicate(Model $record): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("create_{$prefix}") ?? false;
    }

    /**
     * Check if user can reorder records
     */
    public static function canReorder(): bool
    {
        if (static::isAdmin()) {
            return true;
        }

        $prefix = static::getPermissionPrefix();

        return Auth::user()?->can("edit_{$prefix}") ?? false;
    }

    /**
     * Get all required permissions for this resource
     * Use this for seeding permissions
     *
     * @return array<string, string>
     */
    public static function getRequiredPermissions(): array
    {
        $prefix = static::getPermissionPrefix();

        // Get the plural label from the Resource class
        // @phpstan-ignore-next-line
        $modelLabel = static::getPluralModelLabel();

        return [
            "view_{$prefix}" => "Ver {$modelLabel}",
            "create_{$prefix}" => "Criar {$modelLabel}",
            "edit_{$prefix}" => "Editar {$modelLabel}",
            "delete_{$prefix}" => "Deletar {$modelLabel}",
        ];
    }
}
