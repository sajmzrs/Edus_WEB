// Utility functions for EDUS Web Application

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
            // Logged in
            let currentPath = window.location.pathname;
            if (currentPath.includes('login.html') || currentPath.includes('index.html') || currentPath.endsWith('/')) {
                if(res.user.role === 'admin') {
                    window.location.href = 'admin_panel.html';
                } else {
                    window.location.href = 'patient_panel.html';
                }
            } else {
                // Display name
                if($('#user-name-display').length) {
                    $('#user-name-display').text('Bienvenido(a) ' + res.user.first_name);
                }
                if($('#admin-name-display').length) {
                    $('#admin-name-display').text('Admin: ' + res.user.first_name);
                }
                
                // Role check
                if (currentPath.includes('admin_panel.html') && res.user.role !== 'admin') {
                    window.location.href = 'patient_panel.html';
                }
            }
        }, 
        function(err) {
            // Not logged in
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
