import axios from "axios";
import lmsTariffElement from "./lmsTariff/lmsTariff.js";
import searchbox from "./searchBox/searchbox.js";

export class TmsObj{
  constructor(){
    this.id = null;
    this.lmsTariffs = [];
    this.orig = null;
    this.trigger = false;
    this.el = null;
  }

  changedLms(){
    if(JSON.stringify(this.orig.lmsTariffs) != JSON.stringify(this.lmsTariffs)){
      return true;
    }
    return false;
  }

  changed(){
    return false
  }

  showCommit(){
    const btn = this.el.querySelector(".etatms_tariffs_tmsbuttons_commit")
    if(btn){
      btn.classList.add("etatms_tariffs_tmsbuttons_commit-show");
    }
  }

  hideCommit(){
    const btn = this.el.querySelector(".etatms_tariffs_tmsbuttons_commit");
    if(btn){
      btn.classList.remove("etatms_tariffs_tmsbuttons_commit-show");
    }
  }
}

function lmsTariffInArray(tariffID, tariffsList){
  for(const i in tariffsList){
    if(tariffsList[i].id == tariffID){
      return true
    }
  }
  return false
}

export function filterLmsTariffs(e, sb, lmsTariffs, lmsIds=[]){
  let url = "/?m=etatmssynctariffs&ajax=lmstariffs";
  if(e.target.value){
    url += `&q=${e.target.value}`
  }
  sb.setLoading();
  axios.get(url)
  .then(r=>{
    if(r.data){
      lmsTariffs = [];
      r.data.forEach(val=>{
        if(lmsIds){
          if(!lmsTariffInArray(val.id, lmsIds)){
            let tObj = {"value":val.name,"id":val.id};
            lmsTariffs.push(tObj);
          }
        } else {
            let tObj = {"value":val.name,"id":val.id};
            lmsTariffs.push(tObj);
        }
      })
    }
    sb.setContent(lmsTariffs);
    sb.unsetLoading();
  })
}

export function getFirstParentByClass(el, className){
  let node = null;
  if(className == ""){
    return node
  }
  while(el.parentNode && el.parentNode.classList){
    let nel = el.parentNode
    if(el.parentNode.classList.contains(className)){
      return nel
    } else {
      el = nel
    }
  }
  return
}

export function addLmsTariff(id, name, parent, tmsRow){
  const lmsTariff = new lmsTariffElement({id:id, name:name});
  lmsTariff.removeEvent = e=>{
    if(tmsRow){
      eventRemoveLmsTariff(e,null, tmsRow)
    } 
  }
  const el = lmsTariff.render();
  parent.appendChild(el);
  if(tmsRow){
    tmsRow.lmsTariffs.push({
      id:id,
      name: name,
      el:el
    })
  } 
  return el;
}

export function eventRemoveLmsTariff(e, tmsSync, tmsRow){
  let lmsID = null;
  let tmsID = null;
  let lmsPar = getFirstParentByClass(e.target, "etatms_tariffs_lmstariff");
  if(lmsPar){
    lmsID = lmsPar.getAttribute("data-etatms-lmsid");
  } 
  let tmsPar = getFirstParentByClass(e.target, "etatms_tariffs_row");
  if(tmsPar){
    tmsID = tmsPar.getAttribute("data-etatms-tmsid");
  }
  if(lmsID){
    if(tmsRow){
      removeLmsTariff(tmsRow, lmsID);
    } else if (tmsID && tmsSync[tmsID]){
      tmsRow = tmsSync[tmsID];
      removeLmsTariff(tmsRow, lmsID);
    } else {
      console.error(`Failed to find required tmsRow, tmsid:${tmsRow}`);
    }
  } else {
      console.error(`Failed to find required lmsid:${lmsID}`);
  }
}

export function removeLmsTariff(tmsRow, lmsid){
  if(tmsRow){
    let lmsTariffItem = getLmsTariff(tmsRow.lmsTariffs, lmsid)
    tmsRow.lmsTariffs.splice(lmsTariffItem.ind, 1);
    lmsTariffItem.item.el.remove();
  } else {
    console.warn("Can't find tms tariff")
  }
}

export function getLmsTariff(lmsTariffs, lmsid){
  for(const i in lmsTariffs){
    if(lmsTariffs[i].id == lmsid){
      return {ind: i, item: lmsTariffs[i]}
    }
  }
}

export function eventCommitTmsTariff(tmsRow){
}

export function resetTmsRow(tmsRow){
}

export function commitTariff(tariff){
  const data = {id: tariff.syncID, tmsID: tariff.id, lmsTariffs: tariff.lmsTariffs}
  const buttonsArea = tariff.el.getElementsByClassName("etatms_tariffs_tmsbuttons")
  let keepElement = null
  const loader = document.createElement("i");
  loader.classList = "fa fa-spinner fa-spin";
  loader.style.marginRight = "20px";
  if(buttonsArea && buttonsArea[0]){
    keepElement = buttonsArea[0]
    keepElement.parentNode.replaceChild(loader, keepElement);
  }
  return new Promise((resolve, reject)=>{
    const url = "/?m=etatmssynctariffs&ajax=commit"
    axios.post(url, data)
    .then(resp=>{
      loader.parentNode.replaceChild(keepElement, loader);
      resolve(resp)
    })
    .catch(err=>{
      loader.parentNode.replaceChild(keepElement, loader);
      reject(err)
    })
  })
}

export function createAddLmsTariffElement(triggeredElement, rowObj, lmsTariffs){
  const buttonAddTmp = rowObj.el.getElementsByClassName("etatms_tariffs_lmstariff_add");
  let buttonAdd = null;
  if(buttonAddTmp && buttonAddTmp[0]){
    buttonAdd = buttonAddTmp[0];
  }
  // let buttonAdd = getFirstParentByClass(triggeredElement, "etatms_tariffs_lmstariff_add");
  // const parent = buttonAdd.parentNode;
  if(!buttonAdd){
    return
  }
  const sb = new searchbox();
  let el = document.createElement('div');
  let parent = null;
  const parentTmp = rowObj.el.getElementsByClassName("etatms_tariffs_lmsids");
  if(parentTmp && parentTmp[0]){
    parent = parentTmp[0]
  }
  buttonAdd.remove();
  el.setAttribute("data-etatms-addLmsTariff","");
  el.classList = ["etatms_tariffs_lmstariff"];
  el.appendChild(
    sb.render( e=>{filterLmsTariffs(e, sb, lmsTariffs, rowObj.lmsTariffs)})
  );
  sb.setContent(lmsTariffs);
  let elOK = document.createElement("i");
  const saveEvent = (e)=>{
    if(sb.selectedContent && sb.selectedContent.id){
      let tmsPar = getFirstParentByClass(el, "etatms_tariffs_row");
      if(tmsPar){
        const tmsID = tmsPar.getAttribute("data-etatms-tmsid");
      addLmsTariff(sb.selectedContent.id, sb.selectedContent.value, parent, rowObj);
      el.remove()
      parent.appendChild(buttonAdd);
      }
    }
  }
  elOK.classList = ["fa fa-check etatms_tariffs_lmstariff_add"];
  elOK.addEventListener("click", saveEvent)
  //  e=>{
  //   saveEvent();
    // if(sb.selectedContent && sb.selectedContent.id){
    //   let tmsPar = getFirstParentByClass(el, "etatms_tariffs_row");
    //   if(tmsPar){
    //     const tmsID = tmsPar.getAttribute("data-etatms-tmsid");
    //   addLmsTariff(sb.selectedContent.id, sb.selectedContent.value, parent, rowObj);
    //   el.remove()
    //   parent.appendChild(buttonAdd);
    //   }
    // }
  // })
  sb.save = saveEvent;
  
  el.appendChild(elOK);
  let elCancel = document.createElement("i");
  elCancel.classList = ["fa fa-times etatms_tariffs_lmstariff_cancel"];
  el.appendChild(elCancel);
  elCancel.addEventListener("click",e=>{
    el.remove();
    parent.appendChild(buttonAdd);
  });
  parent.appendChild(el);
  sb.close = (e)=>{
    el.remove();
    parent.appendChild(buttonAdd);
  }
}