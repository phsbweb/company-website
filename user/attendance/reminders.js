document.addEventListener('DOMContentLoaded', () => {
    const data = window.attendanceData;
    if (!data) return;

    const checkReminders = () => {
        if (!("Notification" in window)) return;

        if (Notification.permission === "granted") {
            processReminders();
        } else if (Notification.permission !== "denied") {
            showPermissionRequest();
        }
    };

    const showPermissionRequest = () => {
        const reminderKey = `reminder_permission_ignored_${data.userId}`;
        if (localStorage.getItem(reminderKey)) return;

        const banner = document.createElement('div');
        banner.id = 'notification-banner';
        banner.style = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #fff;
            padding: 18px 22px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 320px;
            font-family: 'Inter', sans-serif;
        `;
        banner.innerHTML = `
            <div>
                <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px;">Enable Attendance Reminders?</div>
                <div style="font-size: 0.85rem; color: #64748b; line-height: 1.4;">Don't forget to check in or out! We can send you a friendly nudge.</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button id="enable-notifications" style="background: #4f46e5; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; font-weight: 600; flex: 1;">Enable</button>
                <button id="ignore-notifications" style="background: #f1f5f9; color: #64748b; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; flex: 1;">Not Now</button>
            </div>
        `;
        document.body.appendChild(banner);

        document.getElementById('enable-notifications').onclick = () => {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    banner.remove();
                    processReminders();
                }
            });
        };

        document.getElementById('ignore-notifications').onclick = () => {
            localStorage.setItem(reminderKey, 'true');
            banner.remove();
        };
    };

    const processReminders = () => {
        const now = new Date();
        const hour = now.getHours();
        const minute = now.getMinutes();
        const currentTimeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
        const todayStr = now.toISOString().split('T')[0];

        // Check-in Reminder (if not checked in and time is within shift start window)
        // Only remind in the first hour of shift
        const [startH, startM] = data.shiftStart.split(':').map(Number);
        const shiftStartMinutes = startH * 60 + startM;
        const currentMinutes = hour * 60 + minute;

        if (!data.isCheckedIn && currentMinutes >= shiftStartMinutes && currentMinutes < (shiftStartMinutes + 60)) {
            const lastCheckinReminder = localStorage.getItem(`last_checkin_reminder_${data.userId}`);
            if (lastCheckinReminder !== todayStr) {
                new Notification("Check-in Reminder", {
                    body: `It's time to start your shift (${data.shiftStart}). Don't forget to check in!`,
                    icon: 'https://cdn-icons-png.flaticon.com/512/3652/3652191.png'
                });
                localStorage.setItem(`last_checkin_reminder_${data.userId}`, todayStr);
            }
        }

        // Check-out Reminder (if still checked in and time is past shift end)
        const [endH, endM] = data.shiftEnd.split(':').map(Number);
        const shiftEndMinutes = endH * 60 + endM;

        if (data.isCheckedIn && currentMinutes >= shiftEndMinutes && currentMinutes < (shiftEndMinutes + 120)) {
            const lastCheckoutReminder = localStorage.getItem(`last_checkout_reminder_${data.userId}`);
            if (lastCheckoutReminder !== todayStr) {
                new Notification("Check-out Reminder", {
                    body: `Your shift ended at ${data.shiftEnd}. Don't forget to check out!`,
                    icon: 'https://cdn-icons-png.flaticon.com/512/3652/3652191.png'
                });
                localStorage.setItem(`last_checkout_reminder_${data.userId}`, todayStr);
            }
        }
    };

    // Initial check
    checkReminders();

    // Re-check every minute
    setInterval(() => {
        // Only run if permission is granted
        if (Notification.permission === "granted") {
            processReminders();
        }
    }, 60000);
});
