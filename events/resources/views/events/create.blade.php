<!DOCTYPE html>
<html>

<head>
    <title>Event Management System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2>Event Detail</h2>

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('events.store') }}" method="POST" id="eventForm">
            @csrf
            <div class="mb-3">
                <label>Event Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Event Description</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Organizer</label>
                <input type="text" name="organizer" class="form-control" required>
            </div>
            <div class="mb-3">
                <h4>Tickets</h4>
                <button type="button" id="addTicket" class="btn btn-primary">Add new ticket</button>
                <table class="table mt-3" id="ticketTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ticket No</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-success">Save Event</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            let ticketCount = 0;

            function getNextId() {
                let maxId = 0;
                $('#ticketTable tbody tr').each(function() {
                    let id = parseInt($(this).find('.id').text()) || 0;
                    if (id > maxId) maxId = id;
                });
                return maxId + 1;
            }

            $('#addTicket').click(function() {
                if ($('.save-ticket:visible').length > 0) {
                    alert('Please save or delete the current editing ticket first.');
                    return;
                }

                ticketCount = getNextId();
                $('#ticketTable tbody').append(`
                    <tr data-id="${ticketCount}">
                        <td class="id">${ticketCount}</td>
                        <td>
                            <span class="display-ticket-no" style="display: none;"></span>
                            <input type="text" name="tickets[${ticketCount}][ticket_no]" class="edit-ticket-no form-control" style="display: block;" required>
                        </td>
                        <td>
                            <span class="display-price" style="display: none;"></span>
                            <input type="number" step="0.01" name="tickets[${ticketCount}][price]" class="edit-price form-control" style="display: block;" required>
                        </td>
                        <td>
                            <a href="#" class="save-ticket">Save</a>
                            <a href="#" class="delete-ticket">Delete</a>
                        </td>
                    </tr>
                `);
            });

            $('#ticketTable').on('click', '.save-ticket', function(e) {
                e.preventDefault();
                let row = $(this).closest('tr');
                let ticketNo = row.find('.edit-ticket-no').val().trim();
                let price = parseFloat(row.find('.edit-price').val());

                if (!ticketNo || isNaN(price) || price < 0) {
                    alert('Please enter a valid Ticket No and Price.');
                    return;
                }

                row.find('.display-ticket-no').text(ticketNo).show();
                row.find('.edit-ticket-no').hide();
                row.find('.display-price').text(price.toFixed(2)).show();
                row.find('.edit-price').hide();

                row.find('td:last').html(`
                    <a href="#" class="edit-ticket">Edit</a>
                    <a href="#" class="delete-ticket">Delete</a>
                `);
            });

            $('#ticketTable').on('click', '.edit-ticket', function(e) {
                e.preventDefault();
                let row = $(this).closest('tr');

                if ($('.save-ticket:visible').length > 0) {
                    alert('Please save or delete the current editing ticket first.');
                    return;
                }

                let ticketNo = row.find('.display-ticket-no').text();
                let price = row.find('.display-price').text();
                row.find('.display-ticket-no').hide();
                row.find('.edit-ticket-no').val(ticketNo).show();
                row.find('.display-price').hide();
                row.find('.edit-price').val(price).show();

                row.find('td:last').html(`
                    <a href="#" class="save-ticket">Save</a>
                    <a href="#" class="delete-ticket">Delete</a>
                `);
            });

            $('#ticketTable').on('click', '.delete-ticket', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this ticket?')) {
                    $(this).closest('tr').remove();
                }
            });

            $('#eventForm').submit(function(e) {
                e.preventDefault();
                
                if ($('.save-ticket:visible').length > 0) {
                    alert('Please save or delete all editing tickets before saving the event.');
                    return false;
                }

                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Saving...');

                // Submit form via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success message
                        $('.alert-success').remove();
                        $('#eventForm').before(`
                            <div class="alert alert-success">
                                ${response.message}
                            </div>
                        `);
                        
                        // Reset form
                        $('#eventForm')[0].reset();
                        $('#ticketTable tbody').empty();
                        ticketCount = 0;
                        
                        // Scroll to top to show success message
                        $('html, body').animate({scrollTop: 0}, 500);
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while saving the event.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            let errorList = '<ul>';
                            for (let field in errors) {
                                errors[field].forEach(error => {
                                    errorList += `<li>${error}</li>`;
                                });
                            }
                            errorList += '</ul>';
                            errorMessage = errorList;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Show error message
                        $('.alert-danger').remove();
                        $('#eventForm').before(`
                            <div class="alert alert-danger">
                                ${errorMessage}
                            </div>
                        `);
                        
                        // Scroll to top to show error message
                        $('html, body').animate({scrollTop: 0}, 500);
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
</body>

</html>