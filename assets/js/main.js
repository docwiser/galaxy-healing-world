// Global variables
let isVerified = false;
let userData = null;

// Toggle forms based on registration status
function toggleForms() {
    const registered = document.querySelector('input[name="registered"]:checked')?.value;
    const verificationForm = document.getElementById('verification-form');
    const bookingForm = document.getElementById('booking-form');
    
    if (registered === 'yes') {
        verificationForm.classList.remove('hidden');
        verificationForm.classList.add('fade-in');
        bookingForm.classList.add('hidden');
    } else if (registered === 'no') {
        verificationForm.classList.add('hidden');
        bookingForm.classList.remove('hidden');
        bookingForm.classList.add('fade-in');
        generateClientId();
    }
}

// Handle verification form submission
document.getElementById('verifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const method = document.querySelector('input[name="verify_method"]:checked')?.value;
    const value = document.getElementById('verify_value').value;
    const messageDiv = document.getElementById('verification-message');
    
    if (!method) {
        showMessage(messageDiv, 'Please select a verification method', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/verify-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                method: method,
                value: value
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            isVerified = true;
            userData = result.data;
            showMessage(messageDiv, 'Verification successful!', 'success');
            
            setTimeout(() => {
                document.getElementById('verification-form').classList.add('hidden');
                document.getElementById('booking-form').classList.remove('hidden');
                document.getElementById('booking-form').classList.add('fade-in');
                prefillForm(userData);
            }, 1000);
        } else {
            showMessage(messageDiv, result.message || 'Verification failed. Please check your details.', 'error');
        }
    } catch (error) {
        showMessage(messageDiv, 'Connection error. Please try again.', 'error');
    }
});

// Prefill form with user data
function prefillForm(data) {
    if (!data) return;

    document.getElementById('name').value = data.name || '';
    document.getElementById('mobile').value = data.mobile || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('dob').value = data.dob || '';
    document.getElementById('how_learned').value = data.how_learned || '';

    // Set mobile number radio as default for verified users
    const phoneRadio = document.querySelector('input[name="verify_method"][value="phone"]');
    if (phoneRadio) {
        phoneRadio.checked = true;
        updateVerificationLabel();
    }

    // For verified users, prefill new address fields
    if (data.house_number) document.getElementById('house_number').value = data.house_number;
    if (data.street_locality) document.getElementById('street_locality').value = data.street_locality;
    if (data.pincode) document.getElementById('pincode').value = data.pincode;
    if (data.area_village) document.getElementById('area_village').value = data.area_village;
    if (data.city) document.getElementById('city').value = data.city;
    if (data.state) {
        document.getElementById('state').value = data.state;
        document.getElementById('state').removeAttribute('readonly');
    }
    if (data.district) {
        document.getElementById('district').value = data.district;
        document.getElementById('district').removeAttribute('readonly');
    }

    // Handle attendant info
    if (data.attendant && data.attendant !== 'self') {
        document.getElementById('attendant').value = 'other';
        toggleAttendantInfo();
        document.getElementById('attendant_name').value = data.attendant_name || '';
        document.getElementById('attendant_email').value = data.attendant_email || '';
        document.getElementById('attendant_mobile').value = data.attendant_mobile || '';
        document.getElementById('relationship').value = data.relationship || '';
    }

    // Handle disability info
    if (data.has_disability === 'yes') {
        document.querySelector('input[name="has_disability"][value="yes"]').checked = true;
        toggleDisabilityInfo();
        document.getElementById('disability_type').value = data.disability_type || '';
        document.getElementById('disability_percentage').value = data.disability_percentage || '';
    }

    if (data.dob) {
        calculateAge();
    }
}

// Generate client ID
function generateClientId() {
    const nameField = document.getElementById('name');
    const mobileField = document.getElementById('mobile');
    
    nameField.addEventListener('input', updateClientId);
    mobileField.addEventListener('input', updateClientId);
}

function updateClientId() {
    const name = document.getElementById('name').value.trim();
    const mobile = document.getElementById('mobile').value.trim();
    
    if (name && mobile) {
        const nameParts = name.split(' ');
        const firstInitial = nameParts[0] ? nameParts[0][0].toUpperCase() : '';
        const lastInitial = nameParts[nameParts.length - 1] && nameParts.length > 1 ? 
                           nameParts[nameParts.length - 1][0].toUpperCase() : '';
        const mobileDigits = mobile.slice(-6);
        
        const clientId = firstInitial + lastInitial + mobileDigits;
        
        // Store client ID in a hidden field or display it
        let clientIdField = document.getElementById('client_id');
        if (!clientIdField) {
            clientIdField = document.createElement('input');
            clientIdField.type = 'hidden';
            clientIdField.id = 'client_id';
            clientIdField.name = 'client_id';
            document.getElementById('mainBookingForm').appendChild(clientIdField);
        }
        clientIdField.value = clientId;
    }
}

// Calculate age from date of birth
function calculateAge() {
    const dobInput = document.getElementById('dob');
    const ageDisplay = document.getElementById('age-display');
    const calculatedAgeDiv = document.getElementById('calculated-age');
    
    if (dobInput.value) {
        const dob = new Date(dobInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        
        ageDisplay.textContent = age + ' years';
        calculatedAgeDiv.classList.remove('hidden');
    } else {
        calculatedAgeDiv.classList.add('hidden');
    }
}

// Toggle DOB/Age input
function toggleDOB() {
    const unknownDOB = document.getElementById('unknown_dob');
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('age-input');
    const calculatedAge = document.getElementById('calculated-age');
    
    if (unknownDOB.checked) {
        dobInput.disabled = true;
        dobInput.value = '';
        ageInput.classList.remove('hidden');
        calculatedAge.classList.add('hidden');
        dobInput.removeAttribute('required');
    } else {
        dobInput.disabled = false;
        ageInput.classList.add('hidden');
        document.getElementById('approximate_age').value = '';
    }
}

// Toggle attendant information
function toggleAttendantInfo() {
    const attendantSelect = document.getElementById('attendant');
    const attendantInfo = document.getElementById('attendant-info');
    
    if (attendantSelect.value === 'other') {
        attendantInfo.classList.remove('hidden');
        attendantInfo.classList.add('fade-in');
    } else {
        attendantInfo.classList.add('hidden');
    }
}

// Toggle disability information
function toggleDisabilityInfo() {
    const hasDisability = document.querySelector('input[name="has_disability"]:checked')?.value;
    const disabilityInfo = document.getElementById('disability-info');
    
    if (hasDisability === 'yes') {
        disabilityInfo.classList.remove('hidden');
        disabilityInfo.classList.add('fade-in');
    } else {
        disabilityInfo.classList.add('hidden');
    }
}

// Handle main booking form submission
document.getElementById('mainBookingForm').addEventListener('submit', (e) => {
    e.preventDefault();
    showPrepayment();
});

// Utility function to show messages
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = `message ${type}`;
    element.classList.remove('hidden');
    
    // Hide message after 5 seconds
    setTimeout(() => {
        element.classList.add('hidden');
    }, 5000);
}

// Pincode lookup variables
let pincodeData = null;

// Handle pincode input and lookup
function handlePincodeInput() {
    const pincodeInput = document.getElementById('pincode');
    const pincodeStatus = document.getElementById('pincode-status');
    const areaSelection = document.getElementById('area-selection');
    const citySelection = document.getElementById('city-selection');
    const areaSelect = document.getElementById('area_village');
    const citySelect = document.getElementById('city');

    pincodeInput.addEventListener('input', async function() {
        const pincode = this.value.trim();

        if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
            pincodeStatus.textContent = 'Looking up PIN code...';
            pincodeStatus.style.color = '#667eea';

            try {
                const response = await fetch(`https://api.postalpincode.in/pincode/${pincode}`);
                const data = await response.json();

                if (data[0].Status === 'Success' && data[0].PostOffice) {
                    pincodeData = data[0].PostOffice;
                    pincodeStatus.textContent = `Found ${pincodeData.length} locations`;
                    pincodeStatus.style.color = '#10b981';

                    // Populate Area/Village dropdown (Name column)
                    const uniqueAreas = [...new Set(pincodeData.map(office => office.Name))];
                    areaSelect.innerHTML = '<option value="">Select area/village</option>';
                    uniqueAreas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area;
                        option.textContent = area;
                        areaSelect.appendChild(option);
                    });

                    // Populate City dropdown (Block column)
                    const uniqueCities = [...new Set(pincodeData.map(office => office.Block).filter(block => block))];
                    citySelect.innerHTML = '<option value="">Select city</option>';
                    uniqueCities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        citySelect.appendChild(option);
                    });

                    areaSelection.classList.remove('hidden');
                    citySelection.classList.remove('hidden');
                    areaSelect.required = true;
                    citySelect.required = true;

                    // Auto-fill district and state
                    const firstOffice = pincodeData[0];
                    document.getElementById('district').value = firstOffice.District;
                    document.getElementById('state').value = firstOffice.Circle || firstOffice.State;
                } else {
                    pincodeStatus.textContent = 'Invalid PIN code. Please check and try again.';
                    pincodeStatus.style.color = '#ef4444';
                    areaSelection.classList.add('hidden');
                    citySelection.classList.add('hidden');
                    pincodeData = null;
                    clearAddressFields();
                }
            } catch (error) {
                pincodeStatus.textContent = 'Error looking up PIN code. Please try again.';
                pincodeStatus.style.color = '#ef4444';
                areaSelection.classList.add('hidden');
                citySelection.classList.add('hidden');
                pincodeData = null;
                clearAddressFields();
            }
        } else if (pincode.length > 0) {
            pincodeStatus.textContent = 'Enter 6-digit PIN code';
            pincodeStatus.style.color = '#6b7280';
            areaSelection.classList.add('hidden');
            citySelection.classList.add('hidden');
            pincodeData = null;
            clearAddressFields();
        } else {
            pincodeStatus.textContent = '';
            areaSelection.classList.add('hidden');
            citySelection.classList.add('hidden');
            pincodeData = null;
            clearAddressFields();
        }
    });
}

// Fill address details based on selected city
function fillAddressDetails() {
    // This function is kept for compatibility but the fields are now auto-filled
}

// Clear address fields
function clearAddressFields() {
    document.getElementById('district').value = '';
    document.getElementById('state').value = '';
    document.getElementById('area_village').value = '';
    document.getElementById('city').value = '';
}

// Voice recording functionality
let mediaRecorder = null;
let audioChunks = [];
let recordingStartTime = null;
let recordingTimer = null;
let isPaused = false;
let pausedTime = 0;
const MAX_RECORDING_TIME = 60000; // 60 seconds

function initVoiceRecording() {
    const startBtn = document.getElementById('startRecording');
    const pauseBtn = document.getElementById('pauseRecording');
    const cancelBtn = document.getElementById('cancelRecording');
    const stopBtn = document.getElementById('stopRecording');
    const discardBtn = document.getElementById('discardRecording');

    if (!startBtn) return; // Exit if elements don't exist

    startBtn.addEventListener('click', startRecording);
    pauseBtn.addEventListener('click', pauseResumeRecording);
    cancelBtn.addEventListener('click', cancelRecording);
    stopBtn.addEventListener('click', stopRecording);
    discardBtn.addEventListener('click', discardRecording);
}

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (event) => {
            audioChunks.push(event.data);
        };

        mediaRecorder.onstop = handleRecordingComplete;

        mediaRecorder.start();
        recordingStartTime = Date.now();
        isPaused = false;
        pausedTime = 0;

        // Show recording UI
        document.getElementById('startRecording').classList.add('hidden');
        document.getElementById('recording-active').classList.remove('hidden');

        // Start timer
        updateRecordingTimer();
        recordingTimer = setInterval(() => {
            const elapsed = Date.now() - recordingStartTime - pausedTime;
            if (elapsed >= MAX_RECORDING_TIME) {
                stopRecording();
            } else {
                updateRecordingTimer();
            }
        }, 100);
    } catch (error) {
        alert('Unable to access microphone. Please grant microphone permissions.');
    }
}

function pauseResumeRecording() {
    const pauseBtn = document.getElementById('pauseRecording');

    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.pause();
        isPaused = true;
        pauseBtn.textContent = 'Resume';
        pausedTime = Date.now() - recordingStartTime;
        clearInterval(recordingTimer);
    } else if (mediaRecorder && mediaRecorder.state === 'paused') {
        mediaRecorder.resume();
        isPaused = false;
        pauseBtn.textContent = 'Pause';
        recordingStartTime = Date.now() - pausedTime;
        recordingTimer = setInterval(() => {
            const elapsed = Date.now() - recordingStartTime;
            if (elapsed >= MAX_RECORDING_TIME) {
                stopRecording();
            } else {
                updateRecordingTimer();
            }
        }, 100);
    }
}

function cancelRecording() {
    if (mediaRecorder) {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        mediaRecorder = null;
    }

    clearInterval(recordingTimer);
    audioChunks = [];

    // Reset UI
    document.getElementById('startRecording').classList.remove('hidden');
    document.getElementById('recording-active').classList.add('hidden');
    document.getElementById('recording-complete').classList.add('hidden');
    document.getElementById('recording-timer').textContent = '00:00';
    document.getElementById('pauseRecording').textContent = 'Pause';
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    clearInterval(recordingTimer);
}

function handleRecordingComplete() {
    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    const audioUrl = URL.createObjectURL(audioBlob);

    // Display playback
    const audioPlayback = document.getElementById('audioPlayback');
    audioPlayback.src = audioUrl;

    // Upload audio file
    uploadAudioFile(audioBlob);

    // Update UI
    document.getElementById('recording-active').classList.add('hidden');
    document.getElementById('recording-complete').classList.remove('hidden');
    document.getElementById('recording-timer').textContent = '00:00';
    document.getElementById('pauseRecording').textContent = 'Pause';
}

async function uploadAudioFile(audioBlob) {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'recording.webm');

    try {
        const response = await fetch('api/upload-audio.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('voice_recording_path').value = result.path;
        } else {
            console.error('Audio upload failed:', result.message);
        }
    } catch (error) {
        console.error('Error uploading audio:', error);
    }
}

function discardRecording() {
    // Clear the uploaded file path
    document.getElementById('voice_recording_path').value = '';

    // Reset UI
    document.getElementById('startRecording').classList.remove('hidden');
    document.getElementById('recording-complete').classList.add('hidden');
    document.getElementById('audioPlayback').src = '';
    audioChunks = [];
}

function clearAudioRecording() {
    // Clear the uploaded file path
    document.getElementById('voice_recording_path').value = '';

    // Reset UI
    document.getElementById('startRecording').classList.remove('hidden');
    document.getElementById('recording-active').classList.add('hidden');
    document.getElementById('recording-complete').classList.add('hidden');
    document.getElementById('audioPlayback').src = '';
    document.getElementById('recording-timer').textContent = '00:00';
    document.getElementById('pauseRecording').textContent = 'Pause';

    // Clear recording data
    audioChunks = [];

    // Stop any active recording
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        mediaRecorder = null;
    }

    // Clear timer
    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
}

function updateRecordingTimer() {
    const elapsed = Date.now() - recordingStartTime - (isPaused ? pausedTime : 0);
    const seconds = Math.floor(elapsed / 1000);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    const timerDisplay = document.getElementById('recording-timer');
    timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners
    document.getElementById('dob').addEventListener('change', calculateAge);
    document.getElementById('apply-coupon-btn').addEventListener('click', applyCoupon);
    document.getElementById('remove-coupon-btn').addEventListener('click', removeCoupon);
    document.getElementById('pay-now-btn').addEventListener('click', payNow);
    document.getElementById('book-now-btn').addEventListener('click', () => bookNow(null));
    document.getElementById('retry-payment-btn').addEventListener('click', retryPayment);

    // Initialize client ID generation for new users
    if (!isVerified) {
        generateClientId();
    }

    // Initialize verification label
    updateVerificationLabel();

    // Initialize pincode lookup
    handlePincodeInput();

    // Initialize voice recording
    initVoiceRecording();
});

// Update verification label and placeholder based on selected method
function updateVerificationLabel() {
    const selectedMethod = document.querySelector('input[name="verify_method"]:checked');
    const label = document.getElementById('verify_value_label');
    const input = document.getElementById('verify_value');
    
    if (!selectedMethod) {
        label.textContent = 'Verification Details';
        input.placeholder = 'Enter your verification details';
        input.type = 'text';
        return;
    }
    
    switch(selectedMethod.value) {
        case 'phone':
            label.textContent = 'Phone Number';
            input.placeholder = 'Enter your phone number';
            input.type = 'tel';
            break;
        case 'email':
            label.textContent = 'Email Address';
            input.placeholder = 'Enter your email address';
            input.type = 'email';
            break;
        case 'client_id':
            label.textContent = 'Client ID';
            input.placeholder = 'Enter your client ID';
            input.type = 'text';
            break;
        default:
            label.textContent = 'Verification Details';
            input.placeholder = 'Enter your verification details';
            input.type = 'text';
    }
}

// Payment Flow Logic
let firstSessionAmount = 500; // This can be fetched from config later
let discount = 0;
let couponCode = null;

function showPrepayment() {
    // Basic validation
    if (!document.getElementById('name').value || !document.getElementById('mobile').value || !document.getElementById('email').value) {
        alert('Please fill in all required fields.');
        return;
    }

    document.getElementById('booking-flow').classList.add('hidden');
    document.getElementById('prepayment-summary').classList.remove('hidden');

    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const mobile = document.getElementById('mobile').value;

    const paymentDetails = `
        <p><strong>Name:</strong> ${name}</p>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Mobile:</strong> ${mobile}</p>
    `;
    document.getElementById('payment-details').innerHTML = paymentDetails;

    document.getElementById('subtotal').innerText = firstSessionAmount;
    document.getElementById('total-amount').innerText = firstSessionAmount;
}

async function applyCoupon() {
    couponCode = document.getElementById('coupon-code').value;
    if (!couponCode) {
        alert('Please enter a coupon code.');
        return;
    }

    const response = await fetch('api/validate-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ coupon_code: couponCode, amount: firstSessionAmount })
    });

    const data = await response.json();

    if (data.success) {
        discount = data.discount;
        document.getElementById('discount').innerText = discount;
        updateTotal();
        document.getElementById('coupon-code').disabled = true;
        document.getElementById('apply-coupon-btn').classList.add('hidden');
        document.getElementById('remove-coupon-btn').classList.remove('hidden');
    } else {
        alert(data.message);
    }
}

function removeCoupon() {
    discount = 0;
    couponCode = null;
    document.getElementById('discount').innerText = discount;
    updateTotal();
    document.getElementById('coupon-code').value = '';
    document.getElementById('coupon-code').disabled = false;
    document.getElementById('apply-coupon-btn').classList.remove('hidden');
    document.getElementById('remove-coupon-btn').classList.add('hidden');
}

function updateTotal() {
    const total = firstSessionAmount - discount;
    document.getElementById('total-amount').innerText = total;

    if (total <= 0) {
        document.getElementById('pay-now-btn').classList.add('hidden');
        document.getElementById('book-now-btn').classList.remove('hidden');
    } else {
        document.getElementById('pay-now-btn').classList.remove('hidden');
        document.getElementById('book-now-btn').classList.add('hidden');
    }
}

async function payNow() {
    const totalAmount = firstSessionAmount - discount;

    const response = await fetch('api/create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            amount: firstSessionAmount,
            user_id: userData ? userData.id : null, // Pass user ID if available
            coupon_code: couponCode
        })
    });

    const data = await response.json();

    if (data.success && data.order_id) {
        const options = {
            key: 'YOUR_RAZORPAY_KEY_ID', // Replace with your key
            amount: data.amount * 100,
            currency: 'INR',
            name: 'Galaxy Healing World',
            description: 'First Session Booking',
            order_id: data.order_id,
            handler: function (response) {
                // Payment successful
                bookNow(response.razorpay_payment_id);
            },
            prefill: {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                contact: document.getElementById('mobile').value
            },
            theme: {
                color: '#667eea'
            }
        };
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response) {
            showPaymentFailed();
        });
        rzp.open();
    } else {
        alert('Could not create order. Please try again.');
    }
}

async function bookNow(paymentId = null) {
    const formData = new FormData(document.getElementById('mainBookingForm'));
    if (paymentId) {
        formData.append('payment_id', paymentId);
    }
    formData.append('payment_made', firstSessionAmount - discount);

    const response = await fetch('api/book-session.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        showPaymentSuccess();
    } else {
        alert('Booking failed: ' + data.message);
    }
}

function showPaymentSuccess() {
    document.getElementById('prepayment-summary').classList.add('hidden');
    document.getElementById('payment-success').classList.remove('hidden');
}

function showPaymentFailed() {
    document.getElementById('prepayment-summary').classList.add('hidden');
    document.getElementById('payment-failed').classList.remove('hidden');
}

function retryPayment() {
    document.getElementById('payment-failed').classList.add('hidden');
    document.getElementById('prepayment-summary').classList.remove('hidden');
}
