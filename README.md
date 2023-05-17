# pBase
Community space that contains filehosting for players, bans and autodonate with modules included. Made for source server projects (used by me on my Garry's Mod project).

This project using [PHP-Source-Query](https://github.com/xPaw/PHP-Source-Query) by xPaw | [SteamAuthentication](https://github.com/SmItH197/SteamAuthentication) by SmItH197
### Installation ###
1. Place the contents of this repo except lua folder into your site root directory.
2. Configure your database with php settings and apache or nginx premissions to allow write/read files in root and all subdirectories.
3. Setup config.php file located in `core` folder and autodonate modules located at `modules/autodonate`.
3. [If you using it for gmod] Create a new addon folder within your `garrysmod/addons/` directory and place lua folder into it (you prob need to modify lua code based on your server admin mod and needs).
