$(document).ready(function() {
	$('#fb-wall a.showComments').bind('click', function() {
		$(this).closest('footer').prev('.comments').toggle(300);
	});

});


