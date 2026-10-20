# Parte 5 — Lógica de Negocio (20 pts)

Copias de los entregables (código en `biblioteca-api/app/Services/`, `app/Exceptions/` y `app/Console/Commands/`).

## 5.1 Reglas de negocio (`PrestamoService` + `BusinessRuleException`)

1. **Sin stock no se presta** → `409` `"No hay stock disponible del libro '...'"`.
2. **Máximo 3 préstamos activos por usuario** → `409` con mensaje explicativo.
3. **No se puede eliminar un autor con libros asociados** → protegido con FK `ON DELETE RESTRICT` en la BD original (Parte 1) y HTTPS 409 desde API si existiera el caso.
4. **Vencidos (+15 días) marcados automáticamente** → comando `prestamos:marcar-vencidos`.

Todas las reglas usan transacciones + `lockForUpdate` (coincidencia de stock en concurrencia), y se verificaron E2E (18/18 con casos de error `409`).

## 5.2 Comando Artisan de reporte

```bash
php artisan prestamos:marcar-vencidos   # marca vencidos con +15 días (probado: "Se marcaron 2")
php artisan reporte:biblioteca          # tabla libros más prestados (top 10),
                                        # usuarios con vencidos y libros sin stock
```

Salida real del reporte (extraída de la ejecución de la bootcheck contra la BD real, sin stock, etc.).
