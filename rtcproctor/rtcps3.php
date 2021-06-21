<!-- This version of RTCProctor-Student Page is intended to be included in Moodle plugin -->
<?php
    require_once('../../../config.php');
    require_login();
    define("WEBRTC_SERVER", get_config("onedirectconf", "rtc_signaling_server"));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <title>WebRTC Video Proctoring : Student Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
  <script src="js/popper.min.js"></script>
    <link rel="icon" href="../pix/icon.png">
  <link rel="stylesheet" href="css/rtcproctor.css">
</head>

<body>
    <h1 id="main-header">RTCProctor - Student Page</h1>

    <div class="main-container">
        <div class="input-box">
            <select id="selectcam"></select>
            <input type="text" id="stream-code" placeholder="Enter your stream code">
        </div>
        <div class="button-box">
            <button type="submit" class="btn" id="open-room">Go Live</button>
<!--            <button type="submit" class="btn" id="join-room">Join</button>-->
            <button type="submit" class="btn" id="reset">Stop</button>
        </div>
        <div id="videos-container"></div>
    </div>

    <script src="js/RTCMultiConnection.min.js"></script>
    <script src="js/adapter.js"></script>
    <script src="js/socket.io.js"></script>
    <script src="js/DetectRTC.min.js"></script>
    <link rel="stylesheet" href="css/getHTMLMediaElement.css">
    <script src="js/getHTMLMediaElement.js"></script>

    <script>
        // ......................................................
        // .......................UI Code........................
        // ......................................................
        document.getElementById('open-room').onclick = function() {
            var roomid = document.getElementById('stream-code').value
            if (roomid == "") {
                alert("Enter your stream code first")
            } else {
                disableInputButtons()
                setCamera()
                connection.open(document.getElementById('stream-code').value, function(isRoomOpened, roomid, error) {
                    if(error) {
                         document.getElementById('main-header').style.backgroundColor = "red"
                         document.getElementById('main-header').innerHTML = error + ": Live stream is stopping"
                         var clist  = document.getElementById('videos-container')
                         clist.removeChild(clist.childNodes[0])
                         setTimeout( function() {document.getElementById('reset').click()}, 3000)
                    } else {
                        if(isRoomOpened === true) {
                            document.getElementById('main-header').style.backgroundColor = "green"
                            document.getElementById('stream-code').disabled = true
                            document.getElementById('selectcam').disabled = true
                        }
                    }
                })
            }
        }

        // document.getElementById('join-room').onclick = function() {
        //     disableInputButtons()
        //     setCamera()
        //     connection.sdpConstraints.mandatory = {
        //         OfferToReceiveAudio: true,
        //         OfferToReceiveVideo: true
        //     }
        //     connection.join(document.getElementById('stream-code').value, function(isRoomExist, roomid, error) {
        //         if (isRoomExist == false) {
        //             msg = document.getElementById('stream-code').value
        //             msg = "Stream '" + msg + "' does not exist"
        //             alert(msg)
        //             console.log(error)
        //             document.getElementById('open-room').disabled = false
        //             document.getElementById('join-room').disabled = false
        //         }
        //     })
        // }

        document.getElementById('reset').onclick = function() {
            document.getElementById('main-header').innerHTML = "Resetting connection..."
            document.getElementById('main-header').style.backgroundColor = "darkorange"
            connection.closeSocket()
            var clist  = document.getElementById('videos-container')
            if (clist.childNodes[0]) {
                clist.removeChild(clist.childNodes[0])
            }
            setTimeout(function () {
              location.reload()
            }, 1000)
        }

        // ......................................................
        // ..................RTCMultiConnection Code.............
        // ......................................................

        var connection = new RTCMultiConnection()

        connection.socketURL = '<?php echo WEBRTC_SERVER ?>';

        connection.socketMessageEvent = 'rtc-video-proctoring';

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

        connection.DetectRTC.load(function() {
            // you can access all cameras using "DetectRTC.videoInputDevices"
            connection.DetectRTC.videoInputDevices.forEach(function(device) {
                var option = document.createElement('option')

                // this is what people see
                option.innerHTML = device.label

                // but this is what inernally used to select relevant device
                option.value = device.id

                // append to your choice
                document.querySelector('select').appendChild(option);
            })
        })
        // check camera end

        connection.videosContainer = document.getElementById('videos-container')

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
            if(event.type === 'local') {
                video.volume = 0
                try {
                    video.setAttributeNode(document.createAttribute('muted'));
                } catch (e) {
                    video.setAttribute('muted', true)
                 }
            }
            video.srcObject = event.stream
            txt  = connection.sessionid + " " + event.userid
            var width = parseInt(connection.videosContainer.clientWidth / 3) - 20
            var mediaElement = getHTMLMediaElement(video, {
                title: txt,
                buttons: ['full-screen'],
                width: width,
                showOnMouseEnter: false
            })
            connection.videosContainer.appendChild(mediaElement)
            console.log(mediaElement)
            setTimeout(function() {
                mediaElement.media.play()
            }, 5000)
            mediaElement.id = event.streamid
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

                var secondaryMic = DetectRTC.audioInputDevices[1].deviceId
                connection.mediaConstraints.audio = {
                    deviceId: secondaryMic
                }
                connection.join(connection.sessionid)
            }
        }

       if(location.href.indexOf("#") > -1) {
         document.getElementById('stream-code').value = location.href.split('#')[1]
       }

        connection.onExtraDataUpdated = function(event) {
            if(event.type != 'local') {
                var remotemsg = document.getElementById('remotemsg');
                if (event.extra.msg) remotemsg.value  = event.extra.msg;
            }
        }


        function disableInputButtons() {
            document.getElementById('open-room').disabled = true
            // document.getElementById('join-room').disabled = true
        }

        function setCamera() {
            console.log("check cam")
            var selectedcam = document.getElementById("selectcam")
            console.log(selectedcam.value)

            videoConstraintsSet = videoConstraints
            videoConstraintsSet.deviceId = selectedcam.value

            connection.mediaConstraintsSet = {
                video: videoConstraintsSet,
                audio: true
            }
            console.log(videoConstraintsSet)
        }

        // detect 2G
        if(
            navigator.connection &&
            navigator.connection.type === 'cellular' &&
            navigator.connection.downlinkMax <= 0.115
        ) {
            alert('2G is not supported. Please use a better internet service.');
        }
    </script>
</body>
</html>
