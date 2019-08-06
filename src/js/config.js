import "../css/main.scss";

window.onload = function(){
  var settingsForm = document.querySelector("#settings");

  settingsForm.addEventListener("submit", function(e){
    e.preventDefault();
    e.target.submit();
  });
}
