document.addEventListener("DOMContentLoaded",function(){
    const apiUrl="core/api.php";

    async function apiRequest(formData) {
        try {
            const res=await fetch(apiUrl,{method:"POST",body:formData});
            const text=await res.text();
            try {
                const json=JSON.parse(text);
                return json;
            } catch (e) {
                throw new Error("Invalid JSON response: "+text);
            }
        } catch (e){
            throw e;
        }
    }

    // quill init
    window.quill=new Quill("#editor", {
        theme:"snow",
        modules:{
            syntax:true,
            toolbar:{
                container:[
                    [{header:[1,2,3,false]}],
                    ["blockquote","link"],
                    ["bold","italic","underline","strike"],
                    ["image","video","code-block"],
                    [{"list":"ordered"},{"list":"bullet"},{"font":[]}],
                    [{"align":[]},{"color":[]}],
                ],
                handlers: {
                    image: function() {
                        const range=this.quill.getSelection();
                        const value=prompt("Введите ссылку на картинку.");
                        if (value&&value!=""){
                            this.quill.insertEmbed(range?range.index:0,"image",value,Quill.sources.USER);
                        }
                    }
                }
            }
        }
    });

    // collapse

    const collapses=document.querySelectorAll(".collapse");
    collapses.forEach(collapse=>{
        collapse.addEventListener("show.bs.collapse",function () {
            collapses.forEach(other=>{
                if (other!==collapse&&other.classList.contains("show")){
                    const bsCollapse=bootstrap.Collapse.getInstance(other);
                    if (bsCollapse){
                        bsCollapse.hide();
                    }else{
                      new bootstrap.Collapse(other,{toggle:false}).hide();
                    }
                }
            });
        });
    });

    // create cat/subcat
    const selectAction=document.getElementById("action");
    const subcatIcon=document.getElementById("icon");
    const catFsub=document.getElementById("cat_fsub");
    const createBtn=document.getElementById("createBtn");

    function toggleCreateFields() {
        const isSub=selectAction&&selectAction.value==="new_subcat";
        if (subcatIcon) subcatIcon.style.display=isSub?"": "none";
        if (catFsub) catFsub.style.display=isSub?"":"none";
    }
    if (selectAction) {
        selectAction.addEventListener("change",toggleCreateFields);
        toggleCreateFields();
    }

    if (createBtn) {
        createBtn.addEventListener("click",async () => {
            const action=selectAction?selectAction.value:"new_cat";
            const name=document.getElementById("name").value.trim();
            const prior=document.getElementById("prior").value.trim();
            const icon=subcatIcon?subcatIcon.value.trim():"";
            const cat_sub=catFsub?catFsub.value:"";

            const fd=new FormData();
            fd.append("forum_admin",action);
            fd.append("name",name);
            fd.append("prior",prior);
            if (action==="new_subcat") {
                fd.append("icon",icon);
                fd.append("cat_id",cat_sub);
            }

            try {
                const data=await apiRequest(fd);
                if (data.error) alert(data.error);
                else location.reload();
            } catch (e) {alert("Ошибка: "+e.message); }
        });
    }

    document.querySelectorAll(".edit-btn").forEach(btn=>{
        btn.addEventListener("click",async(ev)=>{
            const catId=btn.id.split("-")[1];
            const actionSel=document.getElementById("action_edit-"+catId);
            const catSel=document.getElementById("cat_id-"+catId);
            const nameInp=document.getElementById("edit_name-"+catId);
            const priorInp=document.getElementById("edit_prior-"+catId);
            const iconInp=document.getElementById("edit_icon-"+catId);

            const act=actionSel?actionSel.value:"edit_cat";
            const fd=new FormData();

            if (act==="edit_cat"){
                fd.append("forum_admin","edit_cat");
                fd.append("id",catId);
                fd.append("name",nameInp.value.trim());
                fd.append("prior",priorInp.value.trim());
            }else{
                fd.append("forum_admin","edit_subcat");
                fd.append("id",catSel?catSel.value:"");
                fd.append("name",nameInp.value.trim());
                fd.append("prior",priorInp.value.trim());
                fd.append("cat_id",catId); 
                fd.append("icon",iconInp?iconInp.value.trim():"");
            }

            try {
                const data=await apiRequest(fd);
                if (data.error) alert(data.error);
                else location.reload();
            } catch (e) {
                alert("Сетевая ошибка: "+e.message);
            }
        });
    });

    // when action_edit or cat list changes, update inputs accordingly
    document.querySelectorAll(".edit-action").forEach(sel=>{
        const catId=sel.id.split("-")[1];
        const catSel=document.getElementById("cat_id-"+catId);
        const nameInp=document.getElementById("edit_name-"+ catId);
        const priorInp=document.getElementById("edit_prior-"+catId);
        const iconInp=document.getElementById("edit_icon-"+catId);

        function updateFields(){
            if (!sel) return;
            const isSub=sel.value==="edit_subcat";
            if (iconInp) iconInp.style.display=isSub?"":"none";
            if (catSel) catSel.style.display=isSub?"":"none";

            if (isSub&&catSel){
                nameInp.value=catSel.options[catSel.selectedIndex].text;
                priorInp.value=catSel.options[catSel.selectedIndex].dataset.prior||"";
                iconInp.value=catSel.options[catSel.selectedIndex].dataset.icon||"";
            } else {
                // fallback — value already set from PHP
            }
        }
        sel.addEventListener("change",updateFields);
        updateFields();

        if (catSel) {
            catSel.addEventListener("change",()=> {
                if (nameInp && priorInp && iconInp){
                    nameInp.value=catSel.options[catSel.selectedIndex].text;
                    priorInp.value=catSel.options[catSel.selectedIndex].dataset.prior||"";
                    iconInp.value=catSel.options[catSel.selectedIndex].dataset.icon||"";
                }
            });
        }
    });

    // Delete handlers
    document.querySelectorAll(".rm-btn").forEach(btn=>{
        btn.addEventListener("click",async()=>{
            const catId=btn.id.split("-")[1];
            const actionSel=document.getElementById("action_edit-"+catId);
            const catSel=document.getElementById("cat_id-"+catId);
            const act=actionSel?actionSel.value:"edit_cat";
            if (!confirm("Удалить выбранный элемент?")) return;

            const fd=new FormData();
            if (act==="edit_cat"){
                fd.append("forum_admin","delete_cat");
                fd.append("id",catId);
            } else {
                fd.append("forum_admin","delete_subcat");
                fd.append("id",catSel?catSel.value:"");
            }

            try {
                const data=await apiRequest(fd);
                if (data.error) alert(data.error);
                else location.reload();
            } catch (e) {alert("Ошибка: "+e.message);}
        });
    });

    // post modal
    let currentSubcat=null;
    document.querySelectorAll(".newpost-btn").forEach(btn=>{
        btn.addEventListener("click",()=>{
            currentSubcat=btn.id.split("-")[1];
            const subName=btn.dataset.name || btn.closest('[id^="subcat-"]')?.querySelector(".subcat-name")?.textContent || "подкатегории";
            const label=document.getElementById("modallbl");
            label.innerText="Новый тред в «"+subName+"»";
            const titleInput=document.getElementById("iinpttl");
            titleInput.value="";
            if (window.quill) window.quill.root.innerHTML="";
        });
    });

    // --------- Publish thread ----------
    const publishBtn = document.getElementById("publish");
    if (publishBtn){
        publishBtn.addEventListener("click",async()=>{
            if (!currentSubcat) {alert("Не выбрана подкатегория");return;}
            const title=document.getElementById("iinpttl").value.trim();
            const delta=quill.getContents();
            const content=JSON.stringify(delta);

            const fd=new FormData();
            fd.append("forum","new_thread");
            fd.append("subcat_id",currentSubcat);
            fd.append("topic",title);
            fd.append("content",content);

            try {
                const data=await apiRequest(fd);
                if (data.error) alert(data.error||"Ошибка при создании треда.");
                else location.reload();
            } catch (e) {
                alert("Сетевая ошибка: "+e.message);
            }
        });
    }
    function plural(number,one,few,many) {
        number=Math.abs(number)%100;
        const n1=number%10;
        if (number>10&&number<20) return many;
        if (n1>1&&n1<5) return few;
        if (n1==1) return one;
        return many;
    }
    window.get_threads=async function(subcatId,page=1){
    const threadList=document.getElementById("threadList-"+subcatId);
    const threadPag=document.getElementById("threadPag-"+subcatId);

    if (!threadList||!threadPag) return;

    const fd=new FormData();
    fd.append("forum","get_threads");
    fd.append("subcat_id",subcatId);
    fd.append("page",page);

    try {
        const resp=await apiRequest(fd);

        if (resp.error){
            alert(resp.error);
            return;
        }

        threadList.innerHTML="";
        for (const thread of resp.data) {
            threadList.innerHTML += `
                <div class="d-flex align-items-center mb-2" style="margin-left:1rem;">
                    <a href="/profile.php?id=${thread.author_steamid}" title="${thread.author_name}" class="text-decoration-none text-body-secondary">
                        <img class="col-auto rounded-circle"
                             style="border:2px solid rgba(71,71,71,1);width:2.3rem;"
                             src="${thread.author_avatar}">
                    </a>
                    <div class="p-2 fs-6">
                        <div class="text-start">
                        <a href="?thread=${thread.id}" class="text-decoration-none text-body-secondary">
                                <span class="mb-0">${thread.topic} ${(thread.pinned==1?"<i class='bi bi-pin-angle-fill'></i>":"")} ${(thread.locked==1?"<i class='bi bi-lock-fill'></i>":"")}</span>
                            </div>
                            <div class="text-start">
                                <span style="color:#178649ff;">${thread.created} • </span>
                                <span style="color:#178649ff;">${thread.replies} ${plural(parseInt(thread.replies),'ответ','ответа','ответов')}</span>
                            </div>
                        </a>
                    </div>
                </div>
                <hr>
            `;
        }

        // pagination
        if (resp.pages>1) {
            threadPag.classList.remove("d-none");
            threadPag.innerHTML="";
            
            if (resp.page>4){
                threadPag.innerHTML+=`<li class='page-item'>
                    <a class='page-link text-black shadow-none' onclick="get_threads('${subcatId}', 1)">
                        <span aria-hidden='true'>&laquo;</span>
                    </a>
                </li>`;
            }

            threadPag.innerHTML+=`<li class='page-item ${resp.page==1?"disabled":""}'>
                <a class='page-link text-black shadow-none' onclick="get_threads('${subcatId}', ${resp.prev})">Prev</a>
            </li>`;

            for (let i=1;i<=resp.pages;i++){
                if (i>=resp.page-3&&i<=resp.page+3){
                    threadPag.innerHTML+=`<li class='page-item ${resp.page==i?"active":""}'>
                        <a class='page-link text-black shadow-none' onclick="get_threads('${subcatId}', ${i})">${i}</a>
                    </li>`;
                }
            }

            threadPag.innerHTML+=`<li class='page-item ${resp.page==resp.pages?"disabled":""}'>
                <a class='page-link text-black shadow-none' onclick="get_threads('${subcatId}', ${resp.next})">Next</a>
            </li>`;

            if (resp.page<resp.pages-2) {
                threadPag.innerHTML+=`<li class='page-item'>
                    <a class='page-link text-black shadow-none' onclick="get_threads('${subcatId}', ${resp.pages})">
                        <span aria-hidden='true'>&raquo;</span>
                    </a>
                </li>`;
            }
        }
    } catch (e) {
        alert(e);
    }
}

    const lists=[...document.querySelectorAll('[id^="threadList-"]')];

    async function loadThreadsSequentially(){
        for (const list of lists) {
            const subcatId=list.id.replace("threadList-","");
            await get_threads(subcatId);
        }
    }
    loadThreadsSequentially();

});