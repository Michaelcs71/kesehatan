<?php

namespace App\Services\Concerns;

/**
 * Trait HasStandardizedMethods
 *
 * Provides standardized CRUD method names that delegate to entity-specific methods.
 * The child Service must define these abstract methods:
 *   - getEntityName(): singular (e.g. "Obat")
 *   - getEntityPluralName(): plural (e.g. "Obats")
 *
 * And the entity-specific methods:
 *   - getAll{Plural}($params): array
 *   - find{Singular}ById($id): ?Model
 *   - create{Singular}($data): Model
 *   - update{Singular}($id, $data): bool
 *   - delete{Singular}($id): bool
 *
 * Example:
 *   class MasterObatService { use HasStandardizedMethods;
 *     protected static function getEntityName() { return "Obat"; }
 *     protected static function getEntityPluralName() { return "Obats"; }
 *     public static function getAllObats($params) { ... }
 *     public static function findObatById($id) { ... }
 *     // etc.
 *   }
 *
 *   Then can be called via:
 *   MasterObatService::getAll($params);  // -> getAllObats($params)
 *   MasterObatService::getById($id);     // -> findObatById($id)
 */
trait HasStandardizedMethods
{
    /**
     * Get singular entity name (e.g. "Obat", "Pasien")
     */
    abstract protected static function getEntityName(): string;

    /**
     * Get plural entity name (e.g. "Obats", "Pasiens")
     */
    abstract protected static function getEntityPluralName(): string;

    /**
     * Standardized: get all with pagination
     * Delegates to: getAll{Plural}($params)
     */
    public static function getAll(array $params): array
    {
        $method = 'getAll'.static::getEntityPluralName();

        if (! method_exists(static::class, $method)) {
            throw new \BadMethodCallException(
                static::class."::{$method}() must be defined."
            );
        }

        return static::{$method}($params);
    }

    /**
     * Standardized: find by ID
     * Delegates to: find{Singular}ById($id)
     */
    public static function getById(string $id)
    {
        $method = 'find'.static::getEntityName().'ById';

        if (! method_exists(static::class, $method)) {
            throw new \BadMethodCallException(
                static::class."::{$method}() must be defined."
            );
        }

        return static::{$method}($id);
    }

    /**
     * Standardized: create
     * Delegates to: create{Singular}($data)
     */
    public static function create(array $data)
    {
        $method = 'create'.static::getEntityName();

        if (! method_exists(static::class, $method)) {
            throw new \BadMethodCallException(
                static::class."::{$method}() must be defined."
            );
        }

        return static::{$method}($data);
    }

    /**
     * Standardized: update
     * Delegates to: update{Singular}($id, $data)
     */
    public static function update(string $id, array $data): bool
    {
        $method = 'update'.static::getEntityName();

        if (! method_exists(static::class, $method)) {
            throw new \BadMethodCallException(
                static::class."::{$method}() must be defined."
            );
        }

        return static::{$method}($id, $data);
    }

    /**
     * Standardized: delete
     * Delegates to: delete{Singular}($id)
     */
    public static function delete(string $id): bool
    {
        $method = 'delete'.static::getEntityName();

        if (! method_exists(static::class, $method)) {
            throw new \BadMethodCallException(
                static::class."::{$method}() must be defined."
            );
        }

        return static::{$method}($id);
    }
}
