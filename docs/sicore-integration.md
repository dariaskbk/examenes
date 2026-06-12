# Integración SICORE ↔ ExamCore — Contrato de URLs

Documento de referencia para implementar el botón **"Evaluar con ExamCore"** desde
el lado de SICORE (en la pantalla *Evaluar componente de Pruebas/Tareas/Cotidiano*).

ExamCore es responsable de toda la lógica de exámenes (creación, intentos,
sincronización de notas). SICORE solo abre URLs hacia ExamCore.

---

## 1. Pre-condiciones

- **El componente debe existir previamente en SICORE.** ExamCore nunca crea
  componentes (eso lo valida SICORE por las reglas de porcentajes del rubro).
- **Tipos de componente soportados** (filtrado por `evaluations.type`):
  - `TESTS` — Pruebas
  - `PROJECT` — Proyectos
  - `DAILY_WORK` — Trabajo Cotidiano
  - `HOMEWORK` — Tareas
- El docente debe estar autenticado en ExamCore. Si no, ExamCore lo lleva al
  login y luego de vuelta a la URL solicitada (flujo Laravel estándar).

---

## 2. Detectar si un componente ya tiene examen en ExamCore

Tabla pivote en la BD compartida:

```sql
exam_evaluation_components (id, exam_id, evaluation_component_id, created_at, updated_at)
```

Para saber si un componente está vinculado:

```sql
SELECT e.id, e.title, e.status
FROM exam_evaluation_components AS p
JOIN exams AS e ON e.id = p.exam_id
WHERE p.evaluation_component_id = ?
LIMIT 1;
```

- **Sin filas** → no hay examen vinculado.
- **Una fila** → ese componente ya está alimentado por el examen retornado
  (la restricción `UNIQUE(evaluation_component_id)` garantiza máximo uno).

---

## 3. URLs

### 3.1 Crear / abrir examen para un componente

```
GET  /exams/create?evaluation_component_id={EC_ID}
```

**Comportamiento:**
- Abre el formulario de creación con ese componente **pre-marcado** en la sección
  *"Calificación en SICORE"*.
- El docente puede agregar componentes de **otras secciones** si quiere que el
  mismo examen las califique también (modelo 1:N multi-sección).
- ExamCore puede pre-llenar título sugerido / materia / año a partir del
  componente (campos editables).
- Al guardar, queda creado el vínculo. El docente sigue al examen para agregar
  preguntas y publicarlo.

### 3.2 Abrir el examen ya vinculado

Si tu query del punto 2 devuelve un `exam_id`:

```
GET  /exams/{exam_id}
```

Lleva directo a la página del examen (donde se administran preguntas, códigos,
resultados y se sincronizan notas).

---

## 4. UX sugerida en SICORE (en la pantalla de evaluar componente)

En la fila de cada Prueba/Tarea/Cotidiano del componente, agregar:

```
[ Evaluar ] [ Editar ] [ Eliminar ] [ Evaluar con ExamCore ]   ← nuevo
```

Lógica al renderizar el botón:

```php
$linkedExamId = DB::table('exam_evaluation_components')
    ->where('evaluation_component_id', $component->id)
    ->value('exam_id');

if ($linkedExamId) {
    // botón "Abrir en ExamCore" → /exams/{$linkedExamId}
} else {
    // botón "Evaluar con ExamCore" → /exams/create?evaluation_component_id={$component->id}
}
```

Recomendación: cambia ligeramente el estilo cuando ya hay vínculo (badge
"vinculado a ExamCore") para que el docente vea que ese componente se alimenta
desde fuera y NO debería digitar notas a mano ahí.

---

## 5. ¿Cuándo escribe ExamCore en SICORE?

Solo cuando el docente presiona **"Sincronizar notas a SICORE"** dentro de
ExamCore (sincronización manual, nunca automática).

Lo que escribe:
- `grade_components.grade` — puntos crudos de cada estudiante en ese componente.
- `grade_components.observation` — `"Calificado por ExamCore — {título examen}"`.
- `grade_components.grade_id` — enlace al `grades.id` consolidado.
- `grades.nota` — recalculado con la misma fórmula que
  `EvaluationComponentController::storeGradeComponents` (réplica fiel).
- `grades.user_id` — `user_id` del docente dueño del examen (auditoría).

ExamCore **nunca** crea ni elimina filas en `evaluation_components` ni
`evaluation_subject_year` ni `evaluations`. Solo actualiza `grade_components` y
hace upsert en `grades`.

---

## 6. Seguridad / validaciones que hace ExamCore

- El componente debe **pertenecer al docente** autenticado
  (`evaluation_components.user_id == auth()->id()`).
- Solo se aceptan tipos `TESTS`, `PROJECT`, `DAILY_WORK`, `HOMEWORK`.
- Un componente **solo puede estar vinculado a UN examen** a la vez
  (índice `UNIQUE(evaluation_component_id)` en el pivote).
- Al sincronizar:
  - Se omiten estudiantes que no pertenecen al roster del componente
    (autoridad: las filas `grade_components` que SICORE pre-creó).
  - Se omiten intentos no finalizados.
  - Se toma el **mejor intento** por estudiante.
  - Se bloquea si hay respuestas manuales (respuesta corta, ejercicio, etc.)
    aún sin calificar.

---

## 7. Resumen del flujo end-to-end

```
SICORE: docente crea componente "Prueba #1" en rubro Pruebas de 7-1 A
SICORE: clic en [Evaluar con ExamCore] en esa fila
   ↓
ExamCore: /exams/create?evaluation_component_id={id}
   ↓
ExamCore: docente arma el examen, vincula otros componentes si aplica
ExamCore: estudiantes rinden el examen
ExamCore: docente clic en [Sincronizar notas a SICORE]
   ↓
BD compartida: grade_components.grade + grades.nota actualizados
   ↓
SICORE: el rubro Pruebas refleja la nota consolidada automáticamente
```

---

## 8. Para más adelante (v2 opcional)

- Endpoint POST `/api/exams/from-component` que SICORE invoque para crear el
  examen sin que el docente vea el formulario (creación 100% automática con
  defaults). Devolvería el `exam_id` y SICORE redirige a `/exams/{id}`.
- Webhook al sincronizar (notificación a SICORE para refrescar UI en vivo).
- Confirmación visual en SICORE de "Última sincronización: {fecha}".
