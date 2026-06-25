document.addEventListener('DOMContentLoaded', function () {
    // 1. Bar Chart: จำนวนการจองแยกตามห้อง
    const ctxRoomEl = document.getElementById('bookingByRoomChart');
    if (ctxRoomEl) {
        const ctxRoom = ctxRoomEl.getContext('2d');
        new Chart(ctxRoom, {
            type: 'bar',
            data: {
                labels: ['Room A (8 ที่นั่ง)', 'Room B (12 ที่นั่ง)', 'Room C (6 ที่นั่ง)', 'Room D (20 ที่นั่ง)', 'Room E (10 ที่นั่ง)', 'Room F (4 ที่นั่ง)'],
                datasets: [{
                    label: 'จำนวนการจอง (ครั้ง)',
                    data: [52, 38, 29, 22, 16, 9],
                    backgroundColor: [
                        '#4338ca', // Indigo
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#ef4444', // Rose
                        '#06b6d4', // Cyan
                        '#8b5cf6'  // Violet
                    ],
                    borderRadius: 10,
                    barThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Doughnut Chart: สถานะการจอง
    const ctxStatusEl = document.getElementById('statusChart');
    if (ctxStatusEl) {
        const ctxStatus = ctxStatusEl.getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['อนุมัติแล้ว', 'รออนุมัติ', 'ปฏิเสธ', 'ยกเลิก'],
                datasets: [{
                    data: [54, 36, 18, 12],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#64748b'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 20, font: { family: 'Noto Sans Thai' } } } },
                cutout: '75%'
            }
        });
    }

    // 3. FullCalendar: ปฏิทินตารางการจอง
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        // สามารถใช้ AJAX เรียกไปที่ /api/bookings/calendar_events ได้
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'th',
            timeZone: 'Asia/Bangkok',
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            slotMinTime: '08:00:00',
            slotMaxTime: '18:00:00',
            allDaySlot: false,
            contentHeight: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน' },
            datesSet: function (info) {
                const titleEl = document.querySelector('.fc-toolbar-title');
                if (titleEl && info.view && info.view.title) {
                    let text = info.view.title;
                    text = text.replace(/\b(20\d{2})\b/g, match => parseInt(match) + 543);
                    titleEl.textContent = text;
                }
            },
            events: [
                { title: 'Room A: ประชุมงบประมาณไตรมาส 3', start: '2026-06-25T09:00:00', end: '2026-06-25T11:30:00', backgroundColor: '#4338ca', borderColor: '#4338ca' },
                { title: 'Room B: สัมภาษณ์พนักงานใหม่', start: '2026-06-25T13:00:00', end: '2026-06-25T15:00:00', backgroundColor: '#10b981', borderColor: '#10b981' },
                { title: 'Room C: อัปเดตงานทีม Design', start: '2026-06-26T10:00:00', end: '2026-06-26T12:00:00', backgroundColor: '#f59e0b', borderColor: '#f59e0b' },
                { title: 'Room A: อบรมการป้องกันความปลอดภัยทางไซเบอร์', start: '2026-06-27T13:30:00', end: '2026-06-27T16:30:00', backgroundColor: '#4338ca', borderColor: '#4338ca' }
            ]
        });
        calendar.render();
    }

    // 4. Flatpickr: วันที่ (พ.ศ.) และ เวลา (24 ชั่วโมง)
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".datepicker", {
            locale: "th",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d F Y",
            onReady: function(selectedDates, dateStr, instance) {
                function updateBuddhistYear() {
                    if (instance.currentYearElement) {
                        instance.currentYearElement.value = instance.currentYear + 543;
                    }
                    if (instance.yearElements && instance.yearElements[0]) {
                        instance.yearElements[0].value = instance.currentYear + 543;
                    }
                    if (instance.altInput && instance.selectedDates[0]) {
                        const yearEl = instance.altInput;
                        const d = instance.selectedDates[0];
                        const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                        yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                    }
                }
                updateBuddhistYear();
                if (instance.calendarContainer) {
                    instance.calendarContainer.addEventListener('click', function() {
                        setTimeout(updateBuddhistYear, 10);
                    });
                }
            },
            onOpen: function(selectedDates, dateStr, instance) {
                setTimeout(function() {
                    if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                    if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                }, 10);
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                setTimeout(function() {
                    if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                    if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                }, 10);
            },
            onYearChange: function(selectedDates, dateStr, instance) {
                setTimeout(function() {
                    if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                    if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                }, 10);
            },
            onChange: function(selectedDates, dateStr, instance) {
                setTimeout(function() {
                    if (instance.altInput && instance.selectedDates[0]) {
                        const yearEl = instance.altInput;
                        const d = instance.selectedDates[0];
                        const thMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                        yearEl.value = d.getDate() + ' ' + thMonths[d.getMonth()] + ' ' + (d.getFullYear() + 543);
                    }
                    if (instance.currentYearElement) instance.currentYearElement.value = instance.currentYear + 543;
                    if (instance.yearElements && instance.yearElements[0]) instance.yearElements[0].value = instance.currentYear + 543;
                }, 10);
            }
        });

        flatpickr(".timepicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });
    }
});
