<?php 
 include '../../controller/myTimeController.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../css/links.html'; ?>
    <title>Manage Unavailability - Clinic System</title>
    <style>
        .time-slot {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #dc3545;
        }
        
        .day-header {
            background-color: #e9ecef;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .time-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .specific-date {
            color: #dc3545;
            font-weight: 500;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        
        .btn-add {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-add:hover {
            background-color: #bb2d3b;
            border-color: #bb2d3b;
        }
    </style>
</head>
<body>
    <?php include '../../Components/doctor-header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Not Available Schedule</h1>
                <p class="text-muted">Manage times when you are NOT available for appointments</p>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addUnavailabilityModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Unavailability
                </button>
            </div>
        </div>
        
        <?php if(isset($_SESSION['time_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['time_success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['time_success']); endif; ?>
        
        <?php if(isset($_SESSION['time_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['time_error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['time_error']); endif; ?>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if(empty($availabilitySlots)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-check text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No Unavailability Set</h4>
                        <p class="text-muted">You haven't set any unavailable times yet. By default, you are considered available for all office hours (8:00 AM to 5:00 PM).</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="availabilityTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availabilitySlots as $slot): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($slot['date'])); ?></td>
                                    <td><?php echo date('l', strtotime($slot['date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($slot['start_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($slot['end_time'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUnavailabilityModal"
                                            data-id="<?php echo $slot['id']; ?>"
                                            data-start="<?php echo $slot['start_time']; ?>"
                                            data-end="<?php echo $slot['end_time']; ?>"
                                            data-date="<?php echo $slot['date']; ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="<?php echo $slot['id']; ?>"
                                            data-date="<?php echo date('M d, Y', strtotime($slot['date'])); ?>"
                                            data-time="<?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Unavailability Modal -->
    <div class="modal fade" id="addUnavailabilityModal" tabindex="-1" aria-labelledby="addUnavailabilityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="addUnavailabilityModalLabel">Add Unavailability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="add-form">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Mark times when you are <strong>NOT</strong> available for appointments
                        </div>
                        
                        <div class="mb-3">
                            <label for="specific_date" class="form-label">Date you are unavailable</label>
                            <input type="date" class="form-control" id="specific_date" name="specific_date" 
                                min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Unavailable From</label>
                                <select class="form-select" id="start_time" name="start_time" required>
                                    <option value="08:00:00">8:00 AM</option>
                                    <option value="08:30:00">8:30 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="09:30:00">9:30 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="10:30:00">10:30 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="11:30:00">11:30 AM</option>
                                    <option value="12:00:00">12:00 PM</option>
                                    <option value="12:30:00">12:30 PM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="13:30:00">1:30 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">Unavailable To</label>
                                <select class="form-select" id="end_time" name="end_time" required>
                                    <option value="08:30:00">8:30 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="09:30:00">9:30 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="10:30:00">10:30 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="11:30:00">11:30 AM</option>
                                    <option value="12:00:00">12:00 PM</option>
                                    <option value="12:30:00">12:30 PM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="13:30:00">1:30 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                    <option value="17:00:00">5:00 PM</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Set As Unavailable</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Unavailability Modal -->
    <div class="modal fade" id="editUnavailabilityModal" tabindex="-1" aria-labelledby="editUnavailabilityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="editUnavailabilityModalLabel">Edit Unavailability</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="edit-form">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Edit times when you are <strong>NOT</strong> available for appointments
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_specific_date" class="form-label">Date you are unavailable</label>
                            <input type="date" class="form-control" id="edit_specific_date" name="specific_date" 
                                min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_start_time" class="form-label">Unavailable From</label>
                                <select class="form-select" id="edit_start_time" name="start_time" required>
                                    <option value="08:00:00">8:00 AM</option>
                                    <option value="08:30:00">8:30 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="09:30:00">9:30 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="10:30:00">10:30 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="11:30:00">11:30 AM</option>
                                    <option value="12:00:00">12:00 PM</option>
                                    <option value="12:30:00">12:30 PM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="13:30:00">1:30 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_end_time" class="form-label">Unavailable To</label>
                                <select class="form-select" id="edit_end_time" name="end_time" required>
                                    <option value="08:30:00">8:30 AM</option>
                                    <option value="09:00:00">9:00 AM</option>
                                    <option value="09:30:00">9:30 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="10:30:00">10:30 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="11:30:00">11:30 AM</option>
                                    <option value="12:00:00">12:00 PM</option>
                                    <option value="12:30:00">12:30 PM</option>
                                    <option value="13:00:00">1:00 PM</option>
                                    <option value="13:30:00">1:30 PM</option>
                                    <option value="14:00:00">2:00 PM</option>
                                    <option value="14:30:00">2:30 PM</option>
                                    <option value="15:00:00">3:00 PM</option>
                                    <option value="15:30:00">3:30 PM</option>
                                    <option value="16:00:00">4:00 PM</option>
                                    <option value="16:30:00">4:30 PM</option>
                                    <option value="17:00:00">5:00 PM</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Update Unavailability</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Form (hidden) -->
    <form id="delete-form" method="post" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>
    
    <!-- Include JavaScript -->
    <?php include '../js/scripts.html'; ?>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#availabilityTable').DataTable({
            order: [[0, 'asc'], [2, 'asc']], // Sort by date ascending, then start time
            language: {
                search: "Search unavailability:",
                lengthMenu: "Show _MENU_ slots per page",
                info: "Showing _START_ to _END_ of _TOTAL_ slots",
                emptyTable: "No unavailable time slots found",
                zeroRecords: "No matching unavailable time slots found"
            }
        });
    
        // Populate edit modal
        $('.edit-btn').click(function() {
            const id = $(this).data('id');
            const start = $(this).data('start');
            const end = $(this).data('end');
            const date = $(this).data('date');
            
            $('#edit_id').val(id);
            $('#edit_specific_date').val(date);
            $('#edit_start_time').val(start);
            $('#edit_end_time').val(end);
        });
        
        // Delete button click
        $('.delete-btn').click(function() {
            const id = $(this).data('id');
            const date = $(this).data('date');
            const time = $(this).data('time');
            
            Swal.fire({
                title: 'Remove Unavailability?',
                html: `Are you sure you want to remove your unavailability on <strong>${date}</strong> from <strong>${time}</strong>?`,
                text: "This will make you available for appointments during this time.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, I am available',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete_id').val(id);
                    $('#delete-form').submit();
                }
            });
        });

        // Form validation for add form
        $('#add-form').submit(function(e) {
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();
            const specificDate = $('#specific_date').val();
            
            if(!specificDate) {
                e.preventDefault();
                Swal.fire({
                    title: 'Date Required',
                    text: 'Please select a date when you are unavailable',
                    icon: 'error'
                });
                return false;
            }
            
            if(endTime <= startTime) {
                e.preventDefault();
                Swal.fire({
                    title: 'Invalid Time Range',
                    text: 'End time must be later than start time',
                    icon: 'error'
                });
                return false;
            }
        });
        
        // Form validation for edit form
        $('#edit-form').submit(function(e) {
            const startTime = $('#edit_start_time').val();
            const endTime = $('#edit_end_time').val();
            const specificDate = $('#edit_specific_date').val();
            
            if(!specificDate) {
                e.preventDefault();
                Swal.fire({
                    title: 'Date Required',
                    text: 'Please select a date when you are unavailable',
                    icon: 'error'
                });
                return false;
            }
            
            if(endTime <= startTime) {
                e.preventDefault();
                Swal.fire({
                    title: 'Invalid Time Range',
                    text: 'End time must be later than start time',
                    icon: 'error'
                });
                return false;
            }
        });
    });
    </script>
</body>
</html>