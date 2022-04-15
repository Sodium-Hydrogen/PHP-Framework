// var fontawesomePath = "/resources/fontawesome/css/all.css";
var not_firefox = navigator.userAgent.toLowerCase().indexOf("firefox") == -1;
var hovered_timeout = undefined;
var input_value_converter = undefined;
var file_editor = undefined;
var open_editors = [];


window.onload = setup_document;


function setup_document(){
	// Load ace libraries
	if(ace){
		// Extensions
		ace.config.loadModule("ace/ext/spellcheck");
		ace.config.loadModule("ace/ext/modelist");

		ace.config.loadModule("ace/mode/html");
		ace.config.loadModule("ace/theme/chaos");
	}

	// Setup help message listeners
	if(!not_firefox && document.cookie.search(/accept_firefox=true/) != -1){
		alert("You are using firefox and some functionality may not work properly?");
		document.cookie = "accept_firefox=true";
	}
	setup_help();

	// General document click listeners
	document.onclick = function(e) {
		if(e.target.matches(".clear_message")){
			e.target.parentElement.classList.add("hide-clear");
			setTimeout(function(){e.target.parentElement.parentElement.removeChild(e.target.parentElement)}, 300);
		}else if (!e.target.matches('.help') && !e.target.matches(".help-msg")) {
			var hide;
			while(hide = document.querySelector(".help-msg:not(.hidden)")){
				hide.classList.add("hidden");
			}
		}
	}

	setup_remove_groups();
	prevent_enter_key_on_form();
	convert_unix_time();
	var elem = document.getElementById("select_all_users"); if(elem)elem.onclick = select_all;
	elem = document.getElementById("bulk_add_group"); if(elem)elem.onclick = group_add;
	elem = document.getElementById("logout_everywhere"); if(elem)elem.onclick = logout_everywhere;
	elem = document.getElementById("user-manager"); if(elem){elem.onsubmit = user_manager_submit;user_manager_submit(elem);}

	elem = document.getElementById("header-tabs"); if(elem)elem.onclick = header_tabs;
	window.onhashchange = header_tabs;

	elem = document.getElementById("tab_settings"); if(elem){elem.onsubmit = settings_submit;settings_submit(elem);}
	elem = document.getElementById("tab_login_links"); if(elem){elem.onsubmit = links_submit;links_submit(elem);}
	elem = document.getElementById("tab_logs"); if(elem){elem.onsubmit = logs_submit;logs_submit(elem);}
	elem = document.getElementById("mobile_menu"); if(elem)elem.onclick = function(e){ e.target.parentElement.classList.toggle("expanded"); }

	elem = document.getElementById("new_setting_type"); if(elem)elem.onchange = change_type_input;
	elem = document.getElementById("logs_uid"); if(elem)elem.onchange = function(e){e.target.form.querySelector("input[type='submit']").click();}

	elem = document.getElementById("tab_pages"); if(elem){elem.onsubmit = pages_submit;pages_submit(elem);}
	elem = document.getElementById("tab_footers"); if(elem){elem.onsubmit = footers_submit;footers_submit(elem);}
	elem = document.getElementById("tab_files"); if(elem){elem.onsubmit = files_submit;files_submit(elem);}

	elem = document.getElementById("add_new_folder"); if(elem){elem.onclick = (e)=>{add_new_file(true);}}
	elem = document.getElementById("add_new_file"); if(elem){elem.onclick = (e)=>{add_new_file();}}
	
	elem = document.getElementById("new_page_content_clone"); if(elem)elem.onchange = (e)=>{
		e.target.parentElement.nextElementSibling.classList.toggle("hidden");
		e.target.parentElement.nextElementSibling.nextElementSibling.classList.toggle("hidden");
	}
	document.querySelectorAll("button.collapse_all").forEach((btn)=>{btn.onclick = (e)=>{
		e.target.parentElement.parentElement.nextElementSibling.querySelectorAll(".expanded .expand").forEach((exp)=>{exp.click()});
		e.preventDefault();
	}});
	document.querySelectorAll(".show_popup").forEach((btn)=>{
		btn.onclick = show_hidden_input;
		var container = document.getElementById(btn.id+"_inputs");
		container.querySelectorAll("input[type=submit]").forEach((submit)=>{
			submit.onclick = (e)=>{container.firstElementChild.firstElementChild.click();}
		});
	});


	// Update font awesome via live preview
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
}

function convert_unix_time(show_older_time=false){
	var elems = document.querySelectorAll(".raw-unix-time");
	if(elems){
		var today = new Date().toLocaleDateString();
		elems.forEach(function(e){
			var unix = new Date(e.innerText*1000);
			if(show_older_time){
				e.innerText = unix.toLocaleString();

			}else if(today == unix.toLocaleDateString()){
				e.innerText = unix.toLocaleTimeString();
			}else{
				e.innerText = unix.toLocaleDateString();
			}
			e.classList.remove("raw-unix-time");

		});
	}

}

function change_type_input(e){
	var val_elem = document.getElementById("new_setting_value");
	if(val_elem){
		if(e.target.value == "BOOL"){
			val_elem.type = "checkbox";
			val_elem.value = 1;
		}else if(e.target.value == "INT"){
			val_elem.type = "number";
		}else{
			val_elem.type = "text";
		}
	}
}
function header_tabs(e){
	if(e === undefined || e.target === window){
		var location_hash = window.location.hash.substr(1)
		if(!location_hash){
			var current = document.querySelector("#header-tabs .tab.current")
			if(current) var location_hash = current.innerText.replace(" ", "_").toLowerCase();
		}
		if(location_hash){
			var tabs = document.querySelectorAll("#header-tabs .tab");
			for(var i = 0; i < tabs.length; i++){
				var tab_name = tabs[i].innerText.replace(" ", "_").toLowerCase();
				if(tab_name == location_hash ){
					if(document.getElementById("tab_"+tab_name).classList.contains('hidden')){
						tabs[i].click();
					}
					return;
				}
			}
			var row = highlight_target_row(location_hash);
			if(row){ window.scroll(window.pageXOffset, window.pageYOffset-40+row.getBoundingClientRect().top); }
		}
		return;
	}
	if(e.target.classList.contains("tab")){
		e.stopPropagation();
		var tab_name = e.target.innerText.replace(" ", "_").toLowerCase();
		if(!e.target.classList.contains("current") || document.getElementById("tab_"+tab_name).classList.contains("hidden")){
			e.target.parentElement.querySelectorAll(".tab.current").forEach(function(elem){ elem.classList.remove("current"); });
			e.target.classList.add("current");
			var content = document.querySelectorAll(".tab_content");
			content.forEach(function(elem){ elem.classList.add("hidden"); })
			var tab = document.getElementById("tab_"+tab_name);
			window.onhashchange = null;
			window.location.hash = "#"+tab_name;
			window.onhashchange = header_tabs;
			if(tab){ tab.classList.remove("hidden"); }
		}
	}
}
function highlight_target_row(id){
	var row = document.getElementById(id);
	if(row && row.classList.contains("tr")){
		row.classList.add("highlight-row");
		setTimeout(function(){ row.style.transition = "0.5s"; }, 1);
		setTimeout(function(){ row.classList.remove("highlight-row"); }, 4000);
		setTimeout(function(){ row.style.transition = ""; }, 4501);
		var tabs = document.querySelectorAll("#header-tabs .tab");
		for(var i = 0; i < tabs.length; i++){
			var tab_name = tabs[i].innerText.replace(" ", "_").toLowerCase();
			var content = document.getElementById("tab_"+tab_name)
			if(content.contains(row) && (!tabs[i].classList.contains("current") || content.classList.contains("hidden"))){
				tabs[i].click(); return row.firstElementChild;
			}
		}
		return row.firstElementChild;
	}
}
function js_post(e, xrh_callback, refresh_form=true, form_data = ""){
	var form_submit = !(e.nodeType && e.nodeType === Node.ELEMENT_NODE);
	if(form_submit){
		var form_data = new FormData(e.target);
		if(e.submitter){
			form_data.append(e.submitter.name, e.submitter.value);
		}else if(e.target.classList.contains("no-enter")){
			return true;		
		}else{
			confirm("You are using an older browser. Things may not work as intended.");
			return false;
		}

		e.preventDefault();

	}else{
		if(!form_data){
			form_data = e.id.replace("tab_", "");
		}
	}


	var req = new XMLHttpRequest();
	req.open("POST", "");
	req.setRequestHeader("accept", "application/json");
	if(!form_submit){
		req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	}
	req.onreadystatechange = function(){
		if(this.readyState === XMLHttpRequest.DONE && this.status === 200){
			var json_data = JSON.parse(this.responseText);
			if(json_data["messages"]){
				json_data["messages"].forEach(function(msg){
					create_message(msg["message"], msg["type"]);
				});
			}
			xrh_callback(json_data);
			header_tabs();
			if(form_submit && refresh_form) e.target.reset();
		}else if(this.readyState === XMLHttpRequest.DONE && this.status !== 200){
			if(form_submit){
				create_message("Unable to submit changes", "error");
			}else{
				create_message("Unable to load data", "error");
			}
		}
	}
	req.send(form_data);
}
function setup_edit_content(){
	document.querySelectorAll("button.preview-edit").forEach((elem)=>{
		elem.onclick = (e)=>{
			var input = e.target.parentElement.querySelector(".content-preview input");
			var txt = input.value;
			var editor = document.getElementById("ace-editor");
			if(!editor){
				var editor = document.createElement("div");
				editor.id = "ace-editor";
				editor.classList.add("hidden_popup");
				editor.classList.add("hidden");
				editor.innerHTML = `<div class='loginBox widebox'><div class='clear_message'></div>
					<div id='editor-content'></div><div id='editor-status-bar'></div><input type='submit' value='Save'></div>`;
				document.body.insertAdjacentElement("beforeend", editor);
			}


			var ace_editor = ace.edit("editor-content");

			// Copy text
			ace_editor.setValue(txt);
			ace_editor.selection.moveCursorFileStart();
			ace_editor.selection.clearSelection();
			ace_editor.session.getUndoManager().markClean();

			// Set config falues
			ace_editor.session.setUseWorker(false);
			ace_editor.setTheme("ace/theme/chaos");
			ace_editor.session.setMode("ace/mode/html");

			var close_btn = editor.firstElementChild.firstElementChild;
			var destroy = (event)=>{
					if(ace_editor.getSession().getUndoManager().isClean() || confirm("You have unsaved changes. Close the editor?")){
						ace_editor.destroy();
						close_btn.removeEventListener('click', destroy, {capture:true});
					}else{
						event.stopPropagation();
					}
				}
			close_btn.addEventListener("click", destroy, {capture:true});

			editor.querySelector("input[type=submit]").onclick = ()=>{
				input.value = ace_editor.getValue();
				ace_editor.session.getUndoManager().markClean();
				editor.firstElementChild.firstElementChild.click();
				ace_editor.destroy();
				input.form.querySelector("input[type=submit][value=Save]").click();
			}

			show_hidden_input(e, "ace-editor");
		}
	});
}
function value_escape(value_string, escape_html = false){
	if(input_value_converter == undefined){
		input_value_converter = {value:document.createElement("input"),html:document.createElement("textarea")};
		input_value_converter.value.type = 'hidden';
	}
	input_value_converter.html.innerHTML="";
	if(escape_html){
		input_value_converter.html.innerHTML = value_string;
		return input_value_converter.html.innerHTML;
	}
	input_value_converter.value.value=value_string;
	return input_value_converter.value.outerHTML.match(/value="(.*?)">/s)[1];
}
function pages_submit(e){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var expanded = [];
		var ordering_box = target.querySelector(".ordering");
		if(ordering_box){target.querySelectorAll(".expanded input[type=hidden][name='page_order[][page]']").forEach((elem)=>{
			expanded.push(parseInt(elem.value));
		});}
		while(ordering_box && ordering_box.firstChild){ordering_box.removeChild(ordering_box.firstChild);}

		var post_parent = document.getElementById("new_post_parent");
		while(post_parent && post_parent.firstChild){post_parent.removeChild(post_parent.firstChild);}

		var page_content_clone = document.getElementById("new_page_content_source");
		if(page_content_clone){
			page_content_clone.parentElement.classList.add("hidden");
			page_content_clone.parentElement.nextElementSibling.classList.remove("hidden");
		}
		while(page_content_clone && page_content_clone.children[1]){page_content_clone.removeChild(page_content_clone.children[1]);}

		var page_content_parents = document.getElementById("new_page_content_parents");
		while(page_content_parents && page_content_parents.firstChild){page_content_parents.removeChild(page_content_parents.firstChild);}

		if(ordering_box && post_parent && page_content_parents && page_content_clone && json_data["pages"] && json_data["pages_order"]){
			for(var index = 0; index < json_data["pages_order"].length; index++){
				var id = json_data["pages_order"][index];
				var data = json_data["pages"][id];

				post_parent.innerHTML += `<option value='${id}'>${data["title"]}</option>`;
				page_content_parents.innerHTML += `<li><input type=checkbox name='new_page_content[parent][]'
					value='${id}' id='new_page_content_parent_${id}'><label for='new_page_content_parent_${id}'>${data['title']}</label></li>`;
				
				var optgroup = document.createElement("optgroup");
				optgroup.label = data["title"];

				var row = document.createElement("div");
				row.classList.add('draggable');
				if(expanded.indexOf(id) != -1){
					row.classList.add("expanded");
				}else{
					row.draggable = true;
				}

				if(json_data["new_content"]["page"] && json_data["new_content"]["page"].indexOf(id) != -1){
					row.classList.add("highlight-row");
				}

				row.innerHTML += ` <div class='section'>
					<div class='dragger'><div></div><div></div></div><input type='hidden' name='page_order[][page]' value='${id}'>
					<div class='expand'></div>
					<div class='title'><input type='text' name='page[${id}][title]' value='${data["title"]}'></div>
					<div class='content-preview no-break'>${value_escape(truncate(data["content"]), true)}
						<input type='hidden' name='page[${id}][content]' value="${value_escape(data["content"])}">
					</div> <button type='button' class='preview-edit'>Edit</button> 
					<span class='no-break'><label for='page_${id}_publish' >Publish: </label>
						<input type='hidden' name='page[${id}][published]' value=0>
						<input type='checkbox' id='page_${id}_publish' name='page[${id}][published]' value=1 ${data["published"]=='1'?"checked":""}>
					</span></div>`;


				var expanded_row = document.createElement("div")
				expanded_row.classList.add("expanded-rows");
				// expanded_row.classList.add("hidden");

				expanded_row.innerHTML += `<div class='row'><div class='section'>
						<span class='no-break'><label>Direction: </label>
							<select name='page[${id}][direction]'>
								<option value='row' ${data["direction"]=="row"?"selected":""}>Row</option>
								<option value='column' ${data["direction"]=="column"?"selected":""}>Column</option>
						</select></span>
						${data["protected"]?"":`<input type='submit' class='negative' name='remove_page[${id}]' value=Delete>`}
					</div></div>`;

				var secondary_types = ["header_content", "page_content"];
				var legend_keys = ["HTML Header", "Additional Content"];
				var content = document.createElement("div");
				for(var t = 0; t < secondary_types.length; t++){
					var fs = document.createElement("fieldset");
					fs.classList.add("dragger-container");
					fs.classList.add(secondary_types[t])
					fs.innerHTML = `<legend>${legend_keys[t]}</legend>`;
					for(var i = 0; i < data[secondary_types[t]].length; i++){
						var pc_data = data[secondary_types[t]][i];
						var post = pc_data["title"] !== undefined;
						pststr = post?"post":"page_content";
						content.innerText = truncate(pc_data["content"]);

						if(!post){
							optgroup.innerHTML += `<option value='${pc_data["id"]}'>${value_escape(truncate(pc_data["content"], 25), true)}</option>`
						}
						var highlight_row = (json_data["new_content"][pststr] && json_data["new_content"][pststr].indexOf(parseInt(pc_data["id"])) != -1);
						if(highlight_row){
							row.classList.add("expanded");
							row.draggable = false;
						}
						
						fs.innerHTML += `<div class='draggable secondary ${pststr} ${highlight_row?"highlight-row":"" }' draggable=true>
							<div class='section'><div class='dragger'><div></div><div></div></div>
							<input type='hidden' name='page_order[][${pststr}]' value='${pc_data["id"]}'>
							${post?`<div class='section'><label for='${pststr}_${pc_data["id"]}'>Post: </label>
								<input type='text' name='${pststr}[${pc_data["id"]}][title]'
									id='${pststr}_${pc_data["id"]}' value="${value_escape(pc_data["title"])}"></div>
								<div class='section'><label for='${pststr}_${pc_data["id"]}_pic'>Picture: </label>
									<input type='text' id='${pststr}_${pc_data["id"]}_pic' name='${pststr}[${pc_data["id"]}][picture]' value='${pc_data["picture"]}'>
								</div><div class='section'>`:""
							}
							<div class='content-preview no-break'>${content.innerHTML}
								<input type='hidden' name='${pststr}[${pc_data["id"]}][content]' id='${pststr}_${pc_data["id"]}_content'
									value='${value_escape(pc_data["content"])}'>
							</div><div class='remove_item'>
								<input type='submit' name='remove_page[${pststr}][${pc_data["id"]}]' value='-'> </div>
							<button type='button' class='preview-edit'>Edit</button><span class='no-break'>
								<label for='${pststr}_${pc_data["id"]}_publish' >Publish: </label>
								<input type='hidden' name='${pststr}[${pc_data["id"]}][published]' value=0>
								<input type='checkbox' id='${pststr}_${pc_data["id"]}_publish'
									name='${pststr}[${pc_data["id"]}][published]' value=1 ${pc_data["published"]=='1'?"checked":""}>
							</span>
						</div></div>${post?"</div>":""}`;
					}
					// if(data[secondary_types[t]].length){
							// expanded_row.innerHTML += "<hr>";
						expanded_row.innerHTML += `<input type='hidden' name='page_order[]' value='${secondary_types[t]}'>`;
						expanded_row.insertAdjacentElement("beforeend", fs);
					// }
				}	
				// expanded_row.innerHTML += `<div`;

				if(optgroup.innerHTML){
					page_content_clone.insertAdjacentElement("beforeend", optgroup);
				}
				row.insertAdjacentElement("beforeend", expanded_row);
				ordering_box.insertAdjacentElement("beforeend", row);
			}
		}
		expander();
		setup_edit_content();
		ordering_box.querySelectorAll("input[type=submit][value=Delete], input[type=submit][value='-']").forEach((elem)=>{
			elem.onclick = ()=>{return confirm("Are you sure you would like to delete this?\nUnpublishing also works for hidding it.")}
		});

		document.querySelectorAll(".ordering .draggable:not(.secondary)").forEach(function(elem){
			elem.ondragstart = startMove;
			elem.ondrag = locationUpdate;
			elem.ondragenter = dragEnter;
			elem.ondragend = endMove;
		});
		document.querySelectorAll(".ordering .dragger").forEach(function(elem){
			elem.addEventListener("touchstart", startMove,{passive:true});
			elem.addEventListener("touchmove", locationUpdate,{passive:true});
			elem.addEventListener("touchend", endMove,{passive:true});
		});
	});
}
function footers_submit(e){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;

		var expanded = [];
		var ordering_box = target.querySelector(".ordering");
		if(ordering_box){target.querySelectorAll(".expanded input[type=hidden][name='footer_order[][footer]']").forEach((elem)=>{
			expanded.push(parseInt(elem.value));
		});}
		while(ordering_box && ordering_box.firstChild){ordering_box.removeChild(ordering_box.firstChild);}

		var link_parent = document.getElementById("new_link_parent");
		while(link_parent && link_parent.firstChild){link_parent.removeChild(link_parent.firstChild);}

		if(ordering_box && link_parent && json_data["footers"] && json_data["footers_order"]){
			for(var index = 0; index < json_data["footers_order"].length; index++){
				var id = json_data["footers_order"][index];
				var data = json_data["footers"][id];

				link_parent.innerHTML += `<option value=${id}>${value_escape(truncate(data["title"], 25), true)}</option>`;

				var row = document.createElement("div");

				row.classList.add("draggable");
				if(expanded.indexOf(id) != -1){
					row.classList.add("expanded");
				}else{
					row.draggable = true;
				}

				if(json_data["new_content"]["footer"] && json_data["new_content"]["footer"].indexOf(id) != -1){
					row.classList.add("highlight-row");
				}

				row.innerHTML = `<div class='section'>
					<div class='dragger'><div></div><div></div></div><input type='hidden' name='footer_order[][footer]' value=${id}>
					<div class='expand'></div><input type='text' name='footer[${id}][title]' value="${value_escape(data["title"])}">
					<div class='content-preview no-break'>${value_escape(truncate(data["content"]), true)}
						<input type='hidden' name='footer[${id}][content]' value="${value_escape(data["content"])}">
					</div><button type='button' class='preview-edit no-break'>Edit</button><span class='no-break'>
						<label for='footer_${id}_publish'>Publish: </label> <input type='hidden' name='footer[${id}][published]' value=0>
						<input type='checkbox' name='footer[${id}][published]' value=1 id='footer_${id}_publish' ${data["published"]=='1'?'checked':""}>
					</span></div>`;

				var expanded_row = document.createElement("div");
				expanded_row.classList.add("expanded-rows");

				expanded_row.innerHTML = `<div class='row'><div class='section'>
					<input class='negative' type='submit' value='Delete' name='remove_footer[${id}]'></div></div>`;

				var fieldset = document.createElement("fieldset");
				fieldset.classList.add("dragger-container");
				fieldset.classList.add("header_content");
				fieldset.innerHTML += `<legend>Icon Links <div class='help-msg hidden'>
					Icon links are links that are represented via an icon through font awesome.
					You can find a list of icons <a href='https://fontawesome.com/icons?d=gallery&s=brands,solid&m=free' target=_blank>here</a>.
					</div></legend>`;
				for(var n = 0; n < data["links"].length; n++){
					var ln_data = data["links"][n];
					var lid = ln_data["id"];
					var highlight_row = (json_data["new_content"]["link"] && json_data["new_content"]["link"].indexOf(parseInt(lid))!= -1);
					if(highlight_row){
						row.classList.add("expanded");
						row.draggable = false;
					}
					fieldset.innerHTML += `<div class='draggable secondary ${highlight_row?"highlight-row":""}' draggable=true>
						<div class='section'><div class='dragger'><div></div><div></div></div>
						<input type='hidden' value=${lid} name='footer_order[][link]'>
						<div class='icon-preview'><i class="fa${ln_data["type"][0]} fa-${value_escape(ln_data["icon"])}"></i></div>
						<input type='text' class='fnt-aw-attribute' name='link[${lid}][icon]' value='${value_escape(ln_data["icon"])}'>
						<select name='link[${lid}][type]' class='fnt-aw-attribute'>
							<option ${ln_data["type"]=='brand'?"selected":""}>brand</option>
							<option ${ln_data["type"]=='solid'?"selected":""}>solid</option>
						</select>
						<input type='url' name='link[${lid}][url]' value='${value_escape(ln_data["url"])}'>
						<div class='remove_item'><input type='submit' name='remove_footer[link][${lid}]' value='-'></div>
						<span class='no-break'><label for='link_${lid}_publish'>Publish: </label>
							<input type='hidden' value=0 name='link[${lid}][published]'>
							<input type='checkbox' value=1 name='link[${lid}][published]' id='link_${lid}_publish' ${ln_data["published"]=='1'?'checked':""}>
					</span> </div></div>`;
				}


				expanded_row.insertAdjacentElement("beforeend", fieldset);
				row.insertAdjacentElement("beforeend", expanded_row);

				ordering_box.insertAdjacentElement("beforeend", row);
			}
		}
		expander();
		setup_edit_content();
		setup_help();
		ordering_box.querySelectorAll("input[type=submit][value=Delete], input[type=submit][value='-']").forEach((elem)=>{
			elem.onclick = ()=>{return confirm("Are you sure you would like to delete this?\nUnpublishing also works for hidding it.")}
		});
		ordering_box.querySelectorAll("input[type=url]").forEach((elem)=>{
			elem.oninvalid = (e)=>{
				if(e.target.offsetParent){
					return;
				}
				var target = e.target;
				while(target && !target.classList.contains('draggable') || target.classList.contains('secondary')){
					target = target.parentElement;
				}
				if(target){
					target.classList.add("expanded");
					target.draggable=false;
				}
			}
		});
		ordering_box.querySelectorAll(".fnt-aw-attribute").forEach((input)=>{
			input.oninput = (e)=>{
				var preview = e.target.parentElement.querySelector(".icon-preview i");
				var icon = e.target.parentElement.querySelector("input[type=text]").value;
				var type = e.target.parentElement.querySelector("select").value;
				preview.classList.value = `fa${type[0]} fa-${icon}`;
			};
		});


		document.querySelectorAll(".ordering .draggable:not(.secondary)").forEach(function(elem){
			elem.ondragstart = startMove;
			elem.ondrag = locationUpdate;
			elem.ondragenter = dragEnter;
			elem.ondragend = endMove;
		});
		document.querySelectorAll(".ordering .dragger").forEach(function(elem){
			elem.addEventListener("touchstart", startMove,{passive:true});
			elem.addEventListener("touchmove", locationUpdate,{passive:true});
			elem.addEventListener("touchend", endMove,{passive:true});
		});
	});
}
function get_file_path(elem, only_dirs=false, return_new=false){
	var target = elem.parentElement.children[1];
	var path = [];
	var input = target.querySelector("input[type=hidden][name^='file_name[']");
	var new_loc = target.querySelector("input[type=hidden][name^='file_move[][']");
	var rename = target.querySelector("input[name^='file_rename[']");
	var new_file = target.querySelector("input[name^='new_file[f'");
	if(input){
		if(return_new && new_loc){
			path.push(...(new_loc.value.split("/")));
		}else{
			path.push(...(input.name.match(/file_name\[(.*)\]\[\]/)[1].split("/")));
		}
		if(return_new && rename){
			path.push(rename.value);
		}else{
			path.push(input.value);
		}
	}else if(new_file){
		path.push(...(new_file.name.match(/new_file\[.*?\]\[(.*)\]\[\]/)[1].split("/")));
		path.push(new_file.value);
	}else{
		path.push(target.lastChild.data.trim());
	}
	if(only_dirs && !target.parentElement.classList.contains("folder")){
		path.pop();
	}
	return path;
}
function create_file_disp(file, root_dir, expanded = false){
	var container = document.createElement("div");
	container.classList.add("ul");
	for(var name in file){
		var file_item = document.createElement("div");
		file_item.classList.add("li");
		file_item.innerHTML = `<i class='fas' draggable='true'></i>
			<div class='file-name' draggable='true'><input type='hidden' name='file_name[${value_escape(root_dir)}][]' 
				value='${value_escape(name)}'> ${value_escape(name, true)}</div>`;
		if(typeof(file[name]) == 'string'){
			file_item.classList.add("file");
			file_item.setAttribute("mime-type", file[name]);
		}else{
			file_item.classList.value += " folder" + (expanded?" expanded":"");
			file_item.appendChild(create_file_disp(file[name], root_dir+'/'+name));
		}
		container.appendChild(file_item);
	}
	return container;
}
function add_new_file(is_folder=false){
	var selected = document.querySelector(".file-tree .folder.selected");
	var type = is_folder?"folder":"file";
	var cnt = selected.children[2].querySelectorAll(`:scope > .new-file.${type}`).length;
	selected.children[2].insertAdjacentHTML("afterbegin",
		`<div class='new-file dirty li ${type}' ${is_folder?"":"mime-type='application/x-empty'"}>
			<i class='fas' draggable='true'></i>
			<div class='file-name' draggable='true'>
				<input type='hidden' name="new_file[${type}][${get_file_path(selected.firstElementChild).join("/")}][]"
					value="New F${type.slice(1)}${cnt?" ("+cnt+")":""}">New F${type.slice(1)}${cnt?" ("+cnt+")":""}</div>
			${is_folder?"<div class='ul'></div>":""}
		</div>`
	);
	selected.children[2].firstElementChild.querySelectorAll('*[draggable=true]').forEach((e)=>{e.ondragstart = file_drag_start});
}
function file_drag_start(e){
	e.target.parentElement.classList.add("dragging");
	e.dataTransfer.setDragImage(e.target.parentElement.firstElementChild, 0, 0);
	var is_new = Boolean(e.target.parentElement.children[1].querySelector("input[name^=new_file]"));
	e.dataTransfer.setData("text/json", JSON.stringify({
		"mime-type":e.target.parentElement.getAttribute("mime-type"),
		"name":e.target.parentElement.children[1].innerText,
		"path":get_file_path(e.target),
		"is-new": is_new,
	}));
}
function files_submit(e){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var editor_file_name = document.getElementById("file-name");

		var allowed_file_types = json_data["allowed_type"];
		var allowed_files = document.getElementById("allowed_files");
		if(allowed_files){
			allowed_files.innerText = allowed_file_types.join(", ");
		}

		var file_tree = target.querySelector(".file-tree");
		while(file_tree && file_tree.firstChild){file_tree.removeChild(file_tree.firstChild);};

		var file_upload = document.getElementById("file_upload");

		var editor = target.querySelector("#file-editor:not(.ace_editor)");
		if(file_tree && editor){
			file_editor = ace.edit("file-editor");
			file_editor.session.setUseWorker(false);
			file_editor.setTheme("ace/theme/tomorrow_night");
			open_editors["__default__"] = file_editor.getSession();
		}

		if(file_tree && json_data["files"] && json_data["root_dir"]){
			
			file_tree.innerHTML = `<div class='li folder expanded root selected'><i class='fas'></i>
				<div class='file-name'>${value_escape(json_data["root_dir"], true)}</div></div>`;
			file_tree.firstElementChild.appendChild(create_file_disp(json_data["files"], json_data['root_dir'],false));

		}
		file_tree.querySelectorAll("*[draggable=true]").forEach((elem)=>{ elem.ondragstart = file_drag_start; });
		file_upload.labels[0].ondragover = file_tree.ondragover = (e)=>{
			// if(e.target && !e.target.classList.contains("ul") &&
			// 	e.target.parentElement && e.target.parentElement.classList.contains("folder")
			// ){
			// console.log(e.dataTransfer);
				e.preventDefault();
			// }
		}
		file_upload.onchange = (e)=>{
			if(e.target.files && e.target.files[0].type){
				var ext = e.target.files[0].name.match(/.+\.(\w+)$/);
				if(ext && allowed_file_types.includes(ext[1].toLowerCase())){
					file_upload.labels[0].querySelector("span").innerText = truncate(file_upload.files[0].name, 15);
					var nfp = file_upload.parentElement.querySelector("input[type=hidden][name=new_file_path]");
					if(!nfp){
						nfp = document.createElement("input");
						nfp.type = "hidden";
						nfp.name = "new_file_path";
						file_upload.insertAdjacentElement("afterend", nfp);
					}
					nfp.value = get_file_path(file_tree.querySelector(".selected").firstChild).join("/");
				}else{
					e.target.value = null;
					e.preventDefault();
					file_upload.labels[0].querySelector("span").innerText = "Upload";
					if(ext){
						create_message(`Disallowed file type <code>${ext[1]}</code>`, "warning");
					}else{
						create_message("Unable to upload file", "warning");
						console.log(e.target.files[0]);
					}
				}
			}
		}
		file_upload.labels[0].ondrop = file_tree.ondrop = (e)=>{
			var data = e.dataTransfer.getData("text/json");
			if(data && e.target && !e.target.classList.contains("ul") &&
				e.target.parentElement && e.target.parentElement.classList.contains("folder")
			){
				data = JSON.parse(data);
				var tgt = get_file_path(e.target)
				if( data["path"].toString() != tgt.toString() &&
					data["path"].slice(0,data["path"].length-1).toString() != tgt.toString()
				){
					elem = null;
					var ext = data["name"].toLowerCase().match(/\.(\w+)$/);
					if(ext && !allowed_file_types.includes(ext[1])){
						create_message(`Permission Denied. Unable to move file with extension <code>${ext[1]}</code>`, "warning");
						return;
					}
					var pth = data["path"].slice(0,data["path"].length-1);
					var in_name = data["is-new"]?`new_file[${data["mime-type"]?"file":"folder"}]`:"file_name";
					if(data["mime-type"]){
						elem = file_tree.querySelector(
							`div[mime-type="${data["mime-type"]}"] input[name="${in_name}[${pth.join("/")}][]"][value="${data["name"]}"]`
						);
					}else{
						elem = file_tree.querySelector(
							`div.folder > .file-name input[name="${in_name}[${pth.join('/')}][]"][value="${data["name"]}"]`
						);
					}
					elem = elem.parentElement.parentElement;
					if(elem.contains(e.target)){
						return;
					}
					elem.parentElement.removeChild(elem);
					elem.classList.add("dirty");
					var move_input = elem.querySelector("input[name*='file_move[][']");
					if(move_input){
						move_input.value = tgt.join("/");
					}else{
						elem.children[1].insertAdjacentHTML("afterbegin",
							`<input type=hidden name="file_move[][${data.path.join("/")}]" value="${tgt.join("/")}">`
						);
					}
					e.target.parentElement.children[2].insertAdjacentElement("afterbegin", elem);
				}
			}else if(!data && e.dataTransfer.files.length && e.dataTransfer.files[0].type){
				var ext = e.dataTransfer.files[0].name.match(/.+\.(\w+)$/);
				if(ext && allowed_file_types.includes(ext[1].toLowerCase())){
					var nfp = file_upload.parentElement.querySelector("input[type=hidden][name=new_file_path]");
					if(!nfp){
						nfp = document.createElement("input");
						nfp.type = "hidden";
						nfp.name = "new_file_path";
						file_upload.insertAdjacentElement("afterend", nfp);
					}
					if(file_tree.contains(e.target)){
						nfp.value = get_file_path(e.target, true).join("/");
					}else{
						nfp.value = get_file_path(file_tree.querySelector(".selected").firstChild).join("/");
					}
					file_upload.files = e.dataTransfer.files;
					file_upload.labels[0].querySelector("span").innerText = truncate(file_upload.files[0].name, 15);
				}else if(ext){
					create_message(`Disallowed file type <code>${ext[1]}</code>`, "warning");
				}else{
					create_message("Unable to upload file", "warning");
					console.log(e.dataTransfer.files[0]);
				}
				e.preventDefault();
			}
			e.dataTransfer.clearData();
		}
		file_tree.onclick = (e)=>{
			if(e.target.tagName == "INPUT"){
				return;
			}
			var tp = e.target.parentElement;
			if( tp.classList.contains("folder")){
				if(!tp.classList.contains("root") && e.target.tagName == "I"){
					tp.classList.toggle("expanded");
				}else{
					file_tree.querySelectorAll(".folder.selected").forEach((elem)=>{elem.classList.remove("selected");});
					tp.classList.add("selected");
					tp.classList.add("expanded");
				}
			}else if(tp.classList.contains("file") && tp.getAttribute("mime-type").match(/text\/|\/svg\+xml|\/x-empty/)){
				var path = '/' + get_file_path(tp.children[1]).join("/");
				var ext = path.toLowerCase().match(/\.(\w+)$/);
				var new_file = Boolean(tp.children[1].querySelector("input[name^='new_file[f']"));
				if(ext && !allowed_file_types.includes(ext[1])){
					create_message(`Unable to edit file '${path}' Its file type is not allowed.<br>
						You can add <code>${ext[1]}</code> to the setting 
						<a href=/content.php/settings#allowed_uploads target=_blank><code>allowed_uploads</code></a>`,
						"warning");
					return;
				}else if(!ext){
					create_message(`File at path <code>${path}</code> has no extension. Please rename it before editing.`, "warning")
					return;
				}
				var cur_path = get_file_path(tp.children[1], false, true).join("/");
				editor_file_name.innerText = cur_path;
				if(!tp.classList.contains["dirty"] && !new_file){
					var xhr = new XMLHttpRequest();
					xhr.open("GET", path);
					xhr.onreadystatechange= function(){
						if(this.readyState === XMLHttpRequest.DONE){
							if(this.status === 200){
								if(!open_editors[path]){
									open_editors[path] = ace.createEditSession(this.responseText);
									open_editors[path].setUseWorker(false);
									open_editors[path].on('change', ()=>{tp.classList.add("dirty")});
								}
								file_editor.setSession(open_editors[path]);
								ace.config.loadModule("ace/ext/modelist", (m)=>{
									file_editor.session.setMode(m.getModeForPath(cur_path).mode);
								});
							}else{
								create_message(`Unable to load file ${path}`, "error");
							}
						}
					}
					xhr.send();
				}else if(new_file && !open_editors[path]){
					open_editors[path] = ace.createEditSession("");
					open_editors[path].setUseWorker(false);
					open_editors[path].on('change', ()=>{tp.classList.add("dirty")});
					file_editor.setSession(open_editors[path]);
					ace.config.loadModule("ace/ext/modelist", (m)=>{
						file_editor.session.setMode(m.getModeForPath(cur_path).mode);
					});
				}else{
					file_editor.setSession(open_editors[path]);
					ace.config.loadModule("ace/ext/modelist", (m)=>{
						file_editor.session.setMode(m.getModeForPath(cur_path).mode);
					});
				}
			}
		}
		file_tree.oncontextmenu = (e, sub_target=undefined)=>{
			if(e.target.classList.contains("file-name") && e.target.parentElement.classList.contains("li")){
				var orig_name = e.target.parentElement.children[1].querySelector("input[type=hidden][name^='file_name[']");
				var new_file = e.target.parentElement.children[1].querySelector("input[type=hidden][name^='new_file[f']");
				if(new_file){ orig_name = new_file; }
				if(!orig_name  || (
					!e.target.parentElement.classList.contains("folder") && orig_name.value.includes(".") &&
					!allowed_file_types.includes(orig_name.value.match(/\.(\w+)$/)[1].toLowerCase())
				)){
					return;
				}
				e.preventDefault();
				var new_name = e.target.parentElement.children[1].querySelector("input[name^='file_rename[']")
				if(new_file){
					new_name = new_file;
				}
				if(!new_name){
					new_name = document.createElement("input");
					new_name.type = 'text';
					new_name.name = orig_name.name.replace(/file_name\[/, "file_rename[");
					new_name.name = new_name.name.replace(/\[\]$/, `[${orig_name.value}]`);
					new_name.value = orig_name.value;
					orig_name.insertAdjacentElement("afterend", new_name);
				}
				if(new_file){
					new_name.setAttribute("original_value", new_name.value);
					new_name.onchange = (c)=>{
						var path = get_file_path(e.target);
						path = "/"+(path.slice(0, path.length-1).join("/"));
						var old_path = path+'/'+c.target.getAttribute("original_value");
						var path = path+'/'+c.target.value;
						if(open_editors[old_path]){
							open_editors[path] = open_editors[old_path];
							delete open_editors[old_path];
							if(file_editor.getSession() == open_editors[path]){
								editor_file_name.innerText = path;
							}
						}
						c.target.setAttribute("original_value", c.target.value);
					}
				}
				new_name.onkeydown = (k)=>{ if(k.keyCode == 13){ k.target.blur(); } }
				new_name.onblur = (b)=>{
					var prnt = b.target.parentElement.parentElement;
					if(!e.target.parentElement.classList.contains("folder") ){
						var ext = b.target.value.match(/\.(\w+)$/)
						if(!ext){
							create_message(`Please use an extension for all files`, "warning");
							if(!new_file && orig_name.value.match(/\.\w+$/)){
								b.target.parentElement.lastChild.data = orig_name.value;
								b.target.parentElement.removeChild(b.target);
							}else{
								b.target.parentElement.lastChild.data = b.target.value;
								b.target.type = "hidden";
								prnt.classList.add("dirty");
							}
							return;
						}
						ext = ext[1].toLowerCase();
						if(!allowed_file_types.includes(ext)){
							create_message(`Rename to file type <code>${ext}</code> not allowed.`, "warning")
							if(!new_file){
								b.target.parentElement.lastChild.data = orig_name.value;
								b.target.parentElement.removeChild(b.target);
							}
							return;
						}
					}
					b.target.parentElement.lastChild.data = b.target.value;
					if(!new_file && b.target.value == orig_name.value){
						b.target.parentElement.removeChild(b.target);
						if(!open_editors[get_file_path(prnt.children[1]).join("/")] && !prnt.querySelector("input[name^='file_move[][']")){
							prnt.classList.remove("dirty");
						}
					}else{
						prnt.classList.add("dirty");
						b.target.type='hidden'
					}
				};
				new_name.type='text';
				new_name.focus();
				new_name.parentElement.lastChild.data = "";

			}else if(e.target.tagName == 'I' && e.target.parentElement.classList.contains("dirty")){
				var is_new = sub_target?false:e.target.parentElement.children[1].querySelector("input[name^='new_file[f'");
				if(sub_target || confirm(`Would you like to discard all changes for:\n${
						get_file_path(e.target, false, true).join("/")
					}${
						is_new?"\nThis will delete the object and all data assisiated with it.":""
					}`)
				){
					var elem = sub_target?sub_target:e.target.parentElement.children[1];
					var root_path = get_file_path(elem).join("/");
					var editor = open_editors['/' + root_path];
					if(!sub_target && editor){
						if(file_editor.getSession() == editor){
							editor_file_name.innerHTML = "";
							file_editor.setSession(open_editors["__default__"]);
						}
						editor.setValue("");
						editor.destroy();
						delete open_editors['/' + root_path];
					}
					if(is_new){
						if(e.target.parentElement.classList.contains("selected")){
							file_tree.firstElementChild.classList.add("selected");
						}
						e.target.parentElement.querySelectorAll(".li:not(.new-file)").forEach((oe)=>{
							// var path = "/"+get_file_path(oe.children[1]).join("/");
							file_tree.oncontextmenu(e, oe.children[1]);
						});
						e.target.parentElement.parentElement.removeChild(e.target.parentElement);
					}else if(elem.querySelector("input[name^='file_move']")){
						var path = get_file_path(elem);
						var root = path.slice(0, path.length-2).join("/");
						var parent_dir = path[path.length-2];
						elem.parentElement.parentElement.removeChild(elem.parentElement);
						var old_parent = file_tree.querySelector(`.folder > .file-name input[name="file_name[${root}][]"][value="${parent_dir}"]`)
						if(!old_parent){
							old_parent = file_tree.firstElementChild;
						}else{
							old_parent = old_parent.parentElement.parentElement;
						}
						old_parent.children[2].insertAdjacentElement("afterbegin", elem.parentElement);
						elem.removeChild(elem.querySelector("input[name^='file_move']"));
					}
					if(!is_new && !sub_target && elem.querySelector("input[name^='file_rename']")){
						elem.lastChild.data = elem.querySelector("input[name^=file_name]").value.trim();
					}
					if(!sub_target){
						elem.querySelectorAll("input:not([name^='file_name['])").forEach((i)=>{elem.removeChild(i)});
						e.preventDefault();
					}
					if(!elem.querySelector("input:not([name^='file_name['])") && !open_editors['/'+root_path]){
						elem.parentElement.classList.remove("dirty");
					}
				}
			}
		}

	});
}
function settings_submit(e){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var table = target.querySelector(".tbody");
		while(table && table.firstChild){table.removeChild(table.firstChild);}

		if(table && json_data["settings"] && json_data["settings_order"]){
			for(var index = 0; index < json_data["settings_order"].length; index++){
				var setting = json_data["settings_order"][index];
				var set_data = json_data["settings"][setting];
				var row = document.createElement("div");
				row.classList.add("tr");
				row.id = setting;
				row.innerHTML = `<div class='td'>${setting}</div>`;
				// row.firstElementChild.id = setting;

				var input = `<input name="setting[${setting}]" type='`;
				if(set_data["type"] == "BOOL"){
					input += `hidden' value="false"><input type='checkbox' name="setting[${setting}]" value="true" `;
					input += (set_data["value"] == "true"?"checked":"") + ">";
				}else if(set_data["type"] == "INT"){
					input += `number' value='${set_data["value"]}'>`
				}else if(set_data["type"] == "STRING"){
					input += `text' value='${set_data["value"]}'>`
				}else if(set_data['type'] == 'BIG STRING'){
					input = `<textarea class='big-string' name='setting[${setting}]'>${value_escape(set_data["value"], true)}</textarea>`;
				}
				row.innerHTML += `<div class='td'>${input}</div>`;
				row.innerHTML += `<div class='td td-center remove_item'>
					${set_data["protected"]?"":`<input type='submit' name='remove_settings[${setting}]' value='-'`}</div>`;
				row.innerHTML += `<div class='td'>${set_data["description"]}</div>`;


				table.insertAdjacentElement("beforeend", row);
			}

		}
		setup_help();
		table.querySelectorAll("textarea.big-string").forEach((elem)=>{
			elem.onblur = elem.onfocus = (e)=>{
				e.target.parentElement.parentElement.classList.toggle("big-string-expanded");
			}
		});
	});
}
function logs_submit(e, form_data=""){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var types = target.querySelector("#logs_uid");
		var selected_type = types?types.value:undefined;
		while (types && types.children[1]){types.removeChild(types.children[1]);}
		var logs = target.querySelector("#logs_container");
		while (logs && logs.firstChild){logs.removeChild(logs.firstChild);}
		var pages = target.querySelector("#logs_paging");
		while (pages && pages.firstChild){pages.removeChild(pages.firstChild);}
		var tmp = target.querySelectorAll(".tmp").forEach(function(tmp){target.removeChild(tmp);});

		if(types && logs && pages && json_data["uid"] && json_data["logs"] && json_data["count"] && json_data["offset"] !== undefined){
			for(var index = 0; index < json_data["uid"].length; index++){
				types.innerHTML += `<option>${json_data["uid"][index]}</option>`;
			}
			types.value = selected_type;
			if(types.selectedIndex < 0){
				types.selectedIndex = 0;
			}
			for(var index = 0; index < json_data["logs"].length; index++){
				var log = json_data["logs"][index];
				logs.innerHTML += `<pre class='raw-unix-time'>${log["timestamp"]}</pre><pre>${log["uid"]}</pre><pre>${log["info"]}</pre>`;
			}
			if(json_data["logs"].length == json_data["count"]["all_items"]){
				pages.innerText = `All ${json_data["count"]["all_items"]} entries shown`;
			}else{
				var items_per_page = json_data["count"]["items_per_page"];
				var cur_page = (json_data["offset"]/items_per_page)+1;
				var btns = new Set([1, json_data["count"]["pages"]]);
				for(var i = -2;i<=2;i++){btns.add(cur_page+i)};
				var added_last = false;
				for(var index = 1; index <= json_data["count"]["pages"]; index++){
					if(btns.has(index)){
						pages.innerHTML += `<span class="nav-page${index==cur_page?" current-page":""}">${index}</span>`;
						added_last = true;
					}else if(added_last){
						pages.innerHTML += "<span>...</span>";
						added_last = false;
					}

				}
				pages.querySelectorAll(".nav-page:not(.current-page)").forEach(function(nav){
					nav.onclick = function(elem){
						var input = document.createElement("input");
						input.name="logs_page";
						input.type="hidden";
						input.value=(elem.target.innerText-1)*items_per_page;
						input.classList.add('tmp');
						target.insertAdjacentElement("afterbegin", input);
						target.querySelector("input[type='submit']").click();
					}
				});
			}
			convert_unix_time(true);
		}

	}, false, form_data);
}
function links_submit(e){
	js_post(e, function(json_data){
		var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var table = target.querySelector(".tbody");
		while(table && table.firstChild){table.removeChild(table.firstChild);}
		var optgroup = target.querySelector("optgroup[label='Groups']");
		if(optgroup){optgroup.innerHTML = "";}

		if(table && optgroup && json_data["links"] && json_data["links_order"] && json_data["groups"]){
			var group_options = "";
			for(var gid in json_data["groups"]){
				group_options += `<option value=${gid}>${json_data["groups"][gid]["name"]}</option>`;
			}
			optgroup.innerHTML = group_options;
			for(var index = 0; index < json_data["links_order"].length; index++){
				var link = json_data["links_order"][index];
				var set_data = json_data["links"][link];
				var row = document.createElement("div");
				row.classList.add("tr");

				row.innerHTML = `<div class='td'>${link}</div>
					<div class="td"><input type='text' value='${set_data["url"]}'
						name='link[${link}][url]' ${set_data["protected"]?"readonly":""}></div>
					<div class="td td-center remove_item">
						${set_data["protected"]?"":`<input type='submit' value='-' name='remove_links[${link}]'>`}</div>
					<div class="td td-center"><input type='hidden' value=0 name='link[${link}][only_local_account]'>
						<input type='checkbox' name='link[${link}][only_local_account]' value=1
							${set_data["only_local_account"] == '1'?"checked":""}></div>
					<div class='td'><select name="link[${link}][permissions]"><option></option>
						<option value="any_user">Any user</option><optgroup label="Groups">${group_options}</optgroup></select> `;
				var select = row.querySelector("select");
				if(set_data["any_user"] == true){
					select.value = "any_user";
				}else if(set_data["groupid"]){
					select.value = set_data["groupid"];
				}
				select.selectedOptions[0].defaultSelected = true

				table.insertAdjacentElement("beforeend", row);
			}
		}
	});
}
function user_manager_submit(e){
	js_post(e, function(json_data){
		// var target = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
		var group_select = document.getElementById('group_to_bulk_add');
		while(group_select && group_select.children[1]){group_select.removeChild(group_select.children[1]);}

		var group_list = document.getElementById("group_edit_list");
		while(group_list && group_list.firstChild){group_list.removeChild(group_list.firstChild);}

		var group_ulist = document.querySelector("#create_new_user_inputs ul.groups");
		while(group_ulist && group_ulist.firstChild){group_ulist.removeChild(group_ulist.firstChild);}

		if(group_select && group_list && group_ulist && json_data["groups"]){
			for(var gid in json_data["groups"]){
				var option = document.createElement("option");
				option.value = gid;
				option.innerText = json_data["groups"][gid]["name"];
				group_select.add(option);

				var row = document.createElement("div");
				row.classList.add("tr");
				row.innerHTML = `<div class="td td-center"><span ${json_data["groups"][gid]["protected"]?"":"class='remove_group'"}></span></div>
					<div class="td"><input type=text value="${json_data["groups"][gid]["name"]}" name="groups[${gid}][name]"></div>
					<div class="td"><input type=text value="${json_data["groups"][gid]["description"]}" name="groups[${gid}][description]"></div>`;
				group_list.insertAdjacentElement("beforeend", row);

				var li = document.createElement("li");
				li.innerHTML = `<input type="checkbox" id="new_user_group_${gid}" value="${gid}" name="new_user_groups[]">
					<label for="new_user_group_${gid}">${json_data["groups"][gid]["name"]}</label>`;
				group_ulist.insertAdjacentElement("beforeend", li);
				
			}
			group_select.value = "";
			setup_remove_groups();
		}

		var tbody = document.querySelector("#user-manager .tbody");
		while(tbody.firstChild){tbody.removeChild(tbody.firstChild);}
		if(tbody && json_data["users"]){
			var users = json_data["users"];
			var ch_users = json_data["changed_users"]?json_data["changed_users"]:[];
			json_data["users_order"].forEach(function(uid){
				var row = document.createElement("div")
				row.classList.add("tr");
				row.innerHTML = `<div class='td td-center'>
					<input class='bulk_select_checkbox' type='checkbox' name='selected_users[]' value='${uid}'></div>`;
				row.innerHTML += `<div class='td'><input type="text" name='users[${uid}][username]' value='${users[uid]["username"]}'></div>`;
				var local_account = "";
				var super_user = "";
				var reset_button = "";
				if(uid != json_data["current_user"]){
					local_account= `<input type='hidden' name='users[${uid}][local_account]' value=0>
						<input type='checkbox' name='users[${uid}][local_account]' value=1 ${users[uid]["local_account"]=='1'?"checked":""}>`;
					super_user = `<input type='hidden' name='users[${uid}][superuser]' value=0>
						<input type='checkbox' name='users[${uid}][superuser]' value=1 ${users[uid]["superuser"]=='1'?"checked":""}>`;
					reset_button = `<input type='submit' name='change_pass[${uid}]' value='Reset Password'>`;
				}else{
					local_account = `<input type='checkbox' disabled ${users[uid]["local_account"]=='1'?"checked":""}>`;
					super_user = `<input type='checkbox' disabled ${users[uid]["superuser"]=='1'?"checked":""}>`;
				}
				if(ch_users["new_pass"] && ch_users["new_pass"][uid]){
					reset_button = `<input type='text' value="${ch_users["new_pass"][uid]}" class="new-password" readonly>`;
					row.classList.add("highlight-row");
				}
				row.innerHTML += `<div class='td td-center'>${local_account}</div><div class='td td-center'>${super_user}`;
				row.innerHTML += `</div><div class='td groups'></div><div class='td'>${reset_button}</div>`;
				tbody.insertAdjacentElement("beforeend", row);
				// var gids = Object.keys(users[uid]["groups"]);
				for(var gid in users[uid]["groups"]){
					add_group_to_selected(gid, users[uid]["groups"][gid], row.querySelectorAll(".bulk_select_checkbox"));
				}
				var select = row.querySelector(".groups select");
				if(select){
					select.value = select.firstChild.value;
				}
			});
		}
		prevent_enter_key_on_form();
	});
}
function truncate(text, max_length=100, strip_newline=true){
	if(text === null){return "";}
	if(strip_newline){
		text = text.replace(/\r|\n/g, " ");
	}
	if(text.length > max_length-3){
		return text.substr(0, max_length-3)+"...";
	}
	return text;
}
function expander(){
	document.querySelectorAll(".expand").forEach(function(elem){
		elem.onclick = function(e){
			var container = e.target.parentElement.parentElement;
			container.classList.toggle("expanded");
			container.draggable = !container.draggable;
		}
	});
}
function show_hidden_input(e, id=undefined){
	e.preventDefault();
	if(!id){
		id = `${e.target.id}_inputs`;
	}

	var blocker = document.createElement("div");
	blocker.id = "content-blocker";
	document.body.insertAdjacentElement("afterbegin", blocker);
	document.body.classList.add("hide-overflow");

	var text_entry = document.getElementById(id);
	text_entry.classList.remove("hidden");
	text_entry.querySelector(".loginBox .clear_message").onclick = function(e){
		e.stopPropagation();
		text_entry.classList.add("hidden");

		document.body.removeChild(blocker);
		document.body.classList.remove("hide-overflow");
	}

}

function setup_help(){
	var elements = document.querySelectorAll(".help-msg");
	elements.forEach(function(elem){
		if(!elem.previousElementSibling || !elem.previousElementSibling.classList.contains("help")){
			var html = "<span class='help'>?</span>";
			elem.insertAdjacentHTML("beforeBegin", html);
			elem.parentElement.querySelector(".help").addEventListener("click", (e)=>{
				e.target.parentElement.querySelector(".help-msg").classList.toggle("hidden");
			});
		}
	});
}

function select_all(event){
	var state = event.target.checked;
	var elements = document.querySelectorAll(".bulk_select_checkbox");
	elements.forEach(function(cb){
		cb.checked = state;
	});
}

function group_add(event){
	var group = document.getElementById("group_to_bulk_add");
	event.preventDefault();
	if(!group.value){
		create_message("Please select a group", "warning");
		return;
	}

	var selected = document.querySelectorAll(".bulk_select_checkbox:checked");
	if(!selected.length){
		create_message("Please select a user", "warning");
		return;
	}

	add_group_to_selected(group.value, group.children[group.selectedIndex].text, selected);
}
function add_group_to_selected(groupid, groupname, selected){

	selected.forEach(function(cb){
		var uid = parseInt(cb.value);
		var groups = cb.parentElement.parentElement.querySelector(".td.groups");
		var select = groups.querySelector("select");
		if(groups.querySelector("input[type='hidden'][value='"+groupid+"']")){
			select.value = groupid;
			return;
		}
		var input = document.createElement("input");
		input.type="hidden";
		input.value  = groupid;
		input.name = `users[${uid}][groups][]`;
		groups.insertAdjacentElement("afterbegin", input);
		var option = document.createElement("option");
		option.value = groupid;
		option.text = groupname;
		if(!select){
			var rm_grp = document.createElement("div");
			rm_grp.onclick = remove_group;
			rm_grp.classList.add("remove_group");
			select = document.createElement("select");

			groups.insertAdjacentElement("afterbegin", rm_grp);
			groups.insertAdjacentElement("beforeend", select);
		}
		select.add(option);
		select.value = groupid;
	});
}
function prevent_enter_key_on_form(){
	var elements = document.querySelectorAll("form.no-enter input");
	elements.forEach(function(e){
		e.onkeydown = function(e){
			if(e.keyCode == 13){
				e.preventDefault();
			}
		}
	});
}
function logout_everywhere(){
	if(confirm("This will logout you out of every device\n\nContinue?")){
		window.location = "/login.php/purge";
	}
}

function setup_remove_groups(){
	var elements = document.querySelectorAll(".remove_group");
	elements.forEach(function(rm_grp){
		rm_grp.onclick = remove_group;
	});
}

function remove_group(event){
	var prnt = event.target.parentElement;
	var select = prnt.querySelector("select");
	if(select){
		var group = select.value;
		prnt.removeChild(prnt.querySelector("input[type='hidden'][value='"+group+"']"));
		select.remove(select.selectedIndex);
		if(!select.childElementCount){
			prnt.removeChild(select);
			prnt.removeChild(event.target);
		}
	}else{
		var row = event.target.parentElement.parentElement;
		event.target.parentElement.parentElement.parentElement.removeChild(row);
	}
}

function create_message(text, type){
	var messages = document.getElementById("messages");
	if(!messages){
		messages = document.createElement("div");
		messages.id = "messages";
		document.body.insertAdjacentElement("afterbegin", messages);
	}
	var msg = document.createElement("div");
	msg.classList.add(type);
	msg.classList.add("new");
	msg.innerHTML = "<div class='clear_message'></div>"+text;
	messages.insertAdjacentElement("afterbegin", msg);
	setTimeout(function(){msg.classList.remove("new")}, 5000);
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

function updateIcon(){
	var text = document.getElementById("icon-input");
	var type = document.getElementById("icon-type");
	var icon = document.getElementById("icon");

	var name = "fa" + type.value[0] + " fa-" + text.value;
	icon.className = name;
}

function locationUpdate(e){
	var elem = document.getElementById("currently_dragging");
	if(!elem){
		return;
	}
	// e.stopPropagation();
	var y = 0;
	if(e.type == "touchmove"){
		y = e.touches[0].clientY;
		x = e.touches[0].clientX;
		dragEnter(document.elementFromPoint(x,y));
	}else{
		y = e.y;
		x = e.x;
	}
	if(y == 0 && x == 0){
		return;
	}
	y += window.scrollY;
	elem.style.top = y - (elem.getBoundingClientRect().height/2) + "px";
	var bcrect = elem.getBoundingClientRect();
	if(bcrect.top - 40 < 0){
		window.scrollBy(0, -200);
	}else if(bcrect.bottom + 40 > window.innerHeight){
		window.scrollBy(0, 200);
	}

}

function startMove(e){
	document.querySelectorAll(".draggable.placeholder").forEach((ph)=>{ph.classList.remove("placeholder")});
	document.querySelectorAll(".draggable#currently_dragging").forEach((cd)=>{cd.parentElement.removeChild(cd)});
	var target = e.target;
	var y = e.pageY;
	if(e.type == "touchstart"){
		target = target.parentElement.parentElement;
		y = e.touches[0].pageY;
		if(!target.draggable){
			return;
		}
	}
	y = y + window.scrollY;
	// e.stopPropagation();

	var elem = undefined;
	if(not_firefox){
		elem = target.cloneNode(true);
		elem.id = "currently_dragging";
	}

	var place_holder = target;
	place_holder.classList.add("placeholder");

	if(!not_firefox){
		return;
	}

	place_holder.insertAdjacentElement("afterend", elem);

	elem.style.top = y - (elem.getBoundingClientRect().height/2) + "px";
	elem.style.width = place_holder.getBoundingClientRect().width + "px";
	if(e.type == "dragstart"){
		e.dataTransfer.setDragImage(new Image(), 0, 0);
	}

}

function endMove(e){
	var place_holder = e.target;
	if(e.type== "touchend"){
		place_holder = e.target.parentElement.parentElement;
	}
	place_holder.classList.remove("placeholder");

	var elem = document.getElementById("currently_dragging");
	if(!elem){
		return;
	}
	elem.parentElement.removeChild(elem);
	// e.stopPropagation();

}
function dragEnter(e){
	if(!e){return;}
	var tgt = (e.nodeType && e.nodeType == Node.ELEMENT_NODE)?e:e.target;
	if( tgt.nodeType != Node.ELEMENT_NODE ||
			(!tgt.classList.contains("draggable") &&
				!tgt.classList.contains("dragger-container")) ||
			tgt.classList.contains("placeholder")
	){
		return;
	}
	var scope = tgt.parentElement;
	if(tgt.classList.contains("secondary")){
		scope = scope.parentElement.parentElement.parentElement;
	}else if(tgt.classList.contains("dragger-container")){
		scope = scope.parentElement.parentElement;
	}

	var placeholder = scope.querySelector(".placeholder");
	if(!placeholder){
		return;
	}

	var location = tgt.getBoundingClientRect().top > placeholder.getBoundingClientRect().top?"afterend":"beforebegin";
	if(tgt.classList.contains("dragger-container")){
		if( placeholder.classList.contains("secondary") && placeholder.parentElement != tgt && 
			!(tgt.classList.contains("header_content") && placeholder.classList.contains("post"))
		){
			location = location=="afterend"?"afterbegin":"beforeend";
			placeholder.parentElement.removeChild(placeholder);
			tgt.insertAdjacentElement(location, placeholder);
		}
	}else if(placeholder.classList.contains("secondary") && !tgt.classList.contains("secondary")){
		clearTimeout(hovered_timeout);
		scope.querySelectorAll(".draggable.hovered").forEach(function(hvrd){hvrd.classList.remove("hovered")});
		if(!tgt.querySelector(".placeholder")){
			if(!tgt.classList.contains("expanded")){
				tgt.classList.add("hovered");
				hovered_timeout = setTimeout(function(){
					if(tgt.classList.contains("hovered")){ tgt.classList.add("expanded"); tgt.draggable = false; }
				}, 1000);
			}
		}
	}else if(placeholder.classList.contains("secondary") == tgt.classList.contains("secondary")){
		if(!(tgt.classList.contains("secondary") && tgt.parentElement.classList.contains("header_content") && placeholder.classList.contains("post"))){
			placeholder.parentElement.removeChild(placeholder);
			tgt.insertAdjacentElement(location, placeholder);
		}
	}

	// e.stopPropagation();
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
