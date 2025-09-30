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
    document.getElementById('state').value = data.state || '';
    document.getElementById('district').value = data.district || '';
    document.getElementById('address').value = data.address || '';
    document.getElementById('how_learned').value = data.how_learned || '';
    
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
document.getElementById('mainBookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const messageDiv = document.getElementById('booking-message');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Booking...';
    
    try {
        const response = await fetch('api/book-session.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(messageDiv, 'Session booked successfully! You will receive an email with payment details shortly.', 'success');
            e.target.reset();
            
            // Hide all conditional sections
            document.getElementById('attendant-info').classList.add('hidden');
            document.getElementById('disability-info').classList.add('hidden');
            document.getElementById('age-input').classList.add('hidden');
            document.getElementById('calculated-age').classList.add('hidden');
        } else {
            showMessage(messageDiv, result.message || 'Booking failed. Please try again.', 'error');
        }
    } catch (error) {
        showMessage(messageDiv, 'Connection error. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Book Session';
    }
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

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners
    document.getElementById('dob').addEventListener('change', calculateAge);
    
    // Initialize client ID generation for new users
    if (!isVerified) {
        generateClientId();
    }
    
    // Initialize verification label
    updateVerificationLabel();
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