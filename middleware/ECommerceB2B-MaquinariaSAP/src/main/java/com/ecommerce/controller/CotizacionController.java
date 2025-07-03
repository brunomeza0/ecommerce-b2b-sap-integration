// CotizacionController.java
package com.ecommerce.controller;

import com.ecommerce.model.CotizacionDto;
import com.ecommerce.service.CotizacionService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api")
public class CotizacionController {

    private final CotizacionService cotizacionService;

    public CotizacionController(CotizacionService cotizacionService) {
        this.cotizacionService = cotizacionService;
    }

    /**
     * Endpoint para recibir una cotización desde el eCommerce y procesarla hacia SAP.
     */
    @PostMapping("/cotizacion")
    public ResponseEntity<String> recibirCotizacion(@RequestBody CotizacionDto cotizacion) {
        try {
            // Llamar al servicio para generar el CSV en SAP_IN
            cotizacionService.enviarCotizacionASap(cotizacion);
            // Retornar respuesta de aceptación
            return ResponseEntity.ok("Cotización recibida. CorrelationId=" + cotizacion.getCorrelationId());
        } catch (Exception e) {
            e.printStackTrace();
            // Devolver error 500 en caso de fallo
            return ResponseEntity.status(500).body("Error al procesar cotización: " + e.getMessage());
        }
    }
}
