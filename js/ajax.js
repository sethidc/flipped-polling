jQuery(document).ready(function($) {
    $('.flipped-poll-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var pollId = $form.data('poll-id');
        var vote = $form.find('input[name="poll_vote_' + pollId + '"]:checked').val();

        if (!vote) {
            alert('Please select an option.');
            return;
        }

        $.ajax({
            url: flippedPollingAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'flipped_polling_vote',
                poll_id: pollId,
                vote: vote,
                nonce: flippedPollingAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $form.parent().replaceWith(response.data);
                } else {
                    alert(response.data || 'Error voting.');
                }
            },
            error: function() {
                alert('An error occurred while submitting your vote.');
            }
        });
    });
});