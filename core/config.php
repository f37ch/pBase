<?php
    $settings = [
        "steam_api_key"=>"2",//get at https://steamcommunity.com/dev/apikey
        "rcon"=>"",//rcon password to interact with your servers
        "api_key"=>"",//api key to make secure api calls from your servers to synch bans for exemple.
        "tinymce_apikey"=>"",//text editor api. Get at https://www.tiny.cloud/
        "access"=>[
            "76561198106529373" => ["global"=>1,"rcon"=>2,"notes"=>3,"servers"=>4],//replace to your steamid64
        ],
        "ranks"=>[
            "76561198106529373" => "Horse",
        ],
        "db"=>[//database settings
            "host" => "localhost",
	        "port" => 3306,
            "username" => "root",
            "database" => "pBase",
            "password" => ""
        ],
        "storage"=>[
            "require_activity"=> true,//require server activity to use storage
            "autodelete"=> true,//autodelete files of unactive users
            "filesize_limit" => 10000000,//10 MB (size in bytes)
            "storage_limit" => 50000000,//50 MB Limit storage size per user
            "storage_maxfiles" => 50,//50 files per one user
            "allowed_extensions" => ["png","jpeg","jpg","gif","mp4","webm","txt","obj","mdl","zip"]
        ],
        "bans_typeicons"=>[
            "ban"=>"<i class='bi bi-dash-circle'></i>",
            "gag"=>"<i class='bi bi-mic-mute-fill'></i>",
            "mute"=>"<i class='bi bi-dash-square'></i>",
            "block"=>"<i class='bi bi-camera-video-off'></i>"
        ],
        "dev_mode"=>true
    ];
?>