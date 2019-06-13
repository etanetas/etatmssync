import "../css/main.scss";

var settingsForm = document.querySelector("#settings");

settingsForm.addEventListener("submit", function(e){
  e.preventDefault();
  console.log("sss")
  e.target.submit();
});