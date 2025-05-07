<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'View/css/links.html'; ?>
    <title>Document</title>
</head>
<style>
    .right-container {
        background: url('Assets/images/hero.png') no-repeat center / cover
    }

    @media (max-width: 576px) {
        .right-container {
            display: none;
        }

    }
</style>
<body>

<div class="main d-flex row" style="height: 100vh; width: 100vw;">
    <div class="left-container d-flex flex-column col-sm-6">
        <header class="px-3">
            <img src="Assets/images/logo-label.png" alt="" width="200px" class="mt-3">
        </header>

        <div class="container w-75 d-flex flex-column justify-content-center align-items-start gap-3 flex-grow-1">
            <h1 style="font-size:2.5vw">MedicAI: A Web-Based <br>Patient Management System <br>with AI Health Insights</h1>

        
            <p class="text-secondary">Empowering Healthcare with Intelligent Care</p>
            <a href="View/guestDashboard.php" class="btn btn-primary">Use Medic AI</a>
            <a href="View/login.php" class="btn btn-primary">Set up an Appointment</a>
        </div>
        
   
    </div>
    <div class="right-container col-sm-6 ">

    </div>
</div>
    

<?php include 'View/js/scripts.html';?>
</body>
</html>