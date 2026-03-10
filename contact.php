<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AfyaBora Outpatient System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Bootstrap -->
    <style>
        /* Global Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        html, body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1; /* Pushes footer to the bottom */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
        }

        .content-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }

        .contact-info {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        footer {
            background: #343a40;
            color: white;
            text-align: center;
            padding: 15px 0;
            width: 100%;
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include "partials/navbar.php"; ?>

    <div class="container">
        <div class="content-box">
            <h1>Contact <span class="text-primary">AfyaBora Outpatient System</span></h1>
            <p>For inquiries, support, or assistance, reach out to us through any of the following channels.</p>

            <div class="contact-info">
                📧 Email: <a href="mailto:support@bilphamclinic.com">support@afyaboraclinic.com</a> <br>
                📞 Phone: +254702129493 <br>
                📍 Address: 123 AfyaCentre Street, Nairobi, Kenya
            </div>

            <h3>Send Us a Message</h3>
            <form action="contact_process.php" method="POST">
                <div class="mb-3">
                    <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                </div>
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include "partials/footer.php"; ?>

</body>
</html>
