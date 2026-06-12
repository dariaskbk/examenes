<p align="center">
  <img src="public/images/sicore-logo.png" alt="SICORE" width="320">
</p>

<p align="center">
  <strong>Módulo de Exámenes en línea de SICORE</strong><br>
  Plataforma para crear, aplicar y calificar evaluaciones digitales con sincronización automática de notas al sistema académico.
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white">
  <img alt="Bootstrap" src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/License-Propietaria-lightgrey">
</p>

---

## 🎯 Qué es el modulo de exámenes?

Es una extensión del sistema académico **SICORE** que cubre todo el ciclo de evaluación digital:

- 🏗️ **Creación de exámenes** con 11 tipos de pregunta (selección única / múltiple, V/F, emparejamiento, ordenamiento, identificación, completar, respuesta corta, restringida, ejercicio, producción escrita).
- 📥 **Importación masiva** desde Excel o ZIP (Excel + multimedia).
- 🔒 **Anti-trampa** (proctoring): detección de salidas de pantalla, bloqueo de copy/paste, pausa estricta con autorización docente.
- ⏱️ **Aplicación segura** con códigos únicos por estudiante, autosave por respuesta y manejo de tiempo extra (accesibilidad).
- 📊 **Calificación automática + manual** con rúbricas (criterio × nivel) para preguntas abiertas.
- 🔄 **Sincronización a SICORE** — las notas se consolidan en la base académica con la misma fórmula que usa SICORE.
- 📈 **Monitor en vivo** del avance de cada estudiante mientras rinde.

---

## 🛠️ Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 · PHP 8.3+ |
| Base de datos | MySQL 8 (doble conexión: `mysql` para ExamCore, `sicore` para el sistema académico) |
| Frontend | Bootstrap 5.3 · Bootstrap Icons · Quill 2 (rich text) · SweetAlert2 |
| Build | Vite (config presente, no obligatorio — los assets viven en `public/`) |
| PDF | barryvdh/laravel-dompdf |
| Excel | maatwebsite/excel |
| Permisos | spatie/laravel-permission |
| Cola | `database` driver (Laravel queues) |

---

## 🚀 Quick start

### Requisitos

- PHP **8.3+** (probado con 8.4)
- MySQL **8+**
- Composer **2.x**
- Node 20+ y npm (solo si querés usar Vite)
- Acceso a la base de datos `sicore` (las conexiones a tablas académicas se hacen vía `DB::connection('sicore')`)

### Setup en local (Laragon / XAMPP / MAMP)

```bash
# 1. Clonar
git clone <repo-url> examcore
cd examcore

# 2. Setup automático (composer + .env + key + migrate + npm)
composer setup

# 3. Configurar conexiones a BD en .env
#    DB_DATABASE=sicore_exams_test
#    SICORE_DB_DATABASE=sicore
#    (ambas conexiones definidas en config/database.php)

# 4. Levantar el entorno completo (server + queue + logs + vite)
composer dev
```

`composer dev` arranca **4 procesos en paralelo** con concurrently:
- `php artisan serve` — servidor web
- `php artisan queue:listen` — worker de jobs (auto-reload de código)
- `php artisan pail` — tail de logs en vivo
- `npm run dev` — Vite con HMR

> Si usás Laragon como servidor (recomendado), no necesitás `php artisan serve` — accedé directo a `http://examcore.test` y arrancá solo el worker + pail si vas a probar features async.

---

## 🧩 Arquitectura

### Modelos principales

```
Exam ──┬── ExamQuestion ── ExamOption
       ├── ExamAccessCode ── ExamAttempt ── ExamAttemptAnswer
       ├── ExamShare (compartir con otros docentes)
       └── ExamEvaluationComponent (link a SICORE)

BackgroundOperation  ← tracking de jobs async
```

### Controllers clave

| Controller | Responsabilidad |
|---|---|
| `ExamController` | CRUD de exámenes, preguntas, códigos, importación, sincronización |
| `StudentExamController` | Flujo del alumno: login con código, autosave, submit, resultados |
| `ExamShareController` | Compartir borradores entre docentes |

### Servicios

- **`SicoreGradeSync`** — réplica exacta de la fórmula de consolidación de SICORE. Cualquier cambio en SICORE debe replicarse acá para mantener consistencia.

---

## 🎨 Frontend — convención de assets

Los blades más pesados fueron refactorizados a la siguiente estructura para mantenerlos legibles:

```
public/css/exams/
  show.css               ← estilos de la página de detalle del examen
  student-exam.css       ← estilos del runtime del alumno
  attempt-detail.css     ← estilos de calificación manual

public/js/exams/
  question-modals.js     ← lógica de los modales (Add/Edit pregunta)
  quill-init.js          ← inicialización del editor rich text
  rubric-builder.js      ← constructor de rúbricas
  question-bank.js       ← banco de preguntas (modal)
  share.js               ← compartir con docentes
  delete-question.js     ← eliminación AJAX
  drag-reorder.js        ← drag & drop de preguntas
  code-regen.js          ← regenerar códigos de acceso
  extra-time.js          ← tiempo extra por estudiante
  monitor.js             ← polling en vivo del monitor
  attempt-detail.js      ← widget de calificación manual
  student-exam.js        ← runtime del alumno (autosave, timer, navegación)
  student-a11y.js        ← panel de accesibilidad
  student-proctoring.js  ← anti-trampa (carga condicional)
  ... y otros
```

### Patrón de carga

Cada blade emite un objeto `window.X` mínimo con las rutas y datos del backend:

```html
<script>
window.ExamShow = {
    examId: {{ $exam->id }},
    canEdit: @json($exam->canBeEdited()),
    routes: {
        questionsUpdate: "{{ route('exams.questions.update', [$exam, '__QID__']) }}",
        // ...
    }
};
</script>
<script src="{{ asset('js/exams/question-modals.js') }}?v={{ filemtime(public_path('js/exams/question-modals.js')) }}"></script>
```

> **Regla:** ningún archivo de `public/js/exams/*.js` puede contener directivas Blade. Si necesitás datos del servidor, agregalos a `window.X` en el blade.

---

## ⚙️ Tareas en segundo plano

ExamCore corre dos cosas detrás del web server.

### 1. Queue worker (jobs async)

Los siguientes procesos se ejecutan en cola para no bloquear el request:

| Job | Disparado por |
|---|---|
| `SyncSicoreGradesJob` | Botón "Sincronizar notas a SICORE" |
| `ImportQuestionsJob` | Subida de Excel/ZIP de preguntas |

Cada dispatch crea una fila en `background_operations` con `status: pending → running → done/failed` para que la UI pueda mostrar progreso.

**Correr el worker en producción:**

```ini
# Supervisor (Linux)
[program:examcore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/examcore/artisan queue:work --tries=2 --timeout=600
autostart=true
autorestart=true
numprocs=2
```

**En local:** abrí una terminal aparte con `php artisan queue:work` cuando vayas a probar sync o import.

### 2. Schedule (cron)

Registrado en `routes/console.php`:

| Tarea | Frecuencia | Qué hace |
|---|---|---|
| `close-timed-out-attempts` | cada 5 min | Cierra intentos abandonados (alumno cerró pestaña, perdió internet) y los califica con lo que alcanzó a autosavear |

**Correr en producción** — un único cron del SO dispara todo:

```cron
* * * * * cd /var/www/examcore && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗃️ Base de datos

ExamCore usa **dos conexiones** definidas en `config/database.php`:

- `mysql` (default) → base de ExamCore (`sicore_exams_test` en local)
- `sicore` → base académica original (`sicore`)

La sincronización de notas hace `DB::connection('sicore')->table('grades')->upsert(...)`. Tratá la base `sicore` como **fuente de verdad** — ExamCore solo escribe las notas finales, nunca toca otras tablas.

### Índices de performance

Los queries hot (página del docente, monitor en vivo, listado de resultados) están cubiertos por índices compuestos. Si agregás un query con `WHERE … ORDER BY …`, revisá primero con `EXPLAIN` que esté usando un índice apropiado — la guía de patrones está en `database/migrations/2026_06_09_220000_add_performance_indexes.php`.

### Migraciones

```bash
php artisan migrate          # corre solo migraciones de ExamCore
php artisan migrate --pretend # ver el SQL sin aplicar
```

---

## 🧪 Testing

```bash
composer test
```

Equivalente a:

```bash
php artisan config:clear
php artisan test
```

Las migraciones de prueba usan un schema separado — ver `phpunit.xml`.

---

## 📝 Convenciones

- **Estilo de código:** Pint (`vendor/bin/pint`) — corré antes de commitear.
- **Mensajes de commit:** descriptivos (`feat: …`, `fix: …`, `refactor: …`).
- **Branches:** `feature/<descripción>`, `fix/<descripción>`, `hotfix/<descripción>`.
- **Variables de entorno nuevas:** agregalas también a `.env.example` con un valor placeholder.
- **Migraciones:** una migración por cambio lógico — no acumular cambios diversos.

---

## 📦 Estructura del proyecto

```
app/
  Console/             ← (vacío — los schedules van en routes/console.php)
  Http/Controllers/    ← ExamController, StudentExamController, ExamShareController
  Jobs/                ← SyncSicoreGradesJob, ImportQuestionsJob
  Models/              ← Exam, ExamQuestion, ExamAttempt, ExamAccessCode, BackgroundOperation, …
  Services/            ← SicoreGradeSync
  Imports/             ← QuestionsImport (Excel)
  Exports/             ← Plantillas Excel + exportes de resultados

resources/views/
  exams/               ← Vistas del docente (show, monitor, results, attempt-detail, …)
  exams/partials/      ← Modales extraídos
  student/             ← Vistas del estudiante (exam, results, code-entry)
  layouts/             ← Layout base

public/
  css/exams/           ← CSS extraídos (ver sección Frontend)
  js/exams/            ← JS extraídos (ver sección Frontend)
  images/              ← Logos y assets
```

---

## 🆘 Troubleshooting

| Problema | Solución |
|---|---|
| "Operación en curso" infinito | Verificá que el queue worker esté corriendo (`php artisan queue:work`) |
| Intentos abandonados quedan `in_progress` | Asegurate de que el cron del SO esté disparando `schedule:run` cada minuto |
| Excel/ZIP no se importa | Revisá `storage/logs/laravel.log` — el job loguea errores ahí; los formatos solo son `.xlsx`, `.xls`, `.zip` |
| Sync de notas falla con "examen no vinculado" | El examen no tiene `evaluation_component_id` asociado — el docente debe vincularlo desde el formulario de edición |
| Quill no aparece en el modal Edit | Verificá la consola: `quill-init.js` debe cargar **después** de Quill CDN |

---

## 📄 Licencia

Software propietario de SICORE. Uso interno autorizado únicamente para el equipo de desarrollo.

---

<p align="center">
  <sub>Hecho con 💜 para el equipo de SICORE</sub>
</p>
