(function ($) { //an IIFE so safely alias jQuery to $
    $.MallCockpitPreloader = function (player) {
        // Set player
        this.player = player;

        // Pre loaded content
        this.preloadedContent = [];

        // Pre loaded content expired time
        this.preloadedContentExpireTime = [];

        // Pre loaded vast xml
        this.preloadedVastXml = null;

        // XML Request
        this.xmlRequest = null;

        // XML Request
        this.xmlVastRequest = null;
    }

    $.MallCockpitPreloader.prototype = {
        // Pre load item
        preload: function (item) {
            if (item.type == 'video') {
                this.preloadVideo(item.file);
            } else if (item.type == 'vast') {
                this.preloadVast(item.url);
                this.preloadVideo(item.fallback.url);
            } else if (item.type == 'image') {
                if (typeof this.preloadedContent[item.file] == 'undefined') {
                    //console.log('%c Pre load next ad (image)', 'color: gray;');
                    var image = new Image();
                        image.src = item.file;

                    var t = (Date.now() + (60*60*1000));
                    this.preloadedContent[item.file] = image;
                    this.preloadedContentExpireTime.push({ file: item.file, time: t});

                    console.log('%c Pre load next ad (image) expires at ' + t, 'color: gray;');
                } else {
                    $.each(this.preloadedContentExpireTime, function(i, o) {
                        if (typeof o.file !== 'undefined' && o.file === item.file) {
                            var t = (Date.now() + (60*60*1000));
                            o.time = t
                            console.log('%c ad (image) expire time extend to ' + t, 'color: gray;');
                        }
                    });
                }
            }
        },

        // Pre load video
        preloadVideo: function (url) {
            if (typeof this.preloadedContent[url] != 'undefined') {
                $.each(this.preloadedContentExpireTime, function(i, o) {
                    if (typeof o !== 'undefined' && o.file === url) {
                        var t = (Date.now() + (60*60*1000));
                        o.time = t
                        console.log('%c ad (video) expire time extend to ' + t, 'color: gray;');
                    }
                });
                return;
            }
            console.log('%c Pre load next ad (video)', 'color: gray;');

            var that = this;
            if (that.xmlRequest === null) {
                that.xmlRequest = new XMLHttpRequest();
            }

            var request = that.xmlRequest;
            request.open('GET', url, true);
            request.responseType = 'blob';

            request.onload = function () {
                if (this.status === 200) {
                    var videoBlob = this.response;
                    var videoUrl = URL.createObjectURL(videoBlob);
                    var t = (Date.now() + (60*60*1000));

                    that.preloadedContent[url] = videoUrl;
                    that.preloadedContentExpireTime.push({ file: url, time: t});

                    console.log('%c Pre load next ad (video) expires at ' + t, 'color: gray;');
                }
            }

            request.onerror = function () {
                console.log('%c Pre load failed (video)', 'color: red;');
            }

            request.send();
        },

        // Pre load vast xml
        preloadVast: function (url) {

            if (navigator.onLine === false) {
                this.preloadedVastXml = null;

                return false;
            }

            var that = this;
            //console.log('%c Pre load next ad (VAST XML)', 'color: gray;');

            var xmlDoc = null;

            if (that.xmlVastRequest === null) {
                that.xmlVastRequest = new XMLHttpRequest();
            }
            var xmlHttpReq = that.xmlVastRequest;
            xmlHttpReq.open("GET", '/?player2=' + this.player.deviceId + '&vast=' + encodeURIComponent(url), false);
            xmlHttpReq.send(null);
            xmlDoc = xmlHttpReq.responseXML;

            if (typeof xmlDoc !== "object" || xmlDoc == null) {
                this.preloadedVastXml = null;
            } else {
                this.preloadedVastXml = xmlDoc;
            }
        }
    }
}(jQuery));
