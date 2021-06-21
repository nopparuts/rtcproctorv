<!-- A version of RTC Proctor for Monitor/Instructor, to be included in Moodle plugin-->
<?php
    require_once('../../../config.php');
    require_login();
    define("WEBRTC_SERVER", get_config("onedirectconf", "rtc_signaling_server"));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset=utf-8>
    <title>WebRTC Video Proctoring : Instructor Page</title>
    <meta name=viewport content="width=device-width, initial-scale=1, , minimum-scale=1.0">
    <link rel="icon" href="../pix/icon.ico">
    <link rel="stylesheet" type="text/css" href="css/jayss.css">
    <link rel="stylesheet" type="text/css" href="css/rtcproctor-m.css">
    <style>
        h1 {
            top: 0;
            background-color:dimgray;
            text-align:center;
            color:whitesmoke;
            margin:0px;
            padding:5px;
            font-size:15px;
            cursor: pointer;
        }
        h1:hover {
            background-color:darkorange;
            color: white;
        }
    </style>
</head>
    
<body onload="document.getElementById('thumb').innerHTML ='';">
    <h1 id="main-header" onclick="location.reload()">RTCProctor - Monitor Page</h1>

    <section id="add_section" class="padding-l-vtc">
        <anchor id="add"></anchor>
        <div class="cont-pd t-right">
            <textarea id="theList" rows="20">
<?php
    foreach( $_POST as $key => $value ) {
        echo $key . "\t" . $value . '&#13;&#10;';
    }
?>
</textarea>
            <sp class=""></sp>
            <button class="btn" id="pastebtn">Clear</button>
            <button class="btn" onclick="letPlay()">Next</button>
        </div>
    </section>

    <section id="display_section" class="padding-xl-vtc">
        <div class="cont-pd t-center" id="display">
            <theboxes class="middle center spacing -clip" boxing="4" id="thumb"></theboxes>
            <sp class="l"></sp>
            <h5>Page<span id="page"></span></h5>
        </div>
    </section>

    <!-- this part for RTCMulticonnection -->
    <script src="js/RTCMultiConnection.min.js"></script>
    <script src="js/adapter.js"></script>
    <script src="js/socket.io.js"></script>
    <link rel="stylesheet" href="css/getHTMLMediaElement.css">
    <script src="js/getHTMLMediaElement.js"></script>
    <script>
        var numblk = 16; //12
        // ......................................................
        // ..................RTCMultiConnection Code.............
        // ......................................................
        var connection = new RTCMultiConnection()
        // by default, socket.io server is assumed to be deployed on your own URL
        connection.socketURL = '<?php echo WEBRTC_SERVER ?>'
        connection.socketMessageEvent = 'rtc-video-proctoring'
        connection.session = {
            audio: true,
            video: true,
            oneway: true
        }
        connection.sdpConstraints.mandatory = {
            OfferToReceiveAudio: false,
            OfferToReceiveVideo: false
        }

        <?php echo get_config("onedirectconf", "additional_config") ?>

        connection.mediaConstraints = {
            video: videoConstraints,
            audio: true
        }
        connection.onstream = function(event) {
            var existing = document.getElementById(event.streamid)
            if(existing && existing.parentNode) {
                existing.parentNode.removeChild(existing)
            }
            event.mediaElement.removeAttribute('src')
            event.mediaElement.removeAttribute('srcObject')
            event.mediaElement.muted = true
            event.mediaElement.volume = 0
            var video = document.createElement('video')
            try {
                video.setAttributeNode(document.createAttribute('autoplay'))
                video.setAttributeNode(document.createAttribute('playsinline'))
            } catch (e) {
                video.setAttribute('autoplay', true)
                video.setAttribute('playsinline', true)
            }
            if(event.type === 'local' | event.type == 'remote') {
                video.volume = 0
                try {
                    video.setAttributeNode(document.createAttribute('muted'))
                } catch (e) {
                    video.setAttribute('muted', true)
                }
            }
            video.srcObject = event.stream
            txt  = event.userid
            var width = parseInt(connection.videosContainer.clientWidth / 3) - 20
            var mediaElement = getHTMLMediaElement(video, {
                buttons:['mute-audio', 'full-screen'],
                width: width,
                showOnMouseEnter: true
            })
            connection.videosContainer.appendChild(mediaElement)
            setTimeout(function() {
                mediaElement.media.play()
            }, 5000)
            mediaElement.id = event.streamid
            cit =  document.getElementById(connection.sessionid).getAttribute('data-id')
            ci = parseInt(cit)
            console.log("ci")
            console.log(ci + " " + maxp)
            if (ci < maxp) {
                setTimeout(function() {playBlk(ci+1, maxp)}, 2000)
            }
        }
        connection.onstreamended = function(event) {
            var mediaElement = document.getElementById(event.streamid)
            if (mediaElement) {
                mediaElement.parentNode.removeChild(mediaElement)
                if(event.userid === connection.sessionid && !connection.isInitiator) {
                    alert('Broadcast is ended. We will reload this page to clear the cache.')
                    location.reload()
                }
            }
        }

        connection.onMediaError = function(e) {
            if (e.message === 'Concurrent mic process limit.') {
                if (DetectRTC.audioInputDevices.length <= 1) {
                    alert('Please select external microphone. Check github issue number 483.')
                    return
                }
                var secondaryMic = DetectRTC.audioInputDevices[1].deviceId;
                connection.mediaConstraints.audio = {
                    deviceId: secondaryMic
                }
                connection.join(connection.sessionid)
            }
        }
    </script>
    <script>
        function pastefn() {
          var pasteText = document.getElementById("theList")
          pasteText.focus()
          pasteText.textContent = ""
          pasteText.value = ""
        }
        document.getElementById("pastebtn").addEventListener("click", pastefn)
        function openPage(url) {
          url2 = "https://erpd.it.kmitl.ac.th:9001/demos/rtcps2b.html#"  + url
          console.log(url2)
           window.open(url2)
        }
        rows = []
        data = []
        function letPlay(){
            rows = []
            data = []
            rows = theList.value.split("\n")
            rows.pop()
            for(let i of rows){
                data.push(i.split("	"))
            }
            console.log(data)
            let pg = ``
            for(let p=0;p<Math.ceil(data.length/numblk);p++){
                pg += `- <a href="#page_${p+1}" id="page_${p+1}" onclick="renderThumb(${p*numblk})" class="page"> ${p+1} </a>`;
            }
            page.innerHTML = pg
            add_section.style.display = "none"
            display_section.style.display = "block"
            renderThumb(0)
        }
        function renderThumb(start){
            let txt = ''
            for(let i = start; i < start+numblk;i++){
                if (data[i] != undefined) {
                    txt += `
                    <box col=""><inner class="t-center bg-white shadow "><div class="youtube-player" data-id="${i}" id="${data[i][1]}"></div>
                    <h5 class="padding" id = "padding-id"">${data[i][0]}</h5>
                    </inner></box>`
                }
            }
            thumb.innerHTML = txt
            //playAll(start); //working now;
            maxp = start + numblk - 1
            if (maxp > data.length - 1) {
                maxp = data.length - 1
            }
            playBlk(start, maxp)
        }

        function playBlk(p, maxp){
            if (p <= maxp) {
                cid = p
                connection.videosContainer = document.getElementById(data[p][1]);
                connection.sdpConstraints.mandatory = {
                    OfferToReceiveAudio: true,
                    OfferToReceiveVideo: true
                }
                connection.join(data[p][1], function(isRoomExist, roomid, error) {
                    console.log("roomid")
                    console.log(roomid)
                    console.log("isexist")
                    console.log(isRoomExist)
                    if(isRoomExist != true) {
                        document.getElementById(data[p][1]).style.backgroundColor = "rgb(0,0,0)"
                        playBlk(p+1, maxp)
                    }
                })
            }
        }
        window.onload=function(){
            if(document.referrer.search("/mod/onedirectconf/view.php") !== -1) {
                letPlay()
            }
        }
    </script>
</body>
</html>
