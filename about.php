 <?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - AfyaBora Outpatient System</title>
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

        .highlight {
            font-weight: bold;
            color: #007bff;
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
            <h1>About <span class="highlight">AfyaBora Outpatient System</span></h1>
            <p>We are committed to revolutionizing the outpatient healthcare experience by leveraging technology to improve efficiency, accessibility, and accuracy.</p>
            
            <h3>What We Offer:</h3>
            <ul class="list-group">
                <li class="list-group-item">✅ Online Appointment Scheduling</li>
                <li class="list-group-item">✅ Electronic Medical Record Management</li>
                <li class="list-group-item">✅ Secure Data Handling & Storage</li>
                <li class="list-group-item">✅ 24/7 Patient Support</li>
            </ul>

            <h3>Our Mission</h3>
            <p>To simplify outpatient management by providing a reliable, user-friendly, and secure digital solution that enhances doctor-patient interactions.</p>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include "partials/footer.php"; ?>

</body>
</html>
