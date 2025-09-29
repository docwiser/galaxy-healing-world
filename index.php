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
                                <input type="radio" name="verify_method" value="phone">
                                Phone Number
                            </label>
                            <label>
                                <input type="radio" name="verify_method" value="email">
                                Email Address
                            </label>
                            <label>
                                <input type="radio" name="verify_method" value="client_id">
                                Client ID
                            </label>
                        </div>
                    </fieldset>
                    <label for="verify_value">Verification Details</label>
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
                                <label for="state">State *</label>
                                <input type="text" id="state" name="state" required>
                            </div>
                            <div>
                                <label for="district">District *</label>
                                <input type="text" id="district" name="district" required>
                            </div>
                        </div>
                        <div>
                            <label for="address">Complete Address *</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                    </fieldset>

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
                                <input type="text" id="disability_type" name="disability_type">
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

                    <button type="submit" class="btn btn-primary">Book Session</button>
                </form>
                <div id="booking-message" class="message hidden" role="alert" aria-live="polite"></div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>