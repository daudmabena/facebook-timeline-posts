$(document).ready(function() {
    var dur = 300;

    // Likes
    $('#fb-wall a.showLikes').bind('click', function(e) {
    	e.preventDefault();
        $(this).closest('footer').parent('.fb-post').find('.likes').toggle(dur);
    });

    // Comments
	$('#fb-wall a.showComments').bind('click', function(e) {
		e.preventDefault();
		$(this).closest('footer').parent('.fb-post').find('.comments').toggle(dur);
	});

});


