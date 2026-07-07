# Avance 2 - Proyecto EDUS Web

## Capítulo de Resultados

### Repositorio del proyecto

El código fuente del proyecto se encuentra en el siguiente repositorio:

https://github.com/sajmzrs/Edus_WEB

Para el trabajo del grupo se dejaron preparadas las siguientes ramas:

- `main`: base estable del repositorio.
- rama personal de trabajo.
- `staging`: rama para integrar y probar cambios del grupo.
- `production`: rama para la versión final funcional.

### Front End desarrollado

El avance incluye una capa de presentación web construida con HTML5, CSS3, Bootstrap 5, JavaScript, jQuery y AJAX. La aplicación cuenta con páginas para inicio, autenticación, panel de paciente y panel administrativo.

Los módulos visibles para el usuario son:

- Inicio del sistema con acceso al portal.
- Registro e inicio de sesión de pacientes.
- Consulta de citas del paciente.
- Agendamiento de citas por especialidad, médico y horario.
- Modificación y cancelación de citas programadas.
- Panel administrativo para pacientes, médicos, horarios y reportes de citas.

### Capa de presentación

La interfaz mantiene una estructura sencilla para facilitar el uso por parte de personas con diferentes niveles de experiencia tecnológica. Se usaron componentes de Bootstrap para formularios, tablas, pestañas, modales y navegación responsive. Además, el CSS propio define colores, tamaños de letra, estados de citas y contraste visual.

La comunicación entre el navegador y el servidor se realiza mediante peticiones AJAX a los archivos PHP ubicados en `backend/api`. Esto permite cargar información y guardar cambios sin recargar toda la página.

### Módulos implementados

#### Pacientes

El módulo de pacientes permite registrar usuarios, iniciar sesión, consultar citas, agendar una cita disponible, modificar una cita existente y cancelar una cita programada.

#### Administración

El panel administrativo permite gestionar pacientes, médicos y horarios. También muestra un reporte general de citas con paciente, médico, fecha y estado.

#### Citas médicas

El flujo de citas usa horarios disponibles asociados a médicos activos. Cuando un paciente agenda una cita, el horario queda ocupado. Si cancela o modifica la cita, el sistema actualiza la disponibilidad correspondiente.

## Diagrama relacional

El diagrama relacional se encuentra en `documentacion/diagrama_relacional.mmd`. Resume las tablas principales:

- `users`
- `doctors`
- `schedules`
- `appointments`

La base de datos mantiene las relaciones entre pacientes, médicos, horarios y citas mediante llaves foráneas.

## Evidencia para incluir en el PDF

Para completar el documento IEEE del Avance 2 se recomienda agregar capturas de:

- Pantalla de inicio.
- Registro e inicio de sesión.
- Panel de paciente con lista de citas.
- Formulario de agendar cita.
- Modal de modificar cita.
- Panel administrativo de pacientes.
- Panel administrativo de médicos.
- Panel administrativo de horarios.
- Reporte general de citas.

## Notas de prueba local

El proyecto se ejecuta en XAMPP desde:

http://localhost/Edus_WEB/

La base de datos se puede crear importando:

`backend/db/schema.sql`

El usuario administrador inicial es:

- Correo: `admin@edus.ccss.sa.cr`
- Contraseña: `admin123`
