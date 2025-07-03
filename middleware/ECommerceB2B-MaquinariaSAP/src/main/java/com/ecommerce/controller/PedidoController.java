// PedidoController.java
package com.ecommerce.controller;

import com.ecommerce.model.PedidoDto;
import com.ecommerce.service.PedidoService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api")
public class PedidoController {

    private final PedidoService pedidoService;

    public PedidoController(PedidoService pedidoService) {
        this.pedidoService = pedidoService;
    }

    /**
     * Endpoint para recibir un pedido desde el eCommerce y procesarlo hacia SAP.
     */
    @PostMapping("/pedidos")
    public ResponseEntity<?> recibirPedido(@RequestBody PedidoDto pedido) {
        try {
            // Enviar pedido a SAP (escritura de CSV en SAP_IN)
            pedidoService.enviarPedidoASap(pedido);
            // Respuesta JSON inicial (sapOrderId vacío, estado PENDIENTE_SAP)
            return ResponseEntity.ok()
                    .body(new java.util.HashMap<String,String>() {{
                        put("sapOrderId", "");
                        put("sapStatus", "PENDIENTE_SAP");
                    }});
        } catch (Exception e) {
            e.printStackTrace();
            return ResponseEntity.status(500)
                    .body(java.util.Map.of("error", e.getMessage()));
        }
    }
}