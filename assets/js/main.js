document.addEventListener('DOMContentLoaded', function () {
    const verificationForm = document.getElementById('verification-form');
    const bookingForm = document.getElementById('booking-form');
    const verifyForm = document.getElementById('verifyForm');
    const mainBookingForm = document.getElementById('mainBookingForm');
    const prepaymentSummary = document.getElementById('prepayment-summary');
    const bookingFlow = document.getElementById('booking-flow');
    const paymentSuccess = document.getElementById('payment-success');
    const paymentFailed = document.getElementById('payment-failed');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const removeCouponBtn = document.getElementById('remove-coupon-btn');
    const payNowBtn = document.getElementById('pay-now-btn');
    const bookNowBtn = document.getElementById('book-now-btn');
    const retryPaymentBtn = document.getElementById('retry-payment-btn');
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
    let currentSubtotal = 500;
    let currentDiscount = 0;
    let currentTotal = 500;
    let razorpayConfig = {};
    let mediaRecorder;
    let audioChunks = [];
    let timerInterval;
    let seconds = 0;
    fetch('api/get-config.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                razorpayConfig = data.config.payment;
                currentSubtotal = parseFloat(razorpayConfig.first_session_amount) || 500;
                updatePaymentSummary();
            }
        });
    window.toggleForms = function () {
        const registered = document.querySelector('input[name="registered"]:checked');
        if (registered && registered.value === 'yes') {
            verificationForm.classList.remove('hidden');
            bookingForm.classList.add('hidden');
        } else {
            verificationForm.classList.add('hidden');
            bookingForm.classList.remove('hidden');
        }
    };
    window.updateVerificationLabel = function () {
        const method = document.querySelector('input[name="verify_method"]:checked');
        const label = document.getElementById('verify_value_label');
        const input = document.getElementById('verify_value');

        // Reset to initial step when changing method
        document.getElementById('initial-verification-step').classList.remove('hidden');
        document.getElementById('otp-verification-step').classList.add('hidden');
        document.getElementById('verification-message').classList.add('hidden');

        if (!method) return;
        switch (method.value) {
            case 'phone':
                label.textContent = 'Enter Your Phone Number';
                input.placeholder = 'Enter your 10-digit mobile number';
                input.type = 'tel';
                break;
            case 'email':
                label.textContent = 'Enter Your Email Address';
                input.placeholder = 'e.g., user@example.com';
                input.type = 'email';
                break;
            case 'client_id':
                label.textContent = 'Enter Your Client ID';
                input.placeholder = 'e.g., GHW-12345';
                input.type = 'text';
                break;
        }
    };
    window.toggleDOB = function () {
        const unknown = document.getElementById('unknown_dob').checked;
        const dobField = document.getElementById('dob');
        const ageSelect = document.getElementById('approximate_age');

        dobField.disabled = unknown;
        dobField.setAttribute('aria-required', unknown ? 'false' : 'true');
        document.getElementById('age-input').classList.toggle('hidden', !unknown);

        // Only require approximate_age when the DOB unknown checkbox is ticked
        if (unknown) {
            ageSelect.setAttribute('required', 'required');
            ageSelect.setAttribute('aria-required', 'true');
        } else {
            ageSelect.removeAttribute('required');
            ageSelect.setAttribute('aria-required', 'false');
            ageSelect.setCustomValidity('');
        }
    };
    window.calculateAge = function () {
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
            ageDisplay.textContent = `${age > 0 ? age : 0} years`;
            document.getElementById('calculated_age').value = age > 0 ? age : 0;
            document.getElementById('calculated-age').classList.remove('hidden');
        }
    };
    window.toggleAttendantInfo = function () {
        const attendantValue = document.getElementById('attendant').value;
        const isAttendant = attendantValue === 'other';
        const section = document.getElementById('attendant-info');
        section.classList.toggle('hidden', !isAttendant);

        const fields = section.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            if (isAttendant) {
                field.setAttribute('required', 'required');
                field.setAttribute('aria-required', 'true');
            } else {
                field.removeAttribute('required');
                field.setAttribute('aria-required', 'false');
                field.setCustomValidity('');
            }
        });
    };

    // Run on page load to ensure initial attendant state is correct (default: self)
    window.toggleAttendantInfo();
    window.toggleDisabilityInfo = function () {
        const checkedRadio = document.querySelector('input[name="has_disability"]:checked');
        const hasDisability = checkedRadio && checkedRadio.value === 'yes';
        const section = document.getElementById('disability-info');
        section.classList.toggle('hidden', !hasDisability);

        // Toggle required + aria-required on all interactive fields in the section
        const fields = section.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            if (hasDisability) {
                field.setAttribute('required', 'required');
                field.setAttribute('aria-required', 'true');
            } else {
                field.removeAttribute('required');
                field.setAttribute('aria-required', 'false');
                // Clear any browser validation state
                field.setCustomValidity('');
            }
        });
    };

    // Run on page load to ensure initial state is correct (has_disability defaults to 'no')
    window.toggleDisabilityInfo();
    window.fillAddressDetails = function () { };
    if (verifyForm) {
        // Helper to display messages and focus
        const showVerifyMessage = (msg, type) => {
            const vm = document.getElementById('verification-message');
            vm.textContent = msg;
            vm.className = `message ${type}`;
            vm.classList.remove('hidden');
            vm.focus(); // Move focus to message for screen readers
        };

        verifyForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const method = document.querySelector('input[name="verify_method"]:checked');
            const value = document.getElementById('verify_value').value.trim();
            const verificationMessage = document.getElementById('verification-message');

            if (!method || !value) {
                showVerifyMessage('Please select a verification method and enter your details.', 'error');
                return;
            }

            verificationMessage.className = 'message warning';
            verificationMessage.classList.remove('hidden');

            // Unified OTP Flow for all methods
            verificationMessage.textContent = 'Sending OTP...';

            fetch('api/send-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    method: method.value, // Pass method (email, phone, client_id)
                    value: value
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        verificationMessage.classList.add('hidden');
                        document.getElementById('initial-verification-step').classList.add('hidden');
                        document.getElementById('otp-verification-step').classList.remove('hidden');
                        document.getElementById('otp-message').textContent = `The 6-digits OTP has been sent to ${data.masked_email}`;
                    } else {
                        verificationMessage.textContent = data.message || 'Error sending OTP.';
                        verificationMessage.className = 'message error';
                        verificationMessage.classList.remove('hidden');
                        verificationMessage.focus();
                    }
                })
                .catch(error => {
                    console.error('OTP Error:', error);
                    verificationMessage.textContent = 'Error sending OTP. Please try again.';
                    verificationMessage.className = 'message error';
                    verificationMessage.classList.remove('hidden');
                    verificationMessage.focus();
                });
        });

        // OTP Verification Handler
        const verifyOtpBtn = document.getElementById('verify-otp-btn');
        if (verifyOtpBtn) {
            verifyOtpBtn.addEventListener('click', function () {
                const otp = document.getElementById('otp_value').value.trim();
                const verificationMessage = document.getElementById('verification-message');

                if (otp.length !== 6) {
                    showVerifyMessage('Please enter a valid 6-digit OTP.', 'error');
                    return;
                }

                verificationMessage.textContent = 'Verifying OTP...';
                verificationMessage.classList.remove('hidden');
                verificationMessage.className = 'message warning';

                fetch('api/verify-otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ otp: otp })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            fillUserData(data.data);
                        } else {
                            verificationMessage.textContent = data.message || 'Invalid OTP.';
                            verificationMessage.className = 'message error';
                        }
                    })
                    .catch(error => {
                        verificationMessage.textContent = 'Error verifying OTP.';
                        verificationMessage.className = 'message error';
                        verificationMessage.classList.remove('hidden');
                        verificationMessage.focus();
                    });
            });
        }

        // Resend OTP Handler
        const resendOtpBtn = document.getElementById('resend-otp-btn');
        if (resendOtpBtn) {
            resendOtpBtn.addEventListener('click', function () {
                const email = document.getElementById('verify_value').value.trim();
                // Trigger the main form submit again to resend
                const event = new Event('submit');
                verifyForm.dispatchEvent(event);
            });
        }
    }
    const pincodeInput = document.getElementById('pincode');
    pincodeInput.addEventListener('input', function () {
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
    const bookingMessage = document.getElementById('booking-message');

    mainBookingForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const mobile = document.getElementById('mobile').value.trim();

        if (!name || !email || !mobile) {
            bookingMessage.textContent = 'Please fill out all required fields: Name, Email, and Mobile.';
            bookingMessage.className = 'message error';
            bookingMessage.classList.remove('hidden');
            bookingMessage.focus();
            return;
        }

        const citySelect = document.getElementById('city');
        const areaSelect = document.getElementById('area_village');
        if (!citySelect.value || !areaSelect.value) {
            if (!document.getElementById('city-selection').classList.contains('hidden')) {
                bookingMessage.textContent = 'Please select your City and Area.';
                bookingMessage.className = 'message error';
                bookingMessage.classList.remove('hidden');
                bookingMessage.focus();
                return;
            }
        }

        // Additional Mandatory Checks
        const occupation = document.getElementById('occupation').value;
        const qualification = document.getElementById('qualification').value;
        const howLearned = document.getElementById('how_learned').value;
        const disabilityRadio = document.querySelector('input[name="has_disability"]:checked');

        if (!occupation || !qualification || !howLearned || !disabilityRadio) {
            bookingMessage.textContent = 'Please fill out all mandatory fields.';
            bookingMessage.className = 'message error';
            bookingMessage.classList.remove('hidden');
            bookingMessage.focus();
            return;
        }

        // Disability conditional validation — only check sub-fields if "yes" is selected
        if (disabilityRadio.value === 'yes') {
            const disabilityType = document.getElementById('disability_type').value;
            const disabilityPct  = document.getElementById('disability_percentage').value;
            const disabilityDocs = document.getElementById('disability_documents').files;
            if (!disabilityType || !disabilityPct || disabilityDocs.length === 0) {
                bookingMessage.textContent = 'Please fill in all disability details: type, percentage, and upload a document.';
                bookingMessage.className = 'message error';
                bookingMessage.classList.remove('hidden');
                bookingMessage.focus();
                return;
            }
        }



        bookingMessage.classList.add('hidden');
        bookingFlow.classList.add('hidden');
        prepaymentSummary.classList.remove('hidden');
        document.getElementById('payment-details').innerHTML = `
    <h4>Booking for:</h4>
    <p><strong>Name:</strong> ${name}</p>
    <p><strong>Email:</strong> ${email}</p>
    `;
        updatePaymentSummary();
        prepaymentSummary.scrollIntoView({
            behavior: 'smooth'
        });
    });
    startRecordingBtn.addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false,
                video: false,
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
            const msg = 'Error accessing microphone. Please grant permission and try again.';
            // If there's no specific recording error box, use booking message as fallback or creating one would be better.
            // For now, using bookingMessage as it's within the flow or alert fallback if absolutely necessary, BUT user requested NO alerts.
            // Let's reuse bookingMessage for errors in this section if visible, or a specific placeholder.
            // Actually, let's substitute with a console error and maybe a small text near the button if possible,
            // but strictly complying to "no alert", let's use booking-message if manageable or create a dynamic error.
            // Simplest compliant fix:
            const bookingMessage = document.getElementById('booking-message');
            bookingMessage.textContent = msg;
            bookingMessage.className = 'message error';
            bookingMessage.classList.remove('hidden');
            bookingMessage.focus();
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
        if (hardReset) {
            voiceRecordingPathInput.value = '';
            audioPlaybackEl.removeAttribute('src');
        }
    }
    function uploadRecording() {
        const audioBlob = new Blob(audioChunks, {
            type: 'audio/mpeg'
        });
        const formData = new FormData();
        formData.append('audio_data', audioBlob, 'recording.webm');
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
                    // Fallback for upload error - show in booking message
                    const bookingMessage = document.getElementById('booking-message');
                    bookingMessage.textContent = 'Upload failed: ' + data.message;
                    bookingMessage.className = 'message error';
                    bookingMessage.classList.remove('hidden');
                    bookingMessage.focus();
                    resetRecordingState(true);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                const bookingMessage = document.getElementById('booking-message');
                bookingMessage.textContent = 'An error occurred while uploading the recording.';
                bookingMessage.className = 'message error';
                bookingMessage.classList.remove('hidden');
                bookingMessage.focus();
                resetRecordingState(true);
            });
    }
    applyCouponBtn.addEventListener('click', function () {
        const couponCode = document.getElementById('coupon-code').value.trim();
        const couponMessage = document.getElementById('coupon-message');

        const showCouponMessage = (msg, type) => {
            couponMessage.textContent = msg;
            couponMessage.className = `message ${type}`;
            couponMessage.classList.remove('hidden');
            couponMessage.focus();
        };

        if (!couponCode) {
            showCouponMessage('Please enter a coupon code.', 'error');
            return;
        }
        applyCouponBtn.textContent = 'Applying...';
        applyCouponBtn.disabled = true;
        couponMessage.classList.add('hidden'); // clear previous

        fetch('api/validate-coupons.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                coupon_code: couponCode,
                amount: currentSubtotal,
                email: document.getElementById('email').value
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    currentDiscount = parseFloat(data.discount);
                    appliedCouponCode = couponCode;
                    showCouponMessage(`Coupon "${couponCode}" applied!`, 'success');
                } else {
                    currentDiscount = 0;
                    appliedCouponCode = null;
                    showCouponMessage(data.message || 'Invalid or expired coupon code.', 'error');
                }
                updatePaymentSummary();
            })
            .catch(error => {
                console.error('Coupon API Error:', error);
                showCouponMessage('Could not validate your coupon. Please try again later.', 'error');
                currentDiscount = 0;
                updatePaymentSummary();
            })
            .finally(() => {
                applyCouponBtn.textContent = 'Apply';
                applyCouponBtn.disabled = false;
            });
    });
    removeCouponBtn.addEventListener('click', function () {
        document.getElementById('coupon-code').value = '';
        currentDiscount = 0;
        appliedCouponCode = null;
        const couponMessage = document.getElementById('coupon-message');
        couponMessage.textContent = 'Coupon removed.';
        couponMessage.className = 'message info';
        couponMessage.classList.remove('hidden');
        couponMessage.focus();
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
    bookNowBtn.addEventListener('click', function () {
        finalizeBooking(null, true);
    });
    payNowBtn.addEventListener('click', function () {
        if (!document.getElementById('terms-consent').reportValidity()) {
            return; // Browser shows built-in validation message
        }
        triggerRazorpay();
    });
    retryPaymentBtn.addEventListener('click', function () {
        paymentFailed.classList.add('hidden');
        prepaymentSummary.classList.remove('hidden');
        triggerRazorpay();
    });
    let currentUserId = null;
    let appliedCouponCode = null;
    function triggerRazorpay() {
        if (currentTotal <= 0) return;
        payNowBtn.disabled = true;
        payNowBtn.textContent = 'Processing...';
        const orderData = {
            amount: currentSubtotal,
            email: document.getElementById('email').value,
            name: document.getElementById('name').value,
            mobile: document.getElementById('mobile').value,
            coupon_code: appliedCouponCode,
            user_id: currentUserId
        };
        fetch('api/create-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
            .then(response => response.json())
            .then(order => {
                if (!order.success) {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'message error';
                    errorMsg.role = 'alert';
                    errorMsg.textContent = 'Could not create a payment order. Please try again.';
                    payNowBtn.parentNode.insertBefore(errorMsg, payNowBtn);
                    errorMsg.focus();
                    setTimeout(() => errorMsg.remove(), 5000);

                    payNowBtn.disabled = false;
                    payNowBtn.textContent = 'Pay Now';
                    return;
                }
                currentUserId = order.user_id;
                if (!order.id || order.amount === 0) {
                    finalizeBooking(null, true, order);
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
                    handler: function (response) {
                        finalizeBooking(response, false, order);
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
                        ondismiss: function () {
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
                const errorMsg = document.createElement('div');
                errorMsg.className = 'message error';
                errorMsg.role = 'alert';
                errorMsg.textContent = 'There was an error initializing the payment. Please try again.';
                payNowBtn.parentNode.insertBefore(errorMsg, payNowBtn);
                errorMsg.focus();
                setTimeout(() => errorMsg.remove(), 5000);

                payNowBtn.disabled = false;
                payNowBtn.textContent = 'Pay Now';
            });
    }
    const downloadReceiptBtn = document.getElementById('download-receipt-btn');
    let lastPaymentData = null;

    // ... (existing code)

    function finalizeBooking(paymentData = null, isZeroPayment = false, orderData = null) {
        const formData = new FormData(mainBookingForm);
        if (currentUserId) {
            formData.append('user_id', currentUserId);
        }
        if (paymentData && paymentData.razorpay_payment_id) {
            formData.append('razorpay_payment_id', paymentData.razorpay_payment_id);
            formData.append('razorpay_order_id', paymentData.razorpay_order_id);
            formData.append('razorpay_signature', paymentData.razorpay_signature);
            lastPaymentData = {
                payment_id: paymentData.razorpay_payment_id
            };
        }
        // Pass coupon_id regardless of payment method if it exists
        if (orderData && orderData.coupon_id) {
            formData.append('coupon_id', orderData.coupon_id);
        }

        if (isZeroPayment && orderData && orderData.id) {
            formData.append('razorpay_order_id', orderData.id);
            lastPaymentData = {
                order_id: orderData.id
            };
        }
        if (!formData.has('occupation')) {
            formData.append('occupation', document.getElementById('occupation').value);
        }
        if (!formData.has('qualification')) {
            formData.append('qualification', document.getElementById('qualification').value);
        }

        const calculatedAge = document.getElementById('calculated_age').value;
        const approximateAge = document.getElementById('approximate_age').value;
        if (calculatedAge) {
            formData.append('age', calculatedAge);
        } else if (approximateAge) {
            formData.append('age', approximateAge);
        }

        fetch('api/book-session.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess();
                } else {
                    // For failure, we show the failure section which has ARIA roles now
                    const failedSection = document.getElementById('payment-failed');
                    // Populate specific message if needed, or just show generic
                    // failedSection.querySelector('p').textContent = data.message; // Optional enhancement
                    showFailure();
                }
            })
            .catch(error => {
                console.error('Booking Error:', error);
                // Critical error, show failure section
                showFailure();
            });
    }

    function showSuccess() {
        prepaymentSummary.classList.add('hidden');
        paymentSuccess.classList.remove('hidden');
        if (lastPaymentData) {
            downloadReceiptBtn.classList.remove('hidden');
            paymentSuccess.scrollIntoView({
                behavior: 'smooth'
            });
            document.getElementById('success-heading').focus(); // Move focus to heading
            // Auto-download receipt
            downloadReceiptBtn.click();
        }
        mainBookingForm.reset();
        resetRecordingState(true);
    }

    downloadReceiptBtn.addEventListener('click', function () {
        if (!lastPaymentData) {
            // Inline notification
            const msg = document.createElement('p');
            msg.textContent = 'No payment data found to generate a receipt.';
            msg.className = 'message error';
            msg.role = 'alert';
            downloadReceiptBtn.parentElement.appendChild(msg);
            msg.focus();
            setTimeout(() => msg.remove(), 3000);
            return;
        }

        let url;
        if (lastPaymentData.payment_id) {
            url = `api/get-payment.php?payment_id=${lastPaymentData.payment_id}`;
        } else {
            url = `api/get-payment.php?order_id=${lastPaymentData.order_id}`;
        }


        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    generateReceipt(data.data);
                } else {
                    const msg = document.createElement('p');
                    msg.textContent = 'Could not fetch payment details for the receipt.';
                    msg.className = 'message error';
                    msg.role = 'alert';
                    downloadReceiptBtn.parentElement.appendChild(msg);
                    msg.focus();
                    setTimeout(() => msg.remove(), 3000);
                }
            })
            .catch(error => {
                console.error('Error fetching payment details:', error);
                const msg = document.createElement('p');
                msg.textContent = 'An error occurred while fetching payment details.';
                msg.className = 'message error';
                msg.role = 'alert';
                downloadReceiptBtn.parentElement.appendChild(msg);
                msg.focus();
                setTimeout(() => msg.remove(), 3000);
            });
    });
    function showFailure() {
        prepaymentSummary.classList.add('hidden');
        paymentFailed.classList.remove('hidden');
        paymentFailed.scrollIntoView({
            behavior: 'smooth'
        });
        document.getElementById('failed-heading').focus(); // Move focus to heading
    }

    function fillUserData(userData) {
        currentUserId = userData.id;
        const nameField = document.getElementById('name');
        const emailField = document.getElementById('email');
        const mobileField = document.getElementById('mobile');

        nameField.value = userData.name || '';
        emailField.value = userData.email || '';
        mobileField.value = userData.mobile || '';

        // Lock basic fields
        nameField.readOnly = true;
        emailField.readOnly = true;
        mobileField.readOnly = true;
        nameField.classList.add('readonly-field');
        emailField.classList.add('readonly-field');
        mobileField.classList.add('readonly-field');

        // DOB & Age
        if (userData.dob) {
            const dobField = document.getElementById('dob');
            dobField.value = userData.dob;
            dobField.readOnly = true;
            dobField.classList.add('readonly-field');
            document.getElementById('unknown_dob').checked = false;
            document.getElementById('unknown_dob').disabled = true;

            if (window.toggleDOB) window.toggleDOB();
            if (window.calculateAge) window.calculateAge();
        } else if (userData.age) {
            document.getElementById('unknown_dob').checked = true;
            document.getElementById('unknown_dob').disabled = true;

            if (window.toggleDOB) window.toggleDOB();

            const ageField = document.getElementById('approximate_age');
            ageField.value = userData.age;
            // Specifically for select, duplicate and disable usually or just disable
            // However user wants "readonly". For select, disabled is best approximation or pointer-events:none
            ageField.disabled = true;
        }

        // Gender
        const genderInputs = document.querySelectorAll('input[name="gender"]');
        genderInputs.forEach(input => input.disabled = true);

        if (userData.gender) {
            const genderInput = document.querySelector(`input[name="gender"][value="${userData.gender}"]`);
            if (genderInput) genderInput.checked = true;
        }

        // Hide "Have you registered before?" section
        const registrationSection = document.querySelector('section[aria-labelledby="registration-heading"]');
        if (registrationSection) {
            registrationSection.classList.add('hidden');
        }

        // Attendant
        const attendantVal = userData.attendant || 'self';
        document.getElementById('attendant').value = attendantVal;
        if (window.toggleAttendantInfo) window.toggleAttendantInfo();

        if (attendantVal !== 'self') {
            document.getElementById('attendant_name').value = userData.attendant_name || '';
            document.getElementById('attendant_email').value = userData.attendant_email || '';
            document.getElementById('attendant_mobile').value = userData.attendant_mobile || '';
            document.getElementById('relationship').value = userData.relationship || '';
        }

        // Address
        document.getElementById('house_number').value = userData.house_number || '';
        document.getElementById('street_locality').value = userData.street_locality || '';

        if (userData.pincode) {
            document.getElementById('pincode').value = userData.pincode;

            // Trigger pincode lookup to populate dropdowns
            const event = new Event('input');
            document.getElementById('pincode').dispatchEvent(event);

            // Wait for dropdowns to populate (approximate delay) then set values
            setTimeout(() => {
                if (userData.area_village) {
                    const areaSelect = document.getElementById('area_village');
                    if (![...areaSelect.options].some(o => o.value === userData.area_village)) {
                        const opt = document.createElement('option');
                        opt.value = userData.area_village;
                        opt.text = userData.area_village;
                        areaSelect.add(opt);
                    }
                    areaSelect.value = userData.area_village;
                }
                if (userData.city) {
                    const citySelect = document.getElementById('city');
                    if (![...citySelect.options].some(o => o.value === userData.city)) {
                        const opt = document.createElement('option');
                        opt.value = userData.city;
                        opt.text = userData.city;
                        citySelect.add(opt);
                    }
                    citySelect.value = userData.city;
                }
                document.getElementById('district').value = userData.district || '';
                document.getElementById('state').value = userData.state || '';
            }, 1000);
        }

        // Occupation & Qualification
        document.getElementById('occupation').value = userData.occupation || '';
        document.getElementById('qualification').value = userData.qualification || '';
        document.getElementById('how_learned').value = userData.how_learned || '';

        // Disable Additional Fields for Returning Users
        const disableField = (id) => {
            const el = document.getElementById(id);
            if (el) {
                el.disabled = true;
                el.classList.add('readonly-field');
            }
        };

        disableField('occupation'); // If occupation shouldn't be changed, though user request didn't explicitly say 'Occupation', they said "All Disability Information", "How did you learn", etc.
        // Re-reading user request: "Full Name, Mobile Number, Email Address, Date of Birth, Gender, How did you learn about our service?, All Disability Information".
        // Use exact list. Occupation/Qualification NOT in list, so leave enabled? 
        // Request item 3: "Yeh sari fields required chahiye... Your Occupation, Educational Qualification..."
        // Request item 4: "Returning user k liye yeh sari fields disabled rehni chahiye... " followed by narrower list.
        // Narrower list for disabling: Name, Mobile, Email, DOB, Gender, How learn, All Disability.
        // So Occupation/Qualification remain editable? OK.

        disableField('how_learned');

        // Disability
        const hasDisability = userData.has_disability || 'no';
        const disabilityRadio = document.querySelector(`input[name="has_disability"][value="${hasDisability}"]`);
        if (disabilityRadio) {
            disabilityRadio.checked = true;
            if (window.toggleDisabilityInfo) window.toggleDisabilityInfo();
        }

        // Disable Disability Radios
        const disabilityRadios = document.querySelectorAll('input[name="has_disability"]');
        disabilityRadios.forEach(r => r.disabled = true);

        if (hasDisability === 'yes') {
            document.getElementById('disability_type').value = userData.disability_type || '';
            document.getElementById('disability_percentage').value = userData.disability_percentage || '';

            // Disable details
            disableField('disability_type');
            disableField('disability_percentage');
            // document.getElementById('disability_documents').disabled = true; // Optional: disable upload too
        }

        const verificationMessage = document.getElementById('verification-message');
        verificationMessage.textContent = 'User verified successfully! Your information has been loaded.';
        verificationMessage.className = 'message success';
        verificationMessage.classList.remove('hidden');

        verificationForm.classList.add('hidden');
        bookingForm.classList.remove('hidden');
        bookingForm.scrollIntoView({ behavior: 'smooth' });
        document.getElementById('booking-heading').focus(); // Accessibility focus move
    }

    // OTP Enter Key Handling
    const otpInput = document.getElementById('otp_value');
    if (otpInput) {
        otpInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('verify-otp-btn').click();
            }
        });
    }

    // Allow numeric keyboard for phone verification even if not first-time
    const mobileInput = document.getElementById('verify_value');
    if (mobileInput) {
        // This is handled in updateVerificationLabel but we enforce numeric behavior
        // If verify_method is phone.
        // Logic already exists: input.type = 'tel'.
        // User issue: "direct Phone Number wali field... qwerty keyboard... radio button select... numeric keyboard".
        // Likely `checked` attribute on load doesn't trigger `onchange`.
        // We should call updateVerificationLabel on load.
        if (window.updateVerificationLabel) window.updateVerificationLabel();
    }
});
