<?php

namespace Api\Support\Commands;

use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
class DefaultMigration extends MigrationBase
{
    protected $commandName = 'mig:default';
    protected $commandDescription = 'Migration default tables.';

    public function dropTables()
    {
        DB::schema()->dropIfExists('sessions');
        DB::schema()->dropIfExists('api_logs');
    }

    public function migrate()
    {
        /**
         * 세션
         */
        DB::schema()->create('sessions', function (Blueprint $table) {
            $table->string('id')->unique()->comment('세션아이디');
            $table->string('user_id', 50)->nullable()->comment('사용자 아이디');
            $table->text('payload')->comment('세션 데이터');
            $table->integer('last_activity')->comment('마지막 활동시간');

            $table->index('id');
            $table->index('user_id');
        });

        /**
         * API 로그
         */
        DB::schema()->create('api_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('path', 500)->comment('API 매서드 및 경로');
            $table->string('query', 500)->comment('API 쿼리');
            $table->text('payload')->comment('API Payload');
            $table->string('user_id', 50)->comment('사용자 아이디');
            $table->string('status', 1)->default('A')->comment('처리 상태(A: 접수, S: 성공, F: 실패)');
            $table->text('errors')->nullable()->comment('오류 내용');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function seed()
    {
    }
}
