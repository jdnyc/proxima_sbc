<?php

namespace Proxima\core;

use Api\Models\Media;
use Api\Models\Content;
use Api\Types\MediaType;
use Api\Types\OSType;

class Directory
{
    private static $mediaTypeDirectories = [
        MediaType::PROXY => 'Proxy',
        'catalog' => 'Catalog',
        MediaType::THUMBNAIL => 'Thumbnail',
        MediaType::ATTACH => 'Attach',
        'sns_thumb' => 'SNSThumbnail'
    ];

    /**
     * 미디어 유형별 폴더명 반환
     *
     * @param string $mediaType
     * @return string
     */
    public static function getMediaDirectory($mediaType)
    {
        return (self::mediaTypeDirectories[$mediaType] ?? null);
    }

    /**
     * 프록시 미디어 경로 조회(/rootDir/123/Proxy/abc.mp4라면 /rootDir/123를 반환함)
     *
     * @param int $content
     * @param bool $fullPath 전체 경로 여부
     * @param bool $withoutMediaDir 미디어 디렉터리 포함 여부
     * @return string
     */
    public static function getProxyDirPath($content, $fullPath = false, $withoutMediaDir = false)
    {
        /**
         * @var \Api\Models\Content $content
         */
        $proxyMedia = $content->getProxyMedia();
        $path = $proxyMedia->path;
        $dirPath = Path::getDirectoryPath($path, '/');

        if($withoutMediaDir) {
            $dirPath = Path::getDirectoryPath($dirPath, '/');
        }

        if($fullPath) {            
            $rootPath = self::getProxyRootPath($content);
            $dirPath = Path::join($rootPath, $dirPath);
        }

        return $dirPath;
    }

    /**
     * 프록시 루트 경로 반환
     * 
     * @param \Api\Models\Content $content
     * @return string
     */
    public static function getProxyRootPath($content)
    {
        $storage = $content->userContent->getLowresStorage();
        $rootPath = self::getServerStoragePath($storage);

        return $rootPath;
    }

    /**
     * 서버 OS에 맞는 경로 리턴
     *
     * @param \Api\Models\Storage $storage
     * @param \
     * @return void
     */
    public static function getServerStoragePath($storage, $osType = null)
    {
        $path = $storage->path;

        if($osType === null) {
            if (server_os() == OSType::LINUX) {
                $path = $storage->path_for_unix;
            } else if (strtoupper($storage->type) === 'NAS') {
                $path = $storage->path_for_win;
            }
        } else {
            switch ($osType) {
                case OSType::LINUX:
                    $path = $storage->path_for_unix;
                    break;
                case OSType::WIN:
                    if(!empty($storage->path_for_win) && strtoupper($storage->type) === 'NAS') {
                        $path = $storage->path_for_win;
                    }
                    break;
                case OSType::MAC:
                    $path = $storage->path_for_mac;
                    break;
                default:
                    
                    break;
            }
        }

        return $path;
    }

}
