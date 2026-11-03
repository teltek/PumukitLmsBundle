document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('autosubmit');
    if (form) {
        window.setTimeout(function () {
            form.submit();
        }, 500);
    }

    document.querySelectorAll('[data-lti-close]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            (window.opener || window.parent).postMessage({ subject: 'org.imsglobal.lti.close' }, '*');
        });
    });
});
