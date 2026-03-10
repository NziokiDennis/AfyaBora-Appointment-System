<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AfyaBora Outpatient System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Bootstrap -->
    <style>
        /* =================== Global Reset =================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        /* =================== Full-Page Layout =================== */
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden; /* Prevent scrolling */
            display: flex;
            flex-direction: column;
        }

        /* =================== Landing Page Styling =================== */
        .landing-page {
            position: relative;
            width: 100%;
            height: 100vh; /* Full screen */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        /* Background Image */
        .bg-image {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures image covers entire screen */
            z-index: -1; /* Moves image to background */
        }

        /* Dark Overlay for Readability */

        .overlay {
            position: relative;
            z-index: 1;
            background: rgba(0, 0, 0, 0.6); /* Dark overlay */
            padding: 20px 50px; /* Increase horizontal padding */
            border-radius: 10px;
            width: 60%; /* Make it wider */
            max-width: 700px; /* Ensure it's not too wide */
            height: auto; /* Let the content dictate height */
        }


        /* =================== Navbar =================== */
        .navbar {
            background: #343a40;
            padding: 15px 0;
            text-align: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .navbar ul {
            list-style: none;
            padding: 0;
        }

        .navbar ul li {
            display: inline;
            margin: 0 15px;
        }

        .navbar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }

        .navbar ul li a:hover {
            text-decoration: underline;
        }

        /* =================== Footer (Always Visible) =================== */
        footer {
            background: #343a40;
            color: white;
            text-align: center;
            padding: 15px 0;
            width: 100%;
            position: absolute;
            bottom: 0;
        }

        /* =================== Buttons =================== */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px; /* Slightly smaller */
            text-decoration: none;
            color: white;
            background: #007bff;
            border-radius: 5px;
            transition: background 0.3s ease-in-out;
        }

        .btn:hover {
            background: #0056b3;
        }

        /* =================== Responsive Design =================== */
        @media (max-width: 768px) {
            .overlay {
                width: 80%; /* Make it fit better on smaller screens */
                max-width: 90%;
                padding: 20px;
            }

            .overlay h1 {
                font-size: 28px;
            }

            .overlay p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include "partials/navbar.php"; ?>

    <!-- Full-Page Landing Section -->
    <div class="landing-page">
        <img src="assets/images/hospital.jpg" alt="Hospital Background" class="bg-image">
        <div class="overlay">
            <h1 class="display-6">Welcome to AfyaBora  Outpatient System</h1> <!-- Reduced size -->
            <p class="lead">Efficient and secure outpatient management for doctors, patients, and administrators.</p>
            <a href="register.php" class="btn btn-primary">Get Started</a>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include "partials/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS -->
</body>
</html>
