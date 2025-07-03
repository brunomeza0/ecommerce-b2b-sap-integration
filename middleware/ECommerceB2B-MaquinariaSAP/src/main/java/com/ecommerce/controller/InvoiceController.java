package com.ecommerce.controller;

import com.ecommerce.model.InvoiceDto;
import com.ecommerce.service.InvoiceService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;

@RestController
@RequestMapping("/api")
public class InvoiceController {

    private final InvoiceService invoiceService;

    public InvoiceController(InvoiceService invoiceService) {
        this.invoiceService = invoiceService;
    }

    @PostMapping("/invoice")
    public ResponseEntity<?> recibirFactura(@RequestBody InvoiceDto factura) {
        try {
            invoiceService.enviarFacturaASap(factura);
            // Respuesta inicial de aceptación
            return ResponseEntity.ok().body(new HashMap<String,String>() {{
                put("invoiceNumber", "");
                put("status", "PENDIENTE_SAP");
            }});
        } catch (Exception e) {
            e.printStackTrace();
            // En caso de error
            return ResponseEntity.status(500)
                    .body(java.util.Map.of("error", "Error al procesar factura: " + e.getMessage()));
        }
    }
}
