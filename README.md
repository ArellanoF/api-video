# 🎬 **API Video — Proyecto**

Este repositorio contiene una **API** con la cual se pueden  **generar videos** a partir de imágenes y transiciones. Incluye instrucciones para levantar los contenedores, ejecutar migraciones, probar endpoints y realizar pruebas locales.

---

## ⚙️ **Requisitos**
- 🐳 Docker y `docker-compose` (o Docker Engine con `docker compose`)  
- 🎞️ `ffmpeg` instalado en el host para crear videos

---

## 🚀 **Levantar los contenedores**
En la raíz del proyecto:  
```bash
docker-compose up -d --build
```  
Servicios principales:  
| Servicio | Descripción | Puerto |
|-----------|--------------|--------|
| 🧩 `app` | Contenedor Laravel (API principal) | 8000 → 80 |
| ⚙️ `queue` | Worker de colas | — |
| 🗄️ `db` | Base de datos MySQL | — |
| 🧭 `phpmyadmin` | Panel opcional para MySQL | 8081 |  

---

## 🧱 **Migraciones**
Ejecutar desde el contenedor `app`:  
```bash
docker-compose exec app php artisan migrate
```  
> 🧩 Si `AUTO_MIGRATE=1` en `docker-compose.yml`, se ejecutan automáticamente al arrancar.

---

## 🔗 **Endpoints principales**
Prefijo: `/api`  
| Método | Ruta | Descripción |
|---------|------|--------------|
| `POST` | `/api/tasks` | Crear una tarea de video |
| `GET` | `/api/tasks/{id}` | Ver estado y partial videos |
| `GET` | `/api/tasks/{id}/final` | Obtener URL del video final y su estado|  

📦 Crear una tarea (via curl):  
```bash
curl -X POST http://localhost:8000/api/tasks 	-H "Content-Type: application/json" 	-d '{
		"images": [
			{"url": "https://example.com/image1.jpg", "transition": "pan"},
			{"url": "https://example.com/image2.jpg", "transition": "zoom_in"}
		]
	}'
```  
Respuesta esperada:  
```json
{ "task_id": 1, "status": "pending" }
```  
🔍 Consultar estado:  
```bash
curl http://localhost:8000/api/tasks/1
```  
🎥 Obtener URL final:  
```bash
curl http://localhost:8000/api/tasks/1/final
```  
🧰 **En Postman:** usar `Content-Type: application/json` y apuntar a `http://localhost:8000`.

Validaciones: `images` debe ser un array con al menos un elemento; cada imagen requiere `url` y `transition` (`pan`, `zoom_in`, `zoom_out`).

---

## 🧪 **Pruebas locales de generación de video**
El servicio usa `ffmpeg` dentro de `VideoProcessingService`.

### ⚙️ 
Al llamar al endpoint (POST /api/tasks). Se crea un Job el cual llama al `VideoProcessingService` que descargará imágenes, generará partials y concatenará el video final.

---

## 🛟 ¿Por qué existe también un Command si ya tengo un Job + Queue Worker?

El **Command (`videos:process-pending`)** actúa como un **sistema de respaldo** para garantizar que **ninguna tarea quede sin procesarse**, incluso si el Job no llegó a procesarse correctamente. Aunque el procesamiento principal lo realiza el queue worker, el Command sirve para:

- 🔄 **Lanzar tareas pendientes** que por algún motivo no generaron su Job.  
- 🛠️ **Recuperarse de fallos** al crear el Job (timeouts, desconexiones, reinicios).   
- 🌐 **Procesar tareas creadas por sistemas externos** que solo insertan en la BD.  
- 🚫 **Evitar que una tarea quede bloqueada indefinidamente** en estado `pending`.

El scheduler ejecuta este Command cada minuto, asegurando que *todo lo que deba procesarse termine procesado*, incluso fuera del flujo normal del Job.

---

## 🗂️ **Almacenamiento**
- Los videos se guardan en `storage/app/public/videos/{task_id}`  
- Se exponen mediante `php artisan storage:link`  
- Acceso externo: `/storage/videos/{task_id}/final_{task_id}.mp4`

---