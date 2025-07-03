// PedidoServiceImpl.java
package com.ecommerce.service.impl;

import com.ecommerce.model.PedidoDto;
import com.ecommerce.model.PedidoItemDto;
import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import com.ecommerce.service.PedidoService;
import com.ecommerce.util.EncryptionUtil;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.File;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.util.ArrayList;
import java.util.List;

@Service
public class PedidoServiceImpl implements PedidoService {
    private static final String SAP_IN_DIR = "C:/SFTP/SAP_IN";
    private static final String ORG_VENTAS = "UE00";  // mismo valor que cotizaciones
    private static final String CANAL = "WH";
    private static final String DIVISION = "BI";     // suposición basada en ejemplo (puede adaptarse)
    @Autowired private ErrorLogRepository errorLogRepository;
    @Value("${app.encryption.key:}") private String encryptionKey;

    @Override
    public void enviarPedidoASap(PedidoDto pedido) throws Exception {
        // Validaciones básicas
        if (pedido.getCorrelationId() == null || pedido.getCorrelationId().trim().isEmpty()) {
            throw new Exception("CorrelationId de pedido no proporcionado");
        }
        if (pedido.getCliente() == null || pedido.getCliente().trim().isEmpty()) {
            throw new Exception("Código de cliente (customerCode) no proporcionado");
        }
        // Completar KUNNR a 10 dígitos si es numérico corto
        String customerCode = pedido.getCliente().trim();
        if (customerCode.matches("\\d+") && customerCode.length() < 10) {
            customerCode = String.format("%010d", Long.parseLong(customerCode));
        }
        pedido.setCliente(customerCode);

        // Validar fechas
        if (pedido.getFechaDespacho() == null) {
            throw new Exception("Fecha de despacho no proporcionada");
        }
        // No se transforma en este ejemplo; asumimos formato correcto (yyyyMMdd).

        // Validar ítems
        if (pedido.getItems() == null || pedido.getItems().isEmpty()) {
            throw new Exception("El pedido no contiene ítems");
        }
        for (PedidoItemDto item : pedido.getItems()) {
            if (item.getMaterial() == null || item.getMaterial().trim().isEmpty()) {
                throw new Exception("Un ítem de pedido no tiene productCode (material)");
            }
            // Normalize material y descripción
            String prodCode = item.getMaterial().trim().toUpperCase();
            item.setMaterial(prodCode);
            if (item.getDescription() == null || item.getDescription().trim().isEmpty()) {
                item.setDescription(prodCode);
            }
            if (item.getUnit() == null || item.getUnit().trim().isEmpty()) {
                item.setUnit("EA");
            }
            if (item.getQuantity() <= 0) {
                item.setQuantity(1);
            }
        }

        // Asegurar directorio
        File dir = new File(SAP_IN_DIR);
        if (!dir.exists()) dir.mkdirs();
        String fileName = "PEDIDO_" + pedido.getCorrelationId() + ".CSV";
        File csvFile = new File(dir, fileName);

        // Generar contenido CSV
        List<String> lines = new ArrayList<>();
        lines.add("CorrelationId;OrderReference;Cliente;Material;Descripción;UM;OrgVentas;Canal;División;Cantidad;ValInicio;ValFin;RefQuote;Plant;RefQuoteItem");
        for (PedidoItemDto item : pedido.getItems()) {
            // Usar pedidoId como OrderReference si existe
            String orderRef = (pedido.getPedidoId() != null ? pedido.getPedidoId().toString() : "");
            String line = String.join(";",
                    pedido.getCorrelationId(),
                    orderRef,
                    pedido.getCliente(),
                    item.getMaterial(),
                    item.getDescription(),
                    item.getUnit(),
                    ORG_VENTAS,
                    CANAL,
                    DIVISION,
                    String.valueOf(item.getQuantity()),
                    pedido.getFechaDespacho(),
                    pedido.getFechaDespacho(),
                    "",
                    "",
                    ""
            );
            lines.add(line);
        }

        // Escribir CSV (con posible encriptación)
        IOException lastException = null;
        for (int intento = 1; intento <= 3; intento++) {
            try {
                String csvContent = String.join(System.lineSeparator(), lines);
                byte[] contentBytes;
                if (encryptionKey != null && !encryptionKey.trim().isEmpty()) {
                    contentBytes = EncryptionUtil.encrypt(encryptionKey, csvContent.getBytes(StandardCharsets.ISO_8859_1));
                } else {
                    contentBytes = csvContent.getBytes(StandardCharsets.ISO_8859_1);
                }
                Files.write(csvFile.toPath(), contentBytes);
                lastException = null;
                break;
            } catch (IOException e) {
                lastException = e;
                if (intento < 3) {
                    Thread.sleep(500);
                }
            }
        }
        if (lastException != null) {
            ErrorLog errorLog = new ErrorLog();
            errorLog.setComponente("PedidoService");
            errorLog.setNivel(NivelError.ERROR);
            errorLog.setMensajeError("Fallo al escribir archivo de pedido tras 3 intentos: " + lastException.getMessage());
            errorLog.setOrigen("PEDIDO_IN");
            errorLogRepository.save(errorLog);
            throw new Exception("No se pudo generar el archivo PEDIDO para SAP luego de 3 intentos. Detalle: " + lastException.getMessage());
        }
    }
}
