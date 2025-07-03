package com.ecommerce.service;

import com.ecommerce.model.InvoiceDto;

public interface InvoiceService {
    void enviarFacturaASap(InvoiceDto factura) throws Exception;
}
