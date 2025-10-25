<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SafeTransaction
{
    /**
     * Run the given callback inside a transaction only when there is no active transaction.
     * If a transaction is already active, executes the callback directly to avoid nested savepoints.
     *
     * @param  callable  $callback
     * @param  string|null  $connectionName
     * @return mixed
     */
    public static function run(callable $callback, ?string $connectionName = null)
    {
        $connection = $connectionName ? DB::connection($connectionName) : DB::connection();

        try {
            $level = $connection->transactionLevel();
        } catch (\Throwable $e) {
            // If we cannot inspect transaction level, fallback to running inside DB::transaction
            try { Log::warning('SafeTransaction: failed to read transaction level, falling back to DB::transaction', ['exception' => $e]); } catch (\Throwable $_) {}
            return DB::transaction($callback);
        }

        if ($level > 0) {
            // already in transaction: run callback directly (no new savepoint)
            try { Log::debug('SafeTransaction: executing callback without opening new transaction', ['transactionLevel' => $level]); } catch (\Throwable $_) {}
            return $callback();
        }

        // No active transaction: run inside DB::transaction
        try {
            return DB::transaction(function () use ($callback) {
                return $callback();
            });
        } catch (\Throwable $e) {
            try { Log::error('SafeTransaction: DB::transaction threw', ['exception' => $e]); } catch (\Throwable $_) {}
            throw $e;
        }
    }
}
