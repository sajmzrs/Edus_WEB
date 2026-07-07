// Funciones generales del proyecto EDUS Web

const API_BASE = 'backend/api/';

function showAlert(message, type = 'danger') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    $('#alert-container').html(alertHtml);
}

function cleanText(value) {
    if(value === null || value === undefined) return '';

    const replacements = {
        '\u00C3\u00A1': 'á', '\u00C3\u00A9': 'é', '\u00C3\u00AD': 'í',
        '\u00C3\u00B3': 'ó', '\u00C3\u00BA': 'ú', '\u00C3\u00B1': 'ñ',
        '\u00C3\u0081': 'Á', '\u00C3\u0089': 'É', '\u00C3\u008D': 'Í',
        '\u00C3\u0093': 'Ó', '\u00C3\u009A': 'Ú', '\u00C3\u0091': 'Ñ',
        '\u00C2\u00BF': '¿', '\u00C2\u00A1': '¡',
        '\u251C\u00A1': 'í', '\u251C\u00AD': 'í', '\u251C\u00ED': 'í', '\u251C\u00A9': 'é',
        '\u251C\u00B3': 'ó', '\u251C\u00BA': 'ú', '\u251C\u00B1': 'ñ'
    };

    let text = String(value);
    Object.keys(replacements).forEach(function(key) {
        text = text.split(key).join(replacements[key]);
    });
    return text;
}

function escapeHtml(value) {
    return cleanText(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatDate(value) {
    if(!value) return '';
    const parts = String(value).split('-');
    if(parts.length !== 3) return cleanText(value);
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function formatTime(value) {
    if(!value) return '';
    return String(value).substring(0, 5);
}

function formatScheduleRange(schedule) {
    return `${formatDate(schedule.schedule_date)} de ${formatTime(schedule.start_time)} a ${formatTime(schedule.end_time)}`;
}

function apiRequest(endpoint, method, data, successCallback, errorCallback) {
    $.ajax({
        url: API_BASE + endpoint,
        type: method,
        dataType: 'json',
        data: data ? JSON.stringify(data) : null,
        contentType: 'application/json',
        success: function(response) {
            if(response.success) {
                if(successCallback) successCallback(response);
            } else {
                if(errorCallback) errorCallback(response.message);
                else showAlert(response.message || 'Error en la solicitud');
            }
        },
        error: function(xhr, status, error) {
            let msg = 'Error de conexión con el servidor.';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            if(xhr.status === 401) {
                let currentPath = window.location.pathname;
                if (!currentPath.includes('login.html') && !currentPath.includes('index.html') && !currentPath.endsWith('/')) {
                    window.location.href = 'login.html';
                    return;
                }
            }
            if(errorCallback) errorCallback(msg);
            else showAlert(msg);
        }
    });
}

function checkSession() {
    apiRequest('auth.php?action=check', 'GET', null, 
        function(res) {
            let currentPath = window.location.pathname;
            if (currentPath.includes('login.html') || currentPath.includes('index.html') || currentPath.endsWith('/')) {
                if(res.user.role === 'admin') {
                    window.location.href = 'admin_panel.html';
                } else {
                    window.location.href = 'patient_panel.html';
                }
            } else {
                if($('#user-name-display').length) {
                    $('#user-name-display').text('Bienvenido(a) ' + res.user.first_name);
                }
                if($('#admin-name-display').length) {
                    $('#admin-name-display').text('Admin: ' + res.user.first_name);
                }
                
                if (currentPath.includes('admin_panel.html') && res.user.role !== 'admin') {
                    window.location.href = 'patient_panel.html';
                }
            }
        }, 
        function(err) {
            let currentPath = window.location.pathname;
            if (currentPath.includes('patient_panel.html') || currentPath.includes('admin_panel.html')) {
                window.location.href = 'login.html';
            }
        }
    );
}

$(document).ready(function() {
    $('#btn-logout').click(function(e) {
        e.preventDefault();
        apiRequest('auth.php?action=logout', 'POST', null, function() {
            window.location.href = 'login.html';
        });
    });
});
