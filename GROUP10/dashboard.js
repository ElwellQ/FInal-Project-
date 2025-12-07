const statusEl = document.getElementById("status");
const dBText = document.getElementById("dBText");
const decibelBar = document.getElementById("decibelBar");
const cameraContainer = document.getElementById("cameraContainer");
const servoSlider = document.getElementById("servoSlider");
const servoVal = document.getElementById("servoVal");

// Servo control
servoSlider.addEventListener("input", () => {
    const val = servoSlider.value;
    servoVal.innerText = val + "°";
    fetch(`command.php?servo=${val}`)
        .then(res => res.text())
        .then(data => statusEl.innerText = "Status: " + data)
        .catch(() => statusEl.innerText = "Status: Error");
});

// Show camera feed
let cameraTimer = null;
let loudStart = 0;
let alertShown = false;
let manualOpen = false;

function showCamera(isAutomatic = false){
    if(!isAutomatic) manualOpen = true;
    cameraContainer.innerHTML = "";
    const iframe = document.createElement("iframe");
    iframe.src = "http://10.50.148.136/stream";
    iframe.width = 320;
    iframe.height = 240;
    iframe.style.border = "none";
    cameraContainer.appendChild(iframe);
    cameraContainer.style.display = "block";

    if(cameraTimer) clearTimeout(cameraTimer);
    // Only set 5-second auto-close if automatically triggered by loud noise
    if(isAutomatic){
        cameraTimer = setTimeout(()=>{
            if(manualOpen) return; // Don't auto-close if user manually opened
            closeCamera();
        }, 5000);
    }
}

function closeCamera(){
    manualOpen = false;
    if(cameraTimer) clearTimeout(cameraTimer);
    cameraTimer = null;
    cameraContainer.style.display = "none";
    cameraContainer.innerHTML = "";
}

// Poll sound every 500ms
function updateSound(){
    fetch("command.php?sound")
    .then(res => res.text())
    .then(val => {
        val = parseInt(val) || 0;
        const perc = Math.min(val / 20, 100);
        decibelBar.style.width = perc + "%";

        const now = Date.now();
        if(val > 1200){
            dBText.innerText = "LOUD";
            if(loudStart === 0) loudStart = now;
            if(now - loudStart >= 10000){
                showCamera(true); // true = automatically triggered
                if(!alertShown){
                    alert("⚠️ Loud Noise Detected for 10 seconds!");
                    alertShown = true;
                }
            }
        } else {
            dBText.innerText = "Quiet";
            loudStart = 0;
            alertShown = false;
        }
    }).catch(()=>console.log("Error reading sound"));
}

setInterval(updateSound, 500);

// Fetch live zones
function refreshNoiseTable(){
    fetch("fetch_zones_live.php")
    .then(res => res.text())
    .then(html => {
        document.getElementById("liveNoiseTable").innerHTML = html;
    });
}

refreshNoiseTable();
setInterval(refreshNoiseTable, 5000);
