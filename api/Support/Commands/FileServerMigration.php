<?php

namespace Api\Support\Commands;

use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

class FileServerMigration extends MigrationBase
{
    protected $commandName = 'mig:fs';
    protected $commandDescription = 'Migration file server tables.';

    public function dropTables()
    {
        DB::schema()->dropIfExists('fs_job_logs');
        DB::schema()->dropIfExists('fs_jobs');
        DB::schema()->dropIfExists('fs_job_history');
        DB::schema()->dropIfExists('fs_file_servers');
    }

    public function migrate()
    {
        /**
         * 파일서버 작업
         */
        DB::schema()->create('fs_jobs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('type')->comment('작업유형(upload, download)');
            $table->string('file_path', 260)->nullable()->comment('파일경로');
            $table->bigInteger('filesize')->nullable()->comment('파일크기');
            $table->string('status')->default('queued')->comment('작업상태(queued, standby, working, finished, aborted, failed)');
            $table->integer('progress')->default(0)->comment('진행률(1~100)');
            $table->integer('priority')->default(3)->comment('우선순위(0,1,2,3,5,6,7) 클수록 높음');
            $table->timestamp('started_at')->nullable()->comment('시작일시');
            $table->timestamp('finished_at')->nullable()->comment('종료일시');
            $table->string('user_id')->nullable()->comment('사용자');
            $table->unsignedInteger('file_server_id')->nullable()->comment('파일 서버');
            $table->string('client_ip')->nullable()->comment('클라이언트 아이피');
            $table->string('client_ver')->nullable()->comment('클라이언트 버전');
            $table->string('client_os')->nullable()->comment('클라이언트 OS(mac,win,linux)');
            $table->string('notify_status')->nullable()->comment('작업 정보 통지상태(working, finished, failed)');
            $table->timestamps();
            $table->text('metadata')->nullable()->comment('메타데이터');

        });

        //DB::statement("ALTER TABLE fs_job_logs COMMENT='파일서버 작업'");
        DB::statement("COMMENT ON TABLE fs_jobs IS '파일서버 작업'");

        /**
         * 파일서버 작업 이력
         */
        DB::schema()->create('fs_job_history', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->unsignedBigInteger('job_id')->comment('작업아이디');
            $table->string('action')->comment('액션(priority, status)');
            $table->string('user_id')->nullable()->comment('액션 사용자');
            $table->json('current_status')->nullable()->comment('현재 상태(json)');
            $table->json('new_status')->nullable()->comment('새로운 상태(json)');
            $table->timestamps();
        });

        //DB::statement("ALTER TABLE fs_job_history COMMENT='파일서버 작업 이력'");
        DB::statement("COMMENT ON TABLE fs_job_history IS '파일서버 작업 이력'");

        /**
         * 파일서버 작업 로그
         */
        DB::schema()->create('fs_job_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->unsignedBigInteger('job_id')->comment('작업아이디');
            $table->string('type')->default('info')->comment('로그유형(info, warn, error, debug)');
            $table->string('message', 4000)->nullable()->comment('로그 메세지');
            $table->timestamps();
        });

        //DB::statement("ALTER TABLE fs_job_logs COMMENT='파일서버 작업'");
        DB::statement("COMMENT ON TABLE fs_job_logs IS '파일서버 작업'");

        /**
         * 파일서버
         */
        DB::schema()->create('fs_file_servers', function (Blueprint $table) {
            $table->increments('id')->comment('PK');
            $table->string('name')->comment('이름');
            $table->string('description')->comment('설명');
            $table->string('type')->default('sftp')->comment('서버유형(sftp, https)');
            $table->string('url', 1000)->nullable()->comment('웹경로(https의 경우)');
            $table->string('host')->nullable()->comment('호스트(sftp의 경우)');
            $table->integer('port')->nullable()->comment('포트(sftp의 경우)');
            $table->string('username')->nullable()->comment('로그인 사용자(sftp의 경우)');
            $table->string('pw')->nullable()->comment('로그인 비밀번호(sftp의 경우)');
            $table->string('remote_dir')->nullable()->default('/')->comment('원격 디렉터리');
            $table->timestamps();
        });

        //DB::statement("ALTER TABLE fs_file_servers COMMENT='파일서버'");
        DB::statement("COMMENT ON TABLE fs_file_servers IS '파일서버'");
    }

    public function seed()
    {
        // File server
        $fileServers = [
            [
                'type' => 'sftp',
                'name' => 'upload',
                'description' => '업로드 서버',
                'host' => '192.168.0.103',
                'port' => 22,
                'username' => 'upload',
                'pw' => 'upload',
            ],
            [
                'type' => 'sftp',
                'name' => 'download',
                'description' => '다운로드 서버',
                'host' => '192.168.0.103',
                'port' => 22,
                'username' => 'download',
                'pw' => 'download',
            ],
        ];

        foreach ($fileServers as $fileServer) {
            \Api\Models\Fs\FileServer::create($fileServer);
        }
    }
}
