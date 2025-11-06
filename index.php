
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
        <header>
            <h1>Galaxy Healing World</h1>
            <p>Your journey to healing starts here</p>
        </header>

        <main>
            <!-- Main Booking Flow -->
            <div id="booking-flow">
                <section aria-labelledby="welcome-heading">
                    <h2 id="welcome-heading">Book Your Therapy Session</h2>
                    <p>Experience personalized healing therapies tailored to your needs</p>
                </section>

                <section class="form-card" aria-labelledby="registration-heading">
                    <fieldset>
                        <legend id="registration-heading">Have you registered with us before?</legend>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="registered" value="yes" onchange="toggleForms()">
                                Yes, I have registered before
                            </label>
                            <label>
                                <input type="radio" name="registered" value="no" onchange="toggleForms()">
                                No, this is my first time
                            </label>
                        </div>
                    </fieldset>
                </section>

                <!-- Verification Form -->
                <section id="verification-form" class="form-card hidden" aria-labelledby="verify-heading">
                    <h3 id="verify-heading">Verify Your Information</h3>
                    <form id="verifyForm">
                        <fieldset>
                            <legend>Select verification method:</legend>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="verify_method" checked value="phone" onchange="updateVerificationLabel()">
                                    Phone Number
                                </label>
                                <label>
                                    <input type="radio" name="verify_method" value="email" onchange="updateVerificationLabel()">
                                    Email Address
                                </label>
                                <label>
                                    <input type="radio" name="verify_method" value="client_id" onchange="updateVerificationLabel()">
                                    Client ID
                                </label>
                            </div>
                        </fieldset>
                        <label for="verify_value" id="verify_value_label">Verification Details</label>
                        <input type="text" id="verify_value" name="verify_value" placeholder="Enter your verification details" required>
                        <button type="submit" class="btn btn-primary">Verify</button>
                    </form>
                    <div id="verification-message" class="message hidden" role="alert" aria-live="polite"></div>
                </section>

                <!-- Main Booking Form -->
                <section id="booking-form" class="form-card hidden" aria-labelledby="booking-heading">
                    <h3 id="booking-heading">Book Your Therapy Session</h3>
                    <form id="mainBookingForm">
                        <div class="form-row">
                            <div>
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div>
                                <label for="mobile">Mobile Number *</label>
                                <input type="tel" id="mobile" name="mobile" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div>
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" onchange="calculateAge()">
                                <div>
                                    <label>
                                        <input type="checkbox" id="unknown_dob" onchange="toggleDOB()">
                                        I don't know my date of birth
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="age-input" class="hidden">
                            <label for="approximate_age">Approximate Age</label>
                            <select id="approximate_age" name="approximate_age">
                                <option value="">Select approximate age</option>
                                <?php for($i = 1; $i <= 100; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> years</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div id="calculated-age" class="info-display hidden" aria-live="polite">
                            <strong>Age:</strong>
                            <span id="age-display">-</span>
                        </div>

                        <!-- Attendant Information -->
                        <div>
                            <label for="attendant">Who is filling this form?</label>
                            <select id="attendant" name="attendant" onchange="toggleAttendantInfo()">
                                <option value="self">Patient (Self)</option>
                                <option value="other">Attendant (Someone else)</option>
                            </select>
                        </div>

                        <fieldset id="attendant-info" class="section hidden">
                            <legend>Attendant Information</legend>
                            <div class="form-row">
                                <div>
                                    <label for="attendant_name">Attendant Name</label>
                                    <input type="text" id="attendant_name" name="attendant_name">
                                </div>
                                <div>
                                    <label for="attendant_email">Attendant Email</label>
                                    <input type="email" id="attendant_email" name="attendant_email">
                                </div>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label for="attendant_mobile">Attendant Mobile</label>
                                    <input type="tel" id="attendant_mobile" name="attendant_mobile">
                                </div>
                                <div>
                                    <label for="relationship">Relationship to Patient</label>
                                    <input type="text" id="relationship" name="relationship" placeholder="e.g., Mother, Father, Friend">
                                </div>
                            </div>
                        </fieldset>

                        <!-- Address Information -->
                        <fieldset class="section">
                            <legend>Address Information</legend>
                            <div class="form-row">
                                <div>
                                    <label for="house_number">House Number/Building Name *</label>
                                    <input type="text" id="house_number" name="house_number" placeholder="e.g., A-101, Sunrise Apartments" required>
                                </div>
                                <div>
                                    <label for="street_locality">Street Name/Locality *</label>
                                    <input type="text" id="street_locality" name="street_locality" placeholder="e.g., MG Road" required>
                                </div>
                            </div>
                            <div>
                                <label for="pincode">PIN Code *</label>
                                <input type="text" id="pincode" name="pincode" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6-digit PIN code" required>
                                <small id="pincode-status"></small>
                            </div>
                            <div id="area-selection" class="hidden">
                                <label for="area_village">Area/Village *</label>
                                <select id="area_village" name="area_village">
                                    <option value="">Select area/village</option>
                                </select>
                            </div>
                            <div id="city-selection" class="hidden">
                                <label for="city">City *</label>
                                <select id="city" name="city" onchange="fillAddressDetails()">
                                    <option value="">Select city</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label for="district">District *</label>
                                    <input type="text" id="district" name="district" readonly required>
                                </div>
                                <div>
                                    <label for="state">Circle/State *</label>
                                    <input type="text" id="state" name="state" readonly required>
                                </div>
                            </div>
                        </fieldset>

                        <div class="form-row">
                            <div>
                                <label for="occupation">Your Occupation</label>
                                <select id="occupation" name="occupation">
                                    <option value="student">Student</option>
                                    <option value="salaried_employee">Salaried Employee</option>
                                    <option value="business_owner">Business Owner</option>
                                    <option value="self_employed">Self-Employed</option>
                                    <option value="unemployed">Unemployed</option>
                                    <option value="homemaker">Homemaker</option>
                                    <option value="retired">Retired</option>
                                    <option value="farmer">Farmer</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="healthcare_professional">Healthcare Professional</option>
                                    <option value="government_employee">Government Employee</option>
                                    <option value="ngo_worker">NGO Worker</option>
                                    <option value="skilled_worker">Skilled Worker</option>
                                    <option value="labourer">Labourer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="qualification">Educational Qualification</label>
                                <select id="qualification" name="qualification">
                                    <option value="no formal education">No Formal Education</option>
                                    <option value="pre primary education">Pre-Primary Education</option>
                                    <option value="primary education">Primary Education</option>
                                    <option value="middle school">Middle School</option>
                                    <option value="secondary education">Secondary Education / High School</option>
                                    <option value="ged">GED (General Educational Development)</option>
                                    <option value="vocational qualification">Vocational Qualification</option>
                                    <option value="technical education">Technical Education</option>
                                    <option value="certificate program">Certificate Program</option>
                                    <option value="associate degree">Associate Degree</option>
                                    <option value="bachelor's degree">Bachelor's Degree</option>
                                    <option value="post graduate diploma">Post-Graduate Diploma</option>
                                    <option value="professional certification">Professional Certification</option>
                                    <option value="master's degree">Master's Degree</option>
                                    <option value="doctoral degree">Doctoral Degree (Ph.D., Ed.D., etc.)</option>
                                    <option value="professional degree">Professional Degree (MD, JD, DDS, etc.)</option>
                                    <option value="post doctoral studies">Post-Doctoral Studies</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div>
                            <label for="how_learned">How did you learn about our service?</label>
                            <select id="how_learned" name="how_learned">
                                <option value="">Select an option</option>
                                <option value="google">Google Search</option>
                                <option value="social_media">Social Media</option>
                                <option value="friend_family">Friend/Family</option>
                                <option value="advertisement">Advertisement</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Disability Information -->
                        <fieldset>
                            <legend>Disability Information</legend>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="has_disability" value="no" onchange="toggleDisabilityInfo()" checked>
                                    No
                                </label>
                                <label>
                                    <input type="radio" name="has_disability" value="yes" onchange="toggleDisabilityInfo()">
                                    Yes
                                </label>
                            </div>
                        </fieldset>

                        <fieldset id="disability-info" class="section hidden">
                            <legend>Disability Details</legend>
                            <div class="form-row">
                                <div>
                                    <label for="disability_type">Type of Disability</label>
                                    <select id="disability_type" name="disability_type">
                                        <option value="">Select a Disability type</option>
                                        <option value="blindness">Blindness</option>
                                        <option value="low-vision">Low-vision</option>
                                        <option value="leprosy-cured-persons">Leprosy Cured persons</option>
                                        <option value="hearing-impairment">Hearing Impairment (deaf and hard of hearing)</option>
                                        <option value="locomotor-disability">Locomotor Disability</option>
                                        <option value="dwarfism">Dwarfism</option>
                                        <option value="intellectual-disability">Intellectual Disability</option>
                                        <option value="mental-illness">Mental Illness</option>
                                        <option value="autism-spectrum-disorder">Autism Spectrum Disorder</option>
                                        <option value="cerebral-palsy">Cerebral Palsy</option>
                                        <option value="muscular-dystrophy">Muscular Dystrophy</option>
                                        <option value="chronic-neurological-conditions">Chronic Neurological conditions</option>
                                        <option value="specific-learning-disabilities">Specific Learning Disabilities</option>
                                        <option value="multiple-sclerosis">Multiple Sclerosis</option>
                                        <option value="speech-and-language-disability">Speech and Language disability</option>
                                        <option value="thalassemia">Thalassemia</option>
                                        <option value="hemophilia">Hemophilia</option>
                                        <option value="sickle-cell-disease">Sickle Cell disease</option>
                                        <option value="multiple-disabilities">Multiple Disabilities including deaf-blindness</option>
                                        <option value="acid-attack-victim">Acid Attack victim</option>
                                        <option value="parkinson-disease">Parkinson's disease</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="disability_percentage">Percentage of Disability</label>
                                    <input type="number" id="disability_percentage" name="disability_percentage" min="0" max="100">
                                </div>
                            </div>
                            <div>
                                <label for="disability_documents">Upload Disability Documents</label>
                                <input type="file" id="disability_documents" name="disability_documents" multiple accept=".pdf,.jpg,.jpeg,.png">
                                <small>Accepted formats: PDF, JPG, JPEG, PNG</small>
                            </div>
                        </fieldset>
                        
                        <!-- Query Section -->
                        <fieldset class="section">
                            <legend>Your Query</legend>
                            <p>Please describe your issue or what you'd like to discuss.</p>
                            <textarea id="query_text" name="query_text" rows="4" cols="50" placeholder="Enter your query here..."></textarea>
                        </fieldset>

                        <!-- Voice Recording Section -->
                        <fieldset class="section">
                            <legend>Record Your Issue (Optional)</legend>
                            <p>You can record your issue or concern (maximum 1 minute)</p>
                            <div id="recording-controls">
                                <button type="button" id="startRecording" class="btn btn-secondary">
                                    <span class="record-icon">●</span> Start Recording
                                </button>
                                <div id="recording-active" class="hidden">
                                    <div id="recording-timer">00:00</div>
                                    <div class="recording-buttons">
                                        <button type="button" id="pauseRecording" class="btn btn-outline">Pause</button>
                                        <button type="button" id="cancelRecording" class="btn btn-outline">Cancel</button>
                                        <button type="button" id="stopRecording" class="btn btn-primary">Done</button>
                                    </div>
                                </div>
                                <div id="recording-complete" class="hidden">
                                    <audio id="audioPlayback" controls></audio>
                                    <div class="recording-buttons">
                                        <button type="button" id="discardRecording" class="btn btn-outline">Discard & Record Again</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="voice_recording_path" name="voice_recording_path">
                        </fieldset>

                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                    <div id="booking-message" class="message hidden" role="alert" aria-live="polite"></div>
                </section>
            </div>

            <!-- Pre-payment Summary -->
            <section id="prepayment-summary" class="form-card hidden" aria-labelledby="prepayment-heading">
                <h3 id="prepayment-heading">Confirm Your Booking</h3>
                <div id="payment-details" class="user-details-summary">
                    <!-- User details will be populated here -->
                </div>
                <div class="coupon-section">
                    <input type="text" id="coupon-code" placeholder="Enter Coupon Code">
                    <button class="btn btn-secondary" id="apply-coupon-btn">Apply</button>
                    <button class="btn btn-danger hidden" id="remove-coupon-btn">Remove Coupon</button>
                </div>
                <div class="payment-summary">
                    <p>Subtotal: <span id="subtotal"></span></p>
                    <p>Discount: <span id="discount">0</span></p>
                    <p>Total: <span id="total-amount"></span></p>
                </div>
                <div class="payment-actions">
                    <button id="pay-now-btn" class="btn btn-primary">Pay Now</button>
                    <button id="book-now-btn" class="btn btn-primary hidden">Book Now</button>
                </div>
            </section>

            <!-- Payment Success Page -->
            <section id="payment-success" class="form-card hidden" aria-labelledby="success-heading">
                <h3 id="success-heading">Booking Confirmed!</h3>
                <p>Thank you. Your therapy session is booked.</p>
                <p>What's next?</p>
                <ul>
                    <li>You will receive a confirmation email shortly.</li>
                    <li>Our team will contact you to schedule your session.</li>
                </ul>
                <a href="/" class="btn btn-primary">Book Another Session</a>
            </section>

            <!-- Payment Failed Page -->
            <section id="payment-failed" class="form-card hidden" aria-labelledby="failed-heading">
                <h3 id="failed-heading">Payment Failed</h3>
                <p>Unfortunately, we were unable to process your payment.</p>
                <p>Please try again. If the problem persists, please contact our support team.</p>
                <button class="btn btn-primary" id="retry-payment-btn">Retry Payment</button>
            </section>

        </main>

        <footer>
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>

    <!-- Library Scripts -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <!-- Main Application Script -->
    <script src="assets/js/main.js"></script>
</body>
</html>
