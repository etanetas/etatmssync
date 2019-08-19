import TmsTariff from "./tmsTariff/tmsTariff";
import axios from "axios";
import {TmsObj, createAddLmsTariffElement, commitTariff, addLmsTariff, eventRemoveLmsTariff, filterLmsTariffs, getFirstParentByClass} from "./common";
import "../css/main.scss";
window.onload = function(){

debugger;
let lmsTariffs = [
]

function getTmsTariffHandler(rObj){
  return {
    set: (obj, prop, value) => {
      if (obj.changed() || obj.changedLms()) {
        rObj.showCommit()
      } else {
        rObj.hideCommit()
      }
      obj[prop] = value
      return true
    }
  }
}

function getLmsTariffsHandler(obj){
  return {
    set(target, props, newVal) {
      target[props] = newVal
      if (obj.changedLms() || obj.changed()) {
        obj.showCommit()
      } else {
        obj.hideCommit()
      }
      return true;gg
    },
    get(target, props) {
      return target[props];
    }
  }
}

let tmsSync = {
}

let newTmsTariff = null

const handler = {
}

function init(){
  const btnAddTmsTariff = document.querySelector(".etatms_tariffs_tmstariff_add")
  if(btnAddTmsTariff){
    btnAddTmsTariff.addEventListener("click", eventAddTmsTariff)
  }
  const btnSyncMiddleware = document.querySelector('.etatms_header_syncbutton')
  btnSyncMiddleware.addEventListener("click", e=>{
    btnSyncMiddleware.classList.remove("etatms_header_syncbutton_error");
    const btnContent = btnSyncMiddleware.innerHTML
    const loader =  document.createElement("i");
    loader.classList = "fa fa-spinner fa-spin";
    btnSyncMiddleware.prepend(loader)
    btnSyncMiddleware.classList.add("button-centered")
    runMiddlewareSync()
    .then(r=>{
      loader.remove();
    })
    .catch(err=>{
      console.log("err");
      loader.remove();
      btnSyncMiddleware.classList.add("etatms_header_syncbutton_error");
    })
  })
  let rows = document.querySelectorAll(".etatms_tariffs_row");
  if(rows){
    const pat = /\d+/
    for(const i in rows){
      if(!pat.test(i)){
        continue
      }
      const el = rows[i]
      let rObj = new TmsObj()
      let id = el.getAttribute("data-etatms-tmsid");
      if(!id){
        console.error("Failed to get tms id");
      }
      let name = el.getAttribute("data-etatms-tmsname");
      if(!name){
        console.error("Failed to get tms name");
      }
      let syncid = el.getAttribute("data-etatms-id");
      if(!syncid){
        console.error("Failed to get sync id");
      }
      rObj.el = el
      rObj.id = id
      rObj.syncID = syncid
      rObj.name = name
      const lmsTariffs = [];
      const lmsTariffsTmp = el.getElementsByClassName("etatms_tariffs_lmstariff");
      for(const i in lmsTariffsTmp){
        if(lmsTariffsTmp[i]){
          const lmsEl = lmsTariffsTmp[i];
          if(lmsEl.tagName){
            const lmsID = lmsEl.getAttribute("data-etatms-lmsid");
            const lmsName = lmsEl.getAttribute("data-etatms-lmsname");
            if(lmsID){
              lmsTariffs.push({id:lmsID, name: lmsName, el: lmsEl})
            }
          }
        }
      }
      const origObj = Object.assign({},rObj);
      rObj.orig = origObj
      const lmsTariffsOrig = Object.assign([],lmsTariffs);
      rObj.orig.lmsTariffs = lmsTariffsOrig;
      rObj.lmsTariffs = new Proxy(lmsTariffs, getLmsTariffsHandler(rObj));
      tmsSync[id] = new Proxy(rObj, getTmsTariffHandler(rObj));
      addRowEvents(rObj)
    }
  }
}

function runMiddlewareSync(){
  return new Promise((resolve, reject)=>{
    axios.post("/?m=etatmssynctariffs&ajax=syncmiddleware")
    .then(r=>{
      resolve()
    })
    .catch(err=>{
      reject(err)
    })
  });
}

function showCommit(tariffEl){
  const btn = tariffEl.getElementsByClassName("etatms_tariffs_tmsbuttons_commit");
  if(btn){
    btn[0].classList.add("etatms_tariffs_tmsbuttons_commit-show");
  }
}

function hideCommit(tariffEl){
  const btn = tariffEl.getElementsByClassName("etatms_tariffs_tmsbuttons_commit");
  if(btn){
    btn[0].classList.remove("etatms_tariffs_tmsbuttons_commit-show");
  }
}

function addRowEvents(rowObj){
  const btnadd = rowObj.el.getElementsByClassName("etatms_tariffs_lmstariff_add")
  if(btnadd && btnadd[0]){
    btnadd[0].addEventListener('click', (e)=>{
      createAddLmsTariffElement(e.target, rowObj, lmsTariffs);
    })
  }
  const btndelete = rowObj.el.getElementsByClassName('etatms_tariffs_tmstariff_delete')
  if(btndelete && btndelete[0]){
    btndelete[0].removeEventListener('click', btndelete[0].click);
    btndelete[0].addEventListener("click", eventRemoveTmsTariff);
  }
  const btndeletelms = rowObj.el.getElementsByClassName('etatms_tariffs_lmstariff_delete')
  for(const i in btndeletelms){
    if(btndeletelms[i].tagName){
      btndeletelms[i].removeEventListener('click', btndeletelms[i].click);
      btndeletelms[i].addEventListener("click",e=>{
        eventRemoveLmsTariff(e, tmsSync)
      })
    }
  }
  const btnCommit = rowObj.el.getElementsByClassName("etatms_tariffs_tmstariff_commit")
  if(btnCommit && btnCommit[0]){
    btnCommit[0].addEventListener('click', e=>{
      commitTmsTariff(e, tmsSync[rowObj.id]);
    })
  }
  const btnReset = rowObj.el.getElementsByClassName("etatms_tariffs_tmstariff_reset")
  if(btnReset && btnReset[0]){
    btnReset[0].addEventListener('click', e=>{
      e.preventDefault()
      rowObj.lmsTariffs = new Proxy(Object.assign([],rowObj.orig.lmsTariffs), getLmsTariffsHandler(rowObj));
      const tmsTariff = new TmsTariff(rowObj)
      tmsTariff.rerenderLmsTariff()
    })
  }
}

function renderTmsRow(rowObj){
  const tmsTariff = new TmsTariff(rowObj)
  const el = tmsTariff.render()
  return el
}

function eventAddTmsTariff(e){
  if(!newTmsTariff){
    newTmsTariff = new TmsTariff();
    newTmsTariff.globalTmsSync = tmsSync
    newTmsTariff.close = (e)=>{
      newTmsTariff.tmsTariff.el.remove()
      newTmsTariff = null
    };
    newTmsTariff.save = (e)=>{
      if(newTmsTariff.tmsTariff){
        tmsSync['new'] = newTmsTariff.tmsTariff
      }
    }
    newTmsTariff.commit = (e, tmsRow)=>{
      commitTariff(tmsRow)
      .then(r=>{
        const origRow = {
          id: tmsRow.id,
          lmsTariffs:Object.assign([],tmsRow.lmsTariffs),
          el:tmsRow.el
        };
        tmsRow.orig = origRow
        tmsRow.lmsTariffs = new Proxy(tmsRow.lmsTariffs, getLmsTariffsHandler(tmsRow));
        tmsSync[tmsRow.id] = new Proxy(Object.assign(new TmsObj(),tmsRow), getTmsTariffHandler(tmsRow));
        tmsSync[tmsRow.id].trigger = new Date()
        if(r.data.id){
          tmsSync[tmsRow.id].syncID = r.data.id;
        }
        const newEl = renderTmsRow(tmsSync[tmsRow.id])
        const par = tmsRow.el.parentNode;
        par.replaceChild(newEl, tmsRow.el);
        tmsSync[tmsRow.id].el = newEl;
        addRowEvents(tmsSync[tmsRow.id]);
        delete(tmsSync['new']);
        newTmsTariff=null;
      })
      .catch(err=>{
        console.error(err);
      })
    }
    newTmsTariff.reset = (e, tmsRow)=>{
      if(tmsSync['new']){
        delete tmsSync['new'];
      }
      newTmsTariff.tmsTariff.el.remove();
      newTmsTariff=null;
    }
    newTmsTariff.remove = (e, tmsRow)=>{
      if(tmsSync['new']){
        delete tmsSync['new'];
      }
      newTmsTariff.tmsTariff.el.remove();
      newTmsTariff=null;
    }
    const content = document.querySelector(".etatms_tariffs_content");
    if(content){
      content.prepend(newTmsTariff.render())
    }
  }
}

function eventRemoveTmsTariff(e){
  let tmsID = null;
  let tmsPar = getFirstParentByClass(e.target, "etatms_tariffs_row");
  if(tmsPar){
    tmsID = tmsPar.getAttribute("data-etatms-tmsid");
  }
  if(tmsID){
    removeTmsTariff(tmsID);
  }
}

function removeTmsTariff(tmsid){
  let url = "/?m=etatmssynctariffs&ajax=delete";
  const el = tmsSync[tmsid].el;
  const buttonsTmp = el.getElementsByClassName("etatms_tariffs_tmsbuttons");
  let buttonsDiv = null
  let buttonsContent = null
  let resetButtons = ()=>{
    buttonsDiv.innerHTML = buttonsContent;
    const btnDel = buttonsDiv.getElementsByClassName("etatms_tariffs_tmstariff_delete")
    if(btnDel){
      btnDel[0].addEventListener('click',e=>{eventRemoveTmsTariff(e, tmsSync)});
    }
  }

  const r = confirm(`Delete?`)
  if(r){
    if(buttonsTmp){
      buttonsDiv = buttonsTmp[0];
      buttonsContent = buttonsDiv.innerHTML;
      buttonsDiv.innerText = "";
      let loader = document.createElement("i");
      loader.classList = "fa fa-spinner fa-spin";
      buttonsDiv.appendChild(loader)
      axios.post(url, {id:tmsSync[tmsid].syncID})
      .then(r=>{
        resetButtons()
        tmsSync[tmsid].el.remove();
        delete tmsSync[tmsid];
      })
      .catch(err=>{
        resetButtons()
        alert(`Server error: ${err}`);
      })
    }
    setTimeout(()=>{
    }, 2000)
  }
}

function commitTmsTariff(e, tariff){
  e.preventDefault();
  commitTariff(tariff)
  .then(r=>{
    tariff.orig.lmsTariffs = Object.assign([],tariff.lmsTariffs)//JSON.parse(JSON.stringify(tariff.lmsTariffs))
    tariff.trigger = new Date();
  })
  .catch(err=>{
    console.error(err)
  })
}

init();
}
