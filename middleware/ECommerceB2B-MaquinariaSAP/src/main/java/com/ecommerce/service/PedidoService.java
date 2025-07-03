// PedidoService.java
package com.ecommerce.service;

import com.ecommerce.model.PedidoDto;

/**
 * Servicio para procesamiento de pedidos hacia SAP.
 */
public interface PedidoService {
    /**
     * Transforma el pedido en un CSV y lo envía (escribe) a SAP_IN.
     */
    void enviarPedidoASap(PedidoDto pedido) throws Exception;
}