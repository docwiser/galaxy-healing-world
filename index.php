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
            <p>Your journey to healing starts here<br>à¤†à¤ªà¤•à¥€ à¤¹à¥€à¤²à¤¿à¤‚à¤— à¤•à¥€ à¤¯à¤¾à¤¤à¥à¤°à¤¾
                à¤¯à¤¹à¤¾à¤ à¤¸à¥‡ à¤¶à¥à¤°à¥‚ à¤¹à¥‹à¤¤à¥€ à¤¹à¥ˆ</p>
        </header>
        <main>
            <div id="booking-flow">
                <section>
                    <h2 id="welcome-heading">Book Your Therapy Session<br>à¤…à¤ªà¤¨à¤¾ à¤¥à¥‡à¤°à¥‡à¤ªà¥€ à¤¸à¤¤à¥à¤°
                        à¤¬à¥à¤• à¤•à¤°à¥‡à¤‚</h2>
                    <p>Experience personalized healing therapies tailored to your needs<br>à¤…à¤ªà¤¨à¥€
                        à¤†à¤µà¤¶à¥à¤¯à¤•à¤¤à¤¾à¤“à¤‚ à¤•à¥‡ à¤…à¤¨à¥à¤¸à¤¾à¤° à¤µà¥à¤¯à¤•à¥à¤¤à¤¿à¤—à¤¤
                        à¤¹à¥€à¤²à¤¿à¤‚à¤— à¤¥à¥‡à¤°à¥‡à¤ªà¥€ à¤•à¤¾ à¤…à¤¨à¥à¤­à¤µ à¤•à¤°à¥‡à¤‚</p>
                </section>
                <section class="form-card" aria-labelledby="registration-heading">
                    <fieldset>
                        <legend id="registration-heading">Have you registered with us before? <br>à¤•à¥à¤¯à¤¾
                            à¤†à¤ªà¤¨à¥‡ à¤ªà¤¹à¤²à¥‡ à¤¹à¤®à¤¾à¤°à¥‡ à¤¸à¤¾à¤¥ à¤ªà¤‚à¤œà¥€à¤•à¤°à¤£ à¤•à¤¿à¤¯à¤¾
                            à¤¹à¥ˆ?</legend>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="registered" value="yes" onchange="toggleForms()">
                                â˜ Yes, I have registered before <br>â˜ à¤¹à¤¾à¤, à¤®à¥ˆà¤‚à¤¨à¥‡ à¤ªà¤¹à¤²à¥‡
                                à¤ªà¤‚à¤œà¥€à¤•à¤°à¤£ à¤•à¤¿à¤¯à¤¾ à¤¹à¥ˆ
                            </label>
                            <label>
                                <input type="radio" name="registered" value="no" onchange="toggleForms()">
                                â˜ No, this is my first time <br>â˜ à¤¨à¤¹à¥€à¤‚, à¤¯à¤¹ à¤®à¥‡à¤°à¤¾ à¤ªà¤¹à¤²à¥€
                                à¤¬à¤¾à¤° à¤¹à¥ˆ
                            </label>
                        </div>
                    </fieldset>
                </section>
                <section id="verification-form" class="form-card hidden" aria-labelledby="verify-heading">
                    <h3 id="verify-heading">Verify Your Information<br>à¤…à¤ªà¤¨à¥€ à¤œà¤¾à¤¨à¤•à¤¾à¤°à¥€
                        à¤¸à¤¤à¥à¤¯à¤¾à¤ªà¤¿à¤¤ à¤•à¤°à¥‡à¤‚</h3>
                    <form id="verifyForm">
                        <fieldset>
                            <legend>Select verification method:</legend>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="verify_method" checked value="phone"
                                        onchange="updateVerificationLabel()">
                                    Phone Number<br>à¤«à¥‹à¤¨ à¤¨à¤‚à¤¬à¤°
                                </label>
                                <label>
                                    <input type="radio" name="verify_method" value="email"
                                        onchange="updateVerificationLabel()">
                                    Email Address<br>à¤‡à¤®à¥‡à¤² à¤à¤¡à¥à¤°à¥‡à¤¸
                                </label>
                                <label>
                                    <input type="radio" name="verify_method" value="client_id"
                                        onchange="updateVerificationLabel()">
                                    Client ID<br>à¤•à¥à¤²à¤¾à¤‡à¤‚à¤Ÿ à¤†à¤ˆà¤¡à¥€
                                </label>
                            </div>
                        </fieldset>
                        <div id="initial-verification-step">
                            <label for="verify_value" id="verify_value_label">Phone number</label>
                            <input type="tel" id="verify_value" name="verify_value"
                                placeholder="Enter your phone number used to register previously" required>
                            <button type="submit" class="btn btn-primary">Verify</button>
                        </div>
                        <div id="otp-verification-step" class="hidden">
                            <p id="otp-message" class="info-text" role="status" aria-live="polite"></p>
                            <label for="otp_value">Enter OTP<br>à¤“à¤Ÿà¥€à¤ªà¥€ à¤¦à¤°à¥à¤œ à¤•à¤°à¥‡à¤‚</label>
                            <input type="text" id="otp_value" name="otp_value" placeholder="Enter 6-digit OTP"
                                maxlength="6" pattern="\d{6}">
                            <button type="button" id="verify-otp-btn" class="btn btn-primary">Verify OTP</button>
                            <button type="button" id="resend-otp-btn" class="btn btn-outline">Resend OTP</button>
                        </div>
                    </form>
                    <div id="verification-message" class="message hidden" role="alert" aria-live="assertive"></div>
                </section>
                <section id="booking-form" class="form-card hidden" aria-labelledby="booking-heading">
                    <h3 id="booking-heading">Book Your Therapy Session <br>à¤…à¤ªà¤¨à¤¾ à¤¥à¥‡à¤°à¥‡à¤ªà¥€ à¤¸à¤¤à¥à¤°
                        à¤¬à¥à¤• à¤•à¤°à¥‡à¤‚</h3>
                    <form id="mainBookingForm">
                        <p>All fields marked with * are mandatory <br>* à¤¸à¥‡ à¤šà¤¿à¤¹à¥à¤¨à¤¿à¤¤ à¤¸à¤­à¥€
                            à¤«à¤¼à¥€à¤²à¥à¤¡ à¤…à¤¨à¤¿à¤µà¤¾à¤°à¥à¤¯ à¤¹à¥ˆà¤‚</p>
                        <div class="form-row">
                            <div>
                                <label for="name">Full Name * <br>à¤ªà¥‚à¤°à¤¾ à¤¨à¤¾à¤® *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div>
                                <label for="mobile">Mobile Number * <br>à¤®à¥‹à¤¬à¤¾à¤‡à¤² à¤¨à¤‚à¤¬à¤° *</label>
                                <input type="tel" id="mobile" name="mobile" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="email">Email Address * <br>à¤ˆà¤®à¥‡à¤² à¤ªà¤¤à¤¾ *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div>
                                <label for="dob">Date of Birth *<br>à¤œà¤¨à¥à¤® à¤¤à¤¿à¤¥à¤¿ *</label>
                                <input type="date" id="dob" name="dob" onchange="calculateAge()" required>
                                <div>
                                    <label>
                                        <input type="checkbox" id="unknown_dob" onchange="toggleDOB()">
                                        â˜ I don't know my date of birth <br>â˜ à¤®à¥à¤à¥‡ à¤…à¤ªà¤¨à¥€ à¤œà¤¨à¥à¤®
                                        à¤¤à¤¿à¤¥à¤¿ à¤¨à¤¹à¥€à¤‚ à¤ªà¤¤à¤¾
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="age-input" class="hidden">
                            <label for="approximate_age">Approximate Age *<br>à¤…à¤¨à¥à¤®à¤¾à¤¨à¤¿à¤¤ à¤†à¤¯à¥
                                *</label>
                            <select id="approximate_age" name="approximate_age" required>
                                <option value="" selected disabled>Select approximate age | à¤…à¤¨à¥à¤®à¤¾à¤¨à¤¿à¤¤
                                    à¤†à¤¯à¥ à¤šà¥à¤¨à¥‡à¤‚</option>
                                <?php for ($i = 1; $i <= 100; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> years</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div id="calculated-age" class="info-display hidden">
                            <strong>Age:<br>à¤†à¤¯à¥:</strong>
                            <span id="age-display">-</span>
                            <input type="hidden" id="calculated_age" name="calculated_age">
                        </div>
                        <div class="form-row">
                            <div>
                                <label>Gender *<br>à¤²à¤¿à¤‚à¤— *</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="gender" value="male">
                                        Male<br>à¤ªà¥à¤°à¥à¤·
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" value="female">
                                        Female<br>à¤®à¤¹à¤¿à¤²à¤¾
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" value="other">
                                        Other<br>à¤…à¤¨à¥à¤¯
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="attendant">Who is filling this form? <br>à¤¯à¤¹ à¤«à¥‰à¤°à¥à¤® à¤•à¥Œà¤¨ à¤­à¤°
                                à¤°à¤¹à¤¾ à¤¹à¥ˆ?</label>
                            <select id="attendant" name="attendant" onchange="toggleAttendantInfo()">
                                <option value="self">Client (Self) | à¤•à¥à¤²à¤¾à¤‡à¤‚à¤Ÿ (à¤¸à¥à¤µà¤¯à¤‚)</option>
                                <option value="other">Attendant (Someone else) | â˜ à¤ªà¤°à¤¿à¤šà¤¾à¤°à¤• (à¤•à¥‹à¤ˆ
                                    à¤”à¤° à¤µà¥à¤¯à¤•à¥à¤¤à¤¿)</option>
                            </select>
                        </div>
                        <fieldset id="attendant-info" class="section hidden">
                            <legend>Attendant Information <br>à¤ªà¤°à¤¿à¤šà¤¾à¤°à¤• à¤•à¥€ à¤œà¤¾à¤¨à¤•à¤¾à¤°à¥€
                            </legend>
                            <div class="form-row">
                                <div>
                                    <label for="attendant_name">Attendant Name *<br>à¤ªà¤°à¤¿à¤šà¤¾à¤°à¤• à¤•à¤¾
                                        à¤¨à¤¾à¤® *</label>
                                    <input type="text" id="attendant_name" name="attendant_name">
                                </div>
                                <div>
                                    <label for="attendant_email">Attendant Email *<br>à¤ªà¤°à¤¿à¤šà¤¾à¤°à¤• à¤•à¤¾
                                        à¤ˆà¤®à¥‡à¤² *</label>
                                    <input type="email" id="attendant_email" name="attendant_email">
                                </div>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label for="attendant_mobile">Attendant Mobile *<br>à¤ªà¤°à¤¿à¤šà¤¾à¤°à¤• à¤•à¤¾
                                        à¤®à¥‹à¤¬à¤¾à¤‡à¤² à¤¨à¤‚à¤¬à¤° *</label>
                                    <input type="tel" id="attendant_mobile" name="attendant_mobile">
                                </div>
                                <div>
                                    <label for="relationship">Relationship to Client *<br>à¤•à¥à¤²à¤¾à¤‡à¤‚à¤Ÿ à¤¸à¥‡
                                        à¤¸à¤‚à¤¬à¤‚à¤§ *</label>
                                    <input type="text" id="relationship" name="relationship"
                                        placeholder="e.g., Mother, Father, Friend">
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="section">
                            <legend>Address Information <br>à¤ªà¤¤à¤¾ à¤µà¤¿à¤µà¤°à¤£</legend>
                            <div class="form-row">
                                <div>
                                    <label for="house_number">House Number/Building Name * <br>à¤®à¤•à¤¾à¤¨ à¤¨à¤‚à¤¬à¤°
                                        / à¤¬à¤¿à¤²à¥à¤¡à¤¿à¤‚à¤— à¤•à¤¾ à¤¨à¤¾à¤® *</label>
                                    <input type="text" id="house_number" name="house_number"
                                        placeholder="e.g., A-101, Sunrise Apartments" required>
                                </div>
                                <div>
                                    <label for="street_locality">Street Name/Locality * <br>à¤¸à¤¡à¤¼à¤• à¤•à¤¾
                                        à¤¨à¤¾à¤® / à¤•à¥à¤·à¥‡à¤¤à¥à¤° *</label>
                                    <input type="text" id="street_locality" name="street_locality"
                                        placeholder="e.g., MG Road" required>
                                </div>
                            </div>
                            <div>
                                <label for="pincode">PIN Code * <br>à¤ªà¤¿à¤¨ à¤•à¥‹à¤¡ *</label>
                                <input type="text" id="pincode" name="pincode" maxlength="6" pattern="[0-9]{6}"
                                    placeholder="Enter 6-digit PIN code" required>
                                <small id="pincode-status"></small>
                            </div>
                            <div id="area-selection" class="hidden">
                                <label for="area_village">Area/Village *<br>à¤•à¥à¤·à¥‡à¤¤à¥à¤°/à¤—à¤¾à¤à¤µ *</label>
                                <select id="area_village" name="area_village" required>
                                    <option value="" selected disabled>Select Area/Village |
                                        à¤•à¥à¤·à¥‡à¤¤à¥à¤°/à¤—à¤¾à¤à¤µ</option>
                                </select>
                            </div>
                            <div id="city-selection" class="hidden">
                                <label for="city">City *<br>à¤¶à¤¹à¤° *</label>
                                <select id="city" name="city" onchange="fillAddressDetails()" required>
                                    <option value="" selected disabled>Select City | à¤¶à¤¹à¤°</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label for="district">District * <br>à¤œà¤¿à¤²à¤¾ *</label>
                                    <input type="text" id="district" name="district" readonly required>
                                </div>
                                <div>
                                    <label for="state">Circle/State * <br>à¤ªà¥à¤°à¤¾à¤‚à¤¤ / à¤°à¤¾à¤œà¥à¤¯ *</label>
                                    <input type="text" id="state" name="state" readonly required>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-row">
                            <div>
                                <label for="occupation">Your Occupation *<br>à¤†à¤ªà¤•à¤¾ à¤µà¥à¤¯à¤µà¤¸à¤¾à¤¯
                                    *</label>
                                <select id="occupation" name="occupation" required>
                                    <option value="" selected disabled>Choose your occupation | à¤…à¤ªà¤¨à¤¾
                                        à¤µà¥à¤¯à¤µà¤¸à¤¾à¤¯ à¤šà¥à¤¨à¥‡à¤‚</option>
                                    <option value="student">Student | à¤µà¤¿à¤¦à¥à¤¯à¤¾à¤°à¥à¤¥à¥€</option>
                                    <option value="salaried_employee">Salaried Employee | à¤µà¥‡à¤¤à¤¨à¤­à¥‹à¤—à¥€
                                        à¤•à¤°à¥à¤®à¤šà¤¾à¤°à¥€</option>
                                    <option value="business_owner">Business Owner | à¤µà¥à¤¯à¤µà¤¸à¤¾à¤¯
                                        à¤¸à¥à¤µà¤¾à¤®à¥€</option>
                                    <option value="self_employed">Self-Employed | à¤¸à¥à¤µ-à¤¨à¤¿à¤¯à¥‹à¤œà¤¿à¤¤
                                    </option>
                                    <option value="unemployed">Unemployed | à¤¬à¥‡à¤°à¥‹à¤œà¤—à¤¾à¤°</option>
                                    <option value="homemaker">Homemaker | à¤—à¥ƒà¤¹à¤¿à¤£à¥€/à¤—à¥ƒà¤¹à¤¸à¥à¤µà¤¾à¤®à¥€
                                    </option>
                                    <option value="retired">Retired | à¤¸à¥‡à¤µà¤¾à¤¨à¤¿à¤µà¥ƒà¤¤à¥à¤¤</option>
                                    <option value="farmer">Farmer | à¤•à¤¿à¤¸à¤¾à¤¨</option>
                                    <option value="teacher">Teacher | à¤¶à¤¿à¤•à¥à¤·à¤•</option>
                                    <option value="healthcare_professional">Healthcare Professional |
                                        à¤¸à¥à¤µà¤¾à¤¸à¥à¤¥à¥à¤¯ à¤¸à¥‡à¤µà¤¾ à¤ªà¥‡à¤¶à¥‡à¤µà¤°</option>
                                    <option value="government_employee">Government Employee | à¤¸à¤°à¤•à¤¾à¤°à¥€
                                        à¤•à¤°à¥à¤®à¤šà¤¾à¤°à¥€</option>
                                    <option value="ngo_worker">NGO Worker | à¤à¤¨à¤œà¥€à¤“
                                        à¤•à¤¾à¤°à¥à¤¯à¤•à¤°à¥à¤¤à¤¾</option>
                                    <option value="skilled_worker">Skilled Worker | à¤•à¥à¤¶à¤² à¤¶à¥à¤°à¤®à¤¿à¤•
                                    </option>
                                    <option value="labourer">Labourer | à¤®à¤œà¤¦à¥‚à¤°</option>
                                    <option value="other">Other | à¤…à¤¨à¥à¤¯</option>

                                </select>
                            </div>
                            <div>
                                <label for="qualification"> * Educational Qualification *<br>à¤¶à¥ˆà¤•à¥à¤·à¤¿à¤•
                                    à¤¯à¥‹à¤—à¥à¤¯à¤¤à¤¾ *</label>
                                <select id="qualification" name="qualification" required>
                                    <option value="" selected disabled>Choose your educational background | à¤…à¤ªà¤¨à¥€
                                        à¤¶à¥ˆà¤•à¥à¤·à¤£à¤¿à¤• à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤šà¥à¤¨à¥‡à¤‚</option>
                                    <option value="no formal education">No Formal Education | à¤•à¥‹à¤ˆ
                                        à¤”à¤ªà¤šà¤¾à¤°à¤¿à¤• à¤¶à¤¿à¤•à¥à¤·à¤¾ à¤¨à¤¹à¥€à¤‚</option>
                                    <option value="pre primary education">Pre-Primary Education |
                                        à¤ªà¥‚à¤°à¥à¤µ-à¤ªà¥à¤°à¤¾à¤¥à¤®à¤¿à¤• à¤¶à¤¿à¤•à¥à¤·à¤¾</option>
                                    <option value="primary education">Primary Education | à¤ªà¥à¤°à¤¾à¤¥à¤®à¤¿à¤•
                                        à¤¶à¤¿à¤•à¥à¤·à¤¾</option>
                                    <option value="middle school">Middle School | à¤®à¤¿à¤¡à¤¿à¤² à¤¸à¥à¤•à¥‚à¤²
                                        (à¤•à¤•à¥à¤·à¤¾ 6-8)</option>
                                    <option value="secondary education">Secondary Education / High School |
                                        à¤®à¤¾à¤§à¥à¤¯à¤®à¤¿à¤• à¤¶à¤¿à¤•à¥à¤·à¤¾ / à¤¹à¤¾à¤ˆ à¤¸à¥à¤•à¥‚à¤²</option>
                                    <option value="ged">GED (General Educational Development) | à¤œà¥€.à¤ˆ.à¤¡à¥€.
                                        (à¤¸à¤¾à¤®à¤¾à¤¨à¥à¤¯ à¤¶à¥ˆà¤•à¥à¤·à¤¿à¤• à¤µà¤¿à¤•à¤¾à¤¸)</option>
                                    <option value="vocational qualification">Vocational Qualification |
                                        à¤µà¥à¤¯à¤¾à¤µà¤¸à¤¾à¤¯à¤¿à¤• à¤¯à¥‹à¤—à¥à¤¯à¤¤à¤¾</option>
                                    <option value="technical education">Technical Education | à¤¤à¤•à¤¨à¥€à¤•à¥€
                                        à¤¶à¤¿à¤•à¥à¤·à¤¾</option>
                                    <option value="certificate program">Certificate Program |
                                        à¤ªà¥à¤°à¤®à¤¾à¤£à¤ªà¤¤à¥à¤° à¤•à¤¾à¤°à¥à¤¯à¤•à¥à¤°à¤®</option>
                                    <option value="associate degree">Associate Degree | à¤à¤¸à¥‹à¤¸à¤¿à¤à¤Ÿ
                                        à¤¡à¤¿à¤—à¥à¤°à¥€</option>
                                    <option value="bachelor's degree">Bachelor's Degree | à¤¸à¥à¤¨à¤¾à¤¤à¤•
                                        à¤¡à¤¿à¤—à¥à¤°à¥€ (à¤¬à¥ˆà¤šà¤²à¤° à¤¡à¤¿à¤—à¥à¤°à¥€)</option>
                                    <option value="post graduate diploma">Post-Graduate Diploma |
                                        à¤¸à¥à¤¨à¤¾à¤¤à¤•à¥‹à¤¤à¥à¤¤à¤° à¤¡à¤¿à¤ªà¥à¤²à¥‹à¤®à¤¾</option>
                                    <option value="professional certification">Professional Certification |
                                        à¤µà¥à¤¯à¤¾à¤µà¤¸à¤¾à¤¯à¤¿à¤• à¤ªà¥à¤°à¤®à¤¾à¤£à¤¨</option>
                                    <option value="master's degree">Master's Degree | à¤¸à¥à¤¨à¤¾à¤¤à¤•à¥‹à¤¤à¥à¤¤à¤°
                                        à¤¡à¤¿à¤—à¥à¤°à¥€ (à¤®à¤¾à¤¸à¥à¤Ÿà¤° à¤¡à¤¿à¤—à¥à¤°à¥€)</option>
                                    <option value="doctoral degree">Doctoral Degree (Ph.D., Ed.D., etc.) |
                                        à¤¡à¥‰à¤•à¥à¤Ÿà¤°à¥‡à¤Ÿ à¤¡à¤¿à¤—à¥à¤°à¥€ (à¤ªà¥€.à¤à¤š.à¤¡à¥€. à¤†à¤¦à¤¿)
                                    </option>
                                    <option value="professional degree">Professional Degree (MD, JD, DDS, etc.) |
                                        à¤ªà¥‡à¤¶à¥‡à¤µà¤° à¤¡à¤¿à¤—à¥à¤°à¥€ (à¤¡à¥‰à¤•à¥à¤Ÿà¤°, à¤µà¤•à¥€à¤²
                                        à¤†à¤¦à¤¿)</option>
                                    <option value="post doctoral studies">Post-Doctoral Studies |
                                        à¤ªà¥‹à¤¸à¥à¤Ÿ-à¤¡à¥‰à¤•à¥à¤Ÿà¤°à¤² à¤…à¤§à¥à¤¯à¤¯à¤¨</option>
                                    <option value="other">Other | à¤…à¤¨à¥à¤¯</option>

                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="how_learned">How did you learn about our service? *<br>à¤†à¤ªà¤•à¥‹
                                à¤¹à¤®à¤¾à¤°à¥€ à¤¸à¥‡à¤µà¤¾ à¤•à¥‡ à¤¬à¤¾à¤°à¥‡ à¤®à¥‡à¤‚ à¤•à¥ˆà¤¸à¥‡ à¤ªà¤¤à¤¾
                                à¤šà¤²à¤¾? *</label>
                            <select id="how_learned" name="how_learned" required>
                                <option value="" selected disabled>Select an option | à¤à¤• à¤µà¤¿à¤•à¤²à¥à¤ª
                                    à¤šà¥à¤¨à¥‡à¤‚</option>
                                <option value="google">Google Search | à¤—à¥‚à¤—à¤² à¤¸à¤°à¥à¤š</option>
                                <option value="social_media">Social Media | à¤¸à¥‹à¤¶à¤² à¤®à¥€à¤¡à¤¿à¤¯à¤¾</option>
                                <option value="friend_family">Friend/Family | à¤®à¤¿à¤¤à¥à¤°/à¤ªà¤°à¤¿à¤µà¤¾à¤°
                                </option>
                                <option value="advertisement">Advertisement | à¤µà¤¿à¤œà¥à¤žà¤¾à¤ªà¤¨</option>
                                <option value="other">Other | à¤…à¤¨à¥à¤¯</option>

                            </select>
                        </div>
                        <fieldset>
                            <legend>Are you living with any kind of disability or medical condition? *<br>à¤•à¥à¤¯à¤¾
                                à¤†à¤ª à¤•à¤¿à¤¸à¥€ à¤­à¥€ à¤ªà¥à¤°à¤•à¤¾à¤° à¤•à¥€ à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾
                                à¤¯à¤¾ à¤¶à¤¾à¤°à¥€à¤°à¤¿à¤•/à¤®à¤¾à¤¨à¤¸à¤¿à¤• à¤¸à¥à¤¥à¤¿à¤¤à¤¿ à¤•à¥‡ à¤¸à¤¾à¤¥
                                à¤œà¥€ à¤°à¤¹à¥‡ à¤¹à¥ˆà¤‚? *</legend>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="has_disability" value="no"
                                        onchange="toggleDisabilityInfo()" checked>
                                    No <br>â˜ à¤¨à¤¹à¥€à¤‚
                                </label>
                                <label>
                                    <input type="radio" name="has_disability" value="yes"
                                        onchange="toggleDisabilityInfo()">
                                    â˜ Yes <br>â˜ à¤¹à¤¾à¤
                                </label>
                            </div>
                        </fieldset>
                        <fieldset id="disability-info" class="section hidden">
                            <legend>Disability Details <br>à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾ à¤•à¤¾ à¤µà¤¿à¤µà¤°à¤£
                            </legend>
                            <div class="form-row">
                                <div>
                                    <label for="disability_type">Type of Disability *<br>à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾
                                        à¤•à¤¾ à¤ªà¥à¤°à¤•à¤¾à¤° *</label>
                                    <select id="disability_type" name="disability_type">
                                        <option value="" selected disabled>Select a Disability type |
                                            à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾ à¤•à¤¾ à¤ªà¥à¤°à¤•à¤¾à¤° à¤šà¥à¤¨à¥‡à¤‚
                                        </option>
                                        <option value="blindness">Blindness | à¤¦à¥ƒà¤·à¥à¤Ÿà¤¿à¤¹à¥€à¤¨à¤¤à¤¾</option>
                                        <option value="low-vision">Low-vision | à¤…à¤²à¥à¤ª-à¤¦à¥ƒà¤·à¥à¤Ÿà¤¿</option>
                                        <option value="leprosy-cured-persons">Leprosy Cured persons | à¤•à¥à¤·à¥à¤ 
                                            à¤°à¥‹à¤— à¤®à¥à¤•à¥à¤¤ à¤µà¥à¤¯à¤•à¥à¤¤à¤¿</option>
                                        <option value="hearing-impairment">Hearing Impairment (deaf and hard of hearing)
                                            | à¤¶à¥à¤°à¤µà¤£ à¤¬à¤¾à¤§à¤¿à¤¤ (à¤¬à¤¹à¤°à¤¾à¤ªà¤¨ à¤”à¤° à¤¸à¥à¤¨à¤¨à¥‡
                                            à¤®à¥‡à¤‚ à¤•à¤ à¤¿à¤¨à¤¾à¤ˆ)</option>
                                        <option value="locomotor-disability">Locomotor Disability | à¤šà¤²à¤¨
                                            à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾</option>
                                        <option value="dwarfism">Dwarfism | à¤¬à¥Œà¤¨à¤¾à¤ªà¤¨</option>
                                        <option value="intellectual-disability">Intellectual Disability |
                                            à¤¬à¥Œà¤¦à¥à¤§à¤¿à¤• à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾</option>
                                        <option value="mental-illness">Mental Illness | à¤®à¤¾à¤¨à¤¸à¤¿à¤•
                                            à¤¬à¥€à¤®à¤¾à¤°à¥€</option>
                                        <option value="autism-spectrum-disorder">Autism Spectrum Disorder |
                                            à¤‘à¤Ÿà¤¿à¤œà¥à¤® à¤¸à¥à¤ªà¥‡à¤•à¥à¤Ÿà¥à¤°à¤® à¤¡à¤¿à¤¸à¤‘à¤°à¥à¤¡à¤°
                                        </option>
                                        <option value="cerebral-palsy">Cerebral Palsy | à¤¸à¥‡à¤°à¥‡à¤¬à¥à¤°à¤²
                                            à¤ªà¤¾à¤²à¥à¤¸à¥€ (à¤®à¤¸à¥à¤¤à¤¿à¤·à¥à¤• à¤ªà¤•à¥à¤·à¤¾à¤˜à¤¾à¤¤)
                                        </option>
                                        <option value="muscular-dystrophy">Muscular Dystrophy | à¤®à¤¸à¥à¤•à¥à¤²à¤°
                                            à¤¡à¤¿à¤¸à¥à¤Ÿà¥à¤°à¥‰à¤«à¥€</option>
                                        <option value="chronic-neurological-conditions">Chronic Neurological conditions
                                            | à¤•à¥à¤°à¥‹à¤¨à¤¿à¤• à¤¨à¥à¤¯à¥‚à¤°à¥‹à¤²à¥‰à¤œà¤¿à¤•à¤²
                                            à¤¸à¥à¤¥à¤¿à¤¤à¤¿à¤¯à¤¾à¤‚</option>
                                        <option value="specific-learning-disabilities">Specific Learning Disabilities |
                                            à¤µà¤¿à¤¶à¤¿à¤·à¥à¤Ÿ à¤¸à¥€à¤–à¤¨à¥‡ à¤•à¥€ à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾
                                        </option>
                                        <option value="multiple-sclerosis">Multiple Sclerosis | à¤®à¤²à¥à¤Ÿà¥€à¤ªà¤²
                                            à¤¸à¥à¤•à¥à¤²à¥‡à¤°à¥‹à¤¸à¤¿à¤¸</option>
                                        <option value="speech-and-language-disability">Speech and Language disability |
                                            à¤µà¤¾à¤•à¥ à¤”à¤° à¤­à¤¾à¤·à¤¾ à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾</option>
                                        <option value="thalassemia">Thalassemia | à¤¥à¥ˆà¤²à¥‡à¤¸à¥€à¤®à¤¿à¤¯à¤¾
                                        </option>
                                        <option value="hemophilia">Hemophilia | à¤¹à¥€à¤®à¥‹à¤«à¥€à¤²à¤¿à¤¯à¤¾</option>
                                        <option value="sickle-cell-disease">Sickle Cell disease | à¤¸à¤¿à¤•à¤² à¤¸à¥‡à¤²
                                            à¤°à¥‹à¤—</option>
                                        <option value="multiple-disabilities">Multiple Disabilities including
                                            deaf-blindness | à¤¬à¤¹à¥-à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾
                                            (à¤¬à¤§à¤¿à¤°-à¤…à¤‚à¤§à¤¤à¤¾ à¤¸à¤¹à¤¿à¤¤)</option>
                                        <option value="acid-attack-victim">Acid Attack victim | à¤à¤¸à¤¿à¤¡
                                            à¤…à¤Ÿà¥ˆà¤• à¤ªà¥€à¤¡à¤¼à¤¿à¤¤</option>
                                        <option value="parkinson-disease">Parkinson's disease |
                                            à¤ªà¤¾à¤°à¥à¤•à¤¿à¤‚à¤¸à¤‚à¤¸ à¤°à¥‹à¤—</option>

                                    </select>
                                </div>
                                <div>
                                    <label for="disability_percentage">Percentage of Disability
                                        *<br>à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾ à¤•à¥‡ à¤ªà¥à¤°à¤¤à¤¿à¤¶à¤¤ *</label>
                                    <input type="number" id="disability_percentage" name="disability_percentage" min="0"
                                        max="100">
                                </div>
                            </div>
                            <div>
                                <label for="disability_documents">Upload Disability Document
                                    *<br>à¤¦à¤¿à¤µà¥à¤¯à¤¾à¤‚à¤—à¤¤à¤¾ à¤¸à¥‡ à¤¸à¤‚à¤¬à¤‚à¤§à¤¿à¤¤
                                    à¤¦à¤¸à¥à¤¤à¤¾à¤µà¥‡à¤œà¤¼ à¤…à¤ªà¤²à¥‹à¤¡ à¤•à¤°à¥‡à¤‚ *</label>
                                <input type="file" id="disability_documents" name="disability_documents" multiple
                                    accept=".pdf,.jpg,.jpeg,.png">
                                <small>Accepted formats: PDF, JPG, JPEG, PNG<br>à¤¸à¥à¤µà¥€à¤•à¥ƒà¤¤
                                    à¤«à¤¼à¥‰à¤°à¥à¤®à¥‡à¤Ÿ: PDF, JPG, JPEG, PNG</small>
                            </div>
                        </fieldset>
                        <fieldset class="section">
                            <legend>Your Query:<br>à¤†à¤ªà¤•à¥€ à¤¸à¤®à¤¸à¥à¤¯à¤¾:</legend>
                            <p>Please describe your issue or what you'd like to discuss.<br>à¤•à¥ƒà¤ªà¤¯à¤¾ à¤…à¤ªà¤¨à¥€
                                à¤¸à¤®à¤¸à¥à¤¯à¤¾ à¤¯à¤¾ à¤†à¤ª à¤œà¤¿à¤¸ à¤µà¤¿à¤·à¤¯ à¤ªà¤° à¤šà¤°à¥à¤šà¤¾
                                à¤•à¤°à¤¨à¤¾ à¤šà¤¾à¤¹à¤¤à¥‡ à¤¹à¥ˆà¤‚ à¤‰à¤¸à¤•à¤¾ à¤µà¤°à¥à¤£à¤¨ à¤•à¤°à¥‡à¤‚à¥¤</p>
                            <textarea id="query_text" name="query_text" rows="4" cols="50"
                                placeholder="Enter your query here..."></textarea>
                        </fieldset>
                        <fieldset class="section">
                            <legend>Record Your Issue (Optional) <br>à¤…à¤ªà¤¨à¥€ à¤¸à¤®à¤¸à¥à¤¯à¤¾
                                à¤°à¤¿à¤•à¥‰à¤°à¥à¤¡ à¤•à¤°à¥‡à¤‚ (à¤µà¥ˆà¤•à¤²à¥à¤ªà¤¿à¤•) </legend>
                            <p>You can record your issue or concern (maximum 1 minute) <br>à¤†à¤ª à¤…à¤ªà¤¨à¥€
                                à¤¸à¤®à¤¸à¥à¤¯à¤¾ à¤¯à¤¾ à¤šà¤¿à¤‚à¤¤à¤¾ à¤•à¥‹ à¤°à¤¿à¤•à¥‰à¤°à¥à¤¡ à¤•à¤°
                                à¤¸à¤•à¤¤à¥‡ à¤¹à¥ˆà¤‚ (à¤…à¤§à¤¿à¤•à¤¤à¤® à¥§ à¤®à¤¿à¤¨à¤Ÿ) </p>
                            <div class="note-box"
                                style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #3b82f6; font-size: 0.9em; color: #555;">
                                <p style="margin-bottom: 5px;"><strong>Note:</strong> By recording audio, you consent to
                                    the storage of your voice recordings for the purposes of therapy analysis.</p>
                                <p style="margin: 0;"><strong>à¤¨à¥‹à¤Ÿ:</strong> à¤‘à¤¡à¤¿à¤¯à¥‹ à¤°à¤¿à¤•à¥‰à¤°à¥à¤¡
                                    à¤•à¤°à¤•à¥‡, à¤†à¤ª à¤¥à¥‡à¤°à¥‡à¤ªà¥€ à¤à¤¨à¤¾à¤²à¤¿à¤¸à¤¿à¤¸ à¤•à¥‡
                                    à¤®à¤•à¤¸à¤¦ à¤¸à¥‡ à¤…à¤ªà¤¨à¥€ à¤µà¥‰à¤¯à¤¸ à¤°à¤¿à¤•à¥‰à¤°à¥à¤¡à¤¿à¤‚à¤— à¤•à¥‹
                                    à¤¸à¥à¤Ÿà¥‹à¤° à¤•à¤°à¤¨à¥‡ à¤•à¥€ à¤¸à¤¹à¤®à¤¤à¤¿ à¤¦à¥‡à¤¤à¥‡ à¤¹à¥ˆà¤‚à¥¤</p>
                            </div>
                            <div id="recording-controls">
                                <button type="button" id="startRecording" class="btn btn-secondary">
                                    <span class="record-icon">â—</span> Start Recording
                                </button>
                                <div id="recording-active" class="hidden">
                                    <div id="recording-timer">00:00</div>
                                    <div class="recording-buttons">
                                        <button type="button" id="pauseRecording" class="btn btn-outline">Pause</button>
                                        <button type="button" id="cancelRecording"
                                            class="btn btn-outline">Cancel</button>
                                        <button type="button" id="stopRecording" class="btn btn-primary">Done</button>
                                    </div>
                                </div>
                                <div id="recording-complete" class="hidden">
                                    <audio id="audioPlayback" controls></audio>
                                    <div class="recording-buttons">
                                        <button type="button" id="discardRecording" class="btn btn-outline">Discard &
                                            Record Again</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="voice_recording_path" name="voice_recording_path">
                        </fieldset>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </form>
                    <div id="booking-message" class="message hidden" role="alert" aria-live="assertive"></div>
                </section>
            </div>
            <section id="prepayment-summary" class="form-card hidden" aria-labelledby="prepayment-heading">
                <h3 id="prepayment-heading">Confirm Your Booking<br>à¤…à¤ªà¤¨à¥€ à¤¬à¥à¤•à¤¿à¤‚à¤— à¤•à¥€
                    à¤ªà¥à¤·à¥à¤Ÿà¤¿ à¤•à¤°à¥‡à¤‚</h3>
                <div id="payment-details" class="user-details-summary">
                    <!-- user booking details will be added here-->
                </div>
                <div class="coupon-section">
                    <label for="coupon-code" class="sr-only">Do you have a coupon code? Enter it here.<br>à¤•à¥à¤¯à¤¾
                        à¤†à¤ªà¤•à¥‡ à¤ªà¤¾à¤¸ à¤•à¥‹à¤ˆ à¤•à¥‚à¤ªà¤¨ à¤•à¥‹à¤¡ à¤¹à¥ˆ? à¤¯à¤¹à¤¾à¤ à¤¦à¤°à¥à¤œ
                        à¤•à¤°à¥‡à¤‚à¥¤</label>
                    <input type="text" id="coupon-code" placeholder="Enter Coupon Code">
                    <button class="btn btn-secondary" id="apply-coupon-btn">Apply</button>
                    <button class="btn btn-danger hidden" id="remove-coupon-btn">Remove Coupon</button>
                    <div id="coupon-message" class="message hidden" role="alert" aria-live="polite"
                        style="margin-top: 10px;"></div>
                </div>
                <div class="payment-summary">
                    <p>Subtotal: <span id="subtotal"></span></p>
                    <p>Discount: <span id="discount">0</span></p>
                    <p>Total: <span id="total-amount"></span></p>
                </div>
                <div class="payment-actions">
                    <div class="terms-checkbox" style="margin-bottom: 20px;">
                        <label
                            style="display: flex; align-items: flex-start; gap: 10px; font-size: 0.9em; line-height: 1.5;">
                            <input type="checkbox" id="terms-consent" required>
                            <div>
                                <p style="margin-bottom: 10px;">
                                    By submitting this form, you certify that the information provided is accurate.
                                    You voluntarily consent to share your personal details and voice recordings for
                                    therapy purposes
                                    and agree to abide by our
                                    <a href="https://www.galaxyhealingworld.in/terms-conditions/" target="_blank">Terms
                                        &amp; Conditions</a>,
                                    <a href="https://www.galaxyhealingworld.in/privacy-policy/" target="_blank">Privacy
                                        Policy</a>
                                    and
                                    <a href="https://www.galaxyhealingworld.in/refund-cancellation-policy/"
                                        target="_blank">Refund &amp; Cancellation Policy</a>.
                                </p>
                                <p style="margin: 0;">
                                    à¤‡à¤¸ à¤«à¥‰à¤°à¥à¤® à¤•à¥‹ à¤¸à¤¬à¤®à¤¿à¤Ÿ à¤•à¤°à¤•à¥‡, à¤†à¤ª
                                    à¤ªà¥à¤·à¥à¤Ÿà¤¿ à¤•à¤°à¤¤à¥‡ à¤¹à¥ˆà¤‚ à¤•à¤¿ à¤†à¤ªà¤•à¥‡ à¤¦à¥à¤µà¤¾à¤°à¤¾
                                    à¤¦à¥€ à¤—à¤ˆ à¤œà¤¾à¤¨à¤•à¤¾à¤°à¥€ à¤¸à¤¹à¥€ à¤¹à¥ˆà¥¤
                                    à¤†à¤ª à¤…à¤ªà¤¨à¥€ à¤¸à¥à¤µà¥‡à¤šà¥à¤›à¤¾ à¤¸à¥‡ à¤…à¤ªà¤¨à¥€
                                    à¤¸à¥à¤µà¤¾à¤¸à¥à¤¥à¥à¤¯ à¤¸à¤‚à¤¬à¤‚à¤§à¥€ à¤œà¤¾à¤¨à¤•à¤¾à¤°à¥€ à¤”à¤°
                                    à¤µà¥‰à¤¯à¤¸ à¤°à¤¿à¤•à¥‰à¤°à¥à¤¡à¤¿à¤‚à¤— à¤¸à¤¾à¤à¤¾ à¤•à¤°à¤¨à¥‡ à¤¤à¤¥à¤¾
                                    à¤¹à¤®à¤¾à¤°à¥‡
                                    <a href="https://www.galaxyhealingworld.in/terms-conditions/"
                                        target="_blank">à¤¨à¤¿à¤¯à¤®
                                        à¤”à¤° à¤¶à¤°à¥à¤¤à¥‡à¤‚</a>,
                                    <a href="https://www.galaxyhealingworld.in/privacy-policy/"
                                        target="_blank">à¤—à¥‹à¤ªà¤¨à¥€à¤¯à¤¤à¤¾
                                        à¤¨à¥€à¤¤à¤¿</a>
                                    à¤”à¤°
                                    <a href="https://www.galaxyhealingworld.in/refund-cancellation-policy/"
                                        target="_blank">à¤§à¤¨à¤µà¤¾à¤ªà¤¸à¥€ à¤”à¤° à¤°à¤¦à¥à¤¦à¥€à¤•à¤°à¤£
                                        à¤¨à¥€à¤¤à¤¿</a>
                                    à¤•à¥‡ à¤ªà¤¾à¤²à¤¨ à¤•à¥‡ à¤²à¤¿à¤ à¤…à¤ªà¤¨à¥€ à¤¸à¤¹à¤®à¤¤à¤¿ à¤¦à¥‡à¤¤à¥‡
                                    à¤¹à¥ˆà¤‚à¥¤
                                </p>
                            </div>
                        </label>
                    </div>
                    <button id="pay-now-btn" class="btn btn-primary">Pay Now</button>
                    <button id="book-now-btn" class="btn btn-primary hidden">Book Now</button>
                </div>
            </section>
            <section id="payment-success" class="form-card hidden" aria-labelledby="success-heading" role="alert"
                aria-live="polite">
                <h3 id="success-heading">Booking Confirmed!<br>à¤¬à¥à¤•à¤¿à¤‚à¤— à¤¸à¤‚à¤ªà¥à¤·à¥à¤Ÿ!</h3>
                <p>Thank you. Your therapy session is booked.<br>à¤§à¤¨à¥à¤¯à¤µà¤¾à¤¦. à¤†à¤ªà¤•à¤¾ à¤¥à¥‡à¤°à¥‡à¤ªà¥€
                    à¤¸à¥‡à¤¶à¤¨ à¤¬à¥à¤• à¤¹à¥‹ à¤—à¤¯à¤¾ à¤¹à¥ˆ.</p>
                <p>What's next?<br>à¤†à¤—à¥‡ à¤•à¥à¤¯à¤¾?</p>
                <ul>
                    <li>You will receive a confirmation email shortly.<br>à¤ªà¥à¤·à¥à¤Ÿà¤¿à¤•à¤°à¤£ à¤ˆà¤®à¥‡à¤²
                        à¤†à¤ªà¤•à¥‹ à¤œà¤²à¥à¤¦ à¤¹à¥€ à¤ªà¥à¤°à¤¾à¤ªà¥à¤¤ à¤¹à¥‹à¤—à¤¾à¥¤</li>
                    <li>Our team will contact you to schedule your session.<br>à¤¹à¤®à¤¾à¤°à¥€ à¤Ÿà¥€à¤® à¤†à¤ªà¤•à¤¾
                        à¤¸à¥‡à¤¶à¤¨ à¤¶à¥‡à¤¡à¥à¤¯à¥‚à¤² à¤•à¤°à¤¨à¥‡ à¤•à¥‡ à¤²à¤¿à¤ à¤†à¤ªà¤¸à¥‡ à¤¸à¤‚à¤ªà¤°à¥à¤•
                        à¤•à¤°à¥‡à¤—à¥€à¥¤</li>
                </ul>
                <button id="download-receipt-btn" class="btn btn-secondary hidden">Download Receipt</button>
                <a href="/" class="btn btn-primary">Book Another Session</a>
            </section>
            <section id="payment-failed" class="form-card hidden" aria-labelledby="failed-heading" role="alert"
                aria-live="assertive">
                <h3 id="failed-heading">Payment Failed<br>à¤­à¥à¤—à¤¤à¤¾à¤¨ à¤µà¤¿à¤«à¤² à¤°à¤¹à¤¾</h3>
                <p>Unfortunately, we were unable to process your payment.<br>à¤¦à¥à¤°à¥à¤­à¤¾à¤—à¥à¤¯ à¤¸à¥‡, à¤¹à¤®
                    à¤†à¤ªà¤•à¤¾ à¤ªà¥‡à¤®à¥‡à¤‚à¤Ÿ à¤ªà¥à¤°à¥‹à¤¸à¥‡à¤¸ à¤¨à¤¹à¥€à¤‚ à¤•à¤° à¤ªà¤¾à¤à¥¤</p>
                <p>Please try again. If the problem persists, please contact our support team.<br>à¤•à¥ƒà¤ªà¤¯à¤¾
                    à¤«à¤¿à¤° à¤¸à¥‡ à¤•à¥‹à¤¶à¤¿à¤¶ à¤•à¤°à¥‡à¤‚à¥¤ à¤…à¤—à¤° à¤¸à¤®à¤¸à¥à¤¯à¤¾ à¤¬à¤¨à¥€ à¤°à¤¹à¤¤à¥€
                    à¤¹à¥ˆ, à¤¤à¥‹ à¤•à¥ƒà¤ªà¤¯à¤¾ à¤¹à¤®à¤¾à¤°à¥€ à¤¸à¤ªà¥‹à¤°à¥à¤Ÿ à¤Ÿà¥€à¤® à¤¸à¥‡
                    à¤¸à¤‚à¤ªà¤°à¥à¤• à¤•à¤°à¥‡à¤‚à¥¤</p>
                <button class="btn btn-primary" id="retry-payment-btn">Retry Payment</button>
            </section>
        </main>
        <footer>
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/create_receipt.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>