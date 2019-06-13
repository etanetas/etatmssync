import tmpl from './searchbox.tmpl.html';
import './searchbox.css';

class searchbox {
  constructor(){
    this.el = null;
    this.selectedElement = null;
    this.selectedClass = "etatms_searchbox-selected"
    this.contentElement = null;
    this.selectedContent = null;
    this.content = [];
  }

  getContentElement(){
    if(this.contentElement){
      return this.contentElement
    }
    const elsContent = this.el.getElementsByClassName('etatms_searchbox-content');
    if(elsContent){
      this.contentElement = elsContent[0];
      return this.contentElement;
    } else {
      return document.createElement('div');
    }
  }

  show(){
    this.getContentElement().classList.add("etatms_searchbox-content_show")
  }

  hide(){
    this.getContentElement().classList.remove("etatms_searchbox-content_show")
  }

  setSelected(newEl){
    if(newEl){
      if(this.selectedElement){
        this.selectedElement.classList.remove(this.selectedClass)
      }
      newEl.classList.add(this.selectedClass);
      newEl.scrollIntoView({block: "nearest"});
      this.selectedElement = newEl;
    }
  }

  setLoading(){
    const els = this.el.getElementsByClassName("etatms_searchbox-loading");
    if(els){
      els[0].classList.add("etatms_searchbox-loading_show");
    }
  }

  unsetLoading(){
    const els = this.el.getElementsByClassName("etatms_searchbox-loading");
    if(els){
      els[0].classList.remove("etatms_searchbox-loading_show");
    }
  }

  selectNext(){
    if(this.selectedElement && this.selectedElement.nextSibling){
      this.setSelected(this.selectedElement.nextSibling);
    } else {
      if(this.content[0]){
        this.setSelected(this.content[0].el);
      }
    }
  }

  selectPrevious(){
    if(this.selectedElement && this.selectedElement.previousSibling){
      this.setSelected(this.selectedElement.previousSibling);
    } else {
      if(this.content[0]){
        this.setSelected(this.content[this.content.length-1].el);
      }
    }
  }

  renderContent(){
    const _this = this;
    const contentEl = this.getContentElement();
    contentEl.innerHTML = "";
    if(this.content.length > 0){
      this.setSelected(this.content[0].el)
      this.content.forEach((v)=>{
        contentEl.appendChild(v.el);
      })
    }
  }

  setPlaceholder(placeHolder){
    this.placeHolder =  placeHolder
  }

  setContent(contentData){
    let _this = this;
    this.content = [];
    if(contentData){
      let i = 0;
      contentData.forEach((v)=>{
        const contentObj = {}
        contentObj.el = document.createElement("a");
        if(v.value){
          contentObj.el.innerHTML = v.value;
          contentObj.value = v.value;
          contentObj.id =  v.id;
        } else {
          contentObj.el.innerHTML = v;
          contentObj.value = v;
        }
        contentObj.el.setAttribute("data-etatms-ind", i);
        contentObj.el.addEventListener('mouseenter', e=>{
          _this.setSelected(e.target);
        })
        contentObj.el.addEventListener('click',e=>{
          const selectedID = e.target.getAttribute("data-etatms-ind");
          if(selectedID){
            const inputs = this.el.getElementsByTagName("input");
            if(inputs){
              const input = inputs[0];
              input.value = this.content[selectedID].value;
              this.selectedContent = this.content[selectedID];
              _this.hide();
            }
          }
        })
        _this.content.push(contentObj);
        i++;
      })
    }
    this.renderContent();
  }


  clear(){
    this.el = null;
    this.selectedElement = null;
    this.contentElement = null;
    this.content = [];
  }

  close(){
    this.el.remove();
    this.clear();
  }

  save(){
  }

  render(inputCallback){
    this.clear();
    let parser = new DOMParser();
    const html = parser.parseFromString(tmpl, "text/html");
    const els = html.getElementsByTagName("div");
    const _this = this;
    if(els){
      this.el = els[0];
      const inputs = this.el.getElementsByTagName("input");
      if(inputs){
        const input = inputs[0];
        if(this.placeHolder){
          input.placeholder = this.placeHolder
        }
        input.addEventListener("focus",e=>{
          inputCallback(e);
          this.show();
        });
        input.addEventListener("keydown",e=>{
          switch(e.keyCode){
            case 38: //up
              if(_this.getContentElement().classList.contains("etatms_searchbox-content_show")){
                e.preventDefault()
                _this.selectPrevious();
                break;
              }
            break;
            case 40: //down
              if(_this.getContentElement().classList.contains("etatms_searchbox-content_show")){
                e.preventDefault();
                _this.selectNext();
                break;
              }
            default:
          }
        })
        input.addEventListener("keyup", e=>{
          if(_this.contentElement.style.display == "none"){
            _this.show();
          }
          switch(e.keyCode){
            case 13: //enter
              const selectedID = this.selectedElement.getAttribute("data-etatms-ind");
              if(!this.getContentElement().classList.contains("etatms_searchbox-content_show") && this.selectedContent){
                this.save();
              }
              if(selectedID){
                e.target.value = this.content[selectedID].value;
                this.selectedContent = this.content[selectedID];
                _this.hide();
              }
            break;
            case 38:
              if(!_this.getContentElement().classList.contains("etatms_searchbox-content_show")){
                _this.show()
              }  else {
                break;
              }
            case 40:
              if(!_this.getContentElement().classList.contains("etatms_searchbox-content_show")){
                _this.show()
              } else { 
                break;
              }
            case 27:
              if(_this.getContentElement().classList.contains("etatms_searchbox-content_show")){
                _this.hide();
              } else {
                _this.close();
              }
            break;
            default:
              if(inputCallback){
                inputCallback(e, _this)
              }
            break;
          }
        })
      }
    }
    return this.el;
  }

}

export default searchbox;