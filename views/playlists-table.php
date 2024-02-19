<?php

$playlists = $args['playlists'] ? $args['playlists'] : [];

?>

<style>
    .postbox-header {
        padding: 0 15px;
    }

    .playlist-kampagnen-table tr {
        border: 1px solid #c1c1c1;
    }

    .playlist-kampagnen-table th {
        padding: 20px 15px;
    }

    .playlist-kampagnen-table td {

    }

    .media-wrapper {
        position: relative;
        display: inline-block;
    }

    .media-actions {
        display: none;
        position: absolute;
        right: 6px;
        top: 6px;
        gap: 2px;
    }

    .media-wrapper:hover .media-actions {
        display: flex;
    }

    .media-action:before {
        font-family: dashicons;
        display: inline-block;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #0a0a0a;
        color: #fff;
        text-align: center;
        line-height: 26px;
        font-size: 20px;
    }

    .media-action.media-action-edit:before {
        content: "\f464";
    }

    .media-action.media-action-remove:before {
        content: "\f335";
    }
    .playlist-table-submit-btn{
        cursor: pointer;
    }
    .media-image {
        max-width: 150px!important;
        max-height: 150px;
        height: 150px;
        width: 150px;
        object-fit: cover;
    }
    .toast {
        position: fixed; 
        top: 25px; 
        right: 25px; 
        max-width: 300px;
        width: 100%;
        background: #fff; 
        padding: 0.5rem; 
        border-radius: 4px; 
        box-shadow: -1px 1px 10px
            rgba(0, 0, 0, 0.3); 
        z-index: 999999; 
        animation: slideInRight 0.3s 
                ease-in-out forwards, 
            fadeOut 0.5s ease-in-out 
                forwards 3s; 
        transform: translateX(110%); 
    } 
    
    .toast.closing { 
        animation: slideOutRight 0.5s 
            ease-in-out forwards; 
    } 
    
    .toast-progress { 
        position: absolute; 
        display: block; 
        bottom: 0; 
        left: 0; 
        height: 4px; 
        width: 100%; 
        background: #b7b7b7; 
        animation: toastProgress 3s 
            ease-in-out forwards; 
    }
    .toast-message { 
        flex: 1; 
        font-size: 1.1rem; 
        color: #000000; 
        padding: 0.5rem; 
    } 
    
    .toast.toast-success { 
        background: #95eab8; 
    } 
    
    .toast.toast-success .toast-progress { 
        background-color: #2ecc71; 
    }
    
    @keyframes slideInRight { 
        0% { 
            transform: translateX(110%); 
        } 
    
        75% { 
            transform: translateX(-10%); 
        } 
    
        100% { 
            transform: translateX(0%); 
        } 
    } 
    
    @keyframes slideOutRight { 
        0% { 
            transform: translateX(0%); 
        } 
    
        25% { 
            transform: translateX(-10%); 
        } 
    
        100% { 
            transform: translateX(110%); 
        } 
    } 
    
    @keyframes fadeOut { 
        0% { 
            opacity: 1; 
        } 
    
        100% { 
            opacity: 0; 
        } 
    } 
    
    @keyframes toastProgress { 
        0% { 
            width: 100%; 
        } 
    
        100% { 
            width: 0%; 
        } 
    }
    .playlist-table-submit-btn.disabled {
        pointer-events: none;
    }
</style>

<h2>Playlists</h2>

<div id="side-sortables" class="meta-box-sortables ui-sortable">
    <?php foreach ($playlists as $playlist) : ?>
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle"><?= $playlist['playlist_name'] ?></h2>
                <div class="handle-actions hide-if-no-js">
                    <button
                        type="button"
                        class="handlediv toggle-button"
                        aria-expanded="true">
                            <span class="screen-reader-text">Toggle panel: Publish</span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>

            <div class="inside">
                <div class="submitbox">
                    <table data-post_id="<?= $playlist['playlist_id'] ?>" data-row_id="<?= $playlist['unique_id'] ?>" data-repeater_id="<?= $playlist['field_key'] ?>" class="form-table playlist-kampagnen-table postbox">
                            <tr>
                                <th>Playlist Name:</th>
                                <td>
                                    <a
                                        href="<?= $playlist['playlist_edit_link'] ?>"
                                        title="<?= $playlist['playlist_name'] ?>"
                                        target="_blank"
                                    ><?= $playlist['playlist_name'] ?></a>
                                </td>
                            </tr>

                            <tr>
                                <th>Kunde</th>
                                <td>
                                    <?php foreach ($playlist['clients'] as $client) : ?>
                                        <p>
                                           <input type="text" name="clientName" value="<?= $client['name'] ?>" />
                                           <?php /* <a
                                        href="<?= $playlist['playlist_edit_link'] ?>"
                                        title="<?= $playlist['playlist_name'] ?>"
                                        target="_blank"
                                    ><?= $client['name'] ?></a> */ ?>
                                        </p>
                                    <?php endforeach; ?>
                                </td>
                            </tr>

                            <tr>
                                <th>Media</th>
                                <td>
                                    <div class="media-wrapper">
                                        <?php if ($playlist['file']['type'] === 'image') : ?>
                                           <?php /* <a href="<?= $playlist['file']['url'] ?>" target="_blank" title="<?= $playlist['file']['alt'] ?>"> */ ?>
                                                <?php $attachment_id = attachment_url_to_postid( $playlist['file']['url'] );
                                                $thumb_url = wp_get_attachment_thumb_url( $attachment_id ); ?>
                                                <img class="media-image" src="<?= $thumb_url ?>" alt="<?= $playlist['file']['alt'] ?>">
                                            <?php /* </a> */?>
                                        <?php elseif ($playlist['file']['type'] === 'video') : ?>
                                            <video class="media-image" controls src="<?= $playlist['file']['url'] ?>" width="320" height="240" >
                                                <source
                                                        src="<?= $playlist['file']['url'] ?>"
                                                        type="<?= $playlist['file']['mime_type'] ?>"
                                                />
                                            </video>
                                        <?php endif; ?>

                                        <div class="media-actions">
                                            <a href="#" class="media-action media-action-edit"></a>
                                            <a href="#" class="media-action media-action-remove"></a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>Order-Nr.</th>
                                <td><input type="text" id="playlist_order_number" name="playlist_order_number" required minlength="1" maxlength="3" size="10" value="<?= $playlist['order_nr'] ?>"/></td>
                            </tr>

                            <tr>
                                <th>Zeitraum</th>
                                <td>
                                    <table>
                                            <?php foreach ($playlist['periods'] as $period) : ?>
                                                <tr>
                                                <td>Start Date: </td><td>
                                                    <input type="date" id="name" name="start_date" required size="10" value="<?= $period['start_date'] ?>"/></td>
                                                <td>End Date: </td><td>
                                                    <input type="date" id="name" name="end_date" required size="10" value="<?= $period['end_date'] ?>"/></td>

                                                </tr>
                                            <?php endforeach; ?>

                                    </table>
                                </td>
                            </tr>
                            <tr><th></th><td><button class="playlist-table-submit-btn">Update Playlist</button></td></tr>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    jQuery('#side-sortables').sortable();

    jQuery('.toggle-button').click(function (e) {
        e.preventDefault();

        jQuery(this).parents('.postbox').toggleClass('closed');
    })

    jQuery('document').ready(function($) {
        $('.media-wrapper').click(function(e) {
            e.preventDefault();

            var element_img = jQuery(this).parents('.playlist-kampagnen-table').find(".media-image");

            var image = wp.media({
                title: 'Upload Image',
                // mutiple: true if you want to upload multiple files at once
                multiple: false
            }).open()
                .on('select', function(e){
                    // This will return the selected image from the Media Uploader, the result is an object
                    var uploaded_image = image.state().get('selection').first();
                    // We convert uploaded_image to a JSON object to make accessing it easier
                    // Output to the console uploaded_image
                    console.log(uploaded_image);
                    console.log(uploaded_image.attributes.url);
                    console.log(element_img);
                    element_img.attr('src', uploaded_image.attributes.url);

                });
        });

        // jQuery('.playlist-table-submit-btn').click(function(e) {
        //     e.preventDefault();

        //     console.log(jQuery(this).parents('.playlist-kampagnen-table').serialize());
        // });
        jQuery(document).on('click','.playlist-table-submit-btn',function(e){
            e.preventDefault();
            jQuery(this).addClass('disabled');
            var start_date = [];
            var end_date = [];
            var client = [];

            jQuery(this).parents('.playlist-kampagnen-table').find("input[name='start_date']").each(function(i, input) {
                start_date.push(jQuery(input).val());
            });
            jQuery(this).parents('.playlist-kampagnen-table').find("input[name='end_date']").each(function(i, input) {
                end_date.push(jQuery(input).val());
            });
            jQuery(this).parents('.playlist-kampagnen-table').find("input[name='clientName']").each(function(i, input) {
                client.push(jQuery(input).val());
            });
            var table_div = jQuery(this).parents('.playlist-kampagnen-table');
            var table_div = jQuery(this).parents('.playlist-kampagnen-table');
            var post_id = jQuery(this).parents('.playlist-kampagnen-table').data('post_id');
            var row_id = jQuery(this).parents('.playlist-kampagnen-table').data('row_id');
            var repeater_id = jQuery(this).parents('.playlist-kampagnen-table').data('repeater_id');
            var playlist_order_number = jQuery(this).parents('.playlist-kampagnen-table').find('#playlist_order_number').val();
            var element_img = jQuery(this).parents('.playlist-kampagnen-table').find(".media-image").attr('src');

            jQuery.ajax({
                url: '<?php echo admin_url( "admin-ajax.php" ) ?>',
                type: "POST",
                data: ({
                    action: "update_row_repeater",
                    post_id: post_id,
                    row_id: row_id,
                    repeater_id: repeater_id,
                    playlist_order_number: playlist_order_number,
                    start_date: start_date,
                    end_date: end_date,
                    client: client,
                    url_img: element_img,
                }),
                success: function(JSON) {
                    table_div.append('<div class="toast toast-success"><div class="toast-message">'+JSON+'</div><div class="toast-progress"></div></div>');
                    let toastAlready = document.body.querySelector(".toast");

                    setTimeout(() => {
                        jQuery('.playlist-table-submit-btn').removeClass('disabled');
                        toastAlready.remove();
                    }, 4000);
                }
            })
        });
    });
</script>
