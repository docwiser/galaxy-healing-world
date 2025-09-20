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
                    <h3>Have you registered with us before?</h3>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="registered" value="yes" onchange="toggleForms()">
                            <span class="radio-custom"></span>
                            Yes, I have registered before
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="registered" value="no" onchange="toggleForms()">
                            <span class="radio-custom"></span>
                            No, this is my first time
                        </label>
                    </div>
                </div>
            </div>

            <!-- Verification Form -->
            <div id="verification-form" class="form-card hidden">
                <h3>Verify Your Information</h3>
                <form id="verifyForm">
                    <div class="form-group">
                        <label>Select verification method:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="phone">
                                <span class="radio-custom"></span>
                                Phone Number
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="email">
                                <span class="radio-custom"></span>
                                Email Address
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="verify_method" value="client_id">
                                <span class="radio-custom"></span>
                                Client ID
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" id="verify_value" name="verify_value" placeholder="Enter your verification details" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </form>
                <div id="verification-message" class="message hidden"></div>
            </div>

            <!-- Main Booking Form -->
            <div id="booking-form" class="form-card hidden">
                <h3>Book Your Therapy Session</h3>
                <form id="mainBookingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="mobile" name="mobile" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" onchange="calculateAge()">
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="unknown_dob" onchange="toggleDOB()">
                                    <span class="checkbox-custom"></span>
                                    I don't know my date of birth
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="age-input" class="form-group hidden">
                        <label for="approximate_age">Approximate Age</label>
                        <select id="approximate_age" name="approximate_age">
                            <option value="">Select approximate age</option>
                            <?php for($i = 1; $i <= 100; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> years</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div id="calculated-age" class="info-display hidden">
                        <span class="label">Age:</span>
                        <span id="age-display">-</span>
                    </div>

                    <!-- Attendant Information -->
                    <div class="form-group">
                        <label for="attendant">Who is filling this form?</label>
                        <select id="attendant" name="attendant" onchange="toggleAttendantInfo()">
                            <option value="self">Patient (Self)</option>
                            <option value="other">Attendant (Someone else)</option>
                        </select>
                    </div>

                    <div id="attendant-info" class="attendant-section hidden">
                        <h4>Attendant Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendant_name">Attendant Name</label>
                                <input type="text" id="attendant_name" name="attendant_name">
                            </div>
                            <div class="form-group">
                                <label for="attendant_email">Attendant Email</label>
                                <input type="email" id="attendant_email" name="attendant_email">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendant_mobile">Attendant Mobile</label>
                                <input type="tel" id="attendant_mobile" name="attendant_mobile">
                            </div>
                            <div class="form-group">
                                <label for="relationship">Relationship to Patient</label>
                                <input type="text" id="relationship" name="relationship" placeholder="e.g., Mother, Father, Friend">
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="address-section">
                        <h4>Address Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State <span class="required">*</span></label>
                                <input type="text" id="state" name="state" required>
                            </div>
                            <div class="form-group">
                                <label for="district">District <span class="required">*</span></label>
                                <input type="text" id="district" name="district" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address">Complete Address <span class="required">*</span></label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="form-group">
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
                    <div class="form-group">
                        <label>Do you have any disability?</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="has_disability" value="no" onchange="toggleDisabilityInfo()" checked>
                                <span class="radio-custom"></span>
                                No
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="has_disability" value="yes" onchange="toggleDisabilityInfo()">
                                <span class="radio-custom"></span>
                                Yes
                            </label>
                        </div>
                    </div>

                    <div id="disability-info" class="disability-section hidden">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="disability_type">Type of Disability</label>
                                <input type="text" id="disability_type" name="disability_type">
                            </div>
                            <div class="form-group">
                                <label for="disability_percentage">Percentage of Disability</label>
                                <input type="number" id="disability_percentage" name="disability_percentage" min="0" max="100">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="disability_documents">Upload Disability Documents</label>
                            <input type="file" id="disability_documents" name="disability_documents" multiple accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Book Session</button>
                </form>
                <div id="booking-message" class="message hidden"></div>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>