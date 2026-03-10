# Payment System Testing Guide

## Quick Start Testing (For Your Lecturer Demo)

### Prerequisites
1. XAMPP/LAMPP is running
2. Database has been updated with payment fields
3. You have test accounts for both patient and doctor

---

## Test Scenario 1: Patient Books & Pays (Mock M-Pesa)

### Step 1: Login as Patient
```
URL: http://localhost/Appointment_system/login.php
Email: (any existing patient account)
Password: (patient password)
```

### Step 2: Book an Appointment
1. Click **"Book Appointment"** from dashboard
2. Select a doctor
3. Choose date and time
4. Select reason (e.g., "Routine Check-up")
5. Click **"Book Appointment"**
6. You will be **automatically redirected** to payment page

### Step 3: Make Mock M-Pesa Payment
1. You should see the payment page showing:
   - Appointment details
   - Amount to pay: KSh 500.00
   - M-Pesa option (pre-selected)

2. Enter any valid phone number:
   - Format: `254712345678`
   - Or just type: `0712345678` (auto-converts to 254712345678)
   - Or: `712345678` (auto-converts to 254712345678)

3. Click **"Pay KSh 500.00"**

4. Watch the **processing animation**:
   - Shows "Processing M-Pesa Payment..."
   - Simulates checking phone for prompt
   - Takes ~3 seconds

5. Success screen appears with:
   - ✅ Green M-Pesa receipt
   - Transaction ID (e.g., QGH8LMXYZ1)
   - Phone number used
   - Amount paid
   - Date/time

6. Click **"Go to Dashboard"**

### Step 4: Verify on Patient Dashboard
- Your appointment should now show **"Paid"** badge (green)
- No "Pay Now" button (payment complete)

---

## Test Scenario 2: Doctor Views Paid Appointment

### Step 1: Logout from Patient Account
Click logout button

### Step 2: Login as Doctor
```
URL: http://localhost/Appointment_system/login.php
Email: (any doctor account)
Password: (doctor password)
```

### Step 3: View Appointments
1. Click **"Scheduled Appointments"** from navigation
2. You should see a table with:
   - Date, Time, Patient Name
   - **Payment Status** column showing:
     - ✅ **"Paid"** with green badge
     - Amount: KSh 500.00
     - Payment date
   - **Action** column with:
     - 📄 **"Add Record"** button (enabled)

### Step 4: Try to Add Medical Record
1. Click **"Add Record"** for the PAID appointment
2. You should be able to:
   - See patient name
   - See payment confirmation badge
   - Fill in diagnosis
   - Fill in prescription
   - Add notes
   - Save medical record successfully

---

## Test Scenario 3: Unpaid Appointment (Locked for Doctor)

### Step 1: Login as Patient
Book another appointment but **DON'T PAY**:
1. Book appointment
2. When redirected to payment page, just copy the URL
3. Navigate away (go to dashboard) without paying

### Step 2: Login as Doctor
1. Go to "Scheduled Appointments"
2. Find the unpaid appointment
3. Payment Status shows: ⚠️ **"Payment Pending"** (yellow badge)
4. Action column shows: 🔒 **"Locked"** button (disabled)
5. Try clicking "Locked" button - it's disabled
6. Tooltip says "Payment required"

### Step 3: Patient Pays Later
1. Login back as patient
2. Dashboard shows "Payment Pending" with **"Pay Now"** button
3. Click "Pay Now" - goes to payment page
4. Complete payment
5. Login as doctor - now shows "Paid" and "Add Record" is enabled

---

## Test Scenarios for Different Payment Methods

### A. Mock Card Payment
1. On payment page, select **"Credit/Debit Card"**
2. Click Pay button
3. Payment succeeds with reference: PAY-XXXXX
4. Works the same as M-Pesa but simpler

### B. Mock Bank Transfer
1. Select **"Bank Transfer"**
2. Click Pay button
3. Payment succeeds immediately
4. Shows bank details (simulated)

---

## Expected Database Changes

After successful payment, check database in phpMyAdmin:

```sql
SELECT appointment_id, patient_id, doctor_id, appointment_date, 
       payment_status, payment_amount, payment_method, payment_reference, payment_date
FROM appointments 
ORDER BY appointment_id DESC 
LIMIT 5;
```

You should see:
- `payment_status` = `'paid'`
- `payment_amount` = `500.00`
- `payment_method` = `'M-Pesa'` (or other method)
- `payment_reference` = Transaction ID
- `payment_date` = Current timestamp

---

## Presentation Tips for Your Lecturer

### Key Points to Demonstrate:

1. **Realistic Flow**: Show how it mimics real-world M-Pesa
   - Phone number validation
   - Processing animation
   - Receipt generation

2. **Security**: Doctors can't proceed without payment
   - Show locked appointments
   - Explain payment verification

3. **User Experience**:
   - Smooth redirect after booking
   - Clear payment status indicators
   - Professional interface

4. **Mock vs Production**:
   - Explain this is mock/demo
   - No real money processed
   - Ready for production API integration

### Demo Script:
```
"After a patient books an appointment, they're immediately 
taken to the payment page. This is a mock M-Pesa payment 
that simulates the real Safaricom M-Pesa STK Push experience.

[Enter phone number: 254712345678]

When I click Pay, you'll see the processing animation that 
simulates checking the phone for the M-Pesa prompt.

[Wait for processing]

And here's the receipt - just like a real M-Pesa transaction 
with a transaction ID, amount, and timestamp.

Now when the doctor logs in, they can see this appointment 
is marked as PAID and they can proceed to add medical records.

If a patient hasn't paid, the doctor sees the appointment 
is LOCKED until payment is completed."
```

---

## Troubleshooting

### Issue: Payment page doesn't show
**Solution:** Check that database columns were added:
```sql
SHOW COLUMNS FROM appointments LIKE 'payment%';
```

### Issue: Doctor can access unpaid appointments
**Solution:** Clear browser cache and check `add_medical_record.php` has payment validation

### Issue: M-Pesa phone validation not working
**Solution:** Use format: 254XXXXXXXXX (12 digits total)

### Issue: Payment shows but doctor doesn't see it
**Solution:** Make sure both users are looking at the same appointment_id

---

## Sample Test Accounts

Create these if needed:

### Patient Account:
```
Email: patient@test.com
Password: patient123
Phone: 254712345678
```

### Doctor Account:
```
Email: doctor@test.com  
Password: doctor123
```

---

## Success Checklist

✅ Patient can book appointment  
✅ Patient redirected to payment page  
✅ M-Pesa phone number accepts 254XXXXXXXXX  
✅ Processing animation appears  
✅ M-Pesa receipt displays after payment  
✅ Patient dashboard shows "Paid" status  
✅ Doctor sees payment status in appointments list  
✅ Doctor can add record to paid appointments  
✅ Doctor CANNOT add record to unpaid appointments  
✅ Unpaid appointments show "Locked" button  
✅ Patient can pay later from dashboard  

---

**Good luck with your presentation! 🎉**

For any issues, check:
1. Database columns exist
2. PHP sessions are working
3. No PHP errors in `/opt/lampp/logs/error_log`
