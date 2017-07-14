/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function header_dropdown() {
  document.getElementById("dropdownMenu").classList.toggle("show");
}

// Close the dropdown if the user clicks outside of it
document.onclick = function(e) {
  if (!e.target.matches('.dropbtn')) {
    var myDropdown = document.getElementById("dropdownMenu");
    if (myDropdown.classList.contains('show')) {
      myDropdown.classList.remove('show');
    }
  }
}

//Hide the menu when the hide button is clicked and it stores the state in a
//cookie so its saved acrossed every reload and page
function hide_logged_menu() {
  document.getElementById("login_menu").classList.toggle("hide");
  document.getElementById("loginHeader").classList.toggle("headerHide");
  var button = document.getElementById("hideBtn");
  if(button.innerHTML == "Show"){
    button.innerHTML = "Hide";
    document.cookie = "menu=visible; path=/";
  }else{
    button.innerHTML = "Show";
    document.cookie = "menu=hidden; path=/";
  }
}


function loadPreferences(){
  var cookieData = document.cookie;
  cookieData = cookieData.split(';');
  for(var i = 0; i < cookieData.length; i++){
    var tmp = cookieData[i];
    if(tmp.indexOf("menu") !== -1){
      tmp = tmp.split('=');
      if(tmp[1] == "hidden"){
        hide_logged_menu();
      }
    }
  }
}
