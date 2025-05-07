<?php


// Include database connection
require_once '../../Model/config.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get appointment ID from query parameter
if (!isset($_GET['id'])) {
    header("Location: myAppointments.php");
    exit();
}

$appointmentId = $_GET['id'];

// Fetch appointment details
try {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $appointmentId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment) {
        throw new Exception("Appointment not found or you don't have permission to reschedule it.");
    }
} catch (Exception $e) {
    $_SESSION['appointment_error'] = $e->getMessage();
    header("Location: myAppointments.php");
    exit();
}

// Get already booked time slots for the selected date
$bookedSlots = [];
$currentDate = date('Y-m-d');

try {
    $stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE appointment_date >= ? AND status != 'cancelled' AND id != ?");
    $stmt->bind_param("si", $currentDate, $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $bookedSlots[] = $row['appointment_date'] . '_' . $row['appointment_time'];
    }
} catch (Exception $e) {
    $error = "Error fetching booked slots: " . $e->getMessage();
}

// Convert booked slots to JSON for JavaScript
$bookedSlotsJson = json_encode($bookedSlots);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - Medic AI</title>
    <?php include '../css/links.html'; ?>
    
   
    
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
        
        .time-slots-container {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include '../../Components/client-header.php'; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Reschedule Appointment</h4>
                    </div>
                    <div class="card-body">
                        <form action="../../Controller/appointmentController.php" method="post" id="rescheduleForm">
                            <input type="hidden" name="action" value="reschedule">
                            <input type="hidden" name="id" value="<?php echo $appointmentId; ?>">
                            <input type="hidden" id="appointment-date" name="appointment_date" required>

                            <div class="row">
                                <!-- Date Selection with Calendar on Left -->
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="card-title-month">Select New Date</div>
                                            <div id="calendar-container"></div>
                                            <div id="selected-date-display" class="date-selected mt-3" style="display: none;">
                                                Selected date: <span id="selected-date-text"></span>
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
                            
                            <div class="d-flex justify-content-end mt-4">
                                <a href="myAppointments.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Reschedule Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookedSlots = <?php echo $bookedSlotsJson; ?>;
            
            // Initialize flatpickr
            const appointmentDate = flatpickr("#calendar-container", {
                inline: true,
                enableTime: false,
                dateFormat: "Y-m-d",
                minDate: "today",
                defaultDate: "<?php echo $appointment['appointment_date']; ?>",
                onChange: function(selectedDates, dateStr) {
                    document.getElementById('appointment-date').value = dateStr;
                    
                    // Show the selected date
                    document.getElementById('selected-date-text').innerText = new Date(dateStr).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    document.getElementById('selected-date-display').style.display = 'block';
                    
                    updateTimeSlots(dateStr);
                }
            });
            
            // Update time slots based on selected date
            function updateTimeSlots(selectedDate) {
                const timeInputs = document.querySelectorAll('input[name="appointment_time"]');
                timeInputs.forEach(input => {
                    const timeSlot = selectedDate + '_' + input.value;
                    
                    // Enable all time slots first
                    input.disabled = false;
                    input.checked = false;
                    
                    // Then disable booked ones
                    if (bookedSlots.includes(timeSlot)) {
                        input.disabled = true;
                    }
                    
                    // If this is the current appointment time and date, select it
                    if (selectedDate === "<?php echo $appointment['appointment_date']; ?>" && 
                        input.value === "<?php echo $appointment['appointment_time']; ?>") {
                        input.checked = true;
                    }
                });
            }
            
            // Set initial date and time
            document.getElementById('appointment-date').value = "<?php echo $appointment['appointment_date']; ?>";
            
            // Display the initially selected date
            document.getElementById('selected-date-text').innerText = new Date("<?php echo $appointment['appointment_date']; ?>").toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            document.getElementById('selected-date-display').style.display = 'block';
            
            // Initialize time slots
            updateTimeSlots("<?php echo $appointment['appointment_date']; ?>");
            
            // Form validation
            document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
                const appointmentDate = document.getElementById('appointment-date').value;
                const appointmentTime = document.querySelector('input[name="appointment_time"]:checked');
                
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
        });
    </script>
</body>
</html>