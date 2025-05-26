function makeRequest(data,callback,url="core/api.php",progress,method="POST"){
  let xmlhttp=new XMLHttpRequest();
  xmlhttp.open(method,url);
  xmlhttp.onload=function() {
      if (this.readyState==4&&this.status==200) {
        if(callback){callback(this.responseText?JSON.parse(this.responseText):undefined)}
      }
  };
  xmlhttp.upload.onprogress=progress
  let form=new FormData();
  for (let key in data){form.append(key,data[key]);}
  xmlhttp.send(form);
}
//----------------------------------------------STORAGE
document.getElementById("fileform").addEventListener('submit', e => {
  e.preventDefault()
  let df=document.getElementById("file").files[0]
  makeRequest({file_submit:"",file:df},function(resp){
    if (resp){
      if (resp.error){
        document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Ошибка!</strong> "+e.error   +"<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
      }else{
        document.getElementById("alertplace").innerHTML=""
        get_file_list()
      }
    }else{
      document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Ошибка!</strong> Файл слишком большой.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
    }
    document.getElementById("progress").style.width="0%"
  },"core/file_manager.php",function(event){
    if (event.lengthComputable)
    {
        var percentComplete=parseInt((event.loaded/event.total)*100);
        console.log("Загрузка: "+percentComplete+"%...")
        document.getElementById("progress").style.width=percentComplete+"%"
    }
  })
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
  let form={file_delete:file}
  if (sid){
    form["sid"]=sid
  }
  makeRequest(form,function(resp){
      if (resp.fm_mod){
        get_file_list(sid)
      }else{
        get_file_list()
      }
  },"core/file_manager.php")
}
function gen_preview(extension,sid,name){
  if (extension=="jpeg"||extension=="jpg"||extension=="png"||extension=="gif"){
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
  let form={file_list:""}
  if (sid){
    form["sid"]=sid
  }
  makeRequest(form,function(resp){
    document.getElementById(sid?"filemb":"filemanager").innerHTML=""
    if (resp.spaceleft){
      document.getElementById("fldrop").innerHTML=""
      document.getElementById("stinf").innerHTML=""
      document.getElementById("aviable").innerHTML="(Доступно: "+formatsize(resp.spaceleft)+")"
      document.getElementById("fldrop").innerHTML="Список файлов ("+resp.storagecnt+"/"+resp.storagemaxcnt+")"
      document.getElementById("stinf").innerHTML="Размер хранилища: "+formatsize(resp.storagelimit)
    }
    for (var i=0,row;row=resp[i];i++) {
      let counter=eval(i+1)
      let fid="file_"+counter
      document.getElementById(sid?"filemb":"filemanager").innerHTML+="<div class='card mb-4 text-black hoverscale stuser' style='border-radius:20px; width:200px; height:auto; cursor: pointer;overflow: hidden;'><div class='card-body'><div class='row p-1 mb-1'><div class='col'>"+gen_preview(row.extension,resp.sid,row.name)+"<h6 class='title'>"+row.name+"</h6><h6 class='title' style='color:#46B7AA;'>Размер: "+row.size+"</h6></div></div></div><div class='btn-group'><button class='btn btn-outline-dark btn-sm border-start-0 border-bottom-0' title='Скопировать ссылку' onclick=\"navigator.clipboard.writeText(location.protocol+'//'+location.hostname+encodeURI('/storage/"+resp.sid+"/"+row.name+"'))\"><i class='bi bi-share'></i></button><a title='Загрузить файл' class='btn btn-outline-dark btn-sm border-bottom-0' href='/storage/"+resp.sid+"/"+row.name+"' download><i class='bi bi-cloud-arrow-down'></i></a><button class='btn btn-outline-dark btn-sm border-end-0 border-bottom-0' title='Удалить файл' onclick=\"file_delete('"+fid+"','"+row.name+(sid?"','"+sid:"")+"')\"><i class='bi bi-trash'></i></button></div>"
    }
    if (resp.warn){
      document.getElementById("alertplace").innerHTML="<div class='alert alert-danger alert-dismissible' role='alert'   data-aos='flip-right' data-aos-delay='100'><i class='bi bi-exclamation-triangle'> </i><strong>Внимание!</strong> "+resp.warn+".<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>"
    }
    if (sid&&name){
      var myModal = new bootstrap.Modal(document.getElementById("filemanager_modal"));
      myModal.toggle();
      document.getElementById("fm_lbl").innerHTML="Moderate "+name+"'s Files"
    }
  },"core/file_manager.php")
}
//----------------------------------------------NOTES
let write_selected=null;
if (document.getElementById("write_modal")!=null){
  document.getElementById("write_modal").addEventListener("show.bs.modal", e => {
    if (!tinymce.activeEditor.modaledit){
      if (!write_selected){
        e.preventDefault()
      }else{
        document.getElementById("modallbl").innerHTML="ultr4 "+write_selected.id+" wr1t3r 3000"
      }
      document.getElementById("iinpttl").value=""
      document.getElementById("iinpimg").value=""
      document.getElementById("nremove").classList.add("d-none")
      document.getElementById("publish").innerHTML="Опубликовать"
      tinymce.activeEditor.execCommand("mceNewDocument");
    }
  })
  document.getElementById("write_modal").addEventListener("hide.bs.modal",e=>{
    tinymce.activeEditor.modaledit=false
    tinymce.activeEditor.isNotDirty=1;
  })
  function toggleWritedrop(item) {
    var mod=document.getElementById("write_modal");
    var myModal=new bootstrap.Modal(mod);
    write_selected=item
    myModal.toggle();
  };
  document.getElementById("publish").addEventListener("click",()=>{
      let form={};
      if (tinymce.activeEditor.modaledit){
        form["write_update"]=tinymce.activeEditor.editingid
      }else{
        form["write_save"]=write_selected.id
      }
      form["headimg"]=document.getElementById("iinpimg").value
      form["title"]=document.getElementById("iinpttl").value
      form["content"]=tinymce.get("tiny").getContent()
      makeRequest(form,function(resp){
          document.getElementById("cancel").click();
          if (resp.success){
            get_notes()
            console.log(resp.success)
          }
      })
  });
  document.addEventListener("focusin",function(e){ //fix focus tinymce with bootstrap modal
      if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) { 
        e.stopImmediatePropagation();
      } 
  });
  function note_edit(id){
    makeRequest({get_tinydata:id},function(resp){
      tinymce.activeEditor.modaledit=true
      tinymce.activeEditor.editingid=id
      document.getElementById("iittl").classList.remove("d-none")
      document.getElementById("iiimg").classList.remove("d-none")
      document.getElementById("nremove").classList.remove("d-none")
      document.getElementById("iinpttl").value=resp.title
      document.getElementById("iinpimg").value=resp.headimg
      document.getElementById("publish").innerHTML="Сохранить"
      document.getElementById("nremove").onclick=function(){makeRequest({nrm:id},function(){get_notes()})}
      var myModal = new bootstrap.Modal(document.getElementById("write_modal"));
      myModal.toggle();
      tinymce.activeEditor.execCommand("mceNewDocument");
      tinymce.activeEditor.execCommand("mceInsertContent",false,decodeURIComponent(escape(atob(resp.content))));
    })
  }
  function get_notes(np){
    makeRequest({get_notes:np??1},function(resp){
      let noteList=document.getElementById("notes_list")
      noteList.innerHTML=""
      for (var i = 0, row; row = resp.data[i]; i++) {
        noteList.innerHTML+="<div class='card mb-2 text-black hoverscale' style='height:fit-content;cursor:pointer;overflow:hidden;'><div class='card-body p-0'><div class='row'><img class='col-3' style='height:60px; object-fit:cover;' src='"+row.headimg+"'></img><h6 class='col title my-auto'><a href='/"+row.type+".php?id="+row.id+"' style='color:black;width:fit-content;height:fit-content;'>"+row.title+"</a></h6><h6 class='col title my-auto' style='color:#46B7AA;'>Тип: "+row.type+"</h6><button title='Редактировать запись' class='m-2 col-1 btn btn-sm btn-outline-dark' onclick=\"note_edit('"+row.id+"')\"><i class='bi bi-pencil-fill'></i></button></div></div></div>"
      }
      if(resp.pages>1){
        let notespag=document.getElementById("notes_pag")
        notespag.classList.remove("d-none")
        notespag.innerHTML=""
        notespag.innerHTML+=(resp.page>4?"<li class='page-item'><a class='page-link text-black shadow-none' onclick=\"get_notes()\"><span aria-hidden='true'>&laquo;</span></a></li>":"")+"<li class='page-item "+(resp.page==1?"disabled":"")+"'><a class='page-link text-black shadow-none' onclick=\"get_notes('"+resp.prev+"')\"><span aria-hidden='true'>Prev</span></a></li>"
        for (let i=1;i<=resp.pages;i++){
          notespag.innerHTML+="<li class='page-item'><a class='page-link shadow-none text-black "+(resp.page==i?"active":"")+"' onclick=\"get_notes('"+i+"')\">"+i+"</a></li>"
        }
        notespag.innerHTML+="<li class='page-item "+(resp.page==resp.pages?"disabled":"")+"'><a class='page-link text-black shadow-none' onclick=\"get_notes('"+resp.next+"')\"><span aria-hidden='true'>Next</span></a></li>"
        if(resp.page<resp.pages-2) {
          notespag.innerHTML+="<li class='page-item'><a class='page-link text-black shadow-none' onclick=\"get_notes('"+resp.pages+"')\"><span aria-hidden='true'>&raquo;</span></a></li>"
        }
      }
    })
  }
}
//----------------------------------------------GLOBAL SETTINGS
let dropInput=document.getElementById("dropInput");
if (dropInput!=null){
  function toggledrop(item) {
    document.getElementById("optionDrop").innerHTML=item.innerHTML;
    selectedDrop=item;
    makeRequest({settings_infoget:item.id},function(){
        dropInput.classList.remove("is-invalid");
        dropInput.value=this.responseText.trim();
    })
  };
  document.getElementById("saveDrop").addEventListener("click",()=>{
    if (typeof selectedDrop=="undefined"){
      dropInput.value="Select option first!";
      dropInput.classList.add("is-invalid");
    }else{
      makeRequest({settings_insert:selectedDrop.id,value:dropInput.value},function(){
        if (selectedDrop.id=="project_name"){
          document.getElementById("project_name").innerHTML=dropInput.value
        }
      })
    }
  })
  function toggleswitch(item) {
    makeRequest({settings_insert:item.id,value:item.checked})
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
    makeRequest({settings_insert:"bg_color",value:picker.value+", "+picker2.value+", "+picker3.value+", "+picker4.value})
    document.body.style.backgroundImage="linear-gradient(-45deg, "+picker.value+", "+picker2.value+", "+picker3.value+", "+picker4.value+")"
  })
}
//----------------------------------------------SERVERS
if (document.getElementById("srv_form")!=null){
  document.getElementById("srv_form").addEventListener("submit",(event)=>{
    event.preventDefault();
    let tbl={svsave:""}
    let form=document.getElementById("srv_form");
    let elements=form.elements;
    for (let i=0;i<elements.length;i++) {
        let element=elements[i];
        if (element.name) {
            tbl[element.name]=element.value;
        }
    }
    makeRequest(tbl,function(){
      get_servers()
    })
  });
  function server_rm(name){
    makeRequest({svrm:name},function(){
        get_servers()
    })
  }
  function get_servers(){
    makeRequest({get_servers},function(resp){
        document.getElementById("servertable").innerHTML=""
        if (document.getElementById("rcon_servs")!=null){
          document.getElementById("rcon_servs").innerHTML=""
        }
        for (var i=0,row;row=resp[i];i++) {
          let counter=eval(i+1)
          document.getElementById("servertable").innerHTML+="<tr><th scope='row'>"+counter+"</  th><td>"+row.sv_name+"</td><td>"+row.sv_ip+"</td><td>"+row.sv_port+"</td><td><button class='btn btn-outline-dark btn-sm' title='Удалить сервер' onclick=\"server_rm('"+row.sv_name+"')\"><i class='bi bi-trash'></i></button></td></td></tr>";
          if (document.getElementById("rcon_servs")!=null){
            document.getElementById("rcon_servs").innerHTML+="<option value="+row.id+">"+row.sv_name +"</option>"
          }
        }
      })
  }
}
//----------------------------------------------RCON
if (document.getElementById("rcon_submit")!=null){
  document.getElementById("rcon_submit").addEventListener("click",(event)=>{
    document.getElementById("typer").classList.add("d-none")
    let rstring=document.getElementById("rcon_string").value
    let rserv=document.getElementById("rcon_servs").value
    makeRequest({rcon_submit:rserv,command:rstring},function(resp){
      document.getElementById("typer").classList.remove("d-none")
      document.getElementById("rcon_response_place").innerHTML=">"+resp.success
    },"core/rcon.php");
  })
}
function nicedate(str){
  str=new Date(str)
  return str.toString().replace(/^[^\s]+\s([^\s]+)\s([^\s]+)\s([^\s]+)\s([^\s]+)\s.*$/ig,'$3-'+(str.getMonth()+1)+'-$2 $4').slice(0,-3);
}
