document.addEventListener('DOMContentLoaded', () => {
    const attendanceBtn = document.getElementById('attendance-btn');
    const logoutBtn = document.getElementById('logout-btn');
    const statusBadge = document.getElementById('status-badge');
    const timeDisplay = document.getElementById('time-display');
    const confirmModal = document.getElementById('confirm-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const confirmCheckoutBtn = document.getElementById('confirm-checkout-btn');

    // Update time every second
    setInterval(() => {
        const now = new Date();
        if (timeDisplay) {
            timeDisplay.textContent = `Current Time: ${now.toLocaleTimeString()}`;
        }
    }, 1000);

    if (attendanceBtn) {
        attendanceBtn.addEventListener('click', () => {
            const action = attendanceBtn.getAttribute('data-action');
            if (action === 'checkout') {
                confirmModal.style.display = 'flex';
            } else {
                handleAttendance('checkin');
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
    }

    if (confirmCheckoutBtn) {
        confirmCheckoutBtn.addEventListener('click', () => {
            confirmModal.style.display = 'none';
            handleAttendance('checkout');
        });
    }

    async function getAddress() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                alert("Geolocation is not supported by this browser.");
                reject("unsupported");
                return;
            }

            navigator.geolocation.getCurrentPosition(async (position) => {
                const { latitude, longitude } = position.coords;
                try {
                    // Using Nominatim (OpenStreetMap) for free reverse geocoding
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`, {
                        headers: {
                            'Accept-Language': 'en'
                        }
                    });
                    const data = await response.json();
                    resolve(data.display_name || `${latitude}, ${longitude}`);
                } catch (error) {
                    console.error("Reverse geocoding error:", error);
                    resolve(`${latitude}, ${longitude}`);
                }
            }, (error) => {
                console.error("Geolocation error:", error);
                if (error.code === error.PERMISSION_DENIED) {
                    alert("Location access is required for attendance. Please enable location permissions in your browser settings.");
                } else {
                    alert("Could not determine your location. Please try again.");
                }
                reject("denied");
            }, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        });
    }

    async function handleAttendance(action) {
        // Disable button during request
        if (attendanceBtn) {
            attendanceBtn.disabled = true;
            const originalText = attendanceBtn.textContent;
            attendanceBtn.textContent = 'Locating...';

            try {
                const location = await getAddress();

                const response = await fetch('attendance_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${action}&location=${encodeURIComponent(location)}`
                });

                const result = await response.json();

                if (result.success) {
                    if (action === 'checkin') {
                        attendanceBtn.textContent = 'Check Out';
                        attendanceBtn.setAttribute('data-action', 'checkout');
                        attendanceBtn.classList.add('btn-danger');
                        attendanceBtn.disabled = false;

                        if (statusBadge) {
                            statusBadge.textContent = 'Status: Checked In';
                            statusBadge.className = 'status-badge status-checked-in';
                        }

                        if (logoutBtn) {
                            logoutBtn.disabled = true;
                            logoutBtn.title = "Please check out before logging out";
                        }
                    } else {
                        window.location.href = 'index.php?trace=checkout_logout';
                    }
                } else {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        alert(result.message || 'An error occurred.');
                        attendanceBtn.textContent = originalText;
                        attendanceBtn.disabled = false;
                    }
                }
            } catch (error) {
                console.error('Attendance handling error:', error);
                // If it was a rejection from getAddress (denied/unsupported), we don't need a double alert
                // since getAddress already alerts the user.
                attendanceBtn.textContent = originalText;
                attendanceBtn.disabled = false;
            }
        }
    }
});
