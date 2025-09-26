# 🏢 Sistema de Recursos Humanos y Seguridad Ocupacional - STB

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel 12">
    <img src="https://img.shields.io/badge/PHP-8.2+-blue?style=for-the-badge&logo=php" alt="PHP 8.2+">
    <img src="https://img.shields.io/badge/MySQL-8.0-orange?style=for-the-badge&logo=mysql" alt="MySQL">
    <img src="https://img.shields.io/badge/TailwindCSS-4.0-teal?style=for-the-badge&logo=tailwindcss" alt="Tailwind CSS">
    <img src="https://img.shields.io/badge/Vite-7.0-purple?style=for-the-badge&logo=vite" alt="Vite">
</p>

## 📋 Descripción

Sistema integral de Recursos Humanos enfocado en Seguridad y Salud Ocupacional desarrollado para Service and Trading Business (STB). Esta aplicación web gestiona de manera completa los aspectos críticos de RRHH relacionados con la seguridad laboral, capacitaciones, equipos de protección personal y control de préstamos.

## ✨ Características Principales

### 🎯 **Módulos del Sistema**

#### 👷‍♂️ **Gestión de Empleados**
- Registro completo de empleados con datos personales y laborales
- Asignación a puestos de trabajo y departamentos
- Matriz organizacional jerárquica
- Importación masiva desde archivos Excel

#### 🦺 **Equipos de Protección Personal (EPP)**
- Catálogo completo de equipos por tipo de protección
- Control de entrega y asignación a empleados
- Historial detallado de entregas por trabajador
- Reportes de EPP requeridos por puesto
- Dashboard con métricas de EPP activos

#### 📚 **Capacitaciones**
- Gestión de capacitaciones obligatorias por puesto
- Control de asistencia y certificaciones
- Instructores internos y externos
- Matriz de capacitaciones requeridas vs recibidas
- Reportes de cumplimiento por empleado

#### 💰 **Control de Préstamos**
- Sistema completo de préstamos a empleados
- **Tabla de amortización automática** con cálculo de intereses
- Control de cuotas pagadas vs pendientes
- **Búsqueda avanzada** en tiempo real sin recargas de página
- Reportes mensuales y estadísticas
- Importación de ajustes de cuotas
- **Gestión de cuotas especiales** y extraordinarias

#### ⚠️ **Evaluación de Riesgos**
- Identificación y evaluación de riesgos por puesto
- Matriz de riesgos con valoración cuantitativa
- Medidas preventivas y correctivas
- Control de exposición a químicos
- Reportes de análisis de riesgos

#### 📊 **Reportes y Analytics**
- Dashboard ejecutivo con KPIs
- Reportes personalizables por módulo
- Exportación a Excel con formato profesional
- Gráficos interactivos y métricas en tiempo real

## 🚀 Tecnologías Utilizadas

### **Backend**
- **Laravel 12.x** - Framework PHP robusto y moderno
- **PHP 8.2+** - Lenguaje de programación principal
- **MySQL** - Base de datos relacional principal
- **SQLite** - Base de datos para desarrollo y testing

### **Frontend**
- **Blade Templates** - Motor de plantillas de Laravel
- **TailwindCSS 4.0** - Framework CSS utility-first para diseño responsivo
- **JavaScript ES6+** - Interactividad del cliente
- **Vite 7.0** - Build tool y dev server ultra-rápido

### **Librerías y Paquetes**
```json
{
  "backend": {
    "barryvdh/laravel-dompdf": "^3.1",    // Generación de PDFs
    "phpoffice/phpspreadsheet": "^5.1",   // Manejo de archivos Excel
    "laravel/ui": "^4.6"                  // Scaffolding de UI
  },
  "frontend": {
    "@tailwindcss/vite": "^4.0.0",       // Integración Tailwind con Vite
    "axios": "^1.8.2",                   // Cliente HTTP
    "bootstrap": "^5.2.3",               // Framework CSS adicional
    "select2": "^4.1.0"                  // Selectores avanzados
  }
}
```

### **Herramientas de Desarrollo**
- **Laravel Pint** - Code formatter siguiendo estándares PSR
- **Laravel Sail** - Entorno de desarrollo con Docker
- **PHPUnit** - Testing framework
- **Faker** - Generación de datos de prueba

## ⚙️ Instalación

### **Requisitos del Sistema**
- PHP >= 8.2
- Composer >= 2.0
- Node.js >= 18.x
- MySQL >= 8.0 o SQLite
- Extensiones PHP: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON

### **1. Clonar el Repositorio**
```bash
git clone https://github.com/Nataly0206/sistema-rrhh-sso-stb.git
cd sistema-rrhh-sso-stb
```

### **2. Instalar Dependencias Backend**
```bash
composer install
```

### **3. Configurar Entorno**
```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar key de aplicación
php artisan key:generate
```

### **4. Configurar Base de Datos**
Editar `.env` con tus credenciales:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_rrhh_stb
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### **5. Ejecutar Migraciones**
```bash
php artisan migrate
```

### **6. Instalar Dependencias Frontend**
```bash
npm install
```

### **7. Compilar Assets**
```bash
# Para desarrollo
npm run dev

# Para producción
npm run build
```

### **8. Iniciar Servidor**
```bash
php artisan serve
```

La aplicación estará disponible en: `http://localhost:8000`

## 🏗️ Arquitectura del Sistema

### **Estructura de Directorios**
```
sistema-rrhh-sso-stb/
├── app/
│   ├── Http/Controllers/          # Controladores del sistema
│   │   ├── Prestamos.php         # Control de préstamos
│   │   ├── EPP.php              # Gestión de EPP
│   │   ├── Capacitaciones.php   # Sistema de capacitaciones
│   │   └── RiesgosController.php # Evaluación de riesgos
│   ├── Models/                   # Modelos Eloquent
│   │   ├── Empleado.php         # Modelo de empleados
│   │   ├── Prestamo.php         # Modelo de préstamos
│   │   └── HistorialCuota.php   # Historial de cuotas
│   └── Exports/                 # Clases para exportación
├── database/
│   ├── migrations/              # Migraciones de BD
│   └── seeders/                # Semillas de datos
├── resources/
│   ├── views/                  # Vistas Blade
│   │   ├── prestamos/         # Vistas de préstamos
│   │   ├── epp/              # Vistas de EPP
│   │   └── capacitaciones/   # Vistas de capacitaciones
│   ├── css/                  # Estilos CSS
│   └── js/                   # JavaScript
└── routes/
    └── web.php               # Rutas de la aplicación
```

### **Módulos Principales**

#### **📊 Dashboard Principal**
- Vista consolidada de métricas clave
- Acceso rápido a todos los módulos
- Estadísticas en tiempo real

#### **👥 Gestión de Personal**
- CRUD completo de empleados
- Asignación organizacional
- Importación masiva desde Excel

#### **🎓 Sistema de Capacitaciones**
- Programación de capacitaciones
- Control de asistencia
- Certificaciones y vencimientos
- Reportes de cumplimiento

#### **🦺 Control de EPP**
- Inventario de equipos
- Asignaciones por puesto
- Historial de entregas
- Alertas de vencimiento

#### **💸 Préstamos Empresariales**
- **Funcionalidades Avanzadas:**
  - Simulación de préstamos con tabla de amortización
  - Cálculo automático de intereses y cuotas
  - Gestión de pagos y cuotas especiales
  - **Búsqueda inteligente** sin recargas de página
  - Control de estados: Activo, Pagado, Cancelado
  - Reportes financieros detallados
  - Exportación a Excel con formato profesional

#### **⚠️ Gestión de Riesgos**
- Identificación de peligros
- Evaluación cuantitativa
- Medidas de control
- Seguimiento de mejoras

## 🔧 Configuración Avanzada

### **Base de Datos**
El sistema soporta múltiples motores:
- **MySQL/MariaDB** (Recomendado para producción)
- **SQLite** (Ideal para desarrollo)

### **Cache y Sesiones**
```env
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### **Configuración de Email**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
```

## 📈 Características Técnicas Destacadas

### **🔍 Búsqueda Avanzada**
- Búsqueda en tiempo real sin recargas
- Filtros inteligentes por múltiples campos
- Paginación con preservación de filtros
- Indicadores visuales de búsqueda activa

### **📊 Exportaciones Profesionales**
- Excel con formato corporativo
- PDFs con diseño responsive
- Reportes personalizables por usuario
- Compresión automática de archivos grandes

### **🚀 Performance**
- Lazy loading de relaciones Eloquent
- Cache inteligente de consultas frecuentes
- Optimización de assets con Vite
- Minificación automática en producción

### **🔒 Seguridad**
- Autenticación Laravel integrada
- Validación robusta de formularios
- Sanitización de inputs
- Protección CSRF en formularios

## 🤝 Contribución

### **Proceso de Desarrollo**
1. Fork del repositorio
2. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
3. Commits descriptivos: `git commit -m 'Agregar función de X'`
4. Push a la rama: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request con descripción detallada

### **Estándares de Código**
- Seguir PSR-12 para PHP
- Usar Laravel Pint: `./vendor/bin/pint`
- Comentar funciones complejas
- Tests unitarios para nuevas funcionalidades

## 🐛 Troubleshooting

### **Problemas Comunes**

**Error de permisos:**
```bash
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

**Limpiar cache:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

**Regenerar autoload:**
```bash
composer dump-autoload
```

## 📞 Soporte

- **Desarrollador:** Nataly0206
- **Email:** [Configurar email de contacto]
- **Issues:** [GitHub Issues](https://github.com/Nataly0206/sistema-rrhh-sso-stb/issues)

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

---

<p align="center">
    <strong>Desarrollado con ❤️ para Service and Trading Business</strong>
</p>

<p align="center">
    Sistema integral para la gestión moderna de Recursos Humanos y Seguridad Ocupacional
</p>

<p align="center">
    Sistema integral para la gestión moderna de Recursos Humanos y Seguridad Ocupacional
</p>

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
