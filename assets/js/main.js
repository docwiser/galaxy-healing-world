
document.addEventListener('DOMContentLoaded', function() {
    // --- Element References ---
    const verificationForm = document.getElementById('verification-form');
    const bookingForm = document.getElementById('booking-form');
    const verifyForm = document.getElementById('verifyForm');
    const mainBookingForm = document.getElementById('mainBookingForm');
    const prepaymentSummary = document.getElementById('prepayment-summary');
    const bookingFlow = document.getElementById('booking-flow');
    const paymentSuccess = document.getElementById('payment-success');
    const paymentFailed = document.getElementById('payment-failed');

    // Payment & Coupon Buttons
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const removeCouponBtn = document.getElementById('remove-coupon-btn');
    const payNowBtn = document.getElementById('pay-now-btn');
    const bookNowBtn = document.getElementById('book-now-btn');
    const retryPaymentBtn = document.getElementById('retry-payment-btn');

    // Audio Recording Elements
    const startRecordingBtn = document.getElementById('startRecording');
    const recordingActiveEl = document.getElementById('recording-active');
    const recordingTimerEl = document.getElementById('recording-timer');
    const pauseRecordingBtn = document.getElementById('pauseRecording');
    const cancelRecordingBtn = document.getElementById('cancelRecording');
    const stopRecordingBtn = document.getElementById('stopRecording');
    const recordingCompleteEl = document.getElementById('recording-complete');
    const audioPlaybackEl = document.getElementById('audioPlayback');
    const discardRecordingBtn = document.getElementById('discardRecording');
    const voiceRecordingPathInput = document.getElementById('voice_recording_path');


    // --- State Management ---
    let currentSubtotal = 500;
    let currentDiscount = 0;
    let currentTotal = 500;
    let razorpayConfig = {};
    let mediaRecorder;
    let audioChunks = [];
    let timerInterval;
    let seconds = 0;


    // --- Initial Data Fetch ---
    fetch('api/get-config.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                razorpayConfig = data.config.payment;
                currentSubtotal = parseFloat(razorpayConfig.first_session_amount) || 500;
                updatePaymentSummary();
            }
        });


    // --- Global Functions for inline HTML listeners ---
    window.toggleForms = function() {
        const registered = document.querySelector('input[name="registered"]:checked');
        if (registered && registered.value === 'yes') {
            verificationForm.classList.remove('hidden');
            bookingForm.classList.add('hidden');
        } else {
            verificationForm.classList.add('hidden');
            bookingForm.classList.remove('hidden');
        }
    };

    window.updateVerificationLabel = function() {
        const method = document.querySelector('input[name="verify_method"]:checked');
        const label = document.getElementById('verify_value_label');
        const input = document.getElementById('verify_value');
        if (!method) return;

        switch (method.value) {
            case 'phone':
                label.textContent = 'Enter Your Phone Number';
                input.placeholder = 'Enter your 10-digit mobile number';
                break;
            case 'email':
                label.textContent = 'Enter Your Email Address';
                input.placeholder = 'e.g., user@example.com';
                break;
            case 'client_id':
                label.textContent = 'Enter Your Client ID';
                input.placeholder = 'e.g., GHW-12345';
                break;
        }
    };

    window.toggleDOB = function() {
        const unknown = document.getElementById('unknown_dob').checked;
        document.getElementById('dob').disabled = unknown;
        document.getElementById('age-input').classList.toggle('hidden', !unknown);
        if (unknown) document.getElementById('calculated-age').classList.add('hidden');
    };

    window.calculateAge = function() {
        const dob = document.getElementById('dob').value;
        const ageDisplay = document.getElementById('age-display');
        if (dob) {
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            ageDisplay.textContent = age > 0 ? age : 0;
            document.getElementById('calculated-age').classList.remove('hidden');
        }
    };

    window.toggleAttendantInfo = function() {
        document.getElementById('attendant-info').classList.toggle('hidden', document.getElementById('attendant').value !== 'other');
    };

    window.toggleDisabilityInfo = function() {
        const hasDisability = document.querySelector('input[name="has_disability"]:checked').value;
        document.getElementById('disability-info').classList.toggle('hidden', hasDisability !== 'yes');
    };

    window.fillAddressDetails = function() {};

    // --- Event Listeners ---
    const pincodeInput = document.getElementById('pincode');
    pincodeInput.addEventListener('input', function() {
        const pincode = this.value.trim();
        const status = document.getElementById('pincode-status');
        if (pincode.length !== 6) {
            status.textContent = '';
            return;
        }
        status.textContent = 'Looking up PIN code...';
        fetch(`https://api.postalpincode.in/pincode/${pincode}`)
            .then(response => response.json())
            .then(data => {
                if (data && data[0].Status === 'Success') {
                    status.textContent = 'PIN code is valid.';
                    status.style.color = 'green';
                    const postOffice = data[0].PostOffice[0];
                    document.getElementById('district').value = postOffice.District;
                    document.getElementById('state').value = postOffice.State;
                    populateLocationDropdowns(data[0].PostOffice);
                } else {
                    status.textContent = 'Invalid PIN code.';
                    status.style.color = 'red';
                }
            }).catch(() => {
                status.textContent = 'Could not fetch PIN code details.';
                status.style.color = 'red';
            });
    });

    function populateLocationDropdowns(postOffices) {
        const areaSelect = document.getElementById('area_village');
        const citySelect = document.getElementById('city');
        areaSelect.innerHTML = '<option value="">Select area/village</option>';
        citySelect.innerHTML = '<option value="">Select city</option>';
        const cities = new Set();
        postOffices.forEach(po => {
            areaSelect.innerHTML += `<option value="${po.Name}">${po.Name}</option>`;
            if (po.Block && po.Block !== "NA") cities.add(po.Block);
        });
        cities.forEach(city => {
            citySelect.innerHTML += `<option value="${city}">${city}</option>`;
        });
        document.getElementById('area-selection').classList.remove('hidden');
        document.getElementById('city-selection').classList.remove('hidden');
    }

    mainBookingForm.addEventListener('submit', function(event) {
        event.preventDefault();
        bookingFlow.classList.add('hidden');
        prepaymentSummary.classList.remove('hidden');
        document.getElementById('payment-details').innerHTML = `
            <h4>Booking for:</h4>
            <p><strong>Name:</strong> ${document.getElementById('name').value}</p>
            <p><strong>Email:</strong> ${document.getElementById('email').value}</p>
        `;
        updatePaymentSummary();
        prepaymentSummary.scrollIntoView({
            behavior: 'smooth'
        });
    });

    // --- Audio Recording Logic ---

    startRecordingBtn.addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };
            mediaRecorder.onstop = uploadRecording;
            mediaRecorder.start();

            startRecordingBtn.classList.add('hidden');
            recordingActiveEl.classList.remove('hidden');
            startTimer();

        } catch (err) {
            alert("Error accessing microphone. Please grant permission and try again.");
            console.error("getUserMedia error:", err);
        }
    });

    stopRecordingBtn.addEventListener('click', () => {
        mediaRecorder.stop();
        stopTimer();
    });
    
    cancelRecordingBtn.addEventListener('click', () => {
        mediaRecorder.stop();
        resetRecordingState(true); // Hard reset
    });

    discardRecordingBtn.addEventListener('click', () => {
        resetRecordingState(true);
    });

    pauseRecordingBtn.addEventListener('click', () => {
        if (mediaRecorder.state === 'recording') {
            mediaRecorder.pause();
            pauseRecordingBtn.textContent = 'Resume';
            stopTimer();
        } else {
            mediaRecorder.resume();
            pauseRecordingBtn.textContent = 'Pause';
            startTimer();
        }
    });


    function startTimer() {
        timerInterval = setInterval(() => {
            seconds++;
            const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            recordingTimerEl.textContent = `${minutes}:${secs}`;
            if (seconds >= 60) {
                stopRecordingBtn.click();
            }
        }, 1000);
    }

    function stopTimer() {
        clearInterval(timerInterval);
    }

    function resetRecordingState(hardReset = false) {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        mediaRecorder = null;
        audioChunks = [];
        stopTimer();
        seconds = 0;
        recordingTimerEl.textContent = '00:00';
        
        recordingActiveEl.classList.add('hidden');
        recordingCompleteEl.classList.add('hidden');
        startRecordingBtn.classList.remove('hidden');

        if(hardReset) {
            voiceRecordingPathInput.value = '';
            audioPlaybackEl.removeAttribute('src');
        }
    }

    function uploadRecording() {
        const audioBlob = new Blob(audioChunks, {
            type: 'audio/webm'
        });
        const formData = new FormData();
        formData.append('audio_data', audioBlob, 'recording.webm');
        
        // Show uploading status
        recordingTimerEl.textContent = 'Uploading...';

        fetch('api/upload-audio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.filepath) {
                    voiceRecordingPathInput.value = data.filepath;
                    audioPlaybackEl.src = URL.createObjectURL(audioBlob);
                    recordingActiveEl.classList.add('hidden');
                    recordingCompleteEl.classList.remove('hidden');
                } else {
                    alert('Upload failed: ' + data.message);
                    resetRecordingState(true);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('An error occurred while uploading the recording.');
                resetRecordingState(true);
            });
    }


    // --- Payment & Coupon Logic ---
    applyCouponBtn.addEventListener('click', function() {
        const couponCode = document.getElementById('coupon-code').value.trim();
        if (!couponCode) {
            alert('Please enter a coupon code.');
            return;
        }

        applyCouponBtn.textContent = 'Applying...';
        applyCouponBtn.disabled = true;

        fetch('api/validate-coupons.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    coupon_code: couponCode,
                    amount: currentSubtotal
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    currentDiscount = parseFloat(data.discount);
                    alert(`Coupon "${couponCode}" applied!`);
                } else {
                    currentDiscount = 0;
                    alert(data.message || 'Invalid or expired coupon code.');
                }
                updatePaymentSummary();
            })
            .catch(error => {
                console.error('Coupon API Error:', error);
                alert('Could not validate your coupon. Please try again later.');
                currentDiscount = 0;
                updatePaymentSummary();
            })
            .finally(() => {
                applyCouponBtn.textContent = 'Apply';
                applyCouponBtn.disabled = false;
            });
    });

    removeCouponBtn.addEventListener('click', function() {
        document.getElementById('coupon-code').value = '';
        currentDiscount = 0;
        alert('Coupon removed.');
        updatePaymentSummary();
    });

    function updatePaymentSummary() {
        currentTotal = Math.max(0, currentSubtotal - currentDiscount);
        document.getElementById('subtotal').textContent = `₹${currentSubtotal.toFixed(2)}`;
        document.getElementById('discount').textContent = `₹${currentDiscount.toFixed(2)}`;
        document.getElementById('total-amount').textContent = `₹${currentTotal.toFixed(2)}`;
        removeCouponBtn.classList.toggle('hidden', currentDiscount === 0);
        applyCouponBtn.classList.toggle('hidden', currentDiscount > 0);
        payNowBtn.classList.toggle('hidden', currentTotal <= 0);
        bookNowBtn.classList.toggle('hidden', currentTotal > 0);
    }

    bookNowBtn.addEventListener('click', function() {
        finalizeBooking();
    });

    payNowBtn.addEventListener('click', function() {
        triggerRazorpay();
    });

    retryPaymentBtn.addEventListener('click', function() {
        paymentFailed.classList.add('hidden');
        prepaymentSummary.classList.remove('hidden');
        triggerRazorpay();
    });

    function triggerRazorpay() {
        if (currentTotal <= 0) return;

        payNowBtn.disabled = true;
        payNowBtn.textContent = 'Processing...';

<<<<<<< HEAD
        const options = {
            key: "rzp_live_RW2PNj1n17fMew", 
            amount: currentTotal * 100,
            currency: "INR",
            name: "Galaxy Healing World",
            description: "Therapy Session Booking",
            image: "https://www.galaxyhealingworld.com/assets/images/logo.png",
            handler: function (response){
                console.log('Payment successful:', response);
                payNowBtn.disabled = false;
                payNowBtn.textContent = 'Pay Now';
                showSuccess();
            },
            prefill: {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                contact: document.getElementById('mobile').value
            },
            theme: {
                color: "#3399cc"
            },
            modal: {
                ondismiss: function(){
                    console.log('Payment modal dismissed.');
=======
        const formData = new FormData();
        formData.append('amount', currentTotal);
        formData.append('email', document.getElementById('email').value);
        
        fetch('api/create-order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(order => {
                if (!order.success) {
                    alert('Could not create a payment order. Please try again.');
>>>>>>> 3a363eaccf806f51d785b4cbcdf6e3f590f28906
                    payNowBtn.disabled = false;
                    payNowBtn.textContent = 'Pay Now';
                    return;
                }

                const options = {
                    key: razorpayConfig.razorpay_key_id,
                    amount: order.amount,
                    currency: "INR",
                    name: "Galaxy Healing World",
                    description: "Therapy Session Booking",
                    image: "https://www.galaxyhealingworld.com/assets/images/logo.png",
                    order_id: order.id,
                    handler: function(response) {
                        finalizeBooking(response);
                    },
                    prefill: {
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        contact: document.getElementById('mobile').value
                    },
                    theme: {
                        color: "#3399cc"
                    },
                    modal: {
                        ondismiss: function() {
                            console.log('Payment modal dismissed.');
                            payNowBtn.disabled = false;
                            payNowBtn.textContent = 'Pay Now';
                            showFailure();
                        }
                    }
                };
                const rzp = new Razorpay(options);
                rzp.open();
            })
            .catch(() => {
                alert('There was an error initializing the payment. Please try again.');
                payNowBtn.disabled = false;
                payNowBtn.textContent = 'Pay Now';
            });
    }

    function finalizeBooking(paymentData = {}) {
        const formData = new FormData(mainBookingForm);

        if (paymentData.razorpay_payment_id) {
            formData.append('razorpay_payment_id', paymentData.razorpay_payment_id);
            formData.append('razorpay_order_id', paymentData.razorpay_order_id);
            formData.append('razorpay_signature', paymentData.razorpay_signature);
        }

        formData.append('occupation', document.getElementById('occupation').value);
        formData.append('qualification', document.getElementById('qualification').value);


        fetch('api/book-session.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess();
                } else {
                    alert('Booking failed: ' + data.message);
                    showFailure();
                }
            })
            .catch(error => {
                console.error('Booking Error:', error);
                alert('A critical error occurred during booking. Please contact support.');
                showFailure();
            });
    }

    function showSuccess() {
        prepaymentSummary.classList.add('hidden');
        paymentSuccess.classList.remove('hidden');
        paymentSuccess.scrollIntoView({
            behavior: 'smooth'
        });
        // Reset the form and audio state for the next booking
        mainBookingForm.reset();
        resetRecordingState(true); 
    }

    function showFailure() {
        prepaymentSummary.classList.add('hidden');
        paymentFailed.classList.remove('hidden');
        paymentFailed.scrollIntoView({
            behavior: 'smooth'
        });
    }
});
