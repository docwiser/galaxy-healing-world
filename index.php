
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
            <section id="booking-flow">
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
                                    <input type="radio" name="verify_method" value="phone" onchange="updateVerificationLabel()">
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
                        <!-- ... (all the form fields from the original index.php) ... -->

                        <button type="button" class="btn btn-primary" onclick="showPrepayment()">Next</button>
                    </form>
                    <div id="booking-message" class="message hidden" role="alert" aria-live="polite"></div>
                </section>
            </section>

            <!-- Pre-payment Summary -->
            <section id="prepayment-summary" class="form-card hidden" aria-labelledby="prepayment-heading">
                <h3 id="prepayment-heading">Confirm Your Booking</h3>
                <div id="payment-details">
                    <!-- User details will be populated here -->
                </div>
                <div class="coupon-section">
                    <input type="text" id="coupon-code" placeholder="Enter Coupon Code">
                    <button class="btn btn-secondary" onclick="applyCoupon()">Apply</button>
                    <button class="btn btn-danger hidden" id="remove-coupon-btn" onclick="removeCoupon()">Remove Coupon</button>
                </div>
                <div class="payment-summary">
                    <p>Subtotal: <span id="subtotal"></span></p>
                    <p>Discount: <span id="discount">0</span></p>
                    <p>Total: <span id="total-amount"></span></p>
                </div>
                <button id="pay-now-btn" class="btn btn-primary" onclick="payNow()">Pay Now</button>
                <button id="book-now-btn" class="btn btn-primary hidden" onclick="bookNow()">Book Now</button>
            </section>

            <!-- Payment Success Page -->
            <section id="payment-success" class="form-card hidden" aria-labelledby="success-heading">
                <h3 id="success-heading">Payment Successful!</h3>
                <p>Thank you for your payment. Your therapy session is booked.</p>
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
                <button class="btn btn-primary" onclick="retryPayment()">Retry Payment</button>
            </section>

        </main>

        <footer>
            <p>&copy; 2025 Galaxy Healing World. All rights reserved.</p>
        </footer>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        let firstSessionAmount = 500; // This can be fetched from config later
        let discount = 0;
        let couponCode = null;

        function showPrepayment() {
            // Basic validation
            if (!document.getElementById('name').value || !document.getElementById('mobile').value || !document.getElementById('email').value) {
                alert('Please fill in all required fields.');
                return;
            }

            document.getElementById('booking-flow').classList.add('hidden');
            document.getElementById('prepayment-summary').classList.remove('hidden');

            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const mobile = document.getElementById('mobile').value;

            const paymentDetails = `
                <p><strong>Name:</strong> ${name}</p>
                <p><strong>Email:</strong> ${email}</p>
                <p><strong>Mobile:</strong> ${mobile}</p>
            `;
            document.getElementById('payment-details').innerHTML = paymentDetails;

            document.getElementById('subtotal').innerText = firstSessionAmount;
            document.getElementById('total-amount').innerText = firstSessionAmount;
        }

        async function applyCoupon() {
            couponCode = document.getElementById('coupon-code').value;
            if (!couponCode) {
                alert('Please enter a coupon code.');
                return;
            }

            const response = await fetch('api/validate-coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ coupon_code: couponCode, amount: firstSessionAmount })
            });

            const data = await response.json();

            if (data.success) {
                discount = data.discount;
                document.getElementById('discount').innerText = discount;
                updateTotal();
                document.getElementById('coupon-code').disabled = true;
                document.getElementById('remove-coupon-btn').classList.remove('hidden');
            } else {
                alert(data.message);
            }
        }

        function removeCoupon() {
            discount = 0;
            couponCode = null;
            document.getElementById('discount').innerText = discount;
            updateTotal();
            document.getElementById('coupon-code').value = '';
            document.getElementById('coupon-code').disabled = false;
            document.getElementById('remove-coupon-btn').classList.add('hidden');
        }

        function updateTotal() {
            const total = firstSessionAmount - discount;
            document.getElementById('total-amount').innerText = total;

            if (total <= 0) {
                document.getElementById('pay-now-btn').classList.add('hidden');
                document.getElementById('book-now-btn').classList.remove('hidden');
            } else {
                document.getElementById('pay-now-btn').classList.remove('hidden');
                document.getElementById('book-now-btn').classList.add('hidden');
            }
        }

        async function payNow() {
            const totalAmount = firstSessionAmount - discount;

            const response = await fetch('api/create-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: firstSessionAmount,
                    user_id: 1, // This should be dynamically set
                    coupon_code: couponCode
                })
            });

            const data = await response.json();

            if (data.success && data.order_id) {
                const options = {
                    key: 'YOUR_RAZORPAY_KEY_ID', // Replace with your key
                    amount: data.amount * 100,
                    currency: 'INR',
                    name: 'Galaxy Healing World',
                    description: 'First Session Booking',
                    order_id: data.order_id,
                    handler: function (response) {
                        // Payment successful
                        bookNow(response.razorpay_payment_id);
                    },
                    prefill: {
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        contact: document.getElementById('mobile').value
                    },
                    theme: {
                        color: '#667eea'
                    }
                };
                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function (response) {
                    showPaymentFailed();
                });
                rzp.open();
            } else {
                alert('Could not create order. Please try again.');
            }
        }

        async function bookNow(paymentId = null) {
            const formData = new FormData(document.getElementById('mainBookingForm'));
            if (paymentId) {
                formData.append('payment_id', paymentId);
            }
            formData.append('payment_made', firstSessionAmount - discount);


            const response = await fetch('api/book-session.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showPaymentSuccess();
            } else {
                alert('Booking failed: ' + data.message);
            }
        }

        function showPaymentSuccess() {
            document.getElementById('prepayment-summary').classList.add('hidden');
            document.getElementById('payment-success').classList.remove('hidden');
        }

        function showPaymentFailed() {
            document.getElementById('prepayment-summary').classList.add('hidden');
            document.getElementById('payment-failed').classList.remove('hidden');
        }

        function retryPayment() {
            document.getElementById('payment-failed').classList.add('hidden');
            document.getElementById('prepayment-summary').classList.remove('hidden');
        }

    </script>
</body>
</html>
