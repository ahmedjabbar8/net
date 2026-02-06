</div> <!-- end container -->

<!-- AUDIO ELEMENTS - Using different sources for better compatibility -->
<audio id="ringtone" preload="auto" loop>
    <source src="https://www.soundjay.com/phone/phone-calling-1.mp3" type="audio/mpeg">
</audio>
<audio id="remoteAudio" autoplay playsinline></audio>

<!-- INCOMING CALL POPUP -->
<div id="incomingCallPopup" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:99999; display:none;">
    <div class="card shadow-lg border-0 rounded-4 p-4 text-center bg-white" style="width:320px;">
        <div class="bg-success text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px;">
            <i class="fas fa-phone fa-2x"></i>
        </div>
        <h5 class="fw-bold mb-1" id="callerNameDisplay">مكالمة واردة</h5>
        <p class="text-muted small mb-3">اضغط رد للتحدث</p>
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-danger px-4 py-2 rounded-pill" onclick="rejectIncomingCall()">رفض</button>
            <button class="btn btn-success px-4 py-2 rounded-pill" onclick="acceptIncomingCall()">رد</button>
        </div>
    </div>
</div>

<!-- ACTIVE CALL OVERLAY -->
<div id="activeCallOverlay" class="position-fixed bottom-0 start-0 m-3" style="z-index:99999; display:none;">
    <div class="card shadow-lg border-0 rounded-4 p-3 bg-dark text-white" style="width:250px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-success rounded-circle" style="width:12px;height:12px;animation:pulse 1s infinite;"></div>
            <div class="flex-grow-1">
                <div class="fw-bold small" id="activeCallName">مكالمة جارية</div>
                <div id="callTimer" style="font-family:monospace;">00:00</div>
            </div>
            <button class="btn btn-danger btn-sm rounded-circle" style="width:40px;height:40px;" onclick="endCurrentCall()">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>
</div>

<!-- CONNECTION STATUS -->
<div id="connectionStatus" class="position-fixed bottom-0 end-0 m-2 px-2 py-1 rounded-pill bg-secondary text-white" style="font-size:0.65rem; z-index:9999;">
    <i class="fas fa-circle me-1" id="statusDot"></i>
    <span id="statusText">جاري الاتصال...</span>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// === HOSPITAL CALL SYSTEM ===
const userId = "<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>";
const userName = "<?php echo isset($_SESSION['full_name']) ? addslashes($_SESSION['full_name']) : 'مستخدم'; ?>";
const peerId = "hospital_" + userId;

let peer = null;
let currentCall = null;
let localStream = null;
let timerInterval = null;
let callSeconds = 0;

// Get audio elements
const ringtoneEl = document.getElementById('ringtone');
const remoteAudioEl = document.getElementById('remoteAudio');

// Initialize on page load
if (userId !== '0') {
    // Pre-load ringtone
    ringtoneEl.load();
    initializeCallSystem();
}

function initializeCallSystem() {
    peer = new Peer(peerId, {
        host: '0.peerjs.com',
        port: 443,
        secure: true,
        debug: 0
    });

    peer.on('open', function(id) {
        console.log('✓ Connected:', id);
        document.getElementById('statusDot').style.color = '#2ecc71';
        document.getElementById('statusText').textContent = 'جاهز للاتصال';
    });

    peer.on('call', function(call) {
        console.log('📞 Incoming call!');
        currentCall = call;
        
        // Get caller name from metadata
        let callerName = 'زميل';
        if (call.metadata && call.metadata.name) {
            callerName = call.metadata.name;
        }
        document.getElementById('callerNameDisplay').textContent = callerName;
        
        // Show popup
        document.getElementById('incomingCallPopup').style.display = 'block';
        
        // Play ringtone - try multiple times
        playRingtone();
    });

    peer.on('error', function(err) {
        console.log('❌ Error:', err.type);
        document.getElementById('statusDot').style.color = '#e74c3c';
        document.getElementById('statusText').textContent = 'خطأ: ' + err.type;
    });

    peer.on('disconnected', function() {
        document.getElementById('statusDot').style.color = '#f39c12';
        document.getElementById('statusText').textContent = 'إعادة الاتصال...';
        setTimeout(() => peer.reconnect(), 1000);
    });
}

function playRingtone() {
    ringtoneEl.currentTime = 0;
    ringtoneEl.volume = 1.0;
    const playPromise = ringtoneEl.play();
    if (playPromise) {
        playPromise.catch(e => {
            console.log('Ringtone blocked, will play on interaction');
        });
    }
}

function stopRingtone() {
    ringtoneEl.pause();
    ringtoneEl.currentTime = 0;
}

// Make outgoing call
window.makeCall = async function(targetUserId, targetName) {
    console.log('📤 Calling:', targetName);
    
    if (!peer || !peer.open) {
        alert('النظام غير جاهز، يرجى الانتظار');
        return false;
    }

    // Get microphone
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        console.log('✓ Microphone ready');
    } catch(e) {
        console.log('⚠ Mic denied, using silent');
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const dest = ctx.createMediaStreamDestination();
        oscillator.connect(dest);
        oscillator.start();
        localStream = dest.stream;
    }

    // Make the call
    const targetPeerId = "hospital_" + targetUserId;
    console.log('📞 Calling peer:', targetPeerId);
    
    currentCall = peer.call(targetPeerId, localStream, {
        metadata: { name: userName }
    });

    if (!currentCall) {
        alert('فشل الاتصال');
        return false;
    }

    setupCallEvents(currentCall);
    showActiveCallUI(targetName);
    return true;
};

// Accept incoming call
function acceptIncomingCall() {
    console.log('✓ Accepted call');
    
    document.getElementById('incomingCallPopup').style.display = 'none';
    stopRingtone();

    // Get microphone
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            console.log('✓ Microphone ready');
            localStream = stream;
            currentCall.answer(stream);
            setupCallEvents(currentCall);
            showActiveCallUI(document.getElementById('callerNameDisplay').textContent);
        })
        .catch(function(e) {
            console.log('⚠ Mic denied, using silent');
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = ctx.createOscillator();
            const dest = ctx.createMediaStreamDestination();
            oscillator.connect(dest);
            oscillator.start();
            localStream = dest.stream;
            currentCall.answer(localStream);
            setupCallEvents(currentCall);
            showActiveCallUI(document.getElementById('callerNameDisplay').textContent);
        });
}

// Reject incoming call
function rejectIncomingCall() {
    console.log('✗ Rejected call');
    document.getElementById('incomingCallPopup').style.display = 'none';
    stopRingtone();
    if (currentCall) {
        currentCall.close();
        currentCall = null;
    }
}

// Setup call event handlers
function setupCallEvents(call) {
    call.on('stream', function(remoteStream) {
        console.log('🔊 Got remote audio stream');
        
        // Set the remote audio source
        remoteAudioEl.srcObject = remoteStream;
        remoteAudioEl.volume = 1.0;
        
        // Force play
        const playPromise = remoteAudioEl.play();
        if (playPromise) {
            playPromise.then(() => {
                console.log('✓ Audio playing');
            }).catch(e => {
                console.log('⚠ Audio blocked:', e);
                // Try again on next user interaction
                document.body.addEventListener('click', function tryPlay() {
                    remoteAudioEl.play();
                    document.body.removeEventListener('click', tryPlay);
                }, { once: true });
            });
        }
    });

    call.on('close', function() {
        console.log('📴 Call ended');
        endCurrentCall();
    });

    call.on('error', function(err) {
        console.log('❌ Call error:', err);
        endCurrentCall();
    });
}

// Show active call UI
function showActiveCallUI(name) {
    document.getElementById('activeCallName').textContent = name;
    document.getElementById('activeCallOverlay').style.display = 'block';
    callSeconds = 0;
    document.getElementById('callTimer').textContent = '00:00';
    
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(function() {
        callSeconds++;
        const mins = Math.floor(callSeconds / 60);
        const secs = callSeconds % 60;
        document.getElementById('callTimer').textContent = 
            (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
    }, 1000);
    
    window.gCall = currentCall;
}

// End current call
function endCurrentCall() {
    console.log('📴 Ending call');
    
    if (currentCall) {
        currentCall.close();
        currentCall = null;
    }
    
    if (localStream) {
        localStream.getTracks().forEach(t => t.stop());
        localStream = null;
    }
    
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    
    stopRingtone();
    remoteAudioEl.srcObject = null;
    
    document.getElementById('activeCallOverlay').style.display = 'none';
    document.getElementById('incomingCallPopup').style.display = 'none';
    
    window.gCall = null;
}

// Expose globally
window.terminateCallGlobal = endCurrentCall;
window.startCallSystem = window.makeCall;

// Enable audio on first click (browser requirement)
document.body.addEventListener('click', function enableAudio() {
    ringtoneEl.play().then(() => ringtoneEl.pause()).catch(() => {});
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    ctx.resume();
}, { once: true });
</script>

</body>
</html>