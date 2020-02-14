/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function header_dropdown() {
	document.getElementById("dropdownMenu").classList.toggle("show");
}

// Close the dropdown if the user clicks outside of it
document.onclick = function(e) {
	if (!e.target.matches('.dropBtn')) {
		var myDropdown = document.getElementById("dropdownMenu");
		if (myDropdown.classList.contains('show')) {
			myDropdown.classList.remove('show');
		}
	}
}
