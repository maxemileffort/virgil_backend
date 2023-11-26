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
