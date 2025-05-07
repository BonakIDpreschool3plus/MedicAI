<?php


// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Get user information from session
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];
$email = $_SESSION['email'] ?? '';

// Get already booked time slots from database
$bookedSlots = [];
$currentDate = date('Y-m-d');

// Replace your current booked slots query with this:
try {
    // Only consider confirmed or completed appointments as "booked"
    $stmt = $conn->prepare("
        SELECT appointment_date, appointment_time 
        FROM appointments 
        WHERE appointment_date >= ? 
        AND (status = 'confirmed' OR status = 'completed')
    ");
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[] = $row['appointment_date'] . '_' . $row['appointment_time'];
    }
} catch (Exception $e) {
    $error = "Error retrieving booked slots: " . $e->getMessage();
}
// Convert booked slots to JSON for JavaScript
$bookedSlotsJson = json_encode($bookedSlots);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Book Appointment - Medic AI</title>
    
    
    <style>
        .form-check-input {
            display: none;
        }
        
        .form-check-label {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .form-check-input:checked + .form-check-label {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        
        .form-check-input:disabled + .form-check-label {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #e9ecef;
        }
        
        /* Make flatpickr calendar always visible */
        .flatpickr-calendar {
            box-shadow: none !important;
            position: static !important;
            display: block !important;
            margin-bottom: 15px;
        }
        
        .card-title-month {
            text-align: center;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .date-selected {
            border: 2px solid #0d6efd;
            border-radius: 5px;
            padding: 8px;
            margin-top: 10px;
            font-weight: 500;
        }
        
        .file-upload-wrapper {
            position: relative;
            text-align: right;
            margin-bottom: 20px;
        }
        
        .time-slots-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>
    <div class="page-header">
        <div class="container">
            <h1 class="mb-0">Set up an Appointment</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="patientDashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Appointment Scheduling</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Book an Appointment</h4>
                    </div>
                    <div class="card-body">
                        <form action="../../Controller/appointmentController.php" method="post" enctype="multipart/form-data" id="appointmentForm">
                            <input type="hidden" name="action" value="book">
                            <input type="hidden" name="userId" value="<?php echo $userId; ?>">
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Appointments</h5>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="patientAI.php">Medic AI</a></li>
                                            <li class="breadcrumb-item active">Appointments</li>
                                        </ol>
                                    </nav>
                                </div>
                                <hr>
                            </div>
                            
                            <!-- File Upload at the Top Right -->
                            <div class="file-upload-wrapper mb-4">
                                <div class="d-flex align-items-center justify-content-end">
                                    <div class="text-end me-3">
                                        <span class="d-block">Do you have MedicalAI Diagnostic Result?</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" id="file-upload-btn">
                                            Attach File
                                        </button>
                                        <input type="file" id="medical-records" name="medical_records" accept=".pdf,.jpg,.jpeg,.png" class="d-none">
                                    </div>
                                </div>
                                <div id="file-name" class="text-end small text-muted mt-1"></div>
                            </div>
                            
                            <div class="row">
                                <!-- Date Selection with Calendar on Left -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-title-month">Select Date</div>
                                            <div id="calendar-container"></div>
                                            <input type="hidden" id="appointment-date" name="appointment_date" class="form-control" required>
                                            <div id="selected-date-display" class="date-selected mt-3" style="display: none;">
                                                Selected date: <span id="selected-date-text"></span>
                                            </div>
                                            <div class="d-grid gap-2 mt-3">
                                                <button type="submit" class="btn btn-success text-white">Book Appointment</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Time Selection on Right -->
                                <div class="col-md-8">
                                    <div class="time-slots-container">
                                        <!-- Morning Time Slots -->
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6>Morning</h6>
                                                <p class="text-muted small">8:00am to 12:00pm</p>
                                                
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-8am" value="08:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-8am">
                                                                8:00am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-830am" value="08:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-830am">
                                                                8:30am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-9am" value="09:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-9am">
                                                                9:00am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-930am" value="09:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-930am">
                                                                9:30am
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row g-2">
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-10am" value="10:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-10am">
                                                                10:00am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-1030am" value="10:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-1030am">
                                                                10:30am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-11am" value="11:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-11am">
                                                                11:00am
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-1130am" value="11:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-1130am">
                                                                11:30am
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Evening Time Slots -->
                                        <div class="card">
                                            <div class="card-body">
                                                <h6>Evening</h6>
                                                <p class="text-muted small">1:00pm to 5:00pm</p>
                                                
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-1pm" value="13:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-1pm">
                                                                1:00pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-130pm" value="13:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-130pm">
                                                                1:30pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-2pm" value="14:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-2pm">
                                                                2:00pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-230pm" value="14:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-230pm">
                                                                2:30pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row g-2">
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-3pm" value="15:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-3pm">
                                                                3:00pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-330pm" value="15:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-330pm">
                                                                3:30pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-4pm" value="16:00:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-4pm">
                                                                4:00pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-6">
                                                        <div class="form-check time-radio">
                                                            <input class="form-check-input" type="radio" name="appointment_time" id="time-430pm" value="16:30:00">
                                                            <label class="form-check-label w-100 text-center py-2 px-1 rounded border" for="time-430pm">
                                                                4:30pm
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../js/scripts.html'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Store booked slots from PHP - critical for detecting already booked times
            const bookedSlots = <?php echo $bookedSlotsJson; ?>;
            
            // Debug log to check values - uncomment if needed
            // console.log('Booked slots:', bookedSlots);
            // console.log('User ID:', <?php echo $userId; ?>);
            
            // Initialize flatpickr with inline calendar
            const appointmentDate = flatpickr("#calendar-container", {
                inline: true,
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today",
                defaultDate: "today",
                locale: {
                    firstDayOfWeek: 1 // Start week on Monday
                },
                onChange: function(selectedDates, dateStr) {
                    // Update the hidden input
                    document.getElementById('appointment-date').value = dateStr;
                    
                    // Show the selected date
                    document.getElementById('selected-date-text').innerText = new Date(dateStr).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    document.getElementById('selected-date-display').style.display = 'block';
                    
                    // Update time slots when date changes
                    updateTimeSlots(dateStr);
                }
            });
            
            // Function to update time slots based on selected date
            function updateTimeSlots(selectedDate) {
                if (!selectedDate) return;
                
                // Disable already booked time slots
                const timeInputs = document.querySelectorAll('input[name="appointment_time"]');
                timeInputs.forEach(input => {
                    const timeSlot = selectedDate + '_' + input.value;
                    
                    // Enable all time slots first
                    input.disabled = false;
                    input.checked = false;
                    input.parentElement.classList.remove('opacity-50');
                    
                    // Then disable booked ones
                    if (bookedSlots.includes(timeSlot)) {
                        input.disabled = true;
                        input.checked = false;
                        input.parentElement.classList.add('opacity-50');
                    }
                });
            }
            
            // Setup file upload button
            document.getElementById('file-upload-btn').addEventListener('click', function() {
                document.getElementById('medical-records').click();
            });
            
            // Show filename when file is selected
            document.getElementById('medical-records').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : '';
                document.getElementById('file-name').textContent = fileName ? 'Selected file: ' + fileName : '';
            });
            
            // Form validation before submit
            document.getElementById('appointmentForm').addEventListener('submit', function(e) {
                const appointmentDate = document.getElementById('appointment-date').value;
                const appointmentTime = document.querySelector('input[name="appointment_time"]:checked');
                
                // Debug log to check values before submission
                console.log('Submitting form with:', {
                    date: appointmentDate,
                    time: appointmentTime ? appointmentTime.value : 'none selected',
                    userId: <?php echo $userId; ?>
                });
                
                if (!appointmentDate || !appointmentTime) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Incomplete Form',
                        text: 'Please select both date and time for your appointment',
                        icon: 'warning',
                        confirmButtonText: 'Ok'
                    });
                }
            });
            
            // Set initial values - important to prevent empty submissions
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('appointment-date').value = today;
            updateTimeSlots(today);
            
            // Show error message if exists
            <?php if(isset($_SESSION['appointment_error'])): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo $_SESSION['appointment_error']; ?>',
                icon: 'error',
                confirmButtonText: 'Ok'
            });
            <?php unset($_SESSION['appointment_error']); ?>
            <?php endif; ?>


            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('booking_success')) {
                // Remove the query parameter from the URL without reloading the page
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
                
                // Show the success SweetAlert
                Swal.fire({
                    title: 'Appointment Booked!',
                    html: `
                        <div class="text-center">
                            <i class="bi bi-check-circle-fill text-success mb-4" style="font-size: 4rem;"></i>
                            <p class="mt-3"><?php echo isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "Your appointment has been booked successfully."; ?></p>
                            <p class="small text-muted mt-2">Appointment ID: #<?php echo isset($_SESSION['appointment_id']) ? $_SESSION['appointment_id'] : ""; ?></p>
                        </div>
                    `,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'View My Appointments',
                    cancelButtonText: 'Close'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'myAppointments.php';
                    }
                    // Clean up session variables regardless of user choice
                    <?php 
                    unset($_SESSION['appointment_success']);
                    unset($_SESSION['success_message']);
                    unset($_SESSION['appointment_id']);
                    ?>
                });
            }
            
            // Also handle existing session variables if user refreshes or comes from elsewhere
            <?php if(isset($_SESSION['appointment_success']) && $_SESSION['appointment_success'] === true): ?>
            Swal.fire({
                title: 'Appointment Booked!',
                html: `
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill text-success mb-4" style="font-size: 4rem;"></i>
                        <p class="mt-3"><?php echo $_SESSION['success_message']; ?></p>
                        <p class="small text-muted mt-2">Appointment ID: #<?php echo $_SESSION['appointment_id']; ?></p>
                    </div>
                `,
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'View My Appointments',
                cancelButtonText: 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'myAppointments.php';
                }
            });
            <?php 
            unset($_SESSION['appointment_success']);
            unset($_SESSION['success_message']);
            unset($_SESSION['appointment_id']);
            ?>
            <?php endif; ?>
                });
    </script>
</body>
</html>