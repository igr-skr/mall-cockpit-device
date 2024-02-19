(function ($) { //an IIFE so safely alias jQuery to $
    $.MallCockpitPlaylist = function (player) {
        // Set player
        this.player = player;

        // Current playlist
        this.currentPlaylist = null;

        // Playlist position
        this.currentPosition = false;
    }

    $.MallCockpitPlaylist.prototype = {
        // Load playlist
        load: function (callback, nextHour) {
            let date = new Date();
            let hour = date.getHours();
            let minute = date.getMinutes();

            // Preload next playlist
            if (nextHour === true && minute > 59) {
                hour = hour + 1;
                minute = 0;
            }
            minute = 0;
            let url = '/wp-json/mall-cockpit-devices/playlist?device=' + this.player.deviceId + '&h=' + hour + '&m=' + minute;
            let that = this;

            //console.log('Load playlist');

            $.getJSON(url, function (data) {
                //console.log('%c Playlist loaded', 'color: green;');
                that.currentPlaylist = data;
                callback();
            });
        },

        sleep: function (milliseconds) {
            return new Promise(resolve => setTimeout(resolve, milliseconds));
        },

        // Get next item by timestamp
        getItemByTimestamp: async function () {
            let that = this;
            let timestamp = new Date();
                timestamp.setMilliseconds(0);
                timestamp = timestamp.getTime();

            let currentTimestamp = new Date();
                currentTimestamp.setMinutes(0);
                currentTimestamp.setSeconds(0);
                currentTimestamp.setMilliseconds(0);
                currentTimestamp = currentTimestamp.getTime();

            let currentPosition = false;
            let prevTimestamp = 0;
            let duration = 0;
            let sleep = 0;
            $.each(this.currentPlaylist, function (index, item) {
                if (index > 0) {
                    duration = (item.type === 'vast' ? 10 : item.duration);
                    currentTimestamp += (duration * 1000);
                }

                if (currentTimestamp >= timestamp) {
                    sleep = currentTimestamp - timestamp;
                    currentPosition = index;
                    return false;
                } else if (currentTimestamp === timestamp) {
                    currentPosition = index;
                    return false;
                }

                prevTimestamp = currentTimestamp;
            });

            if (sleep > 0) {
                await that.sleep(sleep);
            }

            this.currentPosition = currentPosition;

            let item = this.currentPlaylist[this.currentPosition];

            this.incrementCurrentPosition();

            return item;
        },

        // Load new playlist and preload next item
        incrementCurrentPosition: function () {
            this.currentPosition++;

            // Load new playlist
            if (this.currentPosition >= this.currentPlaylist.length) {
                this.currentPosition = 0;
                this.load(function () {
                    //console.log('%c Playlist updated', 'color: green;');
                }, true);
            }

            // Preloader
            let nextItem = this.currentPlaylist[this.currentPosition];
            this.player.preloader.preload(nextItem);
        },

        // Get next item for player
        getItem: function (callback) {
            let that = this;
            if (this.currentPosition === false) {
                this.getItemByTimestamp(10).then(v => {
                    //console.log(that.currentPosition, 'currentPosition');
                    callback(v);
                });
            } else {
                let item = this.currentPlaylist[this.currentPosition];
                this.incrementCurrentPosition();
                //console.log(that.currentPosition, 'currentPosition');
                callback(item);
            }
        },
    }
}(jQuery));
