(function ($) { //an IIFE so safely alias jQuery to $
    $.MallCockpitPreloader = function (player) {
        // Set player
        this.player = player;

        // Pre loaded content
        this.preloadedContent = [];

        // Pre loaded vast xml
        this.preloadedVastXml = null;
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
                    console.log('%c Pre load next ad (image)', 'color: gray;');
                    let image = new Image();
                        image.src = item.file;

                    this.preloadedContent[item.file] = image;
                }
            }
        },

        // Pre load video
        preloadVideo: function (url) {
            if (typeof this.preloadedContent[url] != 'undefined') {
                return;
            }
            console.log('%c Pre load next ad (video)', 'color: gray;');

            let that = this;
            let request = new XMLHttpRequest();
            request.open('GET', url, true);
            request.responseType = 'blob';

            request.onload = function () {
                if (this.status === 200) {
                    let videoBlob = this.response;
                    let videoUrl = URL.createObjectURL(videoBlob);
                    that.preloadedContent[url] = videoUrl;
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

            console.log('%c Pre load next ad (VAST XML)', 'color: gray;');

            let xmlHttpReq;
            let xmlDoc;
            if (window.XMLHttpRequest) {
                xmlHttpReq = new XMLHttpRequest();
            } else {
                xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlHttpReq.open("GET", '/?player=' + this.player.deviceId + '&vast=' + encodeURIComponent(url), false);
            xmlHttpReq.send();
            xmlDoc = xmlHttpReq.responseXML;

            if (typeof xmlDoc !== "object" || xmlDoc == null) {
                this.preloadedVastXml = null;
            } else {
                this.preloadedVastXml = xmlDoc;
            }

            delete xmlHttpReq;
        }
    }
}(jQuery));
