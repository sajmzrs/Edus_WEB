$(document).ready(function() {
    // Verificar sesión al cargar
    checkSession();

    // Toggle entre login y registro
    $('#toggle-register').click(function(e) {
        e.preventDefault();
        $('#login-form').hide();
        $('#register-form').show();
        $('#form-title').text('Registro de Paciente');
        $('#alert-container').empty();
    });

    $('#toggle-login').click(function(e) {
        e.preventDefault();
        $('#register-form').hide();
        $('#login-form').show();
        $('#form-title').text('Iniciar Sesión');
        $('#alert-container').empty();
    });

    $('#login-form').submit(function(e) {
        e.preventDefault();
        const email = $('#login-email').val();
        const password = $('#login-password').val();

        if(!email || !password) {
            showAlert('Por favor, complete todos los campos.', 'warning');
            return;
        }

        const data = {
            email: email,
            password: password
        };

        apiRequest('auth.php?action=login', 'POST', data, 
            function(response) {
                if(response.user.role === 'admin') {
                    window.location.href = 'admin_panel.html';
                } else {
                    window.location.href = 'patient_panel.html';
                }
            }
        );
    });

    $('#register-form').submit(function(e) {
        e.preventDefault();
        
        const data = {
            identification: $('#reg-identification').val(),
            first_name: $('#reg-firstname').val(),
            last_name: $('#reg-lastname').val(),
            email: $('#reg-email').val(),
            password: $('#reg-password').val()
        };

        if(!data.identification || !data.first_name || !data.last_name || !data.email || !data.password) {
            showAlert('Por favor, complete todos los campos.', 'warning');
            return;
        }

        apiRequest('auth.php?action=register', 'POST', data, 
            function(response) {
                showAlert('Registro exitoso. Ahora puede iniciar sesión.', 'success');
                $('#toggle-login').click();
                $('#login-email').val(data.email);
            }
        );
    });
});
