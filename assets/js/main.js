
document.addEventListener('DOMContentLoaded', function() {
    const verificationForm = document.getElementById('verification-form');
    const bookingForm = document.getElementById('booking-form');
    const verifyForm = document.getElementById('verifyForm');
    const mainBookingForm = document.getElementById('mainBookingForm');
    const prepaymentSummary = document.getElementById('prepayment-summary');
    const bookingFlow = document.getElementById('booking-flow');

    // --- Global Functions for inline HTML listeners ---

    // Toggles between verification and new booking forms
    window.toggleForms = function() {
        const registered = document.querySelector('input[name="registered"]:checked');
        if (registered && registered.value === 'yes') {
            verificationForm.classList.remove('hidden');
            verificationForm.setAttribute('aria-hidden', 'false');
            bookingForm.classList.add('hidden');
            bookingForm.setAttribute('aria-hidden', 'true');
        } else {
            verificationForm.classList.add('hidden');
            verificationForm.setAttribute('aria-hidden', 'true');
            bookingForm.classList.remove('hidden');
            bookingForm.setAttribute('aria-hidden', 'false');
        }
    };

    // Updates the placeholder for the verification input
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

    // Toggles between DOB input and approximate age dropdown
    window.toggleDOB = function() {
        const unknown = document.getElementById('unknown_dob').checked;
        const dobInput = document.getElementById('dob');
        const ageInputDiv = document.getElementById('age-input');
        const calculatedAgeDiv = document.getElementById('calculated-age');

        dobInput.disabled = unknown;
        if (unknown) {
            dobInput.value = ''; // Clear DOB value
            ageInputDiv.classList.remove('hidden');
            calculatedAgeDiv.classList.add('hidden');
        } else {
            ageInputDiv.classList.add('hidden');
        }
    };

    // Calculates and displays age from DOB
    window.calculateAge = function() {
        const dob = document.getElementById('dob').value;
        const calculatedAgeDiv = document.getElementById('calculated-age');
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
            calculatedAgeDiv.classList.remove('hidden');
        } else {
            calculatedAgeDiv.classList.add('hidden');
        }
    };

    // Toggles visibility of attendant information fields
    window.toggleAttendantInfo = function() {
        const attendant = document.getElementById('attendant').value;
        const attendantInfo = document.getElementById('attendant-info');
        if (attendant === 'other') {
            attendantInfo.classList.remove('hidden');
            attendantInfo.setAttribute('aria-hidden', 'false');
        } else {
            attendantInfo.classList.add('hidden');
            attendantInfo.setAttribute('aria-hidden', 'true');
        }
    };

    // Toggles visibility of disability information fields
    window.toggleDisabilityInfo = function() {
        const hasDisability = document.querySelector('input[name="has_disability"]:checked').value;
        const disabilityInfo = document.getElementById('disability-info');
        if (hasDisability === 'yes') {
            disabilityInfo.classList.remove('hidden');
            disabilityInfo.setAttribute('aria-hidden', 'false');
        } else {
            disabilityInfo.classList.add('hidden');
            disabilityInfo.setAttribute('aria-hidden', 'true');
        }
    };

    // Fills address details (currently a placeholder)
    window.fillAddressDetails = function() {
        const selectedCity = document.getElementById('city').value;
        // Future logic can go here if needed
    };

    // --- Event Listeners for Form Elements ---

    // PIN Code Lookup
    const pincodeInput = document.getElementById('pincode');
    const pincodeStatus = document.getElementById('pincode-status');

    pincodeInput.addEventListener('input', function() {
        const pincode = pincodeInput.value.trim();
        if (pincode.length !== 6) {
            pincodeStatus.textContent = '';
            return;
        }

        pincodeStatus.textContent = 'Looking up PIN code...';
        pincodeStatus.style.color = '#555';

        fetch(`https://api.postalpincode.in/pincode/${pincode}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && data[0].Status === 'Success') {
                    pincodeStatus.textContent = 'PIN code is valid.';
                    pincodeStatus.style.color = 'green';
                    const postOffice = data[0].PostOffice[0];
                    document.getElementById('district').value = postOffice.District;
                    document.getElementById('state').value = postOffice.State; // Corrected this line
                    populateLocationDropdowns(data[0].PostOffice);
                } else {
                    pincodeStatus.textContent = 'Invalid PIN code. Please check and try again.';
                    pincodeStatus.style.color = 'red';
                }
            })
            .catch(error => {
                pincodeStatus.textContent = 'Could not fetch PIN code details. Please check your connection.';
                pincodeStatus.style.color = 'red';
                console.error("PIN code fetch error:", error);
            });
    });

    function populateLocationDropdowns(postOffices) {
        const areaSelect = document.getElementById('area_village');
        const citySelect = document.getElementById('city');
        
        // Clear previous options but keep the placeholder
        areaSelect.innerHTML = '<option value="">Select area/village</option>';
        citySelect.innerHTML = '<option value="">Select city</option>';

        const cities = new Set();
        postOffices.forEach(po => {
            const areaOption = document.createElement('option');
            areaOption.value = po.Name;
            areaOption.textContent = po.Name;
            areaSelect.appendChild(areaOption);
            if(po.Block && po.Block !== "NA") {
                cities.add(po.Block);
            }
        });

        cities.forEach(city => {
            const cityOption = document.createElement('option');
            cityOption.value = city;
            cityOption.textContent = city;
            citySelect.appendChild(cityOption);
        });

        document.getElementById('area-selection').classList.remove('hidden');
        document.getElementById('city-selection').classList.remove('hidden');
    }

    // --- Form Submission Handling ---
    
    // Handle the main booking form submission
    mainBookingForm.addEventListener('submit', function(event) {
        event.preventDefault(); // PREVENT PAGE RELOAD
        
        // Basic validation can be added here if needed
        // For now, we assume native HTML5 validation is sufficient

        console.log('Form submitted, proceeding to payment summary.');

        // Hide the main booking flow
        bookingFlow.classList.add('hidden');
        bookingFlow.setAttribute('aria-hidden', 'true');

        // Show the pre-payment summary
        prepaymentSummary.classList.remove('hidden');
        prepaymentSummary.setAttribute('aria-hidden', 'false');

        // Populate summary (example)
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const mobile = document.getElementById('mobile').value;

        document.getElementById('payment-details').innerHTML = `
            <h4>Booking for:</h4>
            <p><strong>Name:</strong> ${name}</p>
            <p><strong>Email:</strong> ${email}</p>
            <p><strong>Mobile:</strong> ${mobile}</p>
        `;
        // Dummy pricing
        document.getElementById('subtotal').textContent = '500';
        document.getElementById('total-amount').textContent = '500';
        
        // Scroll to the top of the summary
        prepaymentSummary.scrollIntoView({ behavior: 'smooth' });
    });

    // You can add a similar handler for the verifyForm if it needs to do something via JS
    verifyForm.addEventListener('submit', function(event) {
        event.preventDefault();
        // Add verification logic here, e.g., an AJAX call to your backend
        const messageDiv = document.getElementById('verification-message');
        messageDiv.textContent = 'Verification feature not yet implemented.';
        messageDiv.classList.remove('hidden');
        messageDiv.style.color = 'blue';
    });
    
    // --- Other functionalities like voice recording can be re-added here if needed ---
    // (Keeping the core form logic clean for now)
});
