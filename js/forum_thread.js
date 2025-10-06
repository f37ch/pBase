document.addEventListener("DOMContentLoaded",function(){
    const editor=document.getElementById("editor")
    if (editor){
        window.quill=new Quill(editor,{
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

    const publishBtn=document.getElementById("publish");
    if (publishBtn){
        publishBtn.addEventListener("click",()=>{
            const content=JSON.stringify(window.quill.getContents());
            const threadId=new URLSearchParams(window.location.search).get("thread");

            fetch("core/api.php",{
                method:"POST",
                body: new URLSearchParams({
                    forum:"new_post",
                    thread_id:threadId,
                    content:content
                })
            })
            .then(r=>r.json())
            .then(data=>{
                if (data.success){
                    location.reload();
                } else {
                    alert(data.error||"Ошибка при публикации");
                }
            })
            .catch(err => {
                alert("Не удалось отправить пост");
            });
        });
    }

    document.querySelectorAll(".thread-btn").forEach(function(btn){
        btn.addEventListener("click", function() {
            const id=this.dataset.id;
            const action=this.dataset.action;
            if (!id||!action) return;

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
                        const postEl=document.querySelector(`#post-${id}`);
                        if (postEl) postEl.remove();
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