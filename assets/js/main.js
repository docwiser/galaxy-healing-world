document.addEventListener('DOMContentLoaded', function() {
    // Form switching
    window.toggleForms = function() {
        const registered = document.querySelector('input[name="registered"]:checked').value;
        if (registered === 'yes') {
            document.getElementById('verification-form').classList.remove('hidden');
            document.getElementById('booking-form').classList.add('hidden');
        } else {
            document.getElementById('verification-form').classList.add('hidden');
            document.getElementById('booking-form').classList.remove('hidden');
        }
    }

    // Verification label
    window.updateVerificationLabel = function() {
        const method = document.querySelector('input[name="verify_method"]:checked').value;
        const label = document.getElementById('verify_value_label');
        if (method === 'phone') {
            label.textContent = 'Enter Your Phone Number';
        } else if (method === 'email') {
            label.textContent = 'Enter Your Email Address';
        } else {
            label.textContent = 'Enter Your Client ID';
        }
    }

    // DOB and Age
    window.toggleDOB = function() {
        const unknown = document.getElementById('unknown_dob').checked;
        const dobInput = document.getElementById('dob');
        const ageInput = document.getElementById('age-input');
        if (unknown) {
            dobInput.disabled = true;
            dobInput.value = '';
            ageInput.classList.remove('hidden');
            document.getElementById('calculated-age').classList.add('hidden');
        } else {
            dobInput.disabled = false;
            ageInput.classList.add('hidden');
        }
    }

    window.calculateAge = function() {
        const dob = document.getElementById('dob').value;
        if (dob) {
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            document.getElementById('age-display').textContent = age;
            document.getElementById('calculated-age').classList.remove('hidden');
        }
    }

    // Attendant info
    window.toggleAttendantInfo = function() {
        const attendant = document.getElementById('attendant').value;
        if (attendant === 'other') {
            document.getElementById('attendant-info').classList.remove('hidden');
        } else {
            document.getElementById('attendant-info').classList.add('hidden');
        }
    }

    // Disability info
    window.toggleDisabilityInfo = function() {
        const hasDisability = document.querySelector('input[name="has_disability"]:checked').value;
        if (hasDisability === 'yes') {
            document.getElementById('disability-info').classList.remove('hidden');
        } else {
            document.getElementById('disability-info').classList.add('hidden');
        }
    }

    // Pincode lookup
    const pincodeInput = document.getElementById('pincode');
    pincodeInput.addEventListener('input', handlePincodeInput);

    function handlePincodeInput() {
        const pincode = pincodeInput.value;
        const status = document.getElementById('pincode-status');
        if (pincode.length === 6) {
            fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                .then(response => response.json())
                .then(data => {
                    if (data[0].Status === 'Success') {
                        status.textContent = 'PIN code is valid.';
                        status.style.color = 'green';
                        const postOffice = data[0].PostOffice[0];
                        document.getElementById('district').value = postOffice.District;
                        document.getElementById('state').value = post.Office.State;
                        // Assuming you have city and area dropdowns
                        populateLocationDropdowns(data[0].PostOffice);
                    } else {
                        status.textContent = 'Invalid PIN code.';
                        status.style.color = 'red';
                    }
                })
                .catch(error => {
                    status.textContent = 'Error fetching PIN code data.';
                    status.style.color = 'red';
                });
        } else {
            status.textContent = '';
        }
    }

    function populateLocationDropdowns(postOffices) {
        const areaSelect = document.getElementById('area_village');
        const citySelect = document.getElementById('city');
        areaSelect.innerHTML = '<option value="">Select area/village</option>';
        citySelect.innerHTML = '<option value="">Select city</option>';

        const cities = new Set();
        postOffices.forEach(po => {
            areaSelect.innerHTML += `<option value="${po.Name}">${po.Name}</option>`;
            cities.add(po.Block);
        });

        cities.forEach(city => {
            citySelect.innerHTML += `<option value="${city}">${city}</option>`;
        });

        document.getElementById('area-selection').classList.remove('hidden');
        document.getElementById('city-selection').classList.remove('hidden');
    }

    window.fillAddressDetails = function() {
        const selectedCity = document.getElementById('city').value;
        // You might want to do something here based on the selected city
    }

    // Recording functionality
    let mediaRecorder;
    let audioChunks = [];
    let recordingTimer;

    document.getElementById('startRecording').addEventListener('click', function() {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.start();
                startTimer();

                document.getElementById('recording-controls').querySelector('#startRecording').classList.add('hidden');
                document.getElementById('recording-active').classList.remove('hidden');

                mediaRecorder.addEventListener("dataavailable", event => {
                    audioChunks.push(event.data);
                });
            });
    });

    document.getElementById('stopRecording').addEventListener('click', function() {
        mediaRecorder.stop();
        stopTimer();
        mediaRecorder.addEventListener("stop", () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const audioUrl = URL.createObjectURL(audioBlob);
            const audio = document.getElementById('audioPlayback');
            audio.src = audioUrl;

            // Here you would upload the blob to your server and get a path
            // For now, let'''s just pretend we have a path
            document.getElementById('voice_recording_path').value = 'path/to/your/recording.webm';

            document.getElementById('recording-active').classList.add('hidden');
            document.getElementById('recording-complete').classList.remove('hidden');
        });
    });
    
    document.getElementById('discardRecording').addEventListener('click', function() {
        audioChunks = [];
        document.getElementById('recording-complete').classList.add('hidden');
        document.getElementById('startRecording').classList.remove('hidden');
    });
    
    document.getElementById('cancelRecording').addEventListener('click', function() {
        mediaRecorder.stop();
        stopTimer();
        audioChunks = [];
        document.getElementById('recording-active').classList.add('hidden');
        document.getElementById('startRecording').classList.remove('hidden');
    });

    function startTimer() {
        let seconds = 0;
        recordingTimer = setInterval(() => {
            seconds++;
            let format = new Date(seconds * 1000).toISOString().substr(14, 5);
            document.getElementById('recording-timer').textContent = format;
        }, 1000);
    }

    function stopTimer() {
        clearInterval(recordingTimer);
        document.getElementById('recording-timer').textContent = '00:00';
    }
});
