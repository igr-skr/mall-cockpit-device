jQuery(document).ready(function($) {
    /**
     * Wordpress Topbar Button ClickEvent
     */
    $('#wp-admin-bar-playlist-wizard').click(function() {
        showPlaylistWizardModal($);
    });

    const dropdownCategories = new DropdownCategories();

    $('body').on('focus', 'input[name="kamp_name"]', function(event) {
        $('input[name="kamp_name"]').parent().append(dropdownCategories.show());
        dropdownCategories.findInOptions($(this).val());
    });

    $('body').on('input', 'input[name="kamp_name"]', function(event) {
        dropdownCategories.findInOptions($(this).val());
    });

    $('body').on('option_click_trigger', function (event, data) {
        $('body').find('input[name="kamp_name"]').val(data);
        dropdownCategories.hide();
    });

    $('body').on('close_popup_wizard', function (event) {
        dropdownCategories.hide();
    });
});

class DropdownCategories
{
    data = [];
    selectorSelect = '.admin_playlist_wizard__select';
    selectorOption = '.admin_playlist_wizard__option';
    rendered = '';
    isRendered = false;

    constructor() {
        this.loadData();

        jQuery('body').on('click', this.selectorOption, this.optionClickHandler);

        jQuery(document).click(this.clickOutsideSelectHandler.bind(this));
    }

    clickOutsideSelectHandler (event) {
        const target = jQuery(event.target);

        if((!target.closest(this.selectorSelect).length && 
                    jQuery(this.selectorSelect).is(":visible")) &&
            !target.closest('.controller-container-vertical').length &&
            !target.closest('input[name="kamp_name"]').length) {
            jQuery(this.selectorSelect).hide();
        }
    }

    show() {
        jQuery('body').find(this.selectorSelect).show();

        return this.render();
    }

    hide() {
        jQuery('body').find(this.selectorSelect).hide();
    }

    loadData() {
        const $this = this;

        jQuery.ajax({
            type: 'POST',
            url: playlist_wizard_ajax.ajaxurl,
            data: {
                action: 'categories_list'
            },
            success: function ({data}) {
                $this.data = data;
            }
        });
    }

    findInOptions(search) {
        if (!this.isRendered) return;

        jQuery('body').find(this.selectorOption).each(function (index) {

            if (jQuery(this).text().includes(search)) jQuery(this).show();
            else jQuery(this).hide();
        });
    }

    render() {
        if (this.isRendered) return this.rendered;

        let options = '';

        this.data.forEach(function (item) {
            options += `<span class="admin_playlist_wizard__option" data-value="${item.id}">${item.name}</span>`
        });

        this.isRendered = true;

        return this.rendered = jQuery(`<div class="admin_playlist_wizard__select close">${options}</div>`);
    }

    optionClickHandler(event) {
        const value = jQuery(this).text();

        jQuery('body').trigger('option_click_trigger', value);
    }
}

/**
 * Save Form
 *
 */
function savePlaylistWizardItem($)
{
    $('#modal-playlist-save button').attr('disabled', 'disabled');
    $('#modal-playlist-save button').text('lädt...');

    jQuery.ajax({
        type: 'POST',
        url: playlist_wizard_ajax.ajaxurl,
        data: jQuery('#modal-playlist-wizard-container').serialize() + '&action=playlistwizard',
        success: function (data, textStatus, XMLHttpRequest) {
            data = $.parseJSON(data);
            $('#modal-playlist-wizard-container').html('<div id="playlist-wizard-saved"><h2>Erfolgreich veröffentlicht!</h2><hr />' + data.output + '</div>');
            $('#modal-playlist-save').remove();
            jQuery(document).scrollTop(0);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Fehler beim Veröffentlichen!');
        }
    });
}

/**
 * Add error message
 *
 * @param $
 * @param element
 * @param text
 */
function addErrorPlaylistWizard($, element, text, cn)
{
    var msg = $('<div class="playlist-error-msg '+cn+'"></div>')
        msg.text(text);
        msg.append('<div class="arrow"></div>');

    $(element).append(msg);
}

/**
 * Formular Validierung und speichern
 * @param $
 */
function validatePlaylistFormAndSave($)
{
    $('.playlist-error-msg').remove();

    var status = true;
    if ($('#playlist_wizard_upload_file').val() == '') {
        addErrorPlaylistWizard($, $('.playlist-step-1'), 'Bitte wähle eine Datei aus.', 'playlist-wizard-upload-error');
        status = false;
    }

    if ($('#playlist_wizard_center').val() == null) {
        addErrorPlaylistWizard($, $('.playlist-step-2'), 'Bitte wähle min. ein Center aus.', 'playlist-wizard-center-error');
        status = false;
    }

    var selectedFile = jQuery('#playlist_wizard_upload_selected a');
    var selectedFileType = selectedFile.text().substring(selectedFile.text().length-4, selectedFile.text().length)

    if ($('#playlist_wizard_length').val() == '' && selectedFileType != '.mp4' && selectedFileType != 'webm') {
        addErrorPlaylistWizard($, $('.playlist-step-3'), 'Bitte geben eine Dauer ein.', 'playlist-wizard-length-error');
        status = false;
    } else if ($('#playlist_wizard_length').val() != '' && (selectedFileType == '.mp4' || selectedFileType == 'webm')) {
        addErrorPlaylistWizard($, $('.playlist-step-3'), 'Video-Datei darf keine Dauer haben.', 'playlist-wizard-length-error');
        status = false;
    }

    if ($('.playlist-time-row').length == 0) {
        addErrorPlaylistWizard($, $('.playlist-step-4'), 'Füge min. einen Zeitraum hinzu.', 'playlist-wizard-time-error');
        status = false;
    }

    $.each($('.playlist-time-row'), function(index,item) {
        if ($(this).find('.pl-date-start').val() == '' || $(this).find('.pl-date-end').val() == '') {
            addErrorPlaylistWizard($, $('.playlist-time-row'), 'Datum Start/Ende ist ein Pflichtfeld', 'playlist-wizard-date-error');
            status = false;
        }

        if ($('.playlist-timeline-row').length == 0) {
            addErrorPlaylistWizard($, $(this).find('h2'), 'Zeit darf nicht leer sein.', 'playlist-wizard-timeline-error');
            status = false;
        }
    });

    if (status === true) {
        savePlaylistWizardItem($);
    } else {
        jQuery(document).scrollTop(0);
    }
}

/**
 * Modal generieren und anzeigen
 * @param $
 */
function showPlaylistWizardModal($)
{
    var modal = $('<div id="modal-playlist-wizard"></div>');
    var modalBackground = $('<div id="modal-playlist-wizard-background"></div>');
    var content = $('<form autocomplete="off" id="modal-playlist-wizard-container"></form>')

    content.append('<input autocomplete="false" name="hidden" type="text" style="display:none;">');
    content.append(embedPlaylistWizardStep5($));
    content.append(embedPlaylistWizardStep1($));
    content.append(embedPlaylistWizardStep2($));
    content.append(embedPlaylistWizardStep3($));
    content.append(embedPlaylistWizardStep4($));

    var closeButton = $('<div id="modal-playlist-close"><p>Playlist Wizard</p><button type="button">&times;</button></div>');
        closeButton.click(function() {
            modal.remove();
            modalBackground.remove();
            $('body').trigger('close_popup_wizard');
        });
    var saveButton = $('<div id="modal-playlist-save"><button type="button">Veröffentlichen</button></div>');
        saveButton.click(function() {
            validatePlaylistFormAndSave($);
        });
    modal.append(closeButton);
    modal.append(content);
    modal.append(saveButton);

    $('body').append(modal);
    $('body').append(modalBackground);

    jQuery(document).scrollTop(0);

    $('#playlist_wizard_center').on('click focus keyup keydown', function() {
        $('.playlist-wizard-center-error').remove();
    });

    $('#playlist_wizard_length').on('click focus keyup keydown', function() {
        $('.playlist-wizard-length-error').remove();
    });
}

function embedPlaylistWizardStep5($)
{
    var container = $('<div class="playlist-step playlist-step-0"></div>');
        container.append('<h2>Kampagnen-Daten</h2>');
    var fields = '<div class="controller-container-vertical"><input type="text" name="kamp_name" value="" placeholder="Kampagnenname"></div>';
        fields += '<div class="controller-container-vertical half-column left"><input type="text" id="kunden" name="kunden" value=""  placeholder="Kunden"></div>';
        fields += '<div class="controller-container-vertical half-column right"><input type="text" id="order_nr" name="order_nr" value="" placeholder="Order-Nr"></div>';
        fields += '<div class="clear"></div>';

    container.append(fields);

    return container;
}
/**
 * Schritt 4: Zeitraum auswählen
 * @param $
 * @returns {jQuery|HTMLElement}
 */
function embedPlaylistWizardStep4($)
{
    var container = $('<div class="playlist-step playlist-step-4"></div>');
        container.append('<h2>4. Zeitraum</h2>');

    var timeRows = $('<div id="playlist-time-rows"></div>');
    var button = $('<button type="button" class="clear-after" id="playlist-add-time-row">Zeitraum hinzufügen</button>')
        button.click(function() {
            $('.playlist-wizard-time-error').remove();

            var row = $('<div class="playlist-time-row"></div>');
            var rowDelete = $('<div class="playlist-delete-row"></div>');
                rowDelete.html('&times;');

                rowDelete.click(function() {
                    row.remove();
                });
            row.append(rowDelete);

            var rowId = $('.playlist-time-row').length;

            var fields = '<div class="playlist-time-fields-row"><div class="playlist-time-col-3"><input type="text" placeholder="d.m.Y - Start-Datum" class="pl-date-start " name="date-start['+rowId+']" value=""></div>';
                fields += '<div class="playlist-time-col-3"><input type="text" placeholder="d.m.Y - End-Datum" class="pl-date-end" name="date-end['+rowId+']" value=""></div>';
                fields += '<div class="playlist-time-col-3"><input type="text" placeholder="Wiederholung (e.g. 1)" name="repeats['+rowId+']" value=""></div></div>';
                fields += '<h2>Zeiten</h2>';



            var btn = $('<button type="button" class="clear-after" id="playlist-add-timeline-row">Zeit hinzufügen</button>');
                btn.click(function() {
                    var subRowId = $('#playlist-timeline-row-'+rowId+' .playlist-timeline-row').length;

                    $(this).closest('.playlist-time-row').find('.playlist-wizard-timeline-error').remove();
                    var timeSelectOptions = '';
                    for (var i = 0; i <= 23; i++) {
                        var t = (i < 10 ? '0' + i : i );
                        timeSelectOptions += '<option value="'+i+'">'+t+':00:00 - '+t+':59:59</option>';
                    }

                    var weekdaysOptions = '';
                        weekdaysOptions += '<option value="montag" selected="selected">Montag</option>';
                        weekdaysOptions += '<option value="dienstag" selected="selected">Dienstag</option>';
                        weekdaysOptions += '<option value="mittwoch" selected="selected">Mittwoch</option>';
                        weekdaysOptions += '<option value="donnerstag" selected="selected">Donnerstag</option>';
                        weekdaysOptions += '<option value="freitag" selected="selected">Freitag</option>';
                        weekdaysOptions += '<option value="samstag" selected="selected">Samstag</option>';
                        weekdaysOptions += '<option value="sonntag" selected="selected">Sonntag</option>';

                    var timeLineRow = $('<div class="playlist-timeline-row"></div>');
                    var timeLineFields  = '<label style="width: 190px; display:inline-block;">Uhrzeit von</label> <select class="pl-time-from" name="time-from['+rowId+']['+subRowId+']">' + timeSelectOptions + '</select><div class="wizard-spacer"></div>';
                        timeLineFields += '<label style="width: 190px; display:inline-block;">Uhrzeit bis</label> <select class="pl-time-to" name="time-to['+rowId+']['+subRowId+']">' + timeSelectOptions + '</select><div class="wizard-spacer"></div>';
                        timeLineFields += '<label style="width: 190px; display:inline-block;">Wochentag</label> <select style="width: 169px" class="pl-weekdays" name="time-weekdays['+rowId+']['+subRowId+'][]" multiple>' + weekdaysOptions + '</select>';

                    timeLineRow.append(timeLineFields);

                    var timeLineDelete = $('<div class="playlist-delete-row"></div>');
                    timeLineDelete.html('&times;');
                    timeLineDelete.click(function() {
                        timeLineRow.remove();
                    });

                    timeLineRow.append(timeLineDelete);

                    $('#playlist-timeline-row-' + rowId).append(timeLineRow);
                });

            row.append(fields);
            row.append(btn);
            row.append('<div id="playlist-timeline-row-'+rowId+'" style="position:relative; min-height:10px;"></div>');
            timeRows.append(row);
            timeRows.find('.pl-date-start,.pl-date-end').datepicker({
                dateFormat : "dd.mm.yy"
            });

            timeRows.find('.pl-date-start,.pl-date-end').on('click focus keyup keydown', function() {
                $(this).closest('.playlist-time-row').find('.playlist-wizard-date-error').remove();
            });
        });

    container.append(button);
    container.append(timeRows);

    return container;
}


/**
 * Schritt 3: Advertiser-ID, Dauer und Format
 * @param $
 * @returns {jQuery|HTMLElement}
 */
function embedPlaylistWizardStep3($)
{
    var container = $('<div class="playlist-step playlist-step-3 half-column"></div>');
    container.append('<h2>3. Dauer und Format</h2><p>Dauer ist ein Pflichtfeld, wenn die ausgewählte Datei eine Bild-Datei ist.</p>');

    var fields = '<div class="controller-container-vertical"><input type="text" id="playlist_wizard_length" name="length" value="" placeholder="Dauer in Sekunden"><div class="wizard-spacer"></div></div>';
        fields += '<div class="controller-container-vertical"><label class="devsm-label">Format</label> <select name="format"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select></div>';
    container.append(fields);

    return container;
}

/**
 * Schritt 2: Center auswählen
 * @param $
 * @returns {jQuery|HTMLElement}
 */
function embedPlaylistWizardStep2($)
{
    var container = $('<div class="playlist-step playlist-step-2 half-column"></div>');
    container.append('<h2>2. Center auswählen</h2><p>Bitte wähle ein oder mehrere Center aus.</p>');

    var checkBoxes = '<select id="playlist_wizard_center" name="center[]" multiple>';
    $.each(window.centerItems, function(index, item) {
        checkBoxes += '<option value="'+item.id+'">'+item.name+'</option>';
    });
    checkBoxes += '</select>';

    container.append(checkBoxes);

    return container;
}

/**
 * Schritt 1: Datei auswählen
 * @param $
 * @returns {jQuery|HTMLElement}
 */
function embedPlaylistWizardStep1($)
{
    var uploadButton = $('<input id="playlist_wizard_file_button" type="button" value="Datei auswählen" />');
    var container = $('<div class="playlist-step playlist-step-1"></div>');
        container.append('<h2>1. Datei auswählen</h2><p>Wähle eine Bild- oder Video-Datei aus.</p>');
        container.append('<input id="playlist_wizard_upload_file" name="file" type="hidden" size="36" value="" />');
        container.append(uploadButton);
        container.append('<span id="playlist_wizard_upload_selected">Keine Datei ausgewählt</span>');

    uploadButton.click(function() {
        $('.playlist-wizard-upload-error').remove();
        var uploader = wp.media({
            title: 'Datei hinzufügen',
            button: {
                text: 'Datei anwenden' // button label text
            },
            multiple: false
        }).on('select', function() { // it also has "open" and "close" events
            var attachment = uploader.state().get('selection').first().toJSON();
            $('#playlist_wizard_upload_file').val(attachment.id);
            $('#playlist_wizard_upload_selected').html('<a href="'+attachment.url+'" target="_blank">' + attachment.url + '</a>');
        }).open();
    });

    return container;
}