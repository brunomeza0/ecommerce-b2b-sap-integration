package com.ecommerce.service.impl;

import com.ecommerce.model.InvoiceDto;
import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import com.ecommerce.service.InvoiceService;
import com.ecommerce.util.EncryptionUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Servicio que genera el CSV de factura para SAP.
 */
@Service
public class InvoiceServiceImpl implements InvoiceService {

    @Value("${sap.invoice.csv.dir:C:/SFTP/SAP_IN}")
    private String sapInvoiceCsvDir;

    @Value("${app.encryption.key:}")
    private String encryptionKey;

    private final ErrorLogRepository errorLogRepository;

    public InvoiceServiceImpl(ErrorLogRepository errorLogRepository) {
        this.errorLogRepository = errorLogRepository;
    }

    @Override
    public void enviarFacturaASap(InvoiceDto factura) throws Exception {
        // 1) Armar líneas CSV (cabecera + datos)
        List<String> lines = new ArrayList<>();
        lines.add("Order;Delivery;InvoiceDate");

        StringBuilder sb = new StringBuilder();
        sb.append(factura.getOrderNumber()).append(';')
          .append(factura.getDeliveryNumber()).append(';')
          .append(factura.getInvoiceDate());
        lines.add(sb.toString());

        // 2) Preparar nombre de archivo: FACTURA_<order>.CSV
        String orderNo = factura.getOrderNumber();
        if (orderNo.matches("\\d+") && orderNo.length() < 10) {
            orderNo = String.format("%010d", Long.parseLong(orderNo));
        }
        String fileName = "FACTURA_" + orderNo + ".CSV";
        Path filePath = Paths.get(sapInvoiceCsvDir, fileName);
        Files.createDirectories(filePath.getParent());

        IOException lastException = null;
        // 3) Intentar hasta 3 veces escribir el archivo
        for (int intento = 1; intento <= 3; intento++) {
            try {
                String csvContent = String.join(System.lineSeparator(), lines);
                byte[] contentBytes = csvContent.getBytes(StandardCharsets.ISO_8859_1);

                if (encryptionKey != null && !encryptionKey.trim().isEmpty()) {
                    contentBytes = EncryptionUtil.encrypt(encryptionKey, contentBytes);
                }

                Files.write(filePath, contentBytes,
                        StandardOpenOption.CREATE, StandardOpenOption.TRUNCATE_EXISTING);
                lastException = null;
                break;
            } catch (IOException e) {
                lastException = e;
                if (intento < 3) Thread.sleep(500);
            }
        }

        // 4) Si falló tras 3 intentos, registrar error y lanzar excepción
        if (lastException != null) {
            ErrorLog errorLog = new ErrorLog();
            errorLog.setComponente("InvoiceService");
            errorLog.setNivel(NivelError.ERROR);
            errorLog.setMensajeError("Fallo al escribir archivo de factura tras 3 intentos: " + lastException.getMessage());
            errorLog.setOrigen("INVOICE_IN");
            errorLogRepository.save(errorLog);
            throw new Exception("No se pudo generar el archivo de factura tras 3 intentos. Detalle: " + lastException.getMessage());
        }
    }
}
