<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galaxy Healing World - Book Your Therapy Session</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <h1>Galaxy Healing World</h1>
                <p>Your journey to healing starts here</p>
            </div>
        </header>

        <main class="main-content">
            <div class="welcome-section">
                <h2>Book Your Therapy Session</h2>
                <p>Experience personalized healing therapies tailored to your needs</p>
            </div>

            <div class="registration-check">
                <div class="form-card">
                    <fieldset>
                        <legend>Have you registered with us before?</legend>
                        <div class="radio-group" role="radiogroup" aria-labelledby="registration-legend">
                            <label class="radio-label">
                                <input type="radio" name="registered" value="yes" onchange="toggleForms()" aria-describedby="yes-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                Yes, I have registered before
                                <span id="yes-help" class="sr-only">Select this if you have previously registered with us</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="registered" value="no" onchange="toggleForms()" aria-describedby="no-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                No, this is my first time
                                <span id="no-help" class="sr-only">Select this if this is your first time registering</span>
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>

            <!-- Verification Form -->
            <div id="verification-form" class="form-card hidden">
                <h3>Verify Your Information</h3>
                <form id="verifyForm">
                    <fieldset>
                        <legend>Select verification method:</legend>
                        <div class="radio-group" role="radiogroup" aria-labelledby="verify-legend">
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="phone" aria-describedby="phone-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                Phone Number
                                <span id="phone-help" class="sr-only">Verify using your registered phone number</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="email" aria-describedby="email-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                Email Address
                                <span id="email-help" class="sr-only">Verify using your registered email address</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="client_id" aria-describedby="client-id-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                Client ID
                                <span id="client-id-help" class="sr-only">Verify using your unique client ID</span>
                            </label>
                        </div>
                    </fieldset>
                    <div class="form-group">
                        <input type="text" id="verify_value" name="verify_value" placeholder="Enter your verification details" required aria-describedby="verify-instructions">
                        <div id="verify-instructions" class="sr-only">Enter the information corresponding to your selected verification method</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </form>
                <div id="verification-message" class="message hidden" role="alert" aria-live="polite"></div>
            </div>

            <!-- Main Booking Form -->
            <div id="booking-form" class="form-card hidden">
                <h3>Book Your Therapy Session</h3>
                <form id="mainBookingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required" aria-label="required">*</span></label>
                            <input type="text" id="name" name="name" required aria-describedby="name-help">
                            <div id="name-help" class="sr-only">Enter your full legal name</div>
                        </div>
                        <div class="form-group">
                            <label for="mobile">Mobile Number <span class="required" aria-label="required">*</span></label>
                            <input type="tel" id="mobile" name="mobile" required aria-describedby="mobile-help">
                            <div id="mobile-help" class="sr-only">Enter your 10-digit mobile number</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="required" aria-label="required">*</span></label>
                            <input type="email" id="email" name="email" required aria-describedby="email-booking-help">
                            <div id="email-booking-help" class="sr-only">Enter a valid email address for booking confirmations</div>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" onchange="calculateAge()" aria-describedby="dob-help">
                            <div id="dob-help" class="sr-only">Select your date of birth or check the option below if unknown</div>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="unknown_dob" onchange="toggleDOB()" aria-describedby="unknown-dob-help">
                                    <span class="checkbox-custom" aria-hidden="true"></span>
                                    I don't know my date of birth
                                    <span id="unknown-dob-help" class="sr-only">Check this if you don't know your exact date of birth</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="age-input" class="form-group hidden">
                        <label for="approximate_age">Approximate Age</label>
                        <select id="approximate_age" name="approximate_age" aria-describedby="age-help">
                            <option value="">Select approximate age</option>
                            <?php for($i = 1; $i <= 100; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> years</option>
                            <?php endfor; ?>
                        </select>
                        <div id="age-help" class="sr-only">Select your approximate age in years</div>
                    </div>

                    <div id="calculated-age" class="info-display hidden" role="status" aria-live="polite">
                        <span class="label">Age:</span>
                        <span id="age-display">-</span>
                    </div>

                    <!-- Attendant Information -->
                    <div class="form-group">
                        <label for="attendant">Who is filling this form?</label>
                        <select id="attendant" name="attendant" onchange="toggleAttendantInfo()" aria-describedby="attendant-help">
                            <option value="self">Patient (Self)</option>
                            <option value="other">Attendant (Someone else)</option>
                        </select>
                        <div id="attendant-help" class="sr-only">Select who is filling out this form</div>
                    </div>

                    <div id="attendant-info" class="attendant-section hidden">
                        <h4>Attendant Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendant_name">Attendant Name</label>
                                <input type="text" id="attendant_name" name="attendant_name" aria-describedby="attendant-name-help">
                                <div id="attendant-name-help" class="sr-only">Enter the full name of the person filling this form</div>
                            </div>
                            <div class="form-group">
                                <label for="attendant_email">Attendant Email</label>
                                <input type="email" id="attendant_email" name="attendant_email" aria-describedby="attendant-email-help">
                                <div id="attendant-email-help" class="sr-only">Enter the email address of the attendant</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendant_mobile">Attendant Mobile</label>
                                <input type="tel" id="attendant_mobile" name="attendant_mobile" aria-describedby="attendant-mobile-help">
                                <div id="attendant-mobile-help" class="sr-only">Enter the mobile number of the attendant</div>
                            </div>
                            <div class="form-group">
                                <label for="relationship">Relationship to Patient</label>
                                <input type="text" id="relationship" name="relationship" placeholder="e.g., Mother, Father, Friend" aria-describedby="relationship-help">
                                <div id="relationship-help" class="sr-only">Describe your relationship to the patient</div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="address-section">
                        <h4>Address Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State <span class="required" aria-label="required">*</span></label>
                                <input type="text" id="state" name="state" required aria-describedby="state-help">
                                <div id="state-help" class="sr-only">Enter your state or province</div>
                            </div>
                            <div class="form-group">
                                <label for="district">District <span class="required" aria-label="required">*</span></label>
                                <input type="text" id="district" name="district" required aria-describedby="district-help">
                                <div id="district-help" class="sr-only">Enter your district or county</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address">Complete Address <span class="required" aria-label="required">*</span></label>
                            <textarea id="address" name="address" rows="3" required aria-describedby="address-help"></textarea>
                            <div id="address-help" class="sr-only">Enter your complete postal address</div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="form-group">
                        <label for="how_learned">How did you learn about our service?</label>
                        <select id="how_learned" name="how_learned" aria-describedby="how-learned-help">
                            <option value="">Select an option</option>
                            <option value="google">Google Search</option>
                            <option value="social_media">Social Media</option>
                            <option value="friend_family">Friend/Family</option>
                            <option value="advertisement">Advertisement</option>
                            <option value="other">Other</option>
                        </select>
                        <div id="how-learned-help" class="sr-only">Select how you discovered our therapy services</div>
                    </div>

                    <!-- Disability Information -->
                    <fieldset>
                        <legend>Disability Information</legend>
                        <div class="radio-group" role="radiogroup" aria-labelledby="disability-legend">
                        <label class="radio-label">
                                <input type="radio" name="has_disability" value="no" onchange="toggleDisabilityInfo()" checked aria-describedby="no-disability-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                No
                                <span id="no-disability-help" class="sr-only">Select if you do not have any disability</span>
                        </label>
                        <label class="radio-label">
                                <input type="radio" name="has_disability" value="yes" onchange="toggleDisabilityInfo()" aria-describedby="yes-disability-help">
                                <span class="radio-custom" aria-hidden="true"></span>
                                Yes
                                <span id="yes-disability-help" class="sr-only">Select if you have a disability that may affect your therapy</span>
                        </label>
                    </div>
                    </fieldset>

                    <div id="disability-info" class="disability-section hidden">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="disability_type">Type of Disability</label>
                                <input type="text" id="disability_type" name="disability_type" aria-describedby="disability-type-help">
                                <div id="disability-type-help" class="sr-only">Describe the type of disability</div>
                            </div>
                            <div class="form-group">
                                <label for="disability_percentage">Percentage of Disability</label>
                                <input type="number" id="disability_percentage" name="disability_percentage" min="0" max="100" aria-describedby="disability-percentage-help">
                                <div id="disability-percentage-help" class="sr-only">Enter the percentage of disability as assessed by medical professionals</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="disability_documents">Upload Disability Documents</label>
                            <input type="file" id="disability_documents" name="disability_documents" multiple accept=".pdf,.jpg,.jpeg,.png" aria-describedby="disability-docs-help">
                            <div id="disability-docs-help" class="sr-only">Upload documents related to your disability assessment. Accepted formats: PDF, JPG, JPEG, PNG</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Book Session</button>
                </form>
                <div id="booking-message" class="message hidden" role="alert" aria-live="polite"></div>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>