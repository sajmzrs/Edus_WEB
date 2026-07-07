# Guía XAMPP y Git

## Carpeta fuente

La carpeta fuente del proyecto es:

`C:\Dev\SC502\Proyecto_EDUS\Edus_WEB`

XAMPP carga el proyecto desde:

`C:\xampp\htdocs\Edus_WEB`

Esa ruta quedó configurada como junction hacia la carpeta fuente. Por eso, cualquier cambio hecho en el repo se refleja directamente en XAMPP.

## Probar el proyecto

1. Abrir XAMPP Control Panel.
2. Iniciar Apache y MySQL.
3. Importar `backend/db/schema.sql` desde phpMyAdmin si la base no existe.
4. Abrir `http://localhost/Edus_WEB/`.
5. Probar como paciente registrado o como administrador:
   - `admin@edus.ccss.sa.cr`
   - `admin123`

## Flujo de ramas

- Trabajar cambios personales en rama personal.
- Integrar cambios del grupo en `staging`.
- Pasar a `production` solo cuando la aplicación esté probada.

Comandos útiles:

```bash
git checkout sajmz
git status
git pull
```

Antes de compartir cambios con el grupo:

```bash
git status
git add .
git commit -m "descripcion breve del cambio"
git push
```

Para probar integración:

```bash
git checkout staging
git pull
git merge sajmz
git push
```
