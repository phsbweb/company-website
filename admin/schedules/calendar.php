<?php
include '../shared/auth.php';
require_once '../../user/attendance/db_connect.php';

// Fetch approved leaves with employee and department info
$stmt = $pdo->query("
    SELECT l.*, e.full_name, d.name as dept_name 
    FROM leaves l
    JOIN employees e ON l.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE l.status = 'approved'
");
$leaves = $stmt->fetchAll();

$events = [];
foreach ($leaves as $leave) {
    $title = $leave['full_name'] . " (" . $leave['leave_type'] . ")";
    if ($leave['day_session'] !== 'Full Day') {
        $title .= " [" . $leave['day_session'] . "]";
    }

    // FullCalendar needs end date to be exclusive for all-day events
    $end = date('Y-m-d', strtotime($leave['end_date'] . ' +1 day'));

    $events[] = [
        'title' => $title,
        'start' => $leave['start_date'],
        'end' => $end,
        'allDay' => true,
        'extendedProps' => [
            'department' => $leave['dept_name'] ?? 'No Department',
            'reason' => $leave['reason'],
            'session' => $leave['day_session']
        ],
        'backgroundColor' => ($leave['leave_type'] == 'Annual') ? '#3b82f6' : '#10b981',
        'borderColor' => ($leave['leave_type'] == 'Annual') ? '#2563eb' : '#059669',
    ];
}

// Fetch holidays
$stmt_h = $pdo->query("SELECT * FROM holidays");
$holidays = $stmt_h->fetchAll();
foreach ($holidays as $holiday) {
    $h_end = date('Y-m-d', strtotime($holiday['end_date'] . ' +1 day'));
    $events[] = [
        'title' => "🚩 " . $holiday['name'],
        'start' => $holiday['start_date'],
        'end' => $h_end,
        'allDay' => true,
        'display' => 'background',
        'backgroundColor' => '#fee2e2',
        'classNames' => ['holiday-event']
    ];
}

$events_json = json_encode($events);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Calendar - Admin</title>
    <link rel="stylesheet" href="../shared/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- FullCalendar CDN -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <style>
        .calendar-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-top: 20px;
            min-height: 800px;
        }

        /* Customizing FullCalendar to match the PHSB aesthetic */
        .fc {
            --fc-button-bg-color: #f5f5f5;
            --fc-button-border-color: var(--border-color);
            --fc-button-hover-bg-color: #eeeeee;
            --fc-button-active-bg-color: var(--accent-color);
            --fc-button-text-color: #525252;
            --fc-button-active-text-color: #ffffff;
            --fc-border-color: var(--border-color);
            --fc-today-bg-color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .fc .fc-button-primary {
            border: 1px solid var(--border-color);
            text-transform: capitalize;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 16px;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: #ffffff;
        }

        .fc-event {
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.8rem;
            border: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .holiday-event {
            opacity: 1 !important;
        }

        #eventModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-label {
            font-size: 0.75rem;
            color: #737373;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
            display: block;
        }

        .modal-value {
            font-weight: 600;
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'calendar';
    $baseUrl = '../';
    include '../shared/sidebar.php';
    ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 800;">Company Calendar</h1>
                <p style="color: #737373;">Overview of approved leaves and public holidays</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;">
                    <span style="width: 12px; height: 12px; background: #3b82f6; border-radius: 3px;"></span> Annual
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;">
                    <span style="width: 12px; height: 12px; background: #10b981; border-radius: 3px;"></span> Medical
                </div>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem;">
                    <span style="width: 12px; height: 12px; background: #fee2e2; border-radius: 3px; border: 1px solid #ef4444;"></span> Holiday
                </div>
            </div>
        </div>

        <div class="calendar-container">
            <div id='calendar'></div>
        </div>
    </div>

    <!-- Event Detail Modal -->
    <div id="eventModal">
        <div class="modal-body">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <h3 id="modalTitle" style="font-weight: 800;">Leave Details</h3>
                <button onclick="closeModal()" style="border: none; background: none; cursor: pointer; color: #737373;"><i class="fas fa-times"></i></button>
            </div>

            <span class="modal-label">Employee</span>
            <span id="modalEmployee" class="modal-value">-</span>

            <span class="modal-label">Department</span>
            <span id="modalDept" class="modal-value">-</span>

            <span class="modal-label">Duration / Session</span>
            <span id="modalDuration" class="modal-value">-</span>

            <span class="modal-label">Reason</span>
            <p id="modalReason" style="font-size: 0.9rem; color: #525252; line-height: 1.5;"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: <?php echo $events_json; ?>,
                eventClick: function(info) {
                    if (info.event.display === 'background') return; // Don't show modal for holidays

                    document.getElementById('modalEmployee').innerText = info.event.title;
                    document.getElementById('modalDept').innerText = info.event.extendedProps.department;
                    document.getElementById('modalReason').innerText = info.event.extendedProps.reason || 'No reason provided';

                    let durationText = info.event.start.toLocaleDateString();
                    if (info.event.end) {
                        // Subtract one day for display because FullCalendar end is exclusive
                        let actualEnd = new Date(info.event.end);
                        actualEnd.setDate(actualEnd.getDate() - 1);
                        if (actualEnd.getTime() !== info.event.start.getTime()) {
                            durationText += " to " + actualEnd.toLocaleDateString();
                        }
                    }
                    if (info.event.extendedProps.session !== 'Full Day') {
                        durationText += " (" + info.event.extendedProps.session + ")";
                    }
                    document.getElementById('modalDuration').innerText = durationText;

                    document.getElementById('eventModal').style.display = 'flex';
                }
            });
            calendar.render();
        });

        function closeModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        // Close on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('eventModal')) {
                closeModal();
            }
        }
    </script>
</body>

</html>