function bytes_to_size(bytes) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
}

(function ($) { //an IIFE so safely alias jQuery to $
    $.MallCockpitPlayer = function (deviceId, format, pluginUrl, debug, memory) {
        let that = this;

        // Set device id
        this.deviceId = deviceId;

        // Debug modus
        this.debug = debug;

        // Memory size
        this.memory = memory;

        // Set device format
        this.deviceFormat = format;

        // Set plugin url
        this.pluginUrl = pluginUrl;

        // Set connection status
        this.reloadAfterConnected = false;

        // Init preloader
        this.preloader = new $.MallCockpitPreloader(this);

        // Init playlist
        this.playlist = new $.MallCockpitPlaylist(this);

        // Init vast
        this.vast = new $.MallCockpitVast(this);

        $.get('/?player=' + this.deviceId + '&setVersion=1.5.0');
        $.get('/?player=' + this.deviceId + '&ping=1');

        // Start player
        this.playlist.load(function () {
            that.start();
        });

        if (this.deviceId == 1679) {
            setInterval(function() {
                var d = new Date();
                var date = d.getDate()+'-'+d.getMonth()+'-'+d.getFullYear();
                if (d.getDay() == 0) {
                    $('#blackscreen').show();
                } else {
                    if (d.getHours() < 8) {
                        $('#blackscreen').show();
                    } else if (d.getHours() >= 22) {
                        $('#blackscreen').show();
                    } else if (d.getHours() >= 21 && d.getMinutes() >= 30 && date == '27-10-2020') {
                        $('#blackscreen').show();
                    } else {
                        $('#blackscreen').hide();
                    }
                }
            }, 5000);
        }

        // Debug js heapsize
        /*
        if (that.deviceId === 2015 || that.deviceId === 2606) {
            $('body').append('<div id="memory-info" style="z-index: 1000000; position: fixed; top: 0; left: 0; width: 300px; height: 50px; background: purple; color: white; font-size: 14px;"></div>');
        }*/

        /*var restartNumber = Math.floor(Math.random() * (7200 - 5400 + 1) + 5400);
        var restartCounter = 0;*/
        setInterval(function() {

            if (that.reloadAfterConnected === true && navigator.onLine === true) {
                parent.location.reload();
            } else if (navigator.onLine === false) {
                that.reloadAfterConnected = true;
            }
            //console.log("test");

            /*if (restartCounter >= restartNumber) {
                parent.location.reload();
            } else {
                restartCounter = restartCounter + 30;
            }*/

            /*
            // Debug js heapsize
            if (that.deviceId === 2015 || that.deviceId === 2606) {
                $('#memory-info').html(
                    (bytes_to_size(window.performance.memory.totalJSHeapSize)) + ' TotalJSHeapSize, ' +
                    (bytes_to_size(window.performance.memory.usedJSHeapSize)) + ' UsedJSHeapSize, ' +
                    (bytes_to_size(window.performance.memory.jsHeapSizeLimit)) + ' JsHeapSizeLimit'
                );
            }*/

            // Check expired caches
            $.each(that.preloader.preloadedContentExpireTime, function(i, o) {
                if (typeof o !== 'undefined' && Date.now() > o.time) {
                    delete that.preloader.preloadedContent[o.file];
                    delete that.preloader.preloadedContentExpireTime[i];

                    that.preloader.preloadedContent = that.preloader.preloadedContent.filter(function (el) {
                        return el != null;
                    });

                    that.preloader.preloadedContentExpireTime = that.preloader.preloadedContentExpireTime.filter(function (el) {
                        return el != null;
                    });
                }
            });

        }, 30000);
    }

    $.MallCockpitPlayer.prototype = {

        start: function () {
            $.get('/?player=' + this.deviceId + '&ping=1');

            let that = this;

            // Get next item
            this.playlist.getItem(function(item) {
                var itemType = item.type;
                var itemDesc = '';
                try {
                    // Play video
                    if (item.type == 'video') {
                        that.playVideo(item.file);
                        itemDesc = item.file;
                        if (item.advertiser_id) {
                            that.reportAid(item.advertiser_id);
                        }
                        that.reportMedia(item.file);
                        // Display image
                    } else if (item.type == 'image') {
                        that.displayImage(item.file, item.duration);
                        itemDesc = item.file;
                        if (item.advertiser_id) {
                            that.reportAid(item.advertiser_id);
                        }
                        that.reportMedia(item.file);
                        // Play vast file
                    } else if (item.type == 'vast') {
                        that.playVast(item);
                    }
                } catch (err) {
                    $.get('/?player2=' + that.deviceId + '&type=' + itemType + '&desc=' + itemDesc + '&errorName=' + err.name +
                        '&errorMessage=' + err.message + '&errorStack=' + encodeURI(err.stack));
                    that.start();
                }
            });
        },

        // Report aid
        reportAid: function(advertiser_id) {
            if (!this.memory && !this.debug) {
                $.get('/?player=' + this.deviceId + '&report=' + advertiser_id, function( data ) {
                    //console.log('%c AID reported', 'color: blue;');
                });
            }
        },

        // Report Vast
        reportVast: function() {
            if (!this.memory && !this.debug) {
                $.get('/?player=' + this.deviceId + '&report=vast', function( data ) {
                    //console.log('%c Vast reported', 'color: blue;');
                });
            }
        },

        // Report Vast
        reportMedia: function(media) {
            if (!this.memory && !this.debug) {
                $.get('/?player=' + this.deviceId + '&report=' + media + '&type=media', function( data ) {
                    //console.log('%c Media file reported', 'color: blue;');
                });
            }
        },

        // Get plugin url
        pluginUrl: function () {
            return this.pluginUrl();
        },

        // Play vast video
        playVast: function (item) {
            let that = this;

            //console.log('Start VAST');

            if (this.preloader.preloadedVastXml == null) {
                this.preloader.preloadVast(item.url);
            }

            if (that.debug) {
                setTimeout(function() {
                    that.start();
                }, 1000);
                return;
            }

            if (this.preloader.preloadedVastXml != null && navigator.onLine === true) {
                //console.log('%c VAST XML founded', 'color: green;');
                // Remove current items
                $('video').empty().unbind().remove();
                $('img').empty().unbind().remove();

                // Append element
                $('body').append(
                    $('<video class="new-ad" muted="muted" autoplay id="mall-cockpit-video-vast" width="100%" height="100%"></video>')
                        .append('<source src="'+item.fallback.url+'"></source>')
                );

                html5vast('mall-cockpit-video-vast', this.preloader.preloadedVastXml,{
                    ad_caption: 'Advertisement',
                    format: that.deviceFormat
                }, function() {
                    //console.log('%c VAST XML not valid, play fallback video', 'color: red;');

                    // Remove current items
                    $('video').empty().unbind().remove();
                    $('img').empty().unbind().remove();

                    that.playVideo(item.fallback.url, true);
                }, function() {
                    //console.log('%c VAST ended', 'color: green;');
                    that.start();
                    that.reportVast();
                });

            } else {
                //console.log('%c VAST XML not exists, play fallback', 'color: red;');
                that.playVideo(item.fallback.url);
            }
        },

        // Display image
        displayImage: function(url, duration) {
            let that = this;

            //console.log('Image ad placed');

            $('video').empty().unbind().remove();
            $('img').empty().unbind().remove();

            // Append element
            $('body').append(
                $('<img class="new-ad" id="mall-cockpit-image" width="100%" height="100%"></img>')
            );

            let preloadedContent = this.preloader.preloadedContent;

            if (that.debug) {
                setTimeout(function() {
                    that.start();
                }, 1000);
                return;
            }

            if (typeof preloadedContent[url] != 'undefined') {
                //console.log('%c Load image from cache', 'color: green;');
                $('img').attr('src', preloadedContent[url].src);
            } else {
                $('img').attr('src', url);
            }


            setTimeout(function() {
                that.start();
                //console.log('%c Image ad ended', 'color: green;');
            }, (duration-1) * 1000);
        },

        // Play video
        playVideo: function(file, removeObjectUrl = false) {
            let that = this;

            //console.log('Video ad placed');

            $('video').empty().unbind().remove();
            $('img').empty().unbind().remove();

            let preloadedContent = this.preloader.preloadedContent;
            if (typeof preloadedContent[file] != 'undefined') {
                //console.log('%c Load video from cache', 'color: green;');
                file = preloadedContent[file];
            }

            if (that.debug) {
                setTimeout(function() {
                    that.start();
                }, 1000);
                return;
            }

            // Append element
            $('body').append(
                $('<video autoplay muted="muted" class="new-ad" id="mall-cockpit-video" width="100%" height="100%"></video>')
                    .append('<source src="'+file+'"></source>')
            );

            // Get player
            let player = document.getElementById('mall-cockpit-video');

            // Change source to PreRoll
            player.load();

            // new
            player.onended = function() {
                //console.log('%c Video ended', 'color: green;');
                try {
                    player.pause();
                    if (removeObjectUrl) {
                        window.URL.revokeObjectURL(player.src);
                    }
                } catch (e) { }
                that.start();
            }

            fetch(file)
                .then(response => response.blob())
                .then(blob => {
                    if ('srcObject' in blob) {
                        player.srcObject = blob;
                    } else {
                        player.src = window.URL.createObjectURL(blob);
                    }

                    player.removeAttribute("controls");

                    return player.play();
                })
                .then(_ => {

                })
                .catch(e => {
                    console.log(e);
                    //console.log('%c Video skipped', 'color: red;');
                    that.start();
                })


            /*
            let metaDataLoaded = function() {
                console.log('Video metadata loaded');

                player.removeAttribute("controls");
                player.removeEventListener('loadedmetadata', metaDataLoaded);

                try {
                    player.play();
                } catch(e) {
                    console.log('%c Video skipped', 'color: red;');
                    player.removeEventListener('ended', videoEnded);
                    that.start();
                }
            }

            let videoEnded = function() {
                console.log('%c Video ended', 'color: green;');

                player.removeEventListener('ended', videoEnded);
                that.start();
            }

            // Play after meta data is loaded
            player.addEventListener('loadedmetadata', metaDataLoaded);

            // After video is finished
            player.addEventListener('ended', videoEnded);*/
        }
    }
}(jQuery));
