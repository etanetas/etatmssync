import tmpl from './lmsTariff.tmpl.html';
import './lmsTariff.css';
import hogan from  'hogan.js';

class lmsTariff {
  constructor(lmsObj){
    this.el = null;
    this.id = "";
    if(lmsObj.id){
      this.id = lmsObj.id;
    }
    this.name = "";
    if(lmsObj.name){
      this.name = lmsObj.name;
    }
    this.removeEvent = e=>{console.log("not implemented")}
  }

  close(){
    this.el.remove();
  }

  render(){
    const _this = this;
    let parser = new DOMParser();
    const htmpl = hogan.compile(tmpl);
    const html = parser.parseFromString(htmpl.render({id:this.id, name:this.name}), "text/html");
    const els = html.getElementsByTagName("div");
    if(els){
      this.el = els[0];
    }
    const rmButtonTmp = this.el.getElementsByClassName('etatms_tariffs_lmstariff_delete');
    if(rmButtonTmp){
      rmButtonTmp[0].addEventListener('click', this.removeEvent);
    }
    return this.el;
  }
}

export default lmsTariff;