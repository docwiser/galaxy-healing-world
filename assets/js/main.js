
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

    // --- State Management ---
    let currentSubtotal = 500; // Example subtotal
    let currentDiscount = 0;
    let currentTotal = 500;

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
        if(unknown) document.getElementById('calculated-age').classList.add('hidden');
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
        prepaymentSummary.scrollIntoView({ behavior: 'smooth' });
    });

    // --- Payment & Coupon Logic ---

    applyCouponBtn.addEventListener('click', function() {
        const couponCode = document.getElementById('coupon-code').value.trim();
        if (!couponCode) {
            alert('Please enter a coupon code.');
            return;
        }

        const couponApiUrl = 'api/validate-coupons.php';

        applyCouponBtn.textContent = 'Applying...';
        applyCouponBtn.disabled = true;

        fetch(couponApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ coupon_code: couponCode, amount: currentSubtotal })
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
        showSuccess();
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

        const options = {
            key: "YOUR_KEY_ID", 
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
                    payNowBtn.disabled = false;
                    payNowBtn.textContent = 'Pay Now';
                    showFailure();
                }
            }
        };
        
        const rzp = new Razorpay(options);
        rzp.open();
    }
    
    function showSuccess() {
        prepaymentSummary.classList.add('hidden');
        paymentSuccess.classList.remove('hidden');
        paymentSuccess.scrollIntoView({ behavior: 'smooth' });
    }

    function showFailure() {
        prepaymentSummary.classList.add('hidden');
        paymentFailed.classList.remove('hidden');
        paymentFailed.scrollIntoView({ behavior: 'smooth' });
    }
});
