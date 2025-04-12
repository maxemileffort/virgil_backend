jQuery(document).ready(function($) {

    // Ensure localized data is available
    if (typeof practiceTestMembers === 'undefined') {
        console.error('Members Handler Error: Localized data not available.');
        return;
    }

    const ajax_url = practiceTestMembers.ajax_url;
    const nonce = practiceTestMembers.nonce;
    const $displayContainer = $('#member-display');
    const $contentArea = $('#member-display-content');
    const $loadingIndicator = $('#member-display-loading');

    // Function to load content via AJAX
    function loadMemberContent(action) {
        $contentArea.html(''); // Clear previous content
        $loadingIndicator.show(); // Show loading indicator

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'load_member_content', // The WP AJAX action hook
                nonce: nonce,
                sub_action: action // The specific content to load (e.g., 'view_tests')
            },
            success: function(response) {
                if (response.success) {
                    $contentArea.html(response.data.html);
                } else {
                    console.error('Error loading member content:', response.data.message || 'Unknown error');
                    $contentArea.html('<p class="error">Error loading content. Please try again.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error loading member content:', textStatus, errorThrown);
                $contentArea.html('<p class="error">AJAX Error loading content. Please check connection and try again.</p>');
            },
            complete: function() {
                $loadingIndicator.hide(); // Hide loading indicator
            }
        });
    }

    // Attach click handler to navigation links
    $('#members-area').on('click', '.member-nav-link', function(e) {
        e.preventDefault(); // Prevent default link behavior

        const $link = $(this);
        const actionToLoad = $link.data('action');

        if (actionToLoad) {
            // Optional: Add active class styling to the clicked link
            $('.member-nav-link').removeClass('active'); // Remove from all
            $link.addClass('active'); // Add to clicked

            loadMemberContent(actionToLoad);
        }
    });

    // Optional: Load initial content on page load (e.g., the tests view)
    // Find the link for the default view and trigger a click, or call loadMemberContent directly
    const $defaultLink = $('.member-nav-link[data-action="view_tests"]').first();
    if ($defaultLink.length) {
         $defaultLink.addClass('active'); // Set active state
         loadMemberContent($defaultLink.data('action'));
    } else {
        // Fallback if default link isn't found
        $contentArea.html('<p>Welcome to your member area. Select an option to get started.</p>');
    }

    // --- Handle Link Student Form Submission ---
    // Use event delegation since the form is loaded dynamically
    $displayContainer.on('submit', '#link-student-form', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const $messageArea = $('#link-student-message');
        const studentEmail = $form.find('#student_email').val();
        const formNonce = $form.find('input[name="nonce"]').val(); // Get nonce from the form

        $submitButton.prop('disabled', true).val('Linking...');
        $messageArea.html('').removeClass('error success'); // Clear previous messages

        $.ajax({
            url: ajax_url, // Use the localized ajax_url
            type: 'POST',
            data: {
                action: 'link_student_account', // The WP AJAX action hook
                nonce: formNonce, // Use the nonce from the form
                student_email: studentEmail
            },
            success: function(response) {
                if (response.success) {
                    $messageArea.html('<p style="color: green;">' + response.data.message + '</p>').addClass('success');
                    $form.find('#student_email').val(''); // Clear input on success
                    // TODO: Optionally reload the 'add_student' section to show the updated list of linked students
                    // loadMemberContent('add_student');
                } else {
                    console.error('Error linking student:', response.data.message || 'Unknown error');
                    $messageArea.html('<p style="color: red;">Error: ' + (response.data.message || 'Could not link student.') + '</p>').addClass('error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error linking student:', textStatus, errorThrown);
                 $messageArea.html('<p style="color: red;">AJAX Error: Could not link student. Please try again.</p>').addClass('error');
            },
            complete: function() {
                $submitButton.prop('disabled', false).val('Link Student Account');
            }
        });
    });

    // --- Handle View Student Performance Click ---
    // Use event delegation since the list is loaded dynamically
    $displayContainer.on('click', '.view-student-performance', function(e) {
        e.preventDefault();
        const $link = $(this);
        const studentId = $link.data('student-id');

        if (!studentId) {
            console.error('View Student Performance Error: Missing student ID.');
            return;
        }

        // Show loading state specifically for this action if desired,
        // or just rely on the main loading indicator.
        $contentArea.html(''); // Clear previous content
        $loadingIndicator.show(); // Show loading indicator

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'load_student_performance', // The WP AJAX action hook
                nonce: nonce, // Use the main members area nonce
                student_id: studentId
            },
            success: function(response) {
                if (response.success) {
                    $contentArea.html(response.data.html);
                    // Optional: Add a 'Back' button or modify UI to indicate viewing student data
                    $contentArea.prepend('<p><a href="#" class="member-nav-link" data-action="add_student">&laquo; Back to Link Students</a></p><hr>');
                } else {
                    console.error('Error loading student performance:', response.data.message || 'Unknown error');
                    $contentArea.html('<p class="error">Error loading student performance data. Please try again.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error loading student performance:', textStatus, errorThrown);
                $contentArea.html('<p class="error">AJAX Error loading student performance data. Please check connection and try again.</p>');
            },
            complete: function() {
                $loadingIndicator.hide(); // Hide loading indicator
            }
        });
    });


});
