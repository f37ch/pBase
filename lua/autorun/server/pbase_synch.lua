--user activity synch
local apikey=""//api_key from components/config.php
local host="exemple.com"//your site adress (with no schema and slashes)
hook.Add("PlayerInitialSpawn","SynhWithWeb",function(ply)
	http.Post("http://"..host.."/core/api.php?api_key="..apikey.."&synch_user="..ply:SteamID64(),{},
	function(body)
		local res = util.JSONToTable(body)
			if (type(res) == 'table' && res.success) then
				return
			else
				print("Ошибка синхронизации игрока с web, ответ: "..body)
			end
		end,
	function(failed)
		print("Ошибка синхронизации игрока с web, ответ: "..failed)
	end)
end)
--bans logging exemple
local function synhthis(offender,expiration,reason,admin_steamid,typee,edit,unban)
	if (edit==true) then edit=tostring(edit) end
	if (unban==true) then unban=tostring(unban) end
	if admin_steamid then 
		if admin_steamid=="Console" then
			admin_steamid=nil
		else
			admin_steamid=util.SteamIDTo64(admin_steamid) 
		end
	end
	http.Post("http://"..host.."/core/api.php?api_key="..apikey.."&synch_ban="..util.SteamIDTo64(offender),{reason=reason,admin=admin_steamid,expires=tostring(expiration),edited=edit,unban=unban,type=typee,server="Cinema"},
	function(body)
	local res = util.JSONToTable(body)
		if (type(res) == 'table' && res.success) then
			print("Бан успешно синхронизирован с web сервером.")
		else
			print("Ошибка синхронизации бана с web, ответ: "..body)
		end
	end,
    function(failed)
		print("Ошибка синхронизации бана с web, ответ: "..failed)
	end)
end

hook.Add("SAM.BannedPlayer","SynhBanWithWeb",function(ply,unban_date,reason,admin_steamid)
	synhthis(ply:SteamID(),unban_date,reason,admin_steamid,"ban")
end)
hook.Add("SAM.BannedSteamID","SynhBanWithWeb",function(sid,unban_date,reason,admin_steamid)
	synhthis(sid,unban_date,reason,admin_steamid,"ban")
end)
hook.Add("SAM.EditedBan","SynhBanWithWeb",function(sid,unban_date,reason)
	synhthis(sid,unban_date,reason,nil,"ban",true)
end)
hook.Add("SAM.UnbannedSteamID","SynhBanWithWeb",function(sid,admin_steamid,automatic)
	if not automatic then --automatic unban, does not need to send.
		synhthis(sid,os.time(),nil,nil,"ban",true,true)
	end
end)

hook.Add("SAM.BlockPlayer","SynhBlockWithWeb",function(ply,unblock_date,reason,admin_steamid)
	synhthis(ply:SteamID(),unblock_date,reason,admin_steamid,"block")
end)
hook.Add("SAM.EditedBlock","SynhBlockWithWeb",function(sid,reason,unblock_date)
	synhthis(sid,unblock_date,reason,nil,"block",true)
end)
hook.Add("SAM.UnblockPlayer","SynhBlockWithWeb",function(sid,admin_steamid,automatic)
	if not automatic then --automatic unban, does not need to send.
		synhthis(sid,os.time(),nil,nil,"block",true,true)
	end
end)

hook.Add("SAM.GagPlayer","SynhGagWithWeb",function(ply,ungag_date,reason,admin_steamid)
	synhthis(ply:SteamID(),ungag_date,reason,admin_steamid,"gag")
end)
hook.Add("SAM.EditedGag","SynhGagWithWeb",function(sid,reason,ungag_date)
	synhthis(sid,ungag_date,reason,nil,"gag",true)
end)
hook.Add("SAM.UngagPlayer","SynhGagWithWeb",function(sid,admin_steamid,automatic)
	if not automatic then --automatic unban, does not need to send.
		synhthis(sid,os.time(),nil,nil,"gag",true,true)
	end
end)

hook.Add("SAM.MutePlayer","SynhMuteWithWeb",function(ply,unmute_date,reason,admin_steamid)
	synhthis(ply:SteamID(),unmute_date,reason,admin_steamid,"mute")
end)
hook.Add("SAM.EditedMute","SynhMuteWithWeb",function(sid,reason,unmute_date)
	synhthis(sid,unmute_date,reason,nil,"mute",true)
end)
hook.Add("SAM.UnmutePlayer","SynhMuteWithWeb",function(sid,admin_steamid,automatic)
	if not automatic then --automatic unban, does not need to send.
		synhthis(sid,os.time(),nil,nil,"mute",true,true)
	end
end)
