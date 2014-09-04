
$(document).ready(function () {
    // Add selector for datetime fields
    $('input[type=datetime]').datetimepicker();
    $('input[type=date]').datepicker();
    $('input[type=time]').timepicker({ showMeridian: false });

    // Add rich text editor to TEXT fields
    tinymce.init({
        selector: 'textarea',
        menubar: false,
        plugins: 'code table link autoresize fullscreen',
        toolbar: 'styleselect bold italic link unlink numlink bullist indent outdent code fullscreen',
        autoresize_max_height: 400
    });

    // Add confirmation dialog on delete button
    $('a[data-bb=confirm]').click(function () {
        var anchor = this;
        bootbox.confirm('Are you sure you want to <i>permanently</i> delete this record?', function (result) {
            if (result)
                window.location = anchor.href;
        });
        return false;
    });

    // Add widget for enum choices
    $('select.enum').select2({ minimumResultsForSearch: 8 });

    // Add search select field for foreign keys
    $('input.ref').each(function (_, element) {
        var table = $(element).data('table');
        $(element).select2({
            ajax: {
                url: '../' + table + '.json',
                quietMillis: 500,
                data: function (term, page) {
                    var result = {};
                    result[table + '-search'] = term;
                    result[table + '-page'] = page;
                    return result;
                },
                results: function (data, page) {
                    var t = data.has[table],
                        more = t.pages > t.page;
                    return { results: t.results, more: more };
                }
            },
            initSelection: function (element, callback) {
                var id = $(element).val();
                $.ajax('../' + table + '/' + id + '.json').done(function (data) {
                    callback(data.row);
                })
            },
            formatResult: function (record) {
                return record.__tostring;
            },
            formatSelection: function (record) {
                return record.__tostring;
            },
        });
    });

    // Update infos on blob file upload
    $('input[type="file"]').change(function() {
        $(this).parent().next('.upload-file-info').html($(this).val());
    });
})
