/*
 * HTML5VAST - Play VAST 3.0 Ads on HTML5 Video
 * http://html5vast.com
 * Sadan Nasir
 * version 1.3 2015-04-15
 * Creative Commons Attribution-NonCommercial 4.0 International License
 * http://creativecommons.org/licenses/by-nc/4.0/
*/
 
	function html5vast(video_player_id, vastXml, options, fallback, onended){
		var video_player = document.getElementById(video_player_id);
		
		//Default options
		var html5vast_options = {
			'media_type' : 'video/mp4',
			'media_bitrate_min' : 200,
			'media_bitrate_max' : 1200,
			'ad_caption': 'Advertisement',
			'format': 'portrait'
		};
		for(var key in options){
			html5vast_options[key] = options[key];
		}

		var obj_vast = h5vReadFile(video_player_id,vastXml,html5vast_options);

		if (obj_vast != false) {
			h5vPreRoll(video_player_id,obj_vast,html5vast_options,fallback,onended);
		} else {
			fallback();
		}
	}		
	
	//Parse VAST XML
	function h5vReadFile(video_player_id, vastXml, options){
		let xmlDoc=vastXml;
		if (typeof xmlDoc !== "object" || xmlDoc == null) {
			//console.log('%c VAST XML xml invalid', 'color: red;');
			return false;
		}

		var obj_vast ={};
		
		//Get impression tag
		var impression = xmlDoc.getElementsByTagName("Impression");
		if(impression != null){
			//obj_vast.impression_url = impression[0].childNodes[0].nodeValue;
			obj_vast.impression = impression;
			//alert(obj_vast.impression_url);
		}
		
		//Get Creative
		var creative = xmlDoc.getElementsByTagName("Creative");				
		var media_files;
		var tracking_events;
		for(var i=0;i<creative.length;i++){
			var creative_linear = creative[i].getElementsByTagName("Linear");
			if(creative_linear != null){
				for(var j=0;j<creative_linear.length;j++){
					
					//Get media files
					var creative_linear_mediafiles = creative_linear[j].getElementsByTagName("MediaFiles");
					if(creative_linear_mediafiles!=null){
						for(var k=0;k<creative_linear_mediafiles.length;k++){
							var creative_linear_mediafiles_mediafile = creative_linear_mediafiles[k].getElementsByTagName("MediaFile");
							if(creative_linear_mediafiles_mediafile!=null){
								media_files = creative_linear_mediafiles_mediafile;
							}
						}
					}
					
					//Get Tracking Events
					var creative_linear_trackingevents = creative_linear[j].getElementsByTagName("TrackingEvents");
					if(creative_linear_trackingevents!=null){
						for(var k=0;k<creative_linear_trackingevents.length;k++){
								var creative_linear_trackingevents_tracking = creative_linear_trackingevents[k].getElementsByTagName("Tracking");
								if(creative_linear_trackingevents_tracking!=null){
									tracking_events = creative_linear_trackingevents_tracking;
								}
						}
					}
					
					//Get AD Duration
					var creative_linear_duration =  creative_linear[j].getElementsByTagName("Duration")[0];
					if(creative_linear_duration!=null){
						obj_vast.duration = creative_linear_duration.childNodes[0].nodeValue;
						//alert(obj_vast.duration);
						var arrD = obj_vast.duration.split(':');
						var strSecs = (+arrD[0]) * 60 * 60 + (+arrD[1]) * 60 + (+arrD[2]);
						obj_vast.duration = strSecs;
					}
					
				}
			}
		}

		var statusMediaFileFound = false;
		for(var i=0;i<media_files.length;i++){
			if(media_files[i].getAttribute('type')==options.media_type){
				if (media_files.length > 1) {
					if (parseInt(media_files[i].getAttribute('width')) < parseInt(media_files[i].getAttribute('height')) &&
						options.format === 'portrait') {
						obj_vast.media_file=media_files[i].childNodes[0].nodeValue;
						statusMediaFileFound = true;
					} else if (parseInt(media_files[i].getAttribute('width')) > parseInt(media_files[i].getAttribute('height')) &&
						options.format === 'landscape') {
						obj_vast.media_file=media_files[i].childNodes[0].nodeValue;
						statusMediaFileFound = true;
					}
				} else {
					if (parseInt(media_files[i].getAttribute('width')) < parseInt(media_files[i].getAttribute('height')) &&
						options.format === 'portrait') {
						obj_vast.media_file=media_files[i].childNodes[0].nodeValue;
						statusMediaFileFound = true;
					} else if (parseInt(media_files[i].getAttribute('width')) > parseInt(media_files[i].getAttribute('height')) &&
						options.format === 'landscape') {
						obj_vast.media_file=media_files[i].childNodes[0].nodeValue;
						statusMediaFileFound = true;
					}
				}
			}
		}

		if (statusMediaFileFound == false) {
			//console.log('%c VAST XML media format invalid', 'color: green;');
			return false;
		}

		//Tracking events
		for(var i=0;i<tracking_events.length;i++){
				if(tracking_events[i].getAttribute('event')=="start"){
						if(obj_vast.tracking_start != null){
							obj_vast.tracking_start += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_start =tracking_events[i].childNodes[0].nodeValue;
						}						
						obj_vast.tracking_start_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="firstQuartile"){
						if(obj_vast.tracking_first_quartile != null){
							obj_vast.tracking_first_quartile += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_first_quartile =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_first_quartile_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="midpoint"){
						if(obj_vast.tracking_midpoint != null){
							obj_vast.tracking_midpoint += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_midpoint =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_midpoint_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="thirdQuartile"){
						if(obj_vast.tracking_third_quartile != null){
							obj_vast.tracking_third_quartile += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_third_quartile =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_third_quartile_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="complete"){
						if(obj_vast.tracking_complete != null){
							obj_vast.tracking_complete += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_complete =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_complete_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="mute"){
						if(obj_vast.tracking_mute != null){
							obj_vast.tracking_mute += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_mute =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_mute_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="unmute"){
						if(obj_vast.tracking_unmute != null){
							obj_vast.tracking_unmute += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_unmute =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_unmute_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="pause"){
						if(obj_vast.tracking_pause != null){
							obj_vast.tracking_pause += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_pause =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_pause_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="resume"){
						if(obj_vast.tracking_resume != null){
							obj_vast.tracking_resume += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_resume =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_resume_tracked=false;
				}
				if(tracking_events[i].getAttribute('event')=="fullscreen"){
						if(obj_vast.tracking_fullscreen != null){
							obj_vast.tracking_fullscreen += " "+tracking_events[i].childNodes[0].nodeValue;
						}else{
							obj_vast.tracking_fullscreen =tracking_events[i].childNodes[0].nodeValue;
						}
						obj_vast.tracking_fullscreen_tracked=false;
				}
		}
		
		return obj_vast;
	}
	
	//Preroll 
	function h5vPreRoll(video_player_id, obj_vast, options, fallback, onended){
		var video_player = document.getElementById(video_player_id);
		
		
		//Video play event
		var prev_src = h5vGetCurrentSrc(video_player_id);
		var video_player_play = function(event) {
			//console.log("play");

				//Change source to PreRoll
				video_player.src = obj_vast.media_file;
				video_player.load();

				// New
				video_player.onended = function() {
					try {
						video_player.pause();
						window.URL.revokeObjectURL(video_player.src);
					} catch (e) { }
					onended();
				}

				fetch(obj_vast.media_file)
					.then(response => response.blob())
					.then(blob => {
						if ('srcObject' in blob) {
							video_player.srcObject = blob;
						} else {
							video_player.src = window.URL.createObjectURL(blob);
						}

						video_player.removeAttribute("controls");

						return video_player.play();
					})
					.then(_ => {
						if (obj_vast.impression!=null){
							for(var k=0;k<obj_vast.impression.length;k++){
								//console.log(obj_vast.impression[k].childNodes[0].nodeValue, 'call impression url');
								h5vAddPixel(obj_vast.impression[k].childNodes[0].nodeValue);
							}
						}
					})
					.catch(e => {
						//console.log('%c Video skipped', 'color: red;');
						console.log(e);
						onended();
					})
				/*
                            //On content load
                            var video_player_loaded = function(event){
                                h5vAddClickthrough(video_player_id,obj_vast);
                                h5vAddCaption(video_player_id,options.ad_caption);

                                video_player.removeAttribute("controls"); //Remove Controls

                                video_player.play();

                                //Fire impression(s)
                                if(obj_vast.impression!=null){
                                    for(var k=0;k<obj_vast.impression.length;k++){
                                        console.log(obj_vast.impression[k].childNodes[0].nodeValue, 'call impression url');
                                        h5vAddPixel(obj_vast.impression[k].childNodes[0].nodeValue);
                                    }
                                }
                                video_player.removeEventListener('loadedmetadata',video_player_loaded);
                            }

                            //On PreRoll End
                            var video_player_ended = function(event){
                                h5vRemoveClickthrough(video_player_id);
                                h5vRemoveCaption(video_player_id);
                                video_player.removeEventListener('ended',video_player_ended);
                                onended();
                            }

                            video_player.addEventListener('loadedmetadata', video_player_loaded);
                            video_player.addEventListener('ended', video_player_ended);*/
            video_player.removeEventListener('play', video_player_play);

		}
		
		
		//Ping Tracking URIs
		
		var video_player_timeupdate  = function(event){

			var img_track = new Image();
			var current_time =Math.floor(video_player.currentTime);
			
			if((current_time==0)){ //Start				
				
				if(obj_vast.tracking_start_tracked ==false){
					if(obj_vast.tracking_start != null){
						var arrTrack = obj_vast.tracking_start.split(" ");
						for(var i=0;i<arrTrack.length;i++){
							var img_track = new Image();
							img_track.src=arrTrack[i];
							//console.log(arrTrack[i], 'track start');
						}
					}
					obj_vast.tracking_start_tracked=true;
				}				
			}
			if((current_time==(Math.floor(obj_vast.duration/4)))){ //First Quartile			
				if(obj_vast.tracking_first_quartile_tracked ==false){
					if(obj_vast.tracking_first_quartile != null){
						var arrTrack = obj_vast.tracking_first_quartile.split(" ");
						for(var i=0;i<arrTrack.length;i++){
							var img_track = new Image();
							img_track.src=arrTrack[i];
							//console.log(arrTrack[i], 'track first_quartile');
						}
					}
					obj_vast.tracking_first_quartile_tracked=true;
				}
			}
			if((current_time==(Math.floor(obj_vast.duration/2)))){ //Mid Point
				if(obj_vast.tracking_midpoint_tracked ==false){
					if(obj_vast.tracking_midpoint != null){
						var arrTrack = obj_vast.tracking_midpoint.split(" ");
						for(var i=0;i<arrTrack.length;i++){
							var img_track = new Image();
							img_track.src=arrTrack[i];
							//console.log(arrTrack[i], 'track midpoint');
						}
					}
					obj_vast.tracking_midpoint_tracked=true;
				}
			}
			if((current_time==((Math.floor(obj_vast.duration/2)) + (Math.floor(obj_vast.duration/4))))){ //Third Quartile
				if(obj_vast.tracking_third_quartile_tracked ==false){
					if(obj_vast.tracking_third_quartile != null){
						var arrTrack = obj_vast.tracking_third_quartile.split(" ");
						for(var i=0;i<arrTrack.length;i++){
							var img_track = new Image();
							img_track.src=arrTrack[i];
							//console.log(arrTrack[i], 'track third_quartile');
						}
					}
					obj_vast.tracking_third_quartile_tracked=true;
				}
			}
			if((current_time>=(obj_vast.duration-1))){ //End
				if(obj_vast.tracking_complete_tracked ==false){
					if(obj_vast.tracking_complete != null){
						var arrTrack = obj_vast.tracking_complete.split(" ");
						for(var i=0;i<arrTrack.length;i++){
							var img_track = new Image();
							img_track.src=arrTrack[i];
							//console.log(arrTrack[i], 'track complete');
						}
					}
					obj_vast.tracking_complete_tracked=true;
				}
				video_player.removeEventListener('timeupdate', video_player_timeupdate);
			}
			
				
		}

		video_player.removeEventListener('play', video_player_play);
		video_player.removeEventListener('timeupdate', video_player_timeupdate);

		video_player.addEventListener('play', video_player_play);
		video_player.addEventListener('timeupdate', video_player_timeupdate);
	}
	

	
	//Add Caption
	function h5vAddCaption(video_player_id, caption_text){
		return null;
	}
	
	//Remove Caption
	function h5vRemoveCaption(video_player_id){
		return null;
	}
	
	//Add Clickthrough
	function h5vAddClickthrough(video_player_id,obj_vast){
		return null;
	}
	
	//Remove Clickthrough
	function h5vRemoveClickthrough(video_player_id){
		return null;
	}
	
	//Get current video source src
	function h5vGetCurrentSrc(video_player_id){			
		return document.getElementById(video_player_id).getElementsByTagName("source")[0].getAttribute("src");
	}
	
	//Add pixel for firing impressions, tracking etc
	function h5vAddPixel(pixel_url){
		var image = new Image(1,1); 
		image.src = pixel_url;
	}