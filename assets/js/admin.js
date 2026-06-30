$(document).ready(function() {
    checkSession();

    // Initial load
    loadPatients();
    loadDoctors();
    loadSchedules();
    loadReports();

    // Tab changes
    $('#admin-menu a[data-bs-toggle="list"]').on('shown.bs.tab', function (e) {
        let target = $(e.target).attr("href"); // activated tab
        if(target === '#panel-patients') loadPatients();
        if(target === '#panel-doctors') loadDoctors();
        if(target === '#panel-schedules') loadSchedules();
        if(target === '#panel-reports') loadReports();
    });

    $('#btn-refresh-reports').click(function() {
        loadReports();
    });

    $('#filter-doc-schedule').change(function() {
        loadSchedules($(this).val());
    });

    // ---------------- DOCTORS ----------------
    $('#btn-new-doctor').click(function() {
        $('#form-doctor')[0].reset();
        $('#doc-id').val('');
        $('#doctorModalLabel').text('Registrar Médico');
    });

    $('#btn-save-doctor').click(function() {
        const data = {
            id: $('#doc-id').val(),
            identification: $('#doc-ident').val(),
            first_name: $('#doc-name').val(),
            last_name: $('#doc-lastname').val(),
            specialty: $('#doc-specialty').val(),
            status: $('#doc-status').val()
        };

        if(!data.identification || !data.first_name || !data.last_name || !data.specialty) {
            showAlert('Complete todos los campos del médico.', 'warning');
            return;
        }

        apiRequest('doctors.php?action=save', 'POST', data, function(res) {
            $('#doctorModal').modal('hide');
            showAlert(res.message, 'success');
            loadDoctors();
        });
    });

    // ---------------- SCHEDULES ----------------
    $('#btn-new-schedule').click(function() {
        $('#form-schedule')[0].reset();
        $('#sched-id').val('');
        $('#scheduleModalLabel').text('Asignar Horario');
        populateDoctorSelect('#sched-doctor');
    });

    $('#btn-save-schedule').click(function() {
        const data = {
            id: $('#sched-id').val(),
            doctor_id: $('#sched-doctor').val(),
            schedule_date: $('#sched-date').val(),
            start_time: $('#sched-start').val(),
            end_time: $('#sched-end').val(),
            is_available: $('#sched-available').is(':checked')
        };

        if(!data.doctor_id || !data.schedule_date || !data.start_time || !data.end_time) {
            showAlert('Complete todos los campos del horario.', 'warning');
            return;
        }

        apiRequest('schedules.php?action=save', 'POST', data, function(res) {
            $('#scheduleModal').modal('hide');
            showAlert(res.message, 'success');
            loadSchedules();
        });
    });

});

function loadPatients() {
    apiRequest('users.php?action=list', 'GET', null, function(res) {
        let html = '';
        if(res.data.length === 0) {
            html = '<tr><td colspan="5" class="text-center">No hay pacientes registrados.</td></tr>';
        } else {
            res.data.forEach(function(p) {
                html += `
                    <tr>
                        <td>${p.identification}</td>
                        <td>${p.first_name}</td>
                        <td>${p.last_name}</td>
                        <td>${p.email}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="deletePatient(${p.id})">Eliminar</button></td>
                    </tr>
                `;
            });
        }
        $('#admin-patients-table').html(html);
    });
}

function deletePatient(id) {
    if(confirm('¿Seguro que desea eliminar a este paciente? Esta acción no se puede deshacer.')) {
        apiRequest('users.php?action=delete', 'POST', {id: id}, function(res) {
            showAlert(res.message, 'success');
            loadPatients();
        });
    }
}

function loadDoctors() {
    apiRequest('doctors.php?action=list', 'GET', null, function(res) {
        let html = '';
        let docFilter = '<option value="">Todos</option>';
        if(res.data.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No hay médicos registrados.</td></tr>';
        } else {
            res.data.forEach(function(d) {
                let statusBadge = d.status === 'active' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
                docFilter += `<option value="${d.id}">Dr(a). ${d.first_name} ${d.last_name}</option>`;
                html += `
                    <tr>
                        <td>${d.identification}</td>
                        <td>${d.first_name}</td>
                        <td>${d.last_name}</td>
                        <td>${d.specialty}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" onclick='editDoctor(${JSON.stringify(d)})'>Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDoctor(${d.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        }
        $('#admin-doctors-table').html(html);
        $('#filter-doc-schedule').html(docFilter);
    });
}

function editDoctor(doc) {
    $('#doc-id').val(doc.id);
    $('#doc-ident').val(doc.identification);
    $('#doc-name').val(doc.first_name);
    $('#doc-lastname').val(doc.last_name);
    $('#doc-specialty').val(doc.specialty);
    $('#doc-status').val(doc.status);
    $('#doctorModalLabel').text('Editar Médico');
    $('#doctorModal').modal('show');
}

function deleteDoctor(id) {
    if(confirm('¿Seguro que desea eliminar a este médico?')) {
        apiRequest('doctors.php?action=delete', 'POST', {id: id}, function(res) {
            showAlert(res.message, 'success');
            loadDoctors();
        });
    }
}

function populateDoctorSelect(selector, selectedId = null) {
    apiRequest('doctors.php?action=list', 'GET', null, function(res) {
        let html = '<option value="">Seleccione...</option>';
        res.data.forEach(function(d) {
            if(d.status === 'active') {
                html += `<option value="${d.id}" ${d.id == selectedId ? 'selected' : ''}>Dr(a). ${d.first_name} ${d.last_name} - ${d.specialty}</option>`;
            }
        });
        $(selector).html(html);
    });
}

function loadSchedules(docId = '') {
    let url = 'schedules.php?action=list';
    if(docId) url += '&doctor_id=' + docId;
    
    apiRequest(url, 'GET', null, function(res) {
        let html = '';
        if(res.data.length === 0) {
            html = '<tr><td colspan="6" class="text-center">No hay horarios registrados.</td></tr>';
        } else {
            res.data.forEach(function(s) {
                let availBadge = s.is_available == 1 ? '<span class="badge bg-success">Disponible</span>' : '<span class="badge bg-danger">Ocupado</span>';
                html += `
                    <tr>
                        <td>Dr(a). ${s.first_name} ${s.last_name}</td>
                        <td>${s.schedule_date}</td>
                        <td>${s.start_time}</td>
                        <td>${s.end_time}</td>
                        <td>${availBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" onclick='editSchedule(${JSON.stringify(s)})'>Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${s.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        }
        $('#admin-schedules-table').html(html);
    });
}

function editSchedule(s) {
    $('#sched-id').val(s.id);
    populateDoctorSelect('#sched-doctor', s.doctor_id);
    $('#sched-date').val(s.schedule_date);
    $('#sched-start').val(s.start_time);
    $('#sched-end').val(s.end_time);
    $('#sched-available').prop('checked', s.is_available == 1);
    $('#scheduleModalLabel').text('Editar Horario');
    $('#scheduleModal').modal('show');
}

function deleteSchedule(id) {
    if(confirm('¿Seguro que desea eliminar este horario?')) {
        apiRequest('schedules.php?action=delete', 'POST', {id: id}, function(res) {
            showAlert(res.message, 'success');
            loadSchedules();
        });
    }
}

function loadReports() {
    apiRequest('schedules.php?action=reports', 'GET', null, function(res) {
        let html = '';
        if(res.data.length === 0) {
            html = '<tr><td colspan="5" class="text-center">No hay citas registradas.</td></tr>';
        } else {
            res.data.forEach(function(r) {
                let statusClass = r.status === 'scheduled' ? 'text-primary' : (r.status === 'completed' ? 'text-success' : 'text-danger');
                let statusText = r.status === 'scheduled' ? 'Programada' : (r.status === 'completed' ? 'Completada' : 'Cancelada');
                html += `
                    <tr>
                        <td>#${r.id}</td>
                        <td>${r.pf} ${r.pl} (${r.patient_id})</td>
                        <td>Dr(a). ${r.df} ${r.dl}</td>
                        <td>${r.schedule_date} ${r.start_time}</td>
                        <td class="${statusClass} fw-bold">${statusText}</td>
                    </tr>
                `;
            });
        }
        $('#admin-reports-table').html(html);
    });
}
