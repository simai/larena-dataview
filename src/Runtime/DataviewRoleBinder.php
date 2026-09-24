<?php

declare(strict_types=1);

namespace Larena\Dataview\Runtime;

use Larena\Dataview\Enums\DataviewViewType;

/**
 * Binds a view's field roles to fields whose type the role accepts. A role is filled only by an
 * explicit binding or by the field its convention names; it is never filled with whatever field
 * happens to come first. A missing required role makes the view unavailable, and says which.
 */
final class DataviewRoleBinder
{
    /** @param list<string> $allowed */
    public static function typeAllowed(string $fieldType, array $allowed): bool
    {
        return in_array($fieldType, $allowed, true) || in_array(explode('.', $fieldType, 2)[0], $allowed, true);
    }

    /**
     * @param array<string, string> $fieldTypes field key => field type
     * @param array<string, string> $candidates role => field key to try (explicit binding or convention)
     * @return array{bindings: array<string, string>, missing: list<string>}
     */
    public function bind(DataviewViewType $type, array $fieldTypes, array $candidates): array
    {
        $bindings = [];
        $missing = [];
        foreach ($type->roles() as $role => $rule) {
            $field = $candidates[$role] ?? null;
            if (is_string($field) && isset($fieldTypes[$field]) && self::typeAllowed($fieldTypes[$field], $rule['types'])) {
                $bindings[$role] = $field;
            } elseif ($rule['required']) {
                $missing[] = $role;
            }
        }

        return ['bindings' => $bindings, 'missing' => $missing];
    }

    /**
     * Problems with bindings a descriptor already carries: an unknown role, a field of the wrong
     * type, or a required role left unbound.
     *
     * @param array<string, string> $fieldTypes
     * @param array<string, string> $bindings
     * @return list<string>
     */
    public function problems(DataviewViewType $type, array $fieldTypes, array $bindings): array
    {
        $roles = $type->roles();
        $problems = [];
        foreach ($bindings as $role => $field) {
            if (!isset($roles[$role])) {
                $problems[] = "unknown_role:{$role}";
            } elseif (!isset($fieldTypes[$field]) || !self::typeAllowed($fieldTypes[$field], $roles[$role]['types'])) {
                $problems[] = "role_type:{$role}";
            }
        }
        foreach ($roles as $role => $rule) {
            if ($rule['required'] && !isset($bindings[$role])) {
                $problems[] = "role_missing:{$role}";
            }
        }

        return $problems;
    }
}
