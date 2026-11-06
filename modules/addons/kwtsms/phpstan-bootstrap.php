<?php
/**
 * PHPStan bootstrap: stubs for WHMCS classes/functions not present in vendor.
 * Only used during static analysis: never loaded at runtime.
 */

// ---- Global namespace: WHMCS functions ----
namespace {
    if (!function_exists('add_hook')) {
        function add_hook(string $hook, int $priority, callable $callback): void {}
    }
    if (!function_exists('logActivity')) {
        function logActivity(string $description, int $userId = 0): void {}
    }
}

// ---- Illuminate Support ----
namespace Illuminate\Support {
    class Collection {
        /** @return array<int, object> */
        public function toArray(): array { return []; }
    }
}

// ---- Illuminate Query Builder ----
namespace Illuminate\Database\Query {
    class Builder {
        public function where(string $column, mixed $value = null, mixed $operator = null): static { return $this; }
        public function whereDate(string $column, mixed $value): static { return $this; }
        public function first(array $columns = ['*']): ?object { return null; }
        public function get(array $columns = ['*']): \Illuminate\Support\Collection { return new \Illuminate\Support\Collection(); }
        public function value(string $column): mixed { return null; }
        public function count(): int { return 0; }
        public function orderBy(string $column, string $direction = 'asc'): static { return $this; }
        public function offset(int $value): static { return $this; }
        public function limit(int $value): static { return $this; }
        public function truncate(): void {}
        public function updateOrInsert(array $attributes, array $values = []): bool { return true; }
        public function delete(): int { return 0; }
        /** @param array<string, mixed> $values */
        public function insert(array $values): bool { return true; }
    }
}

// ---- Illuminate Schema ----
namespace Illuminate\Database\Schema {
    class Builder {
        public function hasTable(string $table): bool { return false; }
        public function create(string $table, \Closure $callback): void {}
        public function dropIfExists(string $table): void {}
    }
    class Blueprint {
        public function increments(string $column): object { return new \stdClass(); }
        public function integer(string $column): object { return new \stdClass(); }
        public function string(string $column, int $length = 255): object { return new \stdClass(); }
        public function text(string $column): object { return new \stdClass(); }
        public function decimal(string $column, int $total = 8, int $places = 2): object { return new \stdClass(); }
        public function dateTime(string $column): object { return new \stdClass(); }
        public function enum(string $column, array $allowed): object { return new \stdClass(); }
        public function nullable(): static { return $this; }
    }
}

// ---- WHMCS Capsule ----
namespace WHMCS\Database {
    class Capsule {
        public static function table(string $name): \Illuminate\Database\Query\Builder {
            return new \Illuminate\Database\Query\Builder();
        }
        public static function schema(): \Illuminate\Database\Schema\Builder {
            return new \Illuminate\Database\Schema\Builder();
        }
    }
}
