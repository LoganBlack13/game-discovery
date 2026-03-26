<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->query('PRAGMA table_info(users)');
        if ($stmt === false) {
            return;
        }

        /** @var array<int, array{name: string, type: string, notnull: int, pk: int, dflt_value: mixed, cid: int}> $columns */
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        /** @var array{name: string, type: string, notnull: int, pk: int}|null $idColumn */
        $idColumn = collect($columns)->firstWhere('name', 'id');
        if ($idColumn === null || mb_stripos($idColumn['type'], 'int') === false) {
            return;
        }

        $idMap = [];
        $newTableColumns = [];
        foreach ($columns as $col) {
            $name = $col['name'];
            $type = $name === 'id' ? 'VARCHAR(36)' : mb_strtoupper($col['type']);
            if ($type === '') {
                $type = 'TEXT';
            }

            $notnull = $col['notnull'] === 1 ? ' NOT NULL' : '';
            $pk = $col['pk'] === 1 ? ' PRIMARY KEY' : '';
            $newTableColumns[] = sprintf('"%s" %s%s%s', $name, $type, $notnull, $pk);
        }

        DB::statement('CREATE TABLE users_new ('.implode(', ', $newTableColumns).')');

        $columnNames = array_column($columns, 'name');
        $rows = DB::table('users')->get();
        foreach ($rows as $row) {
            $newId = (string) Str::uuid();
            $idMap[is_scalar($row->id) ? (string) $row->id : ''] = $newId;
            $rowArray = (array) $row;
            $rowArray['id'] = $newId;
            $values = [];
            foreach ($columnNames as $colName) {
                $values[] = $rowArray[$colName] ?? null;
            }

            $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));
            $sql = 'INSERT INTO users_new ("'.implode('", "', $columnNames).'") VALUES ('.$placeholders.')';
            DB::insert($sql, $values);
        }

        foreach ($idMap as $oldId => $newId) {
            DB::table('sessions')->where('user_id', (string) $oldId)->update(['user_id' => $newId]);
        }

        if (Schema::hasTable('personal_access_tokens')) {
            foreach ($idMap as $oldId => $newId) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->where('tokenable_id', (string) $oldId)
                    ->update(['tokenable_id' => $newId]);
            }
        }

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');

        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
        if (Schema::hasColumn('users', 'username')) {
            DB::statement('CREATE UNIQUE INDEX users_username_unique ON users (username)');
        }
    }

    public function down(): void {}
};
