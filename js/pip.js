//Storage
document.getElementById("fileform").addEventListener('submit', e => {
  e.preventDefault()
  let formData = new FormData()
  formData.append("file_submit","")
  formData.append("file",document.getElementById("file").files[0])
  let xmlhttp = new XMLHttpRequest();
  xmlhttp.onload = () => {
    try{
      let yes = JSON.parse(xmlhttp.responseText)
      if (yes.error){
        document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Ошибка!</strong> "+yes.error   +"<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
      }else{
        document.getElementById("alertplace").innerHTML=""
        get_file_list()
      }
    }catch (e){
      document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Ошибка!</strong> Файл слишком большой.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
    }
    document.getElementById("progress").style.width="0%"
  }
  xmlhttp.upload.onprogress = function(event) {
    if (event.lengthComputable)
    {
        var percentComplete = parseInt((event.loaded / event.total) * 100);
        console.log("Загрузка: " + percentComplete + "%...")
        document.getElementById("progress").style.width=percentComplete+"%"
    }
  }
  xmlhttp.open("POST","core/file_manager.php")
  xmlhttp.send(formData)
})
function formatsize(size){
  if (size<=1000000){
    size=(size/1000).toFixed(2)+" KB"
  }
  if (size==1000000 || size<=1000000000){
    size=(size/1000000).toFixed(2)+" MB"
  }
  if (size==1000000000 || size<=1000000000000){
    size=(size/1000000000).toFixed(2)+" GB"
  }
  return size
}
function file_delete(id,file,sid){
  let xmlhttp = new XMLHttpRequest();
  let form = new FormData();
  form.append("file_delete",file);
  if (sid){
    form.append("sid",sid);
  }
  xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      let da=JSON.parse(xmlhttp.responseText)
      if (da.fm_mod){
        get_file_list(sid)
      }else{
        get_file_list()
      }
    }
  }
  xmlhttp.open("POST","core/file_manager.php")
  xmlhttp.send(form)
}
function gen_preview(extension,sid,name){
  if (extension=="jpg"||extension=="png"||extension=="gif"){
    return "<img class='col-auto mb-3' style='border: 2px solid #46B7AA;' src='"+location.protocol+"//"+location.hostname+encodeURI("/storage/"+sid+"/"+name)+"'></img>";
  }else if(extension=="mp4"||extension=="webm"){
    return "<video controls class='col-auto mb-3' style='border: 2px solid #46B7AA; width:100%;' src='"+location.protocol+"//"+location.hostname+encodeURI("/storage/"+sid+"/"+name)+"'></video>";
  }else if (extension=="zip"){
    return "<i style='color: #46B7AA;' class='h1 bi bi-file-earmark-zip-fill'></i>";
  }else if (extension=="txt"){
    return "<i style='color: #46B7AA;' class='h1 bi bi-filetype-txt'></i>"
  }else{
    return "<i style='color: #46B7AA;' class='h1 bi bi-file-earmark-text-fill'></i>"
  }
}
function get_file_list(sid,name){
  let xmlhttp = new XMLHttpRequest();
  let form = new FormData();
  form.append("file_list","");
  if (sid){
    form.append("sid",sid);
  }
  xmlhttp.onload = function() {
    if (this.status == 200) {
      document.getElementById(sid?"filemb":"filemanager").innerHTML=""
      let da=JSON.parse(xmlhttp.responseText)
      if (da.spaceleft){
        document.getElementById("fldrop").innerHTML=""
        document.getElementById("stinf").innerHTML=""
        document.getElementById("aviable").innerHTML="(Доступно: "+formatsize(da.spaceleft)+")"
        document.getElementById("fldrop").innerHTML="Список файлов ("+da.storagecnt+"/"+da.storagemaxcnt+")"
        document.getElementById("stinf").innerHTML="Размер хранилища: "+formatsize(da.storagelimit)
      }
      for (var i = 0, row; row = da[i]; i++) {
        let counter=eval(i+1)
        let fid="file_"+counter
        document.getElementById(sid?"filemb":"filemanager").innerHTML=document.getElementById(sid?"filemb":"filemanager").innerHTML+"<div class='card mb-4 text-black hoverscale stuser' style='border-radius:20px; width:200px; height:auto; cursor: pointer;overflow: hidden;'><div class='card-body'><div class='row p-1 mb-1'><div class='col'>"+gen_preview(row.extension,da.sid,row.name)+"<h6 class='title'>"+row.name+"</h6><h6 class='title' style='color:#46B7AA;'>Размер: "+row.size+"</h6></div></div></div><div class='btn-group'><button class='btn btn-outline-dark btn-sm border-start-0 border-bottom-0' title='Скопировать ссылку' onclick=\"navigator.clipboard.writeText(location.protocol+'//'+location.hostname+encodeURI('/storage/"+da.sid+"/"+row.name+"'))\"><i class='bi bi-share'></i></button><a title='Загрузить файл' class='btn btn-outline-dark btn-sm border-bottom-0' href='/storage/"+da.sid+"/"+row.name+"' download><i class='bi bi-cloud-arrow-down'></i></a><button class='btn btn-outline-dark btn-sm border-end-0 border-bottom-0' title='Удалить файл' onclick=\"file_delete('"+fid+"','"+row.name+(sid?"','"+sid:"")+"')\"><i class='bi bi-trash'></i></button></div>"
      }
      if (da.warn){
        document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Внимание!</strong> "+da.warn+".<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
      }
      if (sid&&name){
        var myModal = new bootstrap.Modal(document.getElementById("filemanager_modal"));
        myModal.toggle();
        document.getElementById("fm_lbl").innerHTML="Moderate "+name+"'s Files"
      }
    }
  }
  xmlhttp.open("POST","core/file_manager.php")
  xmlhttp.send(form)
}




//Notes
if (document.getElementById("write_modal")!=null){
  document.getElementById("write_modal").addEventListener("show.bs.modal", e => {
    if (!tinymce.activeEditor.modaledit){
      document.getElementById("newstitle").classList.remove("is-invalid")
      document.getElementById("newsheadimg").classList.remove("is-invalid")
      document.getElementById("write_selector").classList.remove("btn-outline-danger")
      if (!document.getElementById("write_selector").selected){
        e.preventDefault()
        document.getElementById("write_selector").classList.add("btn-outline-danger")
      }else if (document.getElementById("newstitle").value==""){
        e.preventDefault()
        document.getElementById("newstitle").classList.add("is-invalid")
      }else if(document.getElementById("newsheadimg").value==""){
        e.preventDefault()
        document.getElementById("newsheadimg").classList.add("is-invalid")
      }else{
        document.getElementById("modallbl").innerHTML="ultr4 "+document.getElementById("write_selector").selected.id+" wr1t3r 3000"
      }
      document.getElementById("iittl").classList.add("d-none")
      document.getElementById("iiimg").classList.add("d-none")
      document.getElementById("publish").innerHTML="Опубликовать"
      tinymce.activeEditor.execCommand('mceNewDocument');
    }
  })
  document.getElementById("write_modal").addEventListener("hide.bs.modal", e => {
    tinymce.activeEditor.modaledit=false
  })
  function toggleWritedrop(item) {
    document.getElementById("write_selector").selected=item
    document.getElementById("write_selector").innerHTML = item.innerHTML;
  };
  document.getElementById("publish").addEventListener("click",()=>{
      let xmlhttp = new XMLHttpRequest();
      let tinydata=tinymce.get("tiny").getContent();
      let form = new FormData();
      if (tinymce.activeEditor.modaledit){
        form.append("write_update",tinymce.activeEditor.editingid);
        form.append("headimg",document.getElementById("iinpimg").value);
        form.append("title",document.getElementById("iinpttl").value);
      }else{
        form.append("write_save",document.getElementById("write_selector").selected.id);
        form.append("headimg",document.getElementById("newsheadimg").value);
        form.append("title",document.getElementById("newstitle").value);
      }
      form.append("content",tinydata);
      xmlhttp.open("POST","core/api.php");
      xmlhttp.onload = function() {
          let yes = JSON.parse(this.responseText)
          document.getElementById("cancel").click();
          if (yes.success){
            get_notes()
            document.getElementById("writeralert").innerHTML="<div class='alert alert-success alert-dismissible mt-4' role='alert'  data-aos='flip-right' data-aos-offset='50' data-aos-delay='100'><i class='bi bi-check2-circle'> </i><strong>Success!</strong> "+yes. success+"<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>"
          }
      }
      xmlhttp.send(form);
  });
  document.addEventListener('focusin', function (e) { //fix focus tinymce with bootstrap modal
      if (e.target.closest('.tox-tinymce-aux, .moxman-window, .tam-assetmanager-root') !== null) { 
        e.stopImmediatePropagation();
      } 
  });
  function note_rm(id){
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("nrm",id);
    xmlhttp.onload = function() {
      if (this.status == 200) {
        var da=JSON.parse(this.responseText)
        document.getElementById("writeralert").innerHTML="<div class='alert alert-success alert-dismissible mt-4' role='alert' data-aos='flip-right' data-aos-offset='50' data-aos-delay='100'><i class='bi bi-check2-circle'> </i><strong>Success!</strong> "+da.success+"<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>"
        get_notes()
      }
    }
    xmlhttp.open("POST","core/api.php")
    xmlhttp.send(form)
  }
  function note_edit(id){
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("get_tinydata",id);
    xmlhttp.onload = function() {
      if (this.status == 200) {
        var da=JSON.parse(this.responseText)
        tinymce.activeEditor.modaledit=true
        tinymce.activeEditor.editingid=id
        document.getElementById("iittl").classList.remove("d-none")
        document.getElementById("iiimg").classList.remove("d-none")
        document.getElementById("iinpttl").value=da.title
        document.getElementById("iinpimg").value=da.headimg
        document.getElementById("publish").innerHTML="Сохранить"
        
        var myModal = new bootstrap.Modal(document.getElementById("write_modal"));
        myModal.toggle();
        tinymce.activeEditor.execCommand('mceNewDocument');
        tinymce.activeEditor.execCommand('mceInsertContent',false,decodeURIComponent(escape(atob(da.content))));
      }
    }
    xmlhttp.open("POST","core/api.php")
    xmlhttp.send(form)
  }
  function get_notes(){
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("get_notes","");
    xmlhttp.onload = function() {
      if (this.status == 200) {
        document.getElementById("notetable").innerHTML=""
        let da=JSON.parse(xmlhttp.responseText)
        for (var i = 0, row; row = da[i]; i++) {
          let counter=eval(i+1)
          document.getElementById("notetable").innerHTML=document.getElementById("notetable").innerHTML+"<tr><th scope='row'>"+counter+"</th><td>"+row.type+"</td><td>"+row.title+"</td><td><div class='btn-group column-gap-1'><button title='Редактировать запись' class='btn btn-outline-dark btn-sm' onclick=\"note_edit('"+row.id+"')\"><i class='bi bi-pencil'></i></button><button class='btn btn-outline-dark btn-sm' title='Удалить запись' onclick=\"note_rm('"+row.id+"')\"><i class='bi bi-trash'></i></button></div></td></td></tr>";
        }
      }
    }
    xmlhttp.open("POST","core/api.php")
    xmlhttp.send(form)
  }
}



// Globals
let dropInput=document.getElementById("dropInput");
if (dropInput!=null){
  function toggledrop(item) {
    document.getElementById("optionDrop").innerHTML = item.innerHTML;
    selectedDrop=item;
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("settings_infoget",item.id);
    xmlhttp.open("POST","core/api.php");
    xmlhttp.onreadystatechange = function() {
      if (this.status == 200) {
        dropInput.classList.remove("is-invalid");
        dropInput.value=this.responseText.trim();
      }
    }
    xmlhttp.send(form);
  };
  document.getElementById("saveDrop").addEventListener("click",()=>{
    if (typeof selectedDrop == 'undefined'){
      dropInput.value="Select option first!";
      dropInput.classList.add("is-invalid");
    }else{
      let xmlhttp = new XMLHttpRequest();
      let form = new FormData();
      form.append("settings_insert",selectedDrop.id);
      form.append("value",dropInput.value);
      xmlhttp.open("POST","core/api.php");
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          if (selectedDrop.id=="project_name"){
            document.getElementById("project_name").innerHTML=dropInput.value
          }
        }
      }
      xmlhttp.send(form);
    }
  })
  function toggleswitch(item) {
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("settings_insert",item.id);
    form.append("value",item.checked);
    xmlhttp.open("POST","core/api.php");
    xmlhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {

      }
    }
    xmlhttp.send(form);
  };
  let picker=document.getElementById("BGColorInput")
  let picker2=document.getElementById("BGColorInput2")
  let picker3=document.getElementById("BGColorInput3")
  let picker4=document.getElementById("BGColorInput4")
  document.addEventListener("input",function(event){
    if(event.target.type=="color"){
      document.body.style.backgroundImage="linear-gradient(-45deg, "+picker.value+", "+picker2.value+", "+picker3.value+", "+picker4.value+")"
    }
    if (event.target.id=="file"){
      if (typeof(file.files[0])!="undefined"){
        let filesize=formatsize(file.files[0].size);
        document.getElementById("uploadinf").classList.remove("d-none")
        document.getElementById("filesize").innerHTML=filesize;
      }else{
        document.getElementById("uploadinf").classList.add("d-none")
      };
    }
  })
  document.getElementById("savebg").addEventListener("click",()=>{
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("settings_insert","bg_color");
    form.append("value",picker.value+", "+picker2.value+", "+picker3.value+", "+picker4.value);
    xmlhttp.open("POST","core/api.php");
    xmlhttp.send(form);
    document.body.style.backgroundImage="linear-gradient(-45deg, "+picker.value+", "+picker2.value+", "+picker3.value+", "+picker4.value+")"
  })
}



//Servers
if (document.getElementById("srv_form")!=null){
  document.getElementById("srv_form").addEventListener("submit", (event) => {
    event.preventDefault();
    let XHR = new XMLHttpRequest();
    let form = new FormData(document.getElementById("srv_form"));
    form.append("svsave","");
    XHR.addEventListener("load", (event) => {
      get_servers()
    });
    XHR.open("POST","core/api.php");
    XHR.send(form);
  });
  function server_rm(name){
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("svrm",name);
    xmlhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        get_servers()
      }
    }
    xmlhttp.open("POST","core/api.php")
    xmlhttp.send(form)
  }
  function get_servers(){
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("get_servers","");
    xmlhttp.onload = function() {
      if (this.status == 200) {
        document.getElementById("servertable").innerHTML=""
        if (document.getElementById("rcon_servs")!=null){
          document.getElementById("rcon_servs").innerHTML=""
        }
        let da=JSON.parse(xmlhttp.responseText)
        for (var i = 0, row; row = da[i]; i++) {
          let counter=eval(i+1)
          document.getElementById("servertable").innerHTML=document.getElementById("servertable").innerHTML+"<tr><th scope='row'>"+counter+"</  th><td>"+row.sv_name+"</td><td>"+row.sv_ip+"</td><td>"+row.sv_port+"</td><td><button class='btn btn-outline-dark btn-sm' title='Удалить сервер' onclick=\"server_rm('"+row.sv_name+"')\"><i class='bi bi-trash'></i></button></td></td></tr>";
          if (document.getElementById("rcon_servs")!=null){
            document.getElementById("rcon_servs").innerHTML=document.getElementById("rcon_servs").innerHTML+"<option value="+row.id+">"+row.sv_name +"</option>"
          }
        }
      }
    }
    xmlhttp.open("POST","core/api.php")
    xmlhttp.send(form)
  }
}





//Rcon
if (document.getElementById("rcon_submit")!=null){
  document.getElementById("rcon_submit").addEventListener("click", (event) => {
    let xmlhttp = new XMLHttpRequest();
    let form = new FormData();
    form.append("rcon_submit",document.getElementById("rcon_servs").value);
    form.append("command",document.getElementById("rcon_string").value);
    document.getElementById("typer").classList.add("d-none")
    xmlhttp.onload = function() {
      let si=JSON.parse(xmlhttp.responseText)
      document.getElementById("typer").classList.remove("d-none")
      document.getElementById("rcon_response_place").innerHTML=">"+si.success
    };
    xmlhttp.open("POST","core/rcon.php");
    xmlhttp.send(form);
  })
}
function nicedate(str){
  str=new Date(str)
  return str.toString().replace(/^[^\s]+\s([^\s]+)\s([^\s]+)\s([^\s]+)\s([^\s]+)\s.*$/ig,'$3-'+(str.getMonth()+1)+'-$2 $4').slice(0,-3);
}