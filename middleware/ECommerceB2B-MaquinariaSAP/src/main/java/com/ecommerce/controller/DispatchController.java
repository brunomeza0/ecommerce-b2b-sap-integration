package com.ecommerce.controller;

import com.ecommerce.model.DispatchDto;
import com.ecommerce.service.DispatchService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api")
public class DispatchController {

    private final DispatchService dispatchService;

    public DispatchController(DispatchService dispatchService) {
        this.dispatchService = dispatchService;
    }

    @PostMapping("/dispatch")
    public ResponseEntity<?> recibirDespacho(@RequestBody DispatchDto despacho) {
        try {
            dispatchService.enviarDespachoASap(despacho);
            // Retornar respuesta JSON inicial de aceptación
            return ResponseEntity.ok().body(new java.util.HashMap<String,String>() {{
                put("deliveryNumber", "");
                put("materialDocument", "");
                put("status", "PENDIENTE_SAP");
            }});
        } catch (Exception e) {
            e.printStackTrace();
            // En caso de error, retornar código 500 con mensaje de error
            return ResponseEntity.status(500)
                    .body(java.util.Map.of("error", "Error al procesar despacho: " + e.getMessage()));
        }
    }
}
