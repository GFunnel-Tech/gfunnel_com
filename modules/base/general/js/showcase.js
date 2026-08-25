$(document).ready(function () {
    bx_showcase_view_init();

    $(window).on('resize', function() {
        bx_showcase_view_init();
    });
});

function bx_showcase_view_init() {
    const sSelWrapper = '.bx-base-unit-showcase-wrapper';
    const sSelItem = '.bx-base-unit-showcase';

    bx_showcase_init(sSelWrapper, sSelItem);
}