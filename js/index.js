function makeRequest(data,callback,method="POST",url="core/rcon.php"){
    let xmlhttp=new XMLHttpRequest();
    xmlhttp.open(method,url);
    xmlhttp.onload=function() {
        if (this.readyState==4&&this.status==200) {
            callback(JSON.parse(this.responseText));
        }
    };
    let form=new FormData();
    for (let key in data){form.append(key,data[key]);}
    xmlhttp.send(form);
}
function get_players(sv) {
    makeRequest({get_players:sv},function(resp){
        document.getElementById("edamodal").innerHTML="<table class='table table table-bordered table-striped table-custom'><thead><tr><th scope='col'>#</th><th scope='col'>Имя</th><th scope='col'>Время на сервере</th><th scope='col'>Счёт</th></tr></thead><tbody id='sudalol'></tbody></table>"
        document.getElementById("staticBackdropLabel").innerHTML="Список игроков "+sv;
        var myModal=new bootstrap.Modal(document.getElementById("atakda"));
        myModal.toggle();
        for (var i=0,row;row=resp[i];i++) {
            row.Name=(row.Name!="")?row.Name:"Подключается...";
            document.getElementById("sudalol").innerHTML+="<th scope='row'>"+eval(i+1)+"</th><td>"+row.Name+"</td><td>"+row.TimeF+"</td><td>"+row.Frags+"</td></tr>";
        }
    })
}
function get_servers() {
    let rootel=document.getElementById("serverList");
    if (!rootel) return;
    makeRequest({get_servers:true},function(resp){
        for (let i=0,row;row=resp[i];i++) {
            let cardId = `server-${i}`;
            rootel.innerHTML += `
                <div class='card mb-3 hoverscale border-0 bggrad text-white' id='${cardId}' style='border-radius:20px; overflow: hidden;'>
                    <h3 class='fw-bold mt-1'>${row.sv_name}</h3>
                    <h5 id='${cardId}-map' style='margin-top: auto;'>карта: Загрузка...</h5>
                    <div class='btn-group btn-sm mt-1 input-block-level' role='group'>
                        <button type='button' class='btn btn-light border-dark fw-bold bg-white btn-sm' id='${cardId}-btn'>
                            Игроки: ...
                        </button>
                        <a type='button' href='steam://connect/${row.sv_ip}:${row.sv_port}' class='btn btn btn-light border-dark fw-bold bg-white btn-sm'>
                            <i class='bi bi-plugin'></i> Подключиться
                        </a>
                        <a type='button' class='btn btn btn-light border-dark fw-bold bg-white btn-sm' href='components/donate.php?sv=${row.sv_name}'>
                            <i class='bi bi-bag-heart'></i> Пожертвование
                        </a>
                    </div>
                </div>
            `;
            load_rcon_data(row.sv_name,cardId);
        }
    },"POST","core/api.php");
}
function load_rcon_data(sv_name,cardId){
    makeRequest({get_single_server:sv_name},function(resp){
        if (!resp||!resp.query) return;
        document.getElementById(`${cardId}-map`).textContent="карта: "+resp.query.map;
        let playerBtn=document.getElementById(`${cardId}-btn`);
        playerBtn.textContent=`Игроки: ${resp.query.players}/${resp.query.playersmax}`;
        playerBtn.setAttribute("onclick",`get_players('${sv_name}')`);
    });
}
document.addEventListener("DOMContentLoaded",get_servers())