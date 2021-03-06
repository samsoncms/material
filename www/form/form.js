/* Form main JS */

SamsonCMS_InputINIT_SELECT_STRUCTURE = function(select) {
    select.selectify();

    initLinks(select.prev());

    s('._sjsselect_dropdown li', select.prev()).each(function(li) {
        if (!li.hasClass('selectify-loaded')) {
            li.click(function(li) {
                s.ajax(select.a('data-href-add') + li.a('value') + '/', function(response) {
                    initLinks(select.prev());
                    updateSelect();
                });
                li.addClass('selectify-loaded');
            });
        }
    });

    function initLinks(block) {
        s('._sjsselect ._sjsselect_delete', block).each(function(link) {
            if (!link.hasClass('selectify-loaded')) {
                link.click(function(link) {
                    s.ajax(select.a('data-href-remove') + link.a('value') + '/', function(response) {
                        time = Date.now();
                        updateSelect();
                    });
                    link.addClass('selectify-loaded');
                });
            }
        });
    }
};

// Bind input
SamsonCMS_Input.bind(SamsonCMS_InputINIT_SELECT_STRUCTURE, '.material-structure-selectify');

// Global params
var timer = 0;
var instance = false;
var timeout = 500; // 1 sec

// If update select with structures then update main form
function updateSelect(){

    // If there started some instance of this function then not call any instances
    if ((instance != false)){
        return;
    }
    instance = true;

    // Set timeout and call loading form
    setTimeout(function(){
        loader.show('', true);
        var changeBlock = s('.application-form');
        var url = s('.material-structure-selectify').a('data-update-form');
        s.ajax(url, function(response) {
            if (response != null) {
                var data = JSON.parse(response);
                if (data.form != null) {
                    // Change hash to main
                    location.hash = '#samsoncms_form_tab_Generic';
                    changeBlock.html(data.form);
                    // Init all tabs
                    SamsonCMS_Input.update(s('body'));
                    loader.hide();
                    instance = false;
                }
            }
        });
    }, timeout);
}

s('.generate-material-url-link').pageInit(function(link) {
    var href = link.a('href');
    link.a('href', href + s('#entityId').val() + '/');
    link.ajaxClick(function(response) {
        var field = s('.__inputfield.__unique_url');
        var parent = field.parent();
        if (response.urlError != undefined) {
            var error;
            if (parent.next().length && parent.next().hasClass('template-form-error')) {
                error = parent.next();
                error.html(response.urlError);
            } else {
                error = s('<div class="template-form-error">' + response.urlError  + '</div>');
            }
            parent.css('border-color', 'red');
            parent.parent().append(error);
        } else {
            if (parent.next().length && parent.next().hasClass('template-form-error')) {
                parent.next().remove();
            }
            parent.css('border-color', 'black');
            field.removeClass('__empty');
            if (response.createdUrl.length) {
                s('span', parent).html(response.createdUrl);
                s('.__hidden', parent).val(response.createdUrl);
                s('.__input', parent).val(response.createdUrl);
            }
        }
    });
});