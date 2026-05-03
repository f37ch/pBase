document.addEventListener("DOMContentLoaded",function(){
    const editor=document.getElementById("editor")
    if (editor){
        window.quill=new Quill(editor,{
            theme:"snow",
            modules:{
                syntax:true,
                toolbar:{
                    container:[
                        [{header:[1,2,false]}],
                        ["blockquote","link"],
                        ["bold","italic","underline","strike",{"align":[]},{"color":[]}],
                        ["image","video","code-block"],
                        [{"list":"ordered"},{"list":"bullet"},{"font":[]}],
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
    }


    let BlockEmbed=Quill.import("blots/block/embed");
    class VideoBlot extends BlockEmbed {
      static create(value) {
        let node;
        const isYouTube=value.includes("youtube.com")||value.includes("youtu.be");

        if (isYouTube) {
          node = document.createElement("iframe");
          node.setAttribute("src",value);
          node.setAttribute("frameborder","0");
          node.setAttribute("allowfullscreen","true");
          node.classList.add("ql-video");
        } else {
          node = document.createElement("video");
          node.setAttribute("src",value);
          node.setAttribute("controls","true");
          node.setAttribute("preload","metadata");
          node.removeAttribute("autoplay");
        }
        return node;
      }

      static value(node) {
        return node.getAttribute("src");
      }
    }
    VideoBlot.blotName="video";
    VideoBlot.tagName=["iframe","video"];
    Quill.register(VideoBlot,true);


    document.querySelectorAll(".post-content").forEach(function(el){
        const delta=JSON.parse(el.dataset.delta);

        const quill=new Quill(el,{
            readOnly:true,
            theme:"snow",
            modules:{toolbar:false,syntax:true}
        });
        quill.setContents(delta);
    });

    const clearBtn=document.getElementById("clear");
    if (clearBtn) {
        clearBtn.addEventListener("click",()=>{
            window.quill.setContents();
            window.editingPostId=null;
            window.replyPostId=null;
            clearBtn.innerText="Очистить поле";
            publishBtn.innerText="Ответить";
            const moveWrap=document.getElementById("move_thread_wrap");
            if (moveWrap) moveWrap.classList.add("d-none");
        });
    }

    const publishBtn=document.getElementById("publish");
    if (publishBtn) {
        publishBtn.addEventListener("click",()=>{
            const content=JSON.stringify(window.quill.getContents());
            const threadId=new URLSearchParams(window.location.search).get("thread");
            const counter=publishBtn.dataset.counter;

            let formData;
            if (window.editingPostId) {
                formData=new URLSearchParams({
                    forum:"edit_post",
                    post_id:window.editingPostId,
                    content:content
                });
            } else if(window.replyPostId) {
                formData=new URLSearchParams({
                    forum:"new_post",
                    thread_id:threadId,
                    reply_id:window.replyPostId,
                    content:content
                });
            }else{
                formData=new URLSearchParams({
                    forum:"new_post",
                    thread_id:threadId,
                    content:content
                });
            }
            fetch("core/api.php",{
                method:"POST",
                body:formData
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success){
                    const moveWrap=document.getElementById("move_thread_wrap");
                    const moveSubcatSel=document.getElementById("move_subcat_select");
                    if (moveWrap&&!moveWrap.classList.contains("d-none")&&moveSubcatSel&&moveSubcatSel.value){
                        const threadId=moveWrap.dataset.threadId;
                        fetch("core/api.php",{
                            method:"POST",
                            body:new URLSearchParams({
                                forum_admin:"move_thread",
                                id:threadId,
                                subcat_id:moveSubcatSel.value
                            })
                        }).then(()=>{
                            moveWrap.classList.add("d-none");
                            location.reload();
                        });
                    } else {
                        if (counter==8&&!window.editingPostId){
                            const postsPerPage=8;
                            const lastPage=Math.ceil(data.thread.total_posts/postsPerPage);
                        
                            const url=new URL(window.location.href);
                            url.searchParams.set("pg",lastPage);
                            url.hash="post-"+data.thread.last_post_id;
                            window.location.href=url.toString();
                        }else{
                            location.reload();
                        }
                    }
                } else {
                    alert(data.error||"Ошибка при публикации");
                }
                window.editingPostId=null;
                window.replyPostId=null;
                publishBtn.innerText="Ответить"
                clearBtn.innerText="Очистить поле";
                const moveWrap=document.getElementById("move_thread_wrap");
                if (moveWrap) moveWrap.classList.add("d-none");
            })
            .catch(err=>{
                window.editingPostId=null;
                window.replyPostId=null;
                publishBtn.innerText="Ответить";
                clearBtn.innerText="Очистить поле";
                const moveWrap=document.getElementById("move_thread_wrap");
                if (moveWrap) moveWrap.classList.add("d-none");
                alert("Не удалось отправить пост");
            });
        });
    }

    document.querySelectorAll(".thread-btn").forEach(function(btn){
        btn.addEventListener("click", function() {
            const id=this.dataset.id;
            const action=this.dataset.action;
            if (!id||!action) return;

            if (action==="edit_post") {
                const postEl=document.querySelector(`#post-${id} .post-content`);
                if (!postEl){
                    alert("Не удалось найти содержимое поста");
                    return;
                }

                const delta=JSON.parse(postEl.dataset.delta);
                window.quill.setContents(delta);
                window.editingPostId=id;
                document.querySelector("#editor").scrollIntoView({behavior: "smooth"});
                publishBtn.innerText="Сохранить"
                clearBtn.innerText="Отменить редактирование"

                const moveWrap=document.getElementById("move_thread_wrap");
                const moveCatSel=document.getElementById("move_cat_select");
                const moveSubcatSel=document.getElementById("move_subcat_select");

                const isFirstPost=(document.querySelector(`#post-${id}`)?.previousElementSibling==null);
                const hasThreadControls=document.querySelector(`#post-${id} [data-action="pin_thread"]`);

                if (moveWrap&&hasThreadControls){
                    moveWrap.classList.remove("d-none");
                
                    function loadSubcats(catId,selectedSubcatId){
                        fetch("core/api.php",{
                            method:"POST",
                            body:new URLSearchParams({forum_admin:"get_forum_subcats",cat_id:catId})
                        })
                        .then(r=>r.json())
                        .then(data=>{
                            moveSubcatSel.innerHTML="";
                            (data.subcats||[]).forEach(s=>{
                                const opt=document.createElement("option");
                                opt.value=s.id;
                                opt.textContent=s.name;
                                if (selectedSubcatId&&s.id==selectedSubcatId) opt.selected=true;
                                moveSubcatSel.appendChild(opt);
                            });
                        });
                    }
                
                    fetch("core/api.php",{
                        method:"POST",
                        body:new URLSearchParams({forum_admin:"get_forum_cats"})
                    })
                    .then(r=>r.json())
                    .then(data => {
                        const nav=document.getElementById("forum-breadcrumbs");
                        const currentCatid=nav.dataset.current_catid;
                        const currentSubcatId=nav.dataset.current_subcatid;
                        moveCatSel.innerHTML="";
                        (data.cats || []).forEach(c => {
                            const opt=document.createElement("option");
                            opt.value=c.id;
                            opt.textContent=c.name;
                            if (currentCatid && c.id==currentCatid) {
                                opt.selected=true;
                            }
                            moveCatSel.appendChild(opt);
                        });
                    
                        if (data.cats && data.cats.length > 0) {
                            const targetCatId=currentCatid||data.cats[0].id;
                            loadSubcats(targetCatId,currentSubcatId);
                        }
                    });
                
                    moveCatSel.addEventListener("change",function(){
                        loadSubcats(this.value,null);
                    });
                } else if (moveWrap){
                    moveWrap.classList.add("d-none");
                }

                return;
            }

            if (action==="reply_post") {
                const postEl=document.querySelector(`#post-${id} .post-content`);
                if (!postEl){
                    alert("Не удалось найти содержимое поста");
                    return;
                }

  
                window.replyPostId=id;
                document.querySelector("#editor").scrollIntoView({behavior: "smooth"});
                publishBtn.innerText="Ответить на пост "+this.dataset.vicnt;
                clearBtn.innerText="Отменить ответ"
                const moveWrap=document.getElementById("move_thread_wrap");
                if (moveWrap) moveWrap.classList.add("d-none");

                return;
            }

            if (action==="delete_post"&&!confirm("Удалить пост?")) return;
            if (action==="delete_thread"&&!confirm("Удалить весь тред?")) return;

            fetch("core/api.php",{
                method:"POST",
                body: new URLSearchParams({
                    forum_admin:action,
                    id: id
                })
            })
            .then(res=>res.json())
            .then(data => {
                if (data.success) {
                    if (action==="delete_post") {
                        window.location.reload();
                    } else if (action==="delete_thread") {
                        window.location.href="/forum.php";
                    } else if (action==="pin_thread") {
                        //this.textContent=this.textContent.includes("Закрепить")?"Открепить":"Закрепить";
                        window.location.reload();
                    } else if (action==="lock_thread") {
                        //this.textContent=this.textContent.includes("Закрыть")?"Открыть":"Закрыть";
                        window.location.reload();
                    }
                } else {
                    alert("Ошибка: "+(data.error||"Неизвестная ошибка"));
                }
            })
            .catch(err=>{
                alert("Ошибка запроса");
            });
        });
    });

    document.querySelectorAll(".reactions .reaction").forEach(reaction=>{
        reaction.addEventListener("click",function(){
            const postId=this.dataset.pid;
            const type=this.dataset.type;
        
            fetch("core/api.php", {
                method:"POST",
                body: new URLSearchParams({
                    forum:"reaction",
                    post_id:postId,
                    type:type
                })
            })
            .then(r=>r.json())
            .then(data => {
                if (data.success){
                    const countEl=this.querySelector(".count");
                
                    const count=data.count||0;
                
                    if (data.added) {
                        this.classList.add("reacted");
                    } else if (data.removed) {
                        this.classList.remove("reacted");
                    }
                    if (count > 0) {
                        countEl.style.display="inline";
                        countEl.textContent=count;
                    } else {
                        countEl.style.display="none";
                    }
                } else {
                    alert(data.error||"Ошибка реакции");
                }
            })
            .catch(()=>alert("Ошибка запроса реакции"));
        });
    });

});