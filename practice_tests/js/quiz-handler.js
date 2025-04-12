jQuery(document).ready(function($) {

    // Ensure localized data is available
    if (typeof practiceTestQuiz === 'undefined' || typeof currentPracticeTestAttemptId === 'undefined') {
        console.error('Quiz Handler Error: Localized data not available.');
        return;
    }

    const ajax_url = practiceTestQuiz.ajax_url;
    const nonce = practiceTestQuiz.nonce;
    const attempt_id = currentPracticeTestAttemptId; // Passed via wp_add_inline_script

    // --- Save Answer on Change ---
    $('.practice-test-quiz-container').on('change', 'input[type="radio"]', function() {
        const questionDiv = $(this).closest('.quiz-question');
        const question_id = questionDiv.data('question-id');
        const answer = $(this).val();

        // Disable inputs while saving
        questionDiv.find('input[type="radio"]').prop('disabled', true);
        // Optional: Add a visual indicator (e.g., spinner)

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'save_practice_test_answer',
                nonce: nonce,
                attempt_id: attempt_id,
                question_id: question_id,
                answer: answer
            },
            success: function(response) {
                if (response.success) {
                    // console.log('Answer saved for question ' + question_id);
                    // Optional: Add success indicator
                } else {
                    console.error('Error saving answer:', response.data.message || 'Unknown error');
                    alert('Error saving your answer. Please try again.'); // Inform user
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error saving answer:', textStatus, errorThrown);
                alert('AJAX Error saving your answer. Please check your connection and try again.');
            },
            complete: function() {
                // Re-enable inputs
                questionDiv.find('input[type="radio"]').prop('disabled', false);
                // Optional: Remove visual indicator
            }
        });
    });

    // --- Save and Exit Button ---
    $('.practice-test-quiz-container').on('click', '.save-exit-button', function(e) {
        e.preventDefault();
        const button = $(this);
        button.prop('disabled', true).text('Saving...');

        // Optional: Ensure the last selected answer is saved before pausing
        // This might require triggering the 'change' handler logic if an answer was just selected
        // Or, modify the pause callback to also accept the last answer data.
        // For simplicity now, we assume answers are saved on change.

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'pause_practice_test',
                nonce: nonce,
                attempt_id: attempt_id
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to the members area or a specified 'progress saved' page
                    window.location.href = '/members'; // Adjust this URL as needed
                } else {
                    console.error('Error pausing test:', response.data.message || 'Unknown error');
                    alert('Error saving progress. Please try again.');
                    button.prop('disabled', false).text('Save and Exit');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error pausing test:', textStatus, errorThrown);
                alert('AJAX Error saving progress. Please check your connection and try again.');
                button.prop('disabled', false).text('Save and Exit');
            }
        });
    });

    // --- Submit Test Button ---
    $('.practice-test-quiz-container').on('click', '.quiz-submit-button', function(e) {
        e.preventDefault();
        const button = $(this);

        // Optional: Add a confirmation dialog
        if (!confirm('Are you sure you want to submit the test? You cannot change your answers after submitting.')) {
            return;
        }

        button.prop('disabled', true).text('Submitting...');

        // Optional: Ensure the last selected answer is saved before submitting
        // Similar consideration as for 'Save and Exit'

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'submit_practice_test',
                nonce: nonce,
                attempt_id: attempt_id
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to the results page provided by the server
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        // Fallback if no redirect URL is provided
                        alert('Test submitted successfully! Redirecting...');
                        window.location.href = '/members'; // Or a default results page
                    }
                } else {
                    console.error('Error submitting test:', response.data.message || 'Unknown error');
                    alert('Error submitting test. Please try again.');
                    button.prop('disabled', false).text('Submit Test');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error submitting test:', textStatus, errorThrown);
                alert('AJAX Error submitting test. Please check your connection and try again.');
                button.prop('disabled', false).text('Submit Test');
            }
        });
    });

});
