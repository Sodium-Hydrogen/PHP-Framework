var fontawesomePath = "/resources/fontawesome/css/all.css";
var movement = {"dir":"down", "y":0, "type":""};

window.addEventListener("load", function(){
  var elements = document.querySelectorAll(".item-list .moveable .bars");
  elements.forEach(function(elem){
      elem.addEventListener("dragstart", function(){startMove(elem, event, "mouse")});
      elem.addEventListener("drag", function(){locationUpdate(elem, event, "mouse")});
      elem.addEventListener("dragend", function(){endMove(elem, "mouse")});

      elem.addEventListener("touchstart", function(){startMove(elem, event, "touch")});
      elem.addEventListener("touchmove", function(){locationUpdate(elem, event, "touch")});
      elem.addEventListener("touchend", function(){endMove(elem, "touch")});

  });
  var input = document.getElementById("icon-input");
  var type = document.getElementById("icon-type");
  if(input != null && type != null && fontawesomePath != null){
    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = fontawesomePath;
    document.head.appendChild(link);

    var style = document.createElement("style");
    style.innerText = "i:before {content:'unset'}";
    input.parentElement.append(style);
    input.addEventListener("input", updateIcon);
    type.addEventListener("input", updateIcon);
    updateIcon();
  }
});

function updateIcon(){
  var text = document.getElementById("icon-input");
  var type = document.getElementById("icon-type");
  var icon = document.getElementById("icon");

  var name = "fa" + type.value[0] + " fa-" + text.value;
  icon.className = name;
}

function locationUpdate(e, event){
  if(event.type == "touchmove"){
    var x = event.touches[0].clientX;
    var y = event.touches[0].clientY;
  }else{
    var x = event.x;
    var y = event.y;
  }
  y += window.scrollY;
  if(movement.y < y){
    movement.dir = "down";
  }else if(movement.y > y){
    movement.dir = "up"
  }
  movement.y = y;
  y = y - 2*(e.offsetHeight/3);
  var elements = document.querySelectorAll(".item-list .moveable");
  elements.forEach(function(elem){
    if(!elem.hasAttribute("id") && !elem.classList.contains("selected")){
      var center = elem.offsetTop + elem.offsetHeight/2;
      if(Math.abs(y-center) < elem.offsetHeight/3){
        if(!elem.classList.contains("hovered-over")){
          var tmp = document.getElementById("tmp-item");
          var dup;
          if(movement.dir == "down"){
            dup = elem.insertAdjacentElement("afterend", tmp.cloneNode(true));
          }else{
            dup = elem.insertAdjacentElement("beforebegin", tmp.cloneNode(true));
          }
          tmp.parentElement.removeChild(tmp);
          elem.classList.add("hovered-over");
        }
      }else if(elem.classList.contains("hovered-over")){
        elem.classList.remove("hovered-over");
      }
    }
  });
  e.parentNode.style.top = y + "px";
}

function startMove(e, event){
  if(movement.type == ""){
    movement.type = event.type
  }
  if(movement.type == event.type){
    var dup = e.parentNode.insertAdjacentElement("afterend", e.parentElement.cloneNode(true));
    dup.setAttribute("id", "tmp-item");
    e.parentNode.style.width = window.getComputedStyle(e.parentNode).width;
    e.parentNode.classList.add("selected");
    if(event.type == "dragstart"){
      var null_elem = document.createElement("div");
      null_elem.classList.add("null");
      event.dataTransfer.setDragImage(null_elem, 0, 0);
    }
  }
}

function endMove(e, type){
  e.parentElement.style.top = "";
  e.parentElement.style.width = "";
  e.parentElement.classList.remove("selected");
  var dup;
  while(dup = document.getElementById("tmp-item")){
    dup.parentElement.replaceChild(e.parentElement, dup);
  }
  movement.type = "";
}

function saveValues(e, getOrder){
  var submit = e.querySelector("#page_action");
  if(getOrder){
    elements = document.querySelectorAll(".item-list .moveable .name");
    html = "";
    for(var i = 0; i < elements.length; i++){
      html += "<input type='hidden' name='" + i + "' ";
      html += "value='" + elements[i].innerText.replace("'", "\\'") + "'>";
    }
    e.insertAdjacentHTML("beforeend", html);
  }
}

function promptSubmit(e){
  if(window.varprompt !== undefined){
    var res = confirm(window.varprompt);
    if(!res){
      e.preventDefault();
    }
  }
  window.varprompt = undefined;
}

function confirmDelete(e, type){
  var message = "Are you sure you would like to delete: " + e + "?\n\n";
  message += "This is irreversible";
  if(type == "page"){
    message += " and will delete all posts associated with this page";
  }else if(type == "section"){
    message += " and will delete all links associated with this section";
  }
  message += ".\n\nContinue?";
  return confirm(message);
}

function updatePageOrder(e){
  var pages = document.querySelectorAll(".item-list .moveable .name");
  var html = "";
  for(var i = 0; i < pages.length; i++){
    html += "<input type='hidden' name='" + i + "' ";
    html += "value='" + pages[i].innerText.replace("'", "\\'") + "'>";
  }
  e.insertAdjacentHTML("afterBegin", html);
}
