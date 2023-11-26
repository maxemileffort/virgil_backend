// jQuery(document).ready(function($) {
//     $('#user_email').blur(function() {
//         var email = $(this).val();
//         console.log(email);
//         $.ajax({
//             url: my_plugin_ajax.ajax_url,
//             type: 'POST',
//             data: {
//                 'action': 'wp_ajax_check_email',
//                 'user_email': email
//             },
//             success: function(response) {
//                 if (response === 'exists') {
//                     $('#user_email').next('.email-error').remove();
//                     $('#user_email').after('<p class="email-error">That email address is in use already.</p>');
//                     $('input[type="submit"]').attr('disabled', 'disabled');
//                 } else {
//                     $('.email-error').remove();
//                     $('input[type="submit"]').removeAttr('disabled');
//                 }
//             }
//         });
//     });
// });

document.addEventListener('DOMContentLoaded', function() {
    var userEmailInput = document.getElementById('user_email');
    var submitButton = document.getElementsByName('submit_single_user')[0];
    console.log(submitButton);

    userEmailInput.addEventListener('blur', function() {
        var email = userEmailInput.value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', my_plugin_ajax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = xhr.responseText;
                var emailError = document.querySelector('.email-error');
                
                if (response === 'exists') {
                    console.log(response);
                    if (!emailError) {
                        var errorMessage = document.createElement('p');
                        errorMessage.className = 'email-error';
                        errorMessage.textContent = 'That email address is in use already.';
                        userEmailInput.parentNode.insertBefore(errorMessage, userEmailInput.nextSibling);
                    }
                    submitButton.disabled = true;
                } else {
                    if (emailError) {
                        emailError.parentNode.removeChild(emailError);
                    }
                    submitButton.disabled = false;
                }
            }
        };

        xhr.send('action=check_email&user_email=' + encodeURIComponent(email));
    });
});
