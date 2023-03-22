<?php

namespace Api\Support\Commands;

use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Input\InputOption;

class SocialMigration extends MigrationBase
{
    protected $commandName = 'mig:social';
    protected $commandDescription = 'Migration social tables.';

    protected function configure()
    {
        parent::configure();
        $this->addOption('table', 't', InputOption::VALUE_REQUIRED, '마이그레이션 할 테이블');
    }

    public function dropTables()
    {
        $tableOption = $this->options['table'] ?? null;
        $tables = [
            'platform_settings',
            'categories',
            'sns_posts',
            'platforms',
            'channels',
            'api_jobs',
            //'attach_files'
        ];

        foreach ($tables as $table) {
            if (!empty($tableOption) && $table !== $tableOption) {
                continue;
            }
            DB::schema()->dropIfExists($table);
        }
    }

    public function migrate()
    {
        $tableOption = $this->options['table'] ?? null;
        if (empty($tableOption) || $tableOption === 'platforms') {
            /**
             * 플랫폼
             */
            DB::schema()->create('platforms', function (Blueprint $table) {
                $table->increments('id')->comment('PK');
                $table->string('name', 50)->comment('플랫폼명(종류)');
                $table->string('slug')->comment('플랫폼 별칭');
                $table->timestamps();
                $table->softDeletes();
            });

            //DB::statement("ALTER TABLE platforms COMMENT='SNS 플랫폼'");
            DB::statement("COMMENT ON TABLE platforms IS 'SNS 플랫폼'");
        }

        if (empty($tableOption) || $tableOption === 'channels') {
            /**
             * 채널
             */
            DB::schema()->create('channels', function (Blueprint $table) {

                $table->increments('id');
                $table->string('name', 50)->nullable()->comment('채널명');
                $table->boolean('active')->default(true)->comment('채널활성화');
                $table->string('logo_url')->nullable()->comment('Logo URL');
                $table->timestamps();
                $table->softDeletes();
            });

            //DB::statement("ALTER TABLE channels COMMENT='SNS 채널'");
            DB::statement("COMMENT ON TABLE channels IS 'SNS 채널'");
        }

        if (empty($tableOption) || $tableOption === 'categories') {
            /**
             * SNS 카테고리
             */
            DB::schema()->create('categories', function (Blueprint $table) {
                $table->increments('id')->comment('PK');
                $table->unsignedInteger('platform_id')->comment('플랫폼ID');
                $table->string('name', 50)->comment('카테고리명');
                $table->string('code')->comment('카테고리 코드값');
                $table->timestamps();
            });

            //DB::statement("ALTER TABLE categories COMMENT='유튜브 카테고리'");
            DB::statement("COMMENT ON TABLE categories IS '유튜브 카테고리'");
        }

        if (empty($tableOption) || $tableOption === 'platform_settings') {
            /**
             * 플랫폼 설정
             */
            DB::schema()->create('platform_settings', function (Blueprint $table) {
                $table->increments('id')->comment('PK');
                $table->unsignedInteger('channel_id')->comment('채널ID');
                $table->unsignedInteger('platform_id')->comment('플랫폼ID');
                $table->boolean('sns_account')->default(false)->comment('플랫폼 활성화 여부');
                $table->timestamp('sns_activated_at')->nullable()->comment('플랫폼별 계정 활성화 시간');
                $table->timestamp('sns_opened_at')->nullable()->comment('플랫폼별 계정/채널 생성일 시간');
                $table->text('sns_account_info')->nullable()->comment('플랫폼별 계정정보');
                $table->string('web_url')->nullable()->comment('플랫폼 채널/계정/페이지 접근 URL');
                $table->unsignedInteger('category_id')->nullable()->comment('유튜브 기본 카테고리 ID');
                $table->string('tag')->nullable()->comment('유튜브 기본 태그');
                $table->text('template')->nullable()->comment('플랫폼별 내용 템플릿');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('channel_id')->references('id')->on('channels');
                $table->foreign('platform_id')->references('id')->on('platforms');
                $table->foreign('category_id')->references('id')->on('categories');
            });

            //DB::statement("ALTER TABLE platform_settings COMMENT='SNS 플랫폼 설정'");
            DB::statement("COMMENT ON TABLE platform_settings IS 'SNS 플랫폼 설정'");
        }

        if (empty($tableOption) || $tableOption === 'sns_posts') {
            /**
             * SNS 게시
             */
            DB::schema()->create('sns_posts', function (Blueprint $table) {
                $table->bigIncrements('id')->comment('PK');
                $table->unsignedBigInteger('content_id')->comment('콘텐츠ID');
                $table->unsignedBigInteger('media_id')->nullable()->comment('게시 미디어ID');
                $table->unsignedBigInteger('thumb_media_id')->nullable()->comment('썸네일 미디어ID');
                $table->unsignedInteger('channel_id')->comment('채널ID');
                $table->unsignedInteger('platform_id')->comment('플랫폼ID');
                $table->string('post_id')->nullable()->comment('SNS 게시물 ID');
                $table->string('post_url')->nullable()->comment('SNS 게시물 URL');
                $table->string('extra_id')->nullable()->comment('플랫폼에 따라 추가적으로 필요한 id');
                $table->string('media_url')->nullable()->comment('SNS 게시물 영상 URL');
                $table->string('thumb_url', 1000)->nullable()->comment('SNS 게시물 Thumb URL');
                $table->unsignedInteger('duration')->nullable()->comment('영상재생길이');
                $table->unsignedInteger('category_id')->nullable()->comment('카테고리ID');
                $table->string('title')->nullable()->comment('제목');
                $table->text('description')->nullable()->comment('설명');
                $table->text('tag')->nullable()->comment('태그');
                $table->string('media_type')->nullable()->comment('미디어타입(video,album)');
                $table->string('status')->default('queued')->comment('게시상태(queued, tc_working, tc_finished, uploading, uploaded, published, failed)');
                $table->string('user_id')->nullable()->comment('게시자(사용자)');
                $table->string('privacy_status')->comment('공개여부:공개/비공개/예약(public, private, book, sns_book)');
                $table->timestamp('published_at')->nullable()->comment('게시일시');
                $table->timestamp('booked_at')->nullable()->comment('예약게시일시');
                $table->unsignedInteger('task_id')->nullable()->comment('변환 작업 아이디');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('content_id')->references('content_id')->on('bc_content');
                $table->foreign('platform_id')->references('id')->on('platforms');
                $table->foreign('channel_id')->references('id')->on('channels');
                $table->foreign('task_id')->references('task_id')->on('bc_task');

                $table->index('content_id');
                $table->index('media_id');
                $table->index('channel_id');
                $table->index('platform_id');
                $table->index('task_id');
                $table->index('user_id');
                $table->index(['content_id', 'user_id']);
            });

            //DB::schema()->statement("ALTER TABLE sns_posts COMMENT='SNS 게시물'");
            DB::statement("COMMENT ON TABLE sns_posts IS 'SNS 게시물'");
        }

        if (empty($tableOption) || $tableOption === 'api_jobs') {
            /**
             * API 작업
             */
            DB::schema()->create('api_jobs', function (Blueprint $table) {
                $table->bigIncrements('id')->comment('pk');
                $table->unsignedInteger('owner_id')->nullable()->comment('API 작업 주체 아이디');
                $table->string('type')->nullable()->comment('작업 유형');
                $table->string('url')->nullable()->comment('API URL(http://10.10.10.123:8080/api/v1/users/1)');
                $table->string('method')->nullable()->comment('API Method(post, put, get, delete, patch)');
                $table->json('headers')->nullable()->comment('헤더 정보');
                $table->json('payload')->nullable()->comment('작업 정보');
                $table->unsignedInteger('priority')->default(5)->comment('우선순위(0~9)클수록 높음');
                $table->unsignedInteger('progress')->default(0)->comment('진행율(1~100)');
                $table->string('status')->nullable()->comment('작업상태(queued:대기,assigning:할당중,working:진행중,finished:완료:,failed:실패,canceling:취소중,canceled:취소됨');
                $table->json('result')->nullable()->comment('작업 결과');
                $table->dateTime('started_at')->nullable()->comment('시작일시');
                $table->dateTime('finished_at')->nullable()->comment('종료일시');
                $table->string('api_server_ip')->nullable()->comment('API 서버 아이피');
                $table->text('errors')->nullable()->comment('에러 메세지');
                $table->unsignedInteger('retry_count')->default(0)->commend('재시도 횟수');
                $table->timestamps();

                $table->index('type');
                $table->index('status');
                $table->index('started_at');
                $table->index('finished_at');
            });

            //DB::statement("ALTER TABLE api_jobs COMMENT='SNS 작업 큐'");
            DB::statement("COMMENT ON TABLE api_jobs IS 'SNS 작업 큐'");
        }

        if (empty($tableOption) || $tableOption === 'attach_files') {
            /**
             * 첨부파일
             */
            // DB::schema()->create('attach_files', function (Blueprint $table) {
            //     $table->bigIncrements('id')->comment('파일고유ID');
            //     $table->string('filepath')->comment('파일저장경로');
            //     $table->unsignedInteger('filesize')->comment('파일사이즈');
            //     $table->string('org_name')->nullable()->comment('원본파일명');
            //     $table->timestamps();
            //     $table->softDeletes();
            // });

            //DB::statement("ALTER TABLE attach_files COMMENT='첨부 파일'");
            //DB::statement("COMMENT ON TABLE attach_files IS '첨부 파일'");
        }
    }

    public function seed()
    {
        $tableOption = $this->options['table'] ?? null;

        if (empty($tableOption) || $tableOption === 'channels') {
            // Channel
            $channels = $this->loadSeedData('channels.json');
            foreach ($channels as $channel) {
                \Api\Models\Social\Channel::create($channel);
            }
        }

        if (empty($tableOption) || $tableOption === 'platforms') {
            // Platform
            $platforms = $this->loadSeedData('platforms.json');
            foreach ($platforms as $platform) {
                \Api\Models\Social\Platform::create($platform);
            }
        }

        if (empty($tableOption) || $tableOption === 'categories') {
            // Category
            $categories = $this->loadSeedData('categories.json');
            foreach ($categories as $category) {
                \Api\Models\Social\Category::create($category);
            }
        }

        if (empty($tableOption) || $tableOption === 'platform_settings') {
            // Platform Settings
            $settings = $this->loadSeedData('platform_settings.json');
            foreach ($settings as $setting) {
                $setting['sns_account_info'] = $setting['sns_account_info'];
                \Api\Models\Social\PlatformSettings::create($setting);
            }
        }
    }
}
