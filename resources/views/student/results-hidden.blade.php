<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen Enviado — ExamCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #1E1B4B, #4F46E5); display: flex; align-items: center; justify-content: center; }
        .card { border-radius: 20px; max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="card shadow-lg p-4 text-center">
        <div class="mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
        </div>
        <h4 class="fw-800 mb-1">¡Examen Enviado!</h4>
        <p class="text-muted small mb-4">Tu examen ha sido recibido correctamente. El docente revisará tus respuestas.</p>
        <p class="small"><strong>Estudiante:</strong> {{ $accessCode->student->full_name }}</p>
        <p class="small"><strong>Examen:</strong> {{ $exam->title }}</p>
        <p class="small text-muted">Hora de entrega: {{ $attempt->submitted_at->format('d/m/Y H:i:s') }}</p>
        <a href="{{ route('student.entry') }}" class="btn btn-primary rounded-3 mt-2">
            <i class="bi bi-house me-1"></i>Ir al inicio
        </a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
