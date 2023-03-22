<?php

namespace Proxima\core;

class Path
{
    /**
     * 복수의 파일 경로를 합친다.
     */
    public static function join()
    {
        $path = '';
        $arguments = func_get_args();
        $args = array();
        foreach ($arguments as $a) {
            if ($a !== '') {
                $args[] = self::fixSeparator($a);
            }
        } //Removes the empty elements

        $arg_count = count($args);
        for ($i = 0; $i < $arg_count; $i++) {
            $folder = $args[$i];

            if ($i != 0 and $folder[0] == DIRECTORY_SEPARATOR) {
                $folder = substr($folder, 1);
            } //Remove the first char if it is a '/' - and its not in the first argument
            if ($i != $arg_count - 1 and substr($folder, -1) == DIRECTORY_SEPARATOR) {
                $folder = substr($folder, 0, -1);
            } //Remove the last char - if its not in the last argument

            $path .= $folder;
            if ($i != $arg_count - 1) {
                $path .= DIRECTORY_SEPARATOR;
            } //Add the '/' if its not the last element.
        }
        return $path;
    }

    public static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public static function getFilename($path, $separator = DIRECTORY_SEPARATOR)
    {
        $paths = explode($separator, $path);
        return array_pop($paths);
    }

    public static function getFilenameWithoutExtension($path)
    {
        $filename = self::getFilename($path);
        $filenameParts = explode($filename, '.');
        if (empty($filenameParts) || count($filenameParts) < 2) {
            return $filename;
        }
        return $filenameParts[0];
    }

    public static function getDirectoryPath($path, $separator = DIRECTORY_SEPARATOR)
    {
        $paths = explode($separator, $path);
        if (empty($paths)) {
            return $path;
        }
        array_pop($paths);
        return implode($separator, $paths);
    }

    public static function fixSeparator($path, $separator = DIRECTORY_SEPARATOR)
    {
        $path = str_replace('/', $separator, $path);
        $path = str_replace('\\', $separator, $path);
        return $path;

    }
}
