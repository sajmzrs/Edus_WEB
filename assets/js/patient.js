$(document).ready(function() {
    checkSession();

    loadAppointments();
    loadSpecialties();

    $('#btn-refresh-appointments').click(function() {
        loadAppointments();
    });

    $('#specialty-select').change(function() {
        const spec = $(this).val();
        if(spec) {
            loadDoctors(spec);
        } else {
            $('#doctor-select').prop('disabled', true).html('<option value="">Primero seleccione una especialidad</option>');
            $('#schedule-select').prop('disabled', true).html('<option value="">Primero seleccione un médico</option>');
            $('#btn-submit-booking').prop('disabled', true);
        }
    });

    $('#doctor-select').change(function() {
        const did = $(this).val();
        if(did) {
            loadSchedules(did, '#schedule-select');
        } else {
            $('#schedule-select').prop('disabled', true).html('<option value="">Primero seleccione un médico</option>');
            $('#btn-submit-booking').prop('disabled', true);
        }
    });

    $('#schedule-select').change(function() {
        if($(this).val()) {
            $('#btn-submit-booking').prop('disabled', false);
        } else {
            $('#btn-submit-booking').prop('disabled', true);
        }
    });

    $('#book-appointment-form').submit(function(e) {
        e.preventDefault();
        const sid = $('#schedule-select').val();
        if(!sid) return;

        apiRequest('appointments.php?action=book', 'POST', { schedule_id: sid }, function(res) {
            showAlert(res.message, 'success');
            loadAppointments();
            $('#book-appointment-form')[0].reset();
            $('#doctor-select, #schedule-select, #btn-submit-booking').prop('disabled', true);
            $('#appointments-tab').tab('show'); // Switch back to list
        });
    });
});

function loadAppointments() {
    apiRequest('appointments.php?action=list', 'GET', null, function(res) {
        let html = '';
        if(res.data.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No tiene citas programadas.</td></tr>';
        } else {
            res.data.forEach(function(app) {
                let statusClass = '';
                let statusText = '';
                switch(app.status) {
                    case 'scheduled': statusClass = 'status-scheduled'; statusText = 'Programada'; break;
                    case 'completed': statusClass = 'status-completed'; statusText = 'Completada'; break;
                    case 'cancelled': statusClass = 'status-cancelled'; statusText = 'Cancelada'; break;
                }

                let actions = '';
                if(app.status === 'scheduled') {
                    actions = `<button class="btn btn-sm btn-danger me-1" onclick="cancelAppointment(${app.id})">Cancelar</button>`;
                }

                html += `
                    <tr>
                        <td>${app.schedule_date}</td>
                        <td>${app.start_time}</td>
                        <td>Dr(a). ${app.first_name} ${app.last_name}</td>
                        <td>${app.specialty}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${actions}</td>
                    </tr>
                `;
            });
        }
        $('#appointments-table-body').html(html);
    });
}

function loadSpecialties() {
    apiRequest('appointments.php?action=specialties', 'GET', null, function(res) {
        let html = '<option value="">Seleccione una especialidad...</option>';
        res.data.forEach(function(sp) {
            html += `<option value="${sp.specialty}">${sp.specialty}</option>`;
        });
        $('#specialty-select').html(html);
    });
}

function loadDoctors(specialty) {
    apiRequest('appointments.php?action=doctors&specialty=' + encodeURIComponent(specialty), 'GET', null, function(res) {
        let html = '<option value="">Seleccione un médico...</option>';
        res.data.forEach(function(doc) {
            html += `<option value="${doc.id}">Dr(a). ${doc.first_name} ${doc.last_name}</option>`;
        });
        $('#doctor-select').html(html).prop('disabled', false);
        $('#schedule-select').prop('disabled', true).html('<option value="">Primero seleccione un médico</option>');
        $('#btn-submit-booking').prop('disabled', true);
    });
}

function loadSchedules(doctorId, selectElement) {
    apiRequest('appointments.php?action=schedules&doctor_id=' + doctorId, 'GET', null, function(res) {
        let html = '<option value="">Seleccione un horario...</option>';
        if(res.data.length === 0) {
            html = '<option value="">No hay horarios disponibles</option>';
        } else {
            res.data.forEach(function(sch) {
                html += `<option value="${sch.id}">${sch.schedule_date} de ${sch.start_time} a ${sch.end_time}</option>`;
            });
        }
        $(selectElement).html(html).prop('disabled', false);
    });
}

function cancelAppointment(id) {
    if(confirm('¿Está seguro de que desea cancelar esta cita?')) {
        apiRequest('appointments.php?action=cancel', 'POST', { appointment_id: id }, function(res) {
            showAlert(res.message, 'success');
            loadAppointments();
        });
    }
}
