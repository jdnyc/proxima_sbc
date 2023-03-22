<?php

namespace Api\Support\Commands;

use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Input\InputOption;

class CustomTableMigration extends MigrationBase
{
    protected $commandName = 'custom:table';
    protected $commandDescription = 'create tables.';

    protected function configure()
    {
        parent::configure();
        $this->addOption('table', 't', InputOption::VALUE_REQUIRED, '마이그레이션 할 테이블');
    }

    public function dropTables()
    {        
        $tableOption = $this->options['table'] ?? null;
        $tables = [
            'files'
            //'attach_files'
        ];

        foreach($tables as $table) {
            if (!empty($tableOption) && $table !== $tableOption) {
                continue;
            }
            DB::schema()->dropIfExists($table);
        }
    }

    public function migrate()
    {
        $tableOption = $this->options['table'] ?? null;
  
        if (empty($tableOption) || $tableOption === 'files') {
            
            DB::schema()->create('files', function (Blueprint $table) {
                $table->increments('id')->comment('PK');
                $table->string('file_root', 255)->nullable()->comment('파일볼륨');
                $table->string('file_path', 255)->comment('파일경로');
                $table->string('file_name', 255)->comment('파일명');
                $table->string('file_ext', 255)->nullable()->comment('파일확장자');
                $table->string('ori_file_name', 255)->nullable()->comment('원본파일명');
                $table->unsignedInteger('file_size')->default(0)->comment('파일사이즈');
                $table->string('status', 50)->default('queue')->comment('파일 입수 상태 taskstatus');
                $table->timestamp('expired_at')->nullable()->comment('만료일시');

                $table->unsignedInteger('storage_id')->nullable()->default(0)->comment('스토리지ID');
                $table->unsignedInteger('media_id')->nullable()->default(0)->comment('미디어ID');
                $table->unsignedInteger('content_id')->nullable()->default(0)->comment('콘텐츠ID');

                // $table->boolean('sns_account')->default(false)->comment('플랫폼 활성화 여부');
                // $table->timestamp('sns_activated_at')->nullable()->comment('플랫폼별 계정 활성화 시간');
                // $table->timestamp('sns_opened_at')->nullable()->comment('플랫폼별 계정/채널 생성일 시간');
                // $table->text('sns_account_info')->nullable()->comment('플랫폼별 계정정보');
                // $table->string('web_url')->nullable()->comment('플랫폼 채널/계정/페이지 접근 URL');
                // $table->unsignedInteger('category_id')->nullable()->comment('유튜브 기본 카테고리 ID');
                // $table->string('tag')->nullable()->comment('유튜브 기본 태그');
                // $table->text('template')->nullable()->comment('플랫폼별 내용 템플릿');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('storage_id')->references('storage_id')->on('bc_storage');
                $table->foreign('media_id')->references('media_id')->on('bc_media');
                $table->foreign('content_id')->references('content_id')->on('bc_content');

                $table->index('content_id');
                $table->index('media_id');
            });

            //DB::statement("ALTER TABLE platform_settings COMMENT='SNS 플랫폼 설정'");
            DB::statement("COMMENT ON TABLE files IS '파일목록'");
        }

        if (empty($tableOption) || $tableOption === 'map_files') {
            DB::schema()->create('map_files', function (Blueprint $table) {
                $table->increments('id')->comment('PK');
                $table->string('user_id', 320)->nullable()->comment('사용자 ID');
                $table->string('file_key', 1000)->comment('고유 파일명');
                $table->string('remote_ip', 100)->comment('리모트 IP');
                $table->string('channel', 100)->nullable()->comment('채널명');
                $table->unsignedInteger('content_id')->nullable()->comment('콘텐츠 ID');
                $table->unsignedInteger('task_id')->nullable()->comment('작업 ID');             
                $table->timestamps();
                $table->softDeletes();
            });

            //DB::statement("ALTER TABLE platform_settings COMMENT='SNS 플랫폼 설정'");
            DB::statement("COMMENT ON TABLE map_files IS '플러그인 파일 매핑정보'");
        }
    }

    public function seed()
    {
        $tableOption = $this->options['table'] ?? null;

        // if (empty($tableOption) || $tableOption === 'channels') {
        //     // Channel
        //     $channels = $this->loadSeedData('channels.json');
        //     foreach ($channels as $channel) {
        //         \Api\Models\Social\Channel::create($channel);
        //     }
        // }
    }
}
