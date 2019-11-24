$(function () {
    var element = '[data-toggle="popover"]';
    $(element).popover();
    $('body').on('click', (e) => {
        $(element).each((index, elm) => {
            hidePopover(elm, e);
        }); 
    });
    var hidePopover = (element, e) => {
        if (!$(element).is(e.target) && $(element).has(e.target).length === 0 && $('.popover').has(e.target).length === 0){
            $(element).popover('hide');
        }
    }
});
