$(document).ready(function() {
    var dur = 300;

    // Likes
    $('#fb-wall a.showLikes').bind('click', function() {
        $(this).closest('footer').parent('.fb-post').find('.likes').toggle(dur);
    });

    // Comments
	$('#fb-wall a.showComments').bind('click', function() {
		$(this).closest('footer').parent('.fb-post').find('.comments').toggle(dur);
	});

});


