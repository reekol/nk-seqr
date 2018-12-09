var textMax = suffix = pr = pd = fp = nn = nc = cm = cf = fu = mf = er = hash = n = null;

document.addEventListener('DOMContentLoaded', function () {

    const   camVideo = document.querySelector('#camVideo'),
            camSelect = document.querySelector('#camSelect'),
            camTakePhoto = document.querySelector(/*'#camTakePhoto'*/'#camVideo'),
//             camDeletePhoto = document.querySelector('#camDeletePhoto'),
            camCanvas = document.querySelector('#camCanvas'),
            camFile = document.querySelector('#camFile');
/*            camDownloadPhoto = document.querySelector('#camDownloadPhoto');*/

    let currentStream;

    let cameraChanged = () => {
        const videoConstraints = {};
        if (typeof currentStream !== 'undefined') stopMediaTracks(currentStream);
        if (camSelect.value === '') videoConstraints.facingMode = 'environment';
        else videoConstraints.deviceId = { exact: camSelect.value };

        const constraints = { video: videoConstraints, audio: false };
        navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            currentStream = stream;
            camVideo.srcObject = stream;
            return navigator.mediaDevices.enumerateDevices();
        });
    };

        function stopMediaTracks(stream) {
        stream.getTracks().forEach(track => {
            track.stop();
        });
        }


        function gotDevices(mediaDevices) {
            camSelect.innerHTML = '';
            let count = 1;
            let hasSelected = false;
            mediaDevices.forEach(mediaDevice => {
                if (mediaDevice.kind === 'videoinput') {
                    const option = document.createElement('option');
                    option.value = mediaDevice.deviceId;
                    const label = mediaDevice.label || `Camera ${count++}`;
                    const textNode = document.createTextNode(label);
                    option.appendChild(textNode);
                    camSelect.appendChild(option);
                    if(!hasSelected){
                        hasSelected = true;
                        option.setAttribute('selected','selected')
                        cameraChanged()
                    }
                }
            });
        }

        if(typeof camStop !== 'undefined') camStop.addEventListener('click',(e) => {
            e.preventDefault();
            stopMediaTracks(currentStream) 
        });


        camSelect.addEventListener('change', cameraChanged );

        if(typeof camTakePhoto !== 'undefined') camTakePhoto.addEventListener("click", (e) => {
            e.preventDefault();
            var camCanvas = document.querySelector('canvas'),
                context = camCanvas.getContext('2d');
            var width = camVideo.videoWidth,
                height = camVideo.videoHeight;
            if (width && height) {
                camCanvas.width = width;
                camCanvas.height = height;
                context.drawImage(camVideo, 0, 0, width, height);
                var dataurl = camCanvas.toDataURL('image/png');
                camFile.value = dataurl;
//                $(camFile).trigger('change')
                formSubmit(fu)//.submit();
                if(typeof camDownloadPhoto !== 'undefined') camDownloadPhoto.href = dataurl;
            }
              stopMediaTracks(currentStream) 
//            camVideo.pause();

        });

        if(typeof camDeletePhoto !== 'undefined') camDeletePhoto.addEventListener("click", function(e){
            e.preventDefault();
            camVideo.play();
        });

        function camEnumDevices(){
            navigator.mediaDevices.enumerateDevices().then(gotDevices);
        }

        /* Juery custom scripts for this page */
        $( "#cameraModal" ).on('shown.bs.modal', function(){
            camEnumDevices();
        });
        $( "#cameraModal" ).on('hidden.bs.modal', function(){
            stopMediaTracks(currentStream) 
        });
});

document.addEventListener('DOMContentLoaded', async function () {
    hash = window.location.hash
    if(hash){

    }
    pr = document.getElementById('passRandom');
    pd = document.getElementById('passDefault');
    fu = document.getElementById('fup');
    fp = document.getElementById('fp');
    n  = document.getElementById('n');
    nn = document.getElementById('newNote');
    nc = document.getElementById('newNoteCheck');
    cm = document.getElementById('count_message');
    cf = document.getElementById('customFile');
    mf = document.getElementById('camFile');
    tp = document.getElementById('togglePassVis');
    er = document.getElementById('eraser');

    let res = await fetch('api.php')
        res = await res.json()
            setCsrf(res.csrf);
            setLimit(res.lmt);

    (function ($) {
    $('.spinner .btn:first-of-type').on('click', function() {
        $('.spinner input').val( parseInt($('.spinner input').val(), 10) + 1);
    });
    $('.spinner .btn:last-of-type').on('click', function() {
        $('.spinner input').val( parseInt($('.spinner input').val(), 10) - 1);
    });
    })(jQuery);

    fp.focus();
    er.addEventListener('click',() => { $('#newNote').val(''); nnUp() });
    tp.addEventListener('click',togglePassVis);
    cf.addEventListener('change',() => { formSubmit(fu) })
    mf.addEventListener('change',() => { formSubmit(fu) })
    document.addEventListener('keyup',function(e){ 
        if(e.keyCode === 27) changeCb(e);
    });

    suffix = ' chars left.';
    textMax = nn.getAttribute('maxlength');

    var tl = nn.value.length;
    var tr= textMax - tl;
    cm.innerText = tr + suffix;

    nn.addEventListener('keyup',nnUp);
    fp.addEventListener('keyup',passUp);

    pd.addEventListener('click',(e) => { fp.value = 'RAW:default'; passUp() });
    pr.addEventListener('click',async (e) => { 
        loading('start')
        fp.value = await strRand();
        loading('end')
        passUp()
        let x = document.getElementById('fp');
        let y = document.getElementById('fpEye');
        x.type = 'text';
        y.classList.remove('fa-eye')
        y.classList.add('fa-eye-slash')
        fp.select();
        document.execCommand('copy');
    });

    var strRand = async () => {
         let res = await fetch('api.php?rand=word')
             res = await res.json()
             setCsrf(res.csrf)
        return res.pwd;
            let text = "";
            let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            for (var i = 0; i < 4; i++) text += possible.charAt(Math.floor(Math.random() * possible.length));
        return text;
    }

    var changeCb = (e) => {
        let y = document.getElementById('nnEye');
        if(nn.classList.contains('hiddenText')){
            nn.classList.remove('hiddenText');
            nn.classList.remove('striped');
            y.classList.remove('fa-eye-slash')
            y.classList.add('fa-eye')
        }else{
            nn.classList.add('hiddenText');
            nn.classList.add('striped');
            y.classList.remove('fa-eye')
            y.classList.add('fa-eye-slash')
        }
    };

    nc.addEventListener('click',changeCb);
    $('[data-toggle="tooltip"]').tooltip()

//    passUp();

});

function togglePassVis() {
    let x = document.getElementById('fp');
    let y = document.getElementById('fpEye');
    if(x.type === "password")
    {
        x.type = 'text';
        y.classList.remove('fa-eye')
        y.classList.add('fa-eye-slash')
    }else{
        x.type ='password'  
        y.classList.remove('fa-eye-slash')
        y.classList.add('fa-eye')
    }
}

function nnUp(e){
    var tl = nn.value.length;
    var tr= textMax - tl;
    cm.innerHTML = tr + suffix;
    if(nn.classList.contains('hiddenText')){
        let lastChar = nn.value[nn.value.length -1] || '';
        cm.innerHTML = '<b>' + lastChar + '</b> < ' + cm.innerText
        setTimeout(()=>{ cm.innerHTML = tr + suffix; },50)
    }
}
function passUp(e){
    $('.pass').val(fp.value);
    if(fp.value !== ''){
        $('.requireHide').show('slow');
        if(e && e.keyCode === 13){
            $(cf).trigger('click');
        }
    }else{
        $('.requireHide').hide('slow');
    }
}
function b64DecodeUnicode(str) {
    return decodeURIComponent(Array.prototype.map.call(atob(str), function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
    }).join(''))
}

// Create downloadable png from svg
function convert(svg, cb) {
    if(!svg) return false;
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    var image = new Image();
    image.onload = function load() {
        canvas.height = image.height;
        canvas.width = image.width;
        ctx.drawImage(image, 0, 0);
        cb(canvas);
    };
    image.src = 'data:image/svg+xml;charset-utf-8,' + encodeURIComponent(svg.outerHTML);
}

function postData(form) {
  // Default options are marked with *
    let formData = new FormData(form)
    return fetch('api.php', {
        method: "POST", // *GET, POST, PUT, DELETE, etc.
         mode: "cors", // no-cors, cors, *same-origin
         cache: "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
         credentials: "same-origin", // include, *same-origin, omit
        headers: {
            //"Content-Type": "application/json; charset=utf-8",
//           "Content-Type": "application/x-www-form-urlencoded",
//           "Content-Type": form.enctype,
//           "Content-Type": "application/json",
        },
         redirect: "follow", // manual, *follow, error
         referrer: "no-referrer", // no-referrer, *client
        body: formData, // body data type must match "Content-Type" header
    })
    .then(response => response.json()) // parses response to JSON
    .catch(error => console.error('Error:', error))
}

function setCsrf(csrf){
    document.querySelectorAll('[name="CSRF"]').forEach((el) => { return el.value = csrf; });
}
function setLimit(limit){
    document.querySelectorAll('textarea').forEach((el) => {
        return el.setAttribute('maxlength',limit);
    });
}

function loading(mode = 'start'){
        document.getElementById('qrSvg').innerHTML = (mode == 'start'? '<div class="lds-ripple mt-2"><div></div><div></div></div>': '')
}

function formSubmit(form){
    loading('start');
    let data = postData(form).then((response) => { setResponse(response); form.reset() });
    return false;
}

ERRORS = {
    1: "Unale to read uploaded file",
    2: "Unrecognized or invalidated",
    3: "Wrong passowrd",
    10: "CSRF mismatch"
}
function setResponse(response){
    let qrSvg = document.getElementById('qrSvg'),
        err = document.getElementById('err'),
        rec = document.getElementById('rec'),
        uid = document.getElementById('uid');
        qrSvg.innerHTML = err.innerHTML = rec.innerHTML = uid.innerHTML = n.value = nn.value = ''

        setCsrf(response.csrf)
        setLimit(response.lmt)

        if(typeof response.txt !== 'undefined' && response.txt !== null) nn.value = response.txt
        if(typeof response.nme !== 'undefined' && response.nme !== null) n.value = response.nme.replace('.qr.png','');
        if(typeof response.err !== 'undefined' && response.err > 0) err.innerHTML ='<div class="alert alert-danger m-0 mt-2"><i class="fas fa-spider"></i>&nbsp;' + ERRORS[response.err] + '</div>'
        if(typeof response.rec !== 'undefined' && response.rec !== '') rec.innerHTML ='<div class="alert alert-info m-0 mt-2"><i class="fas fa-recycle"></i>&nbsp;' + response.rec + '</div>'
//        if(typeof response.uid !== 'undefined' && response.uid !== '') uid.innerHTML ='<div class="alert alert-success m-0 mt-2"><i class="fas fa-fingerprint"></i>&nbsp;' + response.uid + '</div>'
        if(typeof response.svg !== 'undefined' && response.svg !== null)
        {
            qrSvg.innerHTML = response.svg;
            let svg = qrSvg.querySelector('svg');
//                svg.innerHTML += `<text font-size="6" x="10" y="7" fill="red">${response.rec}</text>`
            convert(svg,(canvas)=>{
                qrSvg.href = canvas.toDataURL('image/png')
                qrSvg.download=response.nme
//                window.location.hash= (typeof response.uid === 'undefined' ? '' : response.uid )
            })
        }
}
