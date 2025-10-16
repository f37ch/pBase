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
                } else {
                    alert(data.error||"Ошибка при публикации");
                }
                window.editingPostId=null;
                window.replyPostId=null;
                publishBtn.innerText="Ответить"
                clearBtn.innerText="Очистить поле";
            })
            .catch(err=>{
                window.editingPostId=null;
                window.replyPostId=null;
                publishBtn.innerText="Ответить";
                clearBtn.innerText="Очистить поле";
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