function generateReceipt(paymentData) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Destructure payment data
    const {
        user_name,
        user_mobile,
        user_email,
        amount,
        razorpay_details,
        created_at
    } = paymentData;

    const transaction_id = razorpay_details ? razorpay_details.id : 'N/A';
    const payment_method = razorpay_details ? razorpay_details.method : 'N/A';

    // Set up the document
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(20);
    doc.text('Galaxy Healing World – Service Receipt', 105, 20, { align: 'center' });

    doc.setLineWidth(0.5);
    doc.line(20, 25, 190, 25);

    // Service Details
    doc.setFontSize(14);
    doc.text('Service Details', 20, 35);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(12);
    doc.text('Service Name: Healing Therapy', 20, 45);
    doc.text('Service Type: Online', 20, 52);
    doc.text('Session: Single Session', 20, 59);

    // Client Details
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('Client Details', 20, 74);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(12);
    doc.text(`Name: ${user_name}`, 20, 84);
    doc.text(`Phone: ${user_mobile}`, 20, 91);
    doc.text(`Email: ${user_email}`, 20, 98);

    // Payment Information
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('Payment Information', 20, 113);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(12);
    doc.text(`Amount Paid (INR): ${amount}`, 20, 123);
    doc.text(`Mode of Payment: ${payment_method}`, 20, 130);
    doc.text(`Date & Time: ${new Date(created_at).toLocaleString("en-IN", {timeZone: "Asia/Kolkata"})}`, 20, 137);
    doc.text(`Transaction ID: ${transaction_id}`, 20, 144);

    // Declaration
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('Declaration', 20, 159);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.text('This receipt acknowledges payment received for the service mentioned above.', 20, 169);
    doc.text('The amount paid is towards professional services rendered or scheduled and is not a voluntary contribution.', 20, 176);
    
    doc.setFont('helvetica', 'bold');
    doc.text('Website: www.galaxyhealingworld.in', 105, 191, { align: 'center' });
    
    doc.setLineWidth(0.5);
    doc.line(20, 200, 190, 200);
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(8);
    doc.text('This is a system-generated service receipt issued for record purposes.', 105, 205, { align: 'center' });


    // Save the PDF
    doc.save(`receipt-${transaction_id}.pdf`);
}
