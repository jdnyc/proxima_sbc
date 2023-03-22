<?php

namespace Api\Support\Commands;

use Api\Models\ArchiveServer;
use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

class ArchiveServerMigration extends MigrationBase
{
    protected $commandName = 'db:archive';
    protected $commandDescription = 'Migration archive server tables.';

    public function dropTables()
    {
        //DB::schema()->dropIfExists('archive_servers');
    }

    public function migrate()
    {
        /**
         * 파일서버 작업
         */
        DB::schema()->create('archive_servers', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('type')->comment('서버유형(main, backup)');
            $table->string('is_active', 1)->nullable()->comment('active여부(1,0)');            
            $table->string('server_ip')->nullable()->comment('클라이언트 아이피');
            $table->timestamp('access_at')->nullable()->comment('접근일시');
            $table->timestamps();
        });

        //DB::statement("ALTER TABLE fs_job_logs COMMENT='파일서버 작업'");
        DB::statement("COMMENT ON TABLE archive_servers IS '디바커넥터 관리'");
    }

    public function seed()
    {
        $servers = [
            [
                'type' => 'main',
                'is_active' => '1',
                'server_ip' => '10.10.50.170'
            ],
            [
                'type' => 'backup',
                'is_active' => '0',
                'server_ip' => '10.10.50.171'
            ],
        ];

        foreach ($servers as $server) {
            \Api\Models\ArchiveServer::create($server);
        }
    }
}
