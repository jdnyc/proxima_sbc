<?php

namespace Api\Support\Commands;

use Api\Models\Download;
use Api\Support\Commands\MigrationBase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

class DownloadMigration extends MigrationBase
{
    protected $commandName = 'mig:dw';
    protected $commandDescription = 'Migration download tables.';

    public function dropTables()
    {
        DB::schema()->dropIfExists('downloads');
    }

    public function migrate()
    {
        /**
         * 다운로드
         */
        DB::schema()->create('downloads', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('title')->comment('제목');
            $table->string('icon')->default('fa-circle')->comment('아이콘');
            $table->string('path')->comment('파일경로');
            $table->string('description')->nullable()->comment('설명');
            $table->boolean('published')->default(false)->comment('게시여부');
            $table->integer('show_order')->comment('정렬순서');
            
            $table->timestamps();
        });

        //DB::statement("ALTER TABLE downloads COMMENT='다운로드'");
        DB::statement("COMMENT ON TABLE downloads IS '다운로드'");       
    }

    public function seed()
    {
        $downloadItems = [
            [
                'title' => 'CMS 도움말',
                'icon' => 'fa-question-circle',
                'path' => '/install/cms_manual_v.1.0.pdf',
                'description' => 'CMS 도움말',
                'published' => true
            ], [
                'title' => 'CPS 도움말',
                'icon' => 'fa-question-circle',
                'path' => '/install/cps_manual_v.1.0.pdf',
                'description' => 'CPS 도움말',
                'published' => true
            ], [
                'title' => '부조 프로그램 도움말',
                'icon' => 'fa-question-circle',
                'path' => '/install/scr_manual_v.1.0.pdf',
                'description' => '부조 프로그램 도움말',
                'published' => true
            ], [
                'title' => '오디오 파일 서버 도움말',
                'icon' => 'fa-question-circle',
                'path' => '/install/afs_manual_v.1.0.pdf',
                'description' => '오디오 파일 서버 도움말',
                'published' => true
            ], [
                'title' => '크롬 브라우저 - 윈도우즈(32bit)',
                'icon' => 'fa-windows',
                'path' => '/install/ChromeStandaloneSetup32.exe',
                'description' => '크롬 브라우저 - 윈도우즈(32bit)',
                'published' => true
            ], [
                'title' => '크롬 브라우저 - 윈도우즈(64bit)',
                'icon' => 'fa-windows',
                'path' => '/install/ChromeStandaloneSetup64.exe',
                'description' => '크롬 브라우저 - 윈도우즈(64bit)',
                'published' => true
            ], [
                'title' => '크로미움 브라우저 - MAC(10.9.5)',
                'icon' => 'fa-apple',
                'path' => '/install/Chromium_macOS_10.9.5.zip',
                'description' => '크로미움 브라우저 - MAC(10.9.5)',
                'published' => true
            ], [
                'title' => '크로미움 브라우저 - MAC(10.10이상)',
                'icon' => 'fa-apple',
                'path' => '/install/Chromium_macOS_10.10.zip',
                'description' => '크로미움 브라우저 - MAC(10.10이상)',
                'published' => true
            ], [
                'title' => 'CPS 프로그램',
                'icon' => 'fa-microphone',
                'path' => '/install/ktv_cps_installer.exe',
                'description' => 'CPS 프로그램',
                'published' => true
            ]
        ];

        $i = 0;
        foreach($downloadItems as $item) {
            $i++;
            $item['show_order'] = $i;
            Download::create($item);
        }
    }
}
