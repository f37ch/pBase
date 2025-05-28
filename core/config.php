<?php
    $settings = [
        "steam_api_key"=>"2",//get at https://steamcommunity.com/dev/apikey
        "rcon"=>"",//rcon password to interact with your servers
        "api_key"=>"",//api key to make secure api calls from your servers to synch bans for exemple.
        "tinymce_apikey"=>"",//text editor api. Get at https://www.tiny.cloud/
        "access"=>[
            "76561198106529373" => ["global"=>1,"rcon"=>2,"notes"=>3,"servers"=>4,"storagemoderate"=>5],//replace to your steamid64
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
            "unactive_time"=> 2419200,//max unactive time
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
        //loading screen url: yourdomain/loading.php
        "loading_music"=>[//loading screen music.
            ["title"=>"SizzleBird - Memory","file"=>"music/Memory.ogg"],
            ["title"=>"SizzleBird - Elixir","file"=>"music/Elixir.ogg"],
            ["title"=>"NOFAL - Forest","file"=>"music/nofal.ogg"]
        ],
        "loading_imgs"=>[//images displays based on what server gamemode is set. You can use link insted if you want tho.
            "cinema"=>"img/etcinema_v103f.png",
            "unknwn"=>"img/unknwn.jpg"
        ],
        "loading_words"=>[//loading screen words.
            "Добро пожаловать!",
	        "Проверка на наличие нужных для комфортной игры аддонов...",
	        "Внимательно изучите правила сервера дабы избежать недопониманий и споров с администрацией."
        ],
        "loading_volume"=>2,//music volume, where 100 is max.
        "public_ip"=>"145.555.245.345",//useful for local rcon requests
        "dev_mode"=>true,
    ];
?>