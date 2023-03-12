<?php
    class Cache {
        private static string $cacheroot="..".DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR;
        public static function get($name) {
            $file=self::$cacheroot.$name;
            if (file_exists($file)){
                $file=json_decode(file_get_contents($file),true);
                if ($file["time"]>time()){
                    return $file["value"];
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
        public static function put($name,$val,$time=15) {
            if(!is_dir(self::$cacheroot)) {
                mkdir(self::$cacheroot);
            }
            $file=fopen(self::$cacheroot.$name,"w");
            $array=json_encode(array("value"=>$val,"time"=>time()+$time));
            fwrite($file,$array);
            fclose($file);
        }
        public static function flush($name) {
            unlink(self::$cacheroot.$name);
        }
    }
?>