window.pageLoadFiles = [
    'Form',
];

window.pageOnLoad = function () {
    $.form.manage('/notify/api/config', '#notify-config-form');

    window.pageOnUnLoad = function () {
    };

    return false;
};
