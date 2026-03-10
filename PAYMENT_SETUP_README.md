# Payment Functionality Setup Guide

## Overview
This guide explains the new **MOCK PAYMENT** functionality added to your appointment system. After booking an appointment, patients must pay a consultation fee before doctors can proceed with their appointments.

**IMPORTANT:** This is a **demonstration/mock payment system** - no real money is processed. It simulates how M-Pesa and other payment methods would work in a production environment.

## Features Added

### 1. **Mock M-Pesa Payment Flow** ⭐
- **Realistic M-Pesa simulation** with STK Push-like experience
- After booking an appointment, patients are automatically redirected to a payment page
- Features realistic M-Pesa interface:
  - Phone number validation (254XXXXXXXXX format)
  - Processing animation simulating STK Push
  - M-Pesa-style receipt with transaction ID
  - Success confirmation screen
- Also supports mock Card and Bank Transfer payments
- Default appointment fee: **KSh 500.00** (configurable in database)
- **No real money is processed - 100% simulation for demonstration**

### 2. **Patient Dashboard Updates**
- Shows payment status for all appointments
- Displays payment badges: 
  - ✅ **Paid** (Green badge)
  - ⚠️ **Payment Pending** (Yellow badge)
- Quick "Pay Now" button for unpaid appointments

### 3. **Doctor Restrictions**
- Doctors can only add medical records for **PAID** appointments
- Unpaid appointments show a 🔒 **Locked** button
- Payment status clearly displayed with:
  - Payment amount
  - Payment date (when paid)
  - Visual indicators

### 4. **Security Features**
- Payment verification before allowing doctor access
- Patient-specific payment pages (can't pay for other patients' appointments)
- Payment reference tracking for accountability

## Database Changes

### New Fields Added to `appointments` Table:
- `payment_status` - ENUM('pending', 'paid') - Tracks payment status
- `payment_amount` - DECIMAL(10,2) - Default: 500.00 KSh
- `payment_date` - TIMESTAMP - Records when payment was made
- `payment_method` - VARCHAR(50) - Stores payment method used
- `payment_reference` - VARCHAR(100) - Unique payment reference number

## Installation Steps

### Step 1: Apply Database Changes
Run the SQL script to update your database:

```bash
mysql -u root -p bilpham_outpatients_system < database/add_payment_functionality.sql
```

Or manually execute the SQL commands in phpMyAdmin:
1. Open phpMyAdmin
2. Select your database `bilpham_outpatients_system`
3. Go to SQL tab
4. Copy and paste the contents of `database/add_payment_functionality.sql`
5. Click "Go" to execute

### Step 2: Verify Installation
After applying the database changes:
1. Check that the `appointments` table has the new payment columns
2. Existing appointments should have `payment_status` set to 'pending' or 'paid'

### Step 3: Test the System

#### As a Patient:
1. Login to your patient account
2. Book a new appointment
3. You'll be redirected to the payment page
4. Complete the payment (demo mode - any payment method works)
5. Verify the appointment shows "Paid" status in your dashboard

#### As a Doctor:
1. Login to your doctor account
2. View scheduled appointments
3. Notice payment status column
4. Try to add a medical record for an unpaid appointment (should be locked)
5. Add a medical record for a paid appointment (should work)

## File Structure

### New Files Created:
- `patients/payment.php` - Payment interface for patients
- `database/add_payment_functionality.sql` - Database migration script
- `PAYMENT_SETUP_README.md` - This documentation file

### Modified Files:
- `patients/book_appointment.php` - Redirects to payment after booking
- `patients/dashboard.php` - Shows payment status and "Pay Now" button
- `doctors/appointments.php` - Displays payment status, locks unpaid appointments
- `doctors/add_medical_record.php` - Validates payment before allowing record entry

## Configuration

### Changing the Default Fee
To change the appointment fee (currently KSh 500.00):

1. Open phpMyAdmin or MySQL client
2. Run this SQL command:
```sql
ALTER TABLE appointments 
ALTER COLUMN payment_amount SET DEFAULT 1000.00;
```
Replace `1000.00` with your desired amount.

### For Existing Appointments:
```sql
UPDATE appointments 
SET payment_amount = 1000.00 
WHERE payment_amount = 500.00;
```

## Mock Payment Details

### What Happens During Mock Payment:
1. **Patient selects M-Pesa** and enters phone number (e.g., 254712345678)
2. System shows **processing animation** (simulating STK Push)
3. Payment is automatically marked as successful in database
4. Patient receives **M-Pesa-style receipt** with:
   - Transaction ID (e.g., QGH8LMXYZ1)
   - Phone number
   - Amount paid
   - Payment date/time
5. Doctor can now see the appointment as **PAID** and proceed

### For Demo/Presentation:
- Any phone number in format 254XXXXXXXXX will work
- Payment always succeeds (100% success rate)
- Transaction IDs are randomly generated
- Process takes 3 seconds to simulate real M-Pesa delay

## Payment Integration (Future Production Enhancement)

To convert this to a real payment system:

### For M-Pesa (Safaricom):
- Sign up for Daraja API at https://developer.safaricom.co.ke/
- Get Consumer Key and Consumer Secret
- Update `patients/payment.php` to:
  - Generate OAuth tokens
  - Send STK Push requests to Daraja API
  - Handle callback URLs for payment confirmation
  - Verify transaction status

### For Credit/Debit Cards:
- Integrate with payment processors:
  - Stripe (https://stripe.com)
  - Flutterwave (https://flutterwave.com)
  - Pesapal (https://pesapal.com)
  - PayPal

## Troubleshooting

### Issue: Payment page not showing
**Solution:** Ensure the database changes were applied correctly. Check if `payment_status` and `payment_amount` columns exist in the `appointments` table.

### Issue: Doctors can't access any appointments
**Solution:** Run this SQL to mark existing appointments as paid:
```sql
UPDATE appointments 
SET payment_status = 'paid', payment_date = NOW() 
WHERE status = 'scheduled';
```

### Issue: New appointments still showing as unpaid after payment
**Solution:** Check your PHP error logs. Ensure the payment form is submitting correctly and the database is being updated.

## Support

For issues or questions:
1. Check the database structure matches the schema
2. Verify file permissions on new PHP files
3. Check Apache/PHP error logs: `/opt/lampp/logs/error_log`
4. Ensure all files have proper includes (navbar, footer, auth)

## Summary of User Flow

```
PATIENT FLOW:
1. Login → Dashboard
2. Click "Book Appointment"
3. Fill appointment details → Submit
4. Redirected to Payment Page
5. Select payment method → Pay
6. Payment confirmed → Dashboard shows "Paid" status

DOCTOR FLOW:
1. Login → Dashboard
2. View "Scheduled Appointments"
3. See payment status for each appointment
4. Click "Add Record" for PAID appointments only
5. Add medical record → Appointment marked complete
```

---

**Version:** 1.0  
**Date:** February 10, 2026  
**Compatibility:** PHP 7.4+, MySQL 5.7+, MariaDB 10.4+
