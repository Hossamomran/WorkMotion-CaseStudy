//should be deleted kept for testing and Task purpose
jQuery(document).ready(function($) {
    var offset = custom_ajax_object.posts_per_page;
    $('#load-more-posts').on('click', function() {
        $.ajax({
            type: 'POST',
            url: custom_ajax_object.ajax_url,
            data: {
                action: 'load_more_posts',
                nonce: custom_ajax_object.nonce,
                category: custom_ajax_object.category,
                posts_per_page: custom_ajax_object.posts_per_page,
                offset: offset,
            },
            success: function(response) {
                if (response.trim().length > 0) {
                    $('#recent-posts').append(response);
                    offset += custom_ajax_object.posts_per_page;
                } else {
                    $('#load-more-posts').text('No more posts to load').prop('disabled', true);
                }
            },
            error: function(response) {
                console.log(response);
            }
        });
    });
});
