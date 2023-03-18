function get_players(sv) {
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("get_players",sv);
    xmlhttp.open("POST","core/rcon.php");
    xmlhttp.onload = function() {
        document.getElementById("edamodal").innerHTML="<table class='table table table-bordered table-striped table-custom'><thead><tr><th scope='col'>#</th><th scope='col'>Имя</th><th scope='col'>Время на сервере</th><th scope='col'>Счёт</th></tr></thead><tbody id='sudalol'></tbody></table>"
        document.getElementById("sudalol").innerHTML="";
        document.getElementById("staticBackdropLabel").innerHTML="Список игроков "+sv;
        if (this.readyState == 4 && this.status == 200) {
            var myModal = new bootstrap.Modal(document.getElementById("atakda"));
            myModal.toggle();
            let da=JSON.parse(this.responseText)
            for (var i = 0, row; row = da[i]; i++) {
                row.Name = (row.Name!="") ? row.Name : "Подключается...";
                document.getElementById("sudalol").innerHTML=document.getElementById("sudalol").innerHTML+"<th scope='row'>"+eval(i+1)+"</th><td>"+row.Name+"</td><td>"+row.TimeF+"</td><td>"+row.Frags+"</td></tr>";
            }
        }
    }
    xmlhttp.send(form);
}
function get_servers() {
    let rootel=document.getElementById("serverList");
    if (!rootel){return};
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("get_servers","");
    xmlhttp.open("POST","core/rcon.php");
    xmlhttp.onload = function() {
        if (this.readyState == 4 && this.status == 200) {
            let da=JSON.parse(xmlhttp.responseText)
            if (typeof variable === 'undefined') {
                for (var i = 0, row; row = da[i]; i++) {
                    row.query=row.query??{"map":"timeout","players":"err","playersmax":"err"}
                    rootel.innerHTML=rootel.innerHTML+"<div class='card mb-3 hoverscale border-0 bggrad text-white' style='border-radius:20px; overflow: hidden;'><h3 class='fw-bold mt-1'>"+row.sv_name+"</h3><h5 style='margin-top: auto;'>карта: "+row.query.map+"</h5><div class='btn-group btn-sm mt-1 input-block-level' role='group'><button type='button' class='btn btn-light border-dark fw-bold bg-white btn-sm' onclick=\"get_players('"+row.sv_name+"')\">Игроки: "+row.query.players+"/"+row.query.playersmax+"</button><a type='button' href='steam://connect/"+row.sv_ip+":"+row.sv_port+"' class='btn btn btn-light border-dark fw-bold bg-white btn-sm'><i class='bi bi-plugin'></i> Подключиться</a><a type='button' class='btn btn btn-light border-dark fw-bold bg-white btn-sm' href='components/donate.php?sv="+row.sv_name+"'><i class='bi bi-bag-heart'></i> Пожертвование</button></div>";
                }
            }
        }
    }
    xmlhttp.send(form);
}
document.addEventListener("DOMContentLoaded",get_servers())