// Global variables
let isVerified = false;
let userData = null;
let razorpayKey = ''; // To be fetched from config

// Fetch config on load
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('api/get-config.php');
        const config = await response.json();
        razorpayKey = config.razorpay_key;
        firstSessionAmount = parseFloat(config.first_session_amount) || 500;
    } catch (error) {
        console.error('Error fetching config:', error);
        // Use default values if config fetch fails
        firstSessionAmount = 500;
    }
});


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
                prefillForm(result.data);
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
    document.getElementById('query_text').value = data.query_text || '';

    // Set mobile number radio as default for verified users
    const phoneRadio = document.querySelector('input[name="verify_method"][value="phone"]');
    if (phoneRadio) {
        phoneRadio.checked = true;
        updateVerificationLabel();
    }

    // For verified users, prefill new address fields
    if (data.house_number) document.getElementById('house_number').value = data.house_number;
    if (data.street_locality) document.getElementById('street_locality').value = data.street_locality;
    if (data.pincode) {
        document.getElementById('pincode').value = data.pincode;
        // Trigger pincode lookup if prefilled
        handlePincodeInput();
        const pincodeInput = document.getElementById('pincode');
        const event = new Event('input', { bubbles: true });
        pincodeInput.dispatchEvent(event);
    }
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
    
    setTimeout(() => {
        element.classList.add('hidden');
    }, 5000);
}

// Pincode lookup variables
let pincodeData = null;

// Handle pincode input and lookup
function handlePincodeInput() {
    const pincodeInput = document.getElementById('pincode');
    if (!pincodeInput) return;

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

                    const uniqueAreas = [...new Set(pincodeData.map(office => office.Name))];
                    areaSelect.innerHTML = '<option value="">Select area/village</option>';
                    uniqueAreas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area;
                        option.textContent = area;
                        areaSelect.appendChild(option);
                    });

                    const uniqueCities = [...new Set(pincodeData.map(office => office.Block).filter(block => block !== "NA"))];
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

                    const firstOffice = pincodeData[0];
                    document.getElementById('district').value = firstOffice.District;
                    document.getElementById('state').value = firstOffice.Circle || firstOffice.State;
                } else {
                    pincodeStatus.textContent = 'Invalid PIN code';
                    pincodeStatus.style.color = '#ef4444';
                    pincodeData = null;
                    clearAddressFields(false);
                }
            } catch (error) {
                pincodeStatus.textContent = 'Error looking up PIN code';
                pincodeStatus.style.color = '#ef4444';
                pincodeData = null;
                clearAddressFields(false);
            }
        } else {
            pincodeStatus.textContent = '';
            pincodeData = null;
            clearAddressFields(true);
        }
    });
}

// Fill address details based on selected city
function fillAddressDetails() {
    // This function is kept for compatibility but the fields are now auto-filled
}

// Clear address fields
function clearAddressFields(clearPincode = true) {
    if (clearPincode) document.getElementById('pincode').value = '';
    document.getElementById('district').value = '';
    document.getElementById('state').value = '';
    document.getElementById('area_village').innerHTML = '<option value="">Select area/village</option>';
    document.getElementById('city').innerHTML = '<option value="">Select city</option>';
    document.getElementById('area-selection').classList.add('hidden');
    document.getElementById('city-selection').classList.add('hidden');
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

    if (!startBtn) return;

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

        document.getElementById('startRecording').classList.add('hidden');
        document.getElementById('recording-active').classList.remove('hidden');

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
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        mediaRecorder = null;
    }

    clearInterval(recordingTimer);
    audioChunks = [];

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

    const audioPlayback = document.getElementById('audioPlayback');
    audioPlayback.src = audioUrl;

    uploadAudioFile(audioBlob);

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
    document.getElementById('voice_recording_path').value = '';
    document.getElementById('startRecording').classList.remove('hidden');
    document.getElementById('recording-complete').classList.add('hidden');
    document.getElementById('audioPlayback').src = '';
    audioChunks = [];
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('dob').addEventListener('change', calculateAge);
    document.getElementById('apply-coupon-btn').addEventListener('click', applyCoupon);
    document.getElementById('remove-coupon-btn').addEventListener('click', removeCoupon);
    document.getElementById('pay-now-btn').addEventListener('click', payNow);
    document.getElementById('book-now-btn').addEventListener('click', () => bookNow(userData, null, null));
    document.getElementById('retry-payment-btn').addEventListener('click', retryPayment);

    if (!isVerified) {
        generateClientId();
    }

    updateVerificationLabel();
    handlePincodeInput();
    initVoiceRecording();
});

// Update verification label and placeholder based on selected method
function updateVerificationLabel() {
    const selectedMethod = document.querySelector('input[name="verify_method"]:checked');
    const label = document.getElementById('verify_value_label');
    const input = document.getElementById('verify_value');
    
    if (!selectedMethod) return;
    
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
    }
}

// Payment Flow Logic
let firstSessionAmount = 500;
let discount = 0;
let couponCode = null;
let currentOrderId = null;

function showPrepayment() {
    if (!document.getElementById('name').value || !document.getElementById('mobile').value || !document.getElementById('email').value) {
        alert('Please fill in all required fields.');
        return;
    }

    document.getElementById('booking-flow').classList.add('hidden');
    document.getElementById('prepayment-summary').classList.remove('hidden');

    const paymentDetails = `
        <p><strong>Name:</strong> ${document.getElementById('name').value}</p>
        <p><strong>Email:</strong> ${document.getElementById('email').value}</p>
        <p><strong>Mobile:</strong> ${document.getElementById('mobile').value}</p>
    `;
    document.getElementById('payment-details').innerHTML = paymentDetails;

    updateTotal();
}

async function applyCoupon() {
    couponCode = document.getElementById('coupon-code').value;
    if (!couponCode) return;

    const response = await fetch('api/validate-coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ coupon_code: couponCode, amount: firstSessionAmount })
    });

    const data = await response.json();

    if (data.success) {
        discount = parseFloat(data.discount);
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
    updateTotal();
    document.getElementById('coupon-code').value = '';
    document.getElementById('coupon-code').disabled = false;
    document.getElementById('apply-coupon-btn').classList.remove('hidden');
    document.getElementById('remove-coupon-btn').classList.add('hidden');
}

function updateTotal() {
    const total = Math.max(0, firstSessionAmount - discount);
    document.getElementById('subtotal').innerText = '₹' + firstSessionAmount.toFixed(2);
    document.getElementById('discount').innerText = '₹' + discount.toFixed(2);
    document.getElementById('total-amount').innerText = '₹' + total.toFixed(2);

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

    const orderData = {
        amount: totalAmount,
        coupon_code: couponCode,
        user_id: userData ? userData.id : null,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        mobile: document.getElementById('mobile').value,
    };

    try {
        const response = await fetch('api/create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const data = await response.json();

        if (data.success) {
            userData = data.user; // Update user data with what the server returns
            currentOrderId = data.order_id;
            
            if (data.amount > 0 && data.order_id) {
                const options = {
                    key: razorpayKey,
                    amount: data.amount * 100,
                    currency: 'INR',
                    name: 'Galaxy Healing World',
                    description: 'First Session Booking',
                    order_id: data.order_id,
                    handler: function (response) {
                        bookNow(userData, response.razorpay_payment_id, response.razorpay_order_id);
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
                    showPaymentFailed(response.error.description);
                });
                rzp.open();
            } else {
                // Free booking
                bookNow(userData, null, currentOrderId);
            }
        } else {
            alert('Could not create order: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        alert('An error occurred while creating the order. Please try again.');
    }
}

async function bookNow(user, paymentId, orderId) {
    const formData = new FormData(document.getElementById('mainBookingForm'));
    
    if (user && user.id) {
        formData.append('user_id', user.id);
    }

    if (paymentId) formData.append('razorpay_payment_id', paymentId);
    if (orderId) formData.append('razorpay_order_id', orderId);

    try {
        const response = await fetch('api/book-session.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server error: ${response.status} ${response.statusText} - ${errorText}`);
        }

        const data = await response.json();

        if (data.success) {
            showPaymentSuccess();
        } else {
            showPaymentFailed('Booking failed: ' + (data.message || 'Please contact support'));
        }
    } catch (error) {
        showPaymentFailed(`Booking failed: ${error.message}. Please contact support.`);
    }
}

function showPaymentSuccess() {
    document.getElementById('prepayment-summary').classList.add('hidden');
    document.getElementById('payment-success').classList.remove('hidden');
    document.getElementById('payment-failed').classList.add('hidden');
}

function showPaymentFailed(message = '') {
    document.getElementById('prepayment-summary').classList.add('hidden');
    document.getElementById('payment-failed').classList.remove('hidden');
    document.getElementById('payment-success').classList.add('hidden');
    
    const failedMessage = document.querySelector('#payment-failed p');
    if (message) {
        failedMessage.textContent = message;
    } else {
        failedMessage.textContent = 'Unfortunately, we were unable to process your payment.';
    }
}

function retryPayment() {
    document.getElementById('payment-failed').classList.add('hidden');
    document.getElementById('prepayment-summary').classList.remove('hidden');
}
