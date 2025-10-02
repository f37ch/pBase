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
                            if (value){
                                const safeValue=value.replace(/\\/g,'\\\\').replace(/"/g,'\\"');
                                this.quill.insertEmbed(range?range.index:0,"image",safeValue,Quill.sources.USER);
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

            if (!content||!threadId) {
                alert("Ошибка: нет текста или треда");
                return;
            }

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
});