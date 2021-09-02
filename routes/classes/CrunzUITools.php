<?php

declare(strict_types=1);

namespace CrunzUI\Tools;

class CrunzUITools{

    public static function taskDirectoryRotator($base_path) {

        $dir_rotator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base_path));

        $aPATH = [];
        if(!empty($dir_rotator)){
            foreach ($dir_rotator as $path) {

                if (!$path->isDir()){
                    continue;
                }

                $path = (string)$path;
                $path = str_replace(['.','/.','/.','/..', $base_path], '', $path);

                if($path != '/'){
                    $path = rtrim($path, "/");
                }

                if(!in_array($path, $aPATH)){
                    $aPATH[] = $path;
                }
            }
        }

        return $aPATH;
    }
}
