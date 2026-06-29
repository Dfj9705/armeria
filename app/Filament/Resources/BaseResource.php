<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseResource extends Resource
{
    protected static string $permissionPrefix = '';

    protected static function permission(string $action): string
    {
        return "{$action}_" . static::$permissionPrefix;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::permission('view')) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::permission('create')) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can(static::permission('update')) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can(static::permission('delete')) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(static::permission('delete')) ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore($record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function canReplicate($record): bool
    {
        return false;
    }

    public static function canReorder(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canAction(string $permission): bool
    {
        return auth()->user()?->can($permission) ?? false;
    }
}