const labels = ProcessWire.config.ProcessSessionInfo.labels;
var refreshSessionTimer = null;

// Refresh the session list
// This is copied from the core ProcessSessionDB.js
function refreshSessionList() {
	var $icon = $("#submit_session i, #submit_session_copy i");
	$icon.removeClass('fa-refresh').addClass('fa-spin fa-spinner');

	$.get("./", function (data) {
		$("#SessionList").html(data);
		refreshSessionTimer = setTimeout('refreshSessionList()', 5000);
		$icon.removeClass('fa-spin fa-spinner').addClass('fa-refresh');
	});
}

$(document).ready(function() {

	// First refresh
	// This is copied from the core ProcessSessionDB.js
	refreshSessionTimer = setTimeout('refreshSessionList()', 5000);

	// When force logout link is clicked
	$(document).on('click', 'a.force-logout', function(event) {
		event.preventDefault();
		const href = $(this).attr('href');
		const user_name = $(this).closest('tr').find('td:nth-child(2)').text();
		ProcessWire.confirm(labels.confirm_logout + ` "${user_name}"?`, function() {
			window.location.href = href;
		});
	});

});
