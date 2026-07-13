<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pkm_notification_reads')->whereNotExists(function ($query): void {
            $query->selectRaw('1')->from('users')->whereColumn('users.id', 'pkm_notification_reads.user_id');
        })->delete();

        if (DB::getDriverName() === 'mysql') {
            $this->alignMysqlForeignKeyColumns();
        }

        Schema::table('pkm_notification_reads', function (Blueprint $table): void {
            $table->foreign('user_id', 'pkm_notification_reads_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pkm_notification_reads', function (Blueprint $table): void {
            $table->dropForeign('pkm_notification_reads_user_fk');
        });
    }

    private function alignMysqlForeignKeyColumns(): void
    {
        $database = DB::getDatabaseName();
        $userIdColumn = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'users')
            ->where('COLUMN_NAME', 'id')
            ->first(['COLUMN_TYPE']);

        if (! $userIdColumn || ! preg_match('/^(?:tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(?: unsigned)?$/i', (string) $userIdColumn->COLUMN_TYPE)) {
            throw new RuntimeException('Tipe kolom users.id tidak dapat digunakan untuk foreign key PKM notification reads.');
        }

        $engines = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('TABLE_NAME', ['users', 'pkm_notification_reads'])
            ->pluck('ENGINE', 'TABLE_NAME');

        if (strcasecmp((string) ($engines['users'] ?? ''), 'InnoDB') !== 0) {
            DB::statement('ALTER TABLE `users` ENGINE = InnoDB');
        }

        if (strcasecmp((string) ($engines['pkm_notification_reads'] ?? ''), 'InnoDB') !== 0) {
            DB::statement('ALTER TABLE `pkm_notification_reads` ENGINE = InnoDB');
        }

        DB::statement(sprintf(
            'ALTER TABLE `pkm_notification_reads` MODIFY `user_id` %s NOT NULL',
            $userIdColumn->COLUMN_TYPE,
        ));
    }
};
