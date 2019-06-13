import tmpl from './tmsTariff.tmpl.html';
import tmplNew from './new.tmpl.html';
import hogan from  'hogan.js';
import SearchBox from '../searchBox/searchbox';
import axios from 'axios';
import {TmsObj, createAddLmsTariffElement, addLmsTariff, filterLmsTariffs} from "../common";
import searchbox from '../searchBox/searchbox';

class tmsTariff {
  constructor(tmsObj){
    this.el = null;
    this.id = null;
    this.url = "/?m=etatmssynctariffs&ajax=tmstariffs"
    this.tmsTariffs = {updated: 0, tariffs: []}
    this.lmsTariffs = []
    if(tmsObj){
      this.tmsTariff = tmsObj
    } else {
      this.tmsTariff = new TmsObj()
    }
    this.globalTmsSync = []
  }

  updateTmsTariffs(){
    const _this = this
    return new Promise((resolve, reject)=>{
      axios.get(this.url)
      .then(resp =>{
        const now = new Date()
        if(resp && resp.data){
          _this.tmsTariffs.tariffs = []
          _this.tmsTariffs.updated = now.getTime()
          for(const i in resp.data){
            const tmsTariff = resp.data[i]
            const tariffObject = {}
            if(tmsTariff.enabled){
              tariffObject.id = tmsTariff.id
              tariffObject.value = tmsTariff.tarif_name
              _this.tmsTariffs.tariffs.push(tariffObject)
            }
          }
          resolve(true);
        } else {
          resolve(false);
        }
      })
      .catch(err=>{
        reject(err)
      })
    })
  }

  commit(e){
    alert("Not implemented");
  }

  reset(e){
    alert("Not implemented");
  }

  remove(e){
    alert("Not implemented");
  }

  save(e){
    alert("Not implemented");
  }

  close(e){
    this.tmsTariff.el.remove();
  }

  filterTmsTariffs(filterValue){
    let tariffs = [];
    for(const i in this.tmsTariffs.tariffs){
      const t = this.tmsTariffs.tariffs[i];
      const pat = new RegExp(`.*${filterValue}.*`,"i")///.*filterValue.*/
      if(pat.test(t.value)){
        if(!this.globalTmsSync[t.id]){
          tariffs.push(t);
        }
      } 
    }
    return tariffs;
  }

  eventAddLmsTariff(e, buttonAdd){
    createAddLmsTariffElement(null, this.tmsTariff.el, this.tmsTariff.lmsTariffs)
    return
  }

  renderTariffContent(el, name){
    const tmpl = `
      ${name} <div class="etatms_tariffs_tmsbuttons">
        <div class="etatms_tariffs_tmsbuttons_commit etatms_tariffs_tmsbuttons_commit-show">
          <a href class="etatms_tariffs_tmstariff_commit etatms_button">commit</a>
          <a href class="etatms_tariffs_tmstariff_reset etatms_button">reset</a>
        </div>
        <i class="fa fa-minus etatms_tariffs_tmstariff_delete etatms_button" aria-hidden="true"></i>
      </div>
    `
    el.innerHTML = tmpl
    const btnCommit = el.getElementsByClassName("etatms_tariffs_tmstariff_commit")
    if(btnCommit && btnCommit[0]){
      btnCommit[0].addEventListener('click', e=>{
        e.preventDefault();
        this.commit(e, this.tmsTariff);
      });
    }

    const btnReset = el.getElementsByClassName("etatms_tariffs_tmstariff_reset")
    if(btnReset && btnReset[0]){
      btnReset[0].addEventListener('click', e=>{
        e.preventDefault();
        this.reset(e);
      });
    }

    const btnDelete = el.getElementsByClassName("etatms_tariffs_tmstariff_delete")
    if(btnDelete && btnDelete[0]){
      btnDelete[0].addEventListener('click', e=>{
        e.preventDefault();
        this.remove(e);
      });
    }
  }
  rerenderLmsTariff(){
    const btnAdd = this.tmsTariff.el.querySelector(".etatms_tariffs_lmstariff_add");
    const lmsTariffsContainer = this.tmsTariff.el.querySelector(".etatms_tariffs_lmsids");
    lmsTariffsContainer.innerHTML = "";
    for(const i in this.tmsTariff.lmsTariffs){
      lmsTariffsContainer.appendChild(this.tmsTariff.lmsTariffs[i].el);
    }
    lmsTariffsContainer.appendChild(btnAdd);
    this.tmsTariff.hideCommit()
  }

  render(){
    const _this = this;
    let parser = new DOMParser();
    let htmpl = null;
    let htmlTmpl = null;
    if(this.tmsTariff.id){
      htmpl = hogan.compile(tmpl);
      htmlTmpl = parser.parseFromString(htmpl.render({
        id:this.tmsTariff.id,
        name: this.tmsTariff.name,
        syncID: this.tmsTariff.syncID,
        tariffs: this.tmsTariff.lmsTariffs
      }), "text/html");
    } else {
      const searchBox = new SearchBox();
      searchBox.close = e=>{
        _this.close()
      }
      const searchBoxElement = searchBox.render((e)=>{
        const now = new Date()
        if(!this.tmsTariffs.updated || this.tmsTariffs.updated < (now - (1*60*1000))){
          this.updateTmsTariffs()
          .then(r=>{
            if(r){
              searchBox.setContent(this.filterTmsTariffs(e.target.value))
            }
          })
          .catch(err=>{
            console.error(err) }) 
        } else {
          searchBox.setContent(this.filterTmsTariffs(e.target.value))
        }
      })
      htmpl = hogan.compile(tmplNew);
      htmlTmpl = parser.parseFromString(htmpl.render(), "text/html");

      const searchBoxPlace = htmlTmpl.getElementsByClassName("etatms_tariffs_tmsid");
      if(searchBoxPlace && searchBoxPlace[0]){
        searchBoxPlace[0].prepend(searchBoxElement);
      }

      const btnClose = htmlTmpl.getElementsByClassName("etatms_tariffs_newtmstariff_cancel");
      if(btnClose && btnClose[0]){
        btnClose[0].addEventListener("click",e=>{
          _this.close();
        })
      }

      const saveEvent = e=>{
          const tmsContent = this.tmsTariff.el.getElementsByClassName("etatms_tariffs_tmsid");
          if(tmsContent && tmsContent[0]){
            this.tmsTariff.id = searchBox.selectedContent.id
            this.tmsTariff.name = searchBox.selectedContent.value
            this.renderTariffContent(tmsContent[0], searchBox.selectedContent.value)
          }
          this.save()
      }

      const btnSave = htmlTmpl.getElementsByClassName("etatms_tariffs_newtmstariff_save");
      if(btnSave && btnSave[0]){
        btnSave[0].addEventListener("click", saveEvent)
        // e=>{
        //   const tmsContent = this.tmsTariff.el.getElementsByClassName("etatms_tariffs_tmsid");
        //   if(tmsContent && tmsContent[0]){
        //     this.tmsTariff.id = searchBox.selectedContent.id
        //     this.tmsTariff.name = searchBox.selectedContent.value
        //     this.renderTariffContent(tmsContent[0], searchBox.selectedContent.value)
        //   }
        //   this.save()
        // })
      }
      searchBox.save = saveEvent
    }
    this.tmsTariff.el = htmlTmpl.querySelector("div");
    const buttonAdd = this.tmsTariff.el.querySelector('.etatms_tariffs_lmstariff_add')
    for(const i in this.tmsTariff.lmsTariffs){
      const t = this.tmsTariff.lmsTariffs[i]
      buttonAdd.parentNode.insertBefore(t.el, buttonAdd);
    }
    if(!this.tmsTariff.id){
      const addLmsTariffBtn = this.tmsTariff.el.getElementsByClassName("etatms_tariffs_lmstariff_add");
      if(addLmsTariffBtn && addLmsTariffBtn[0]){
        addLmsTariffBtn[0].addEventListener('click',(e)=>{
          createAddLmsTariffElement(null, this.tmsTariff, this.tmsTariff.lmsTariffs);
        });
      }
    }
    return this.tmsTariff.el;
  }
}

export default tmsTariff;