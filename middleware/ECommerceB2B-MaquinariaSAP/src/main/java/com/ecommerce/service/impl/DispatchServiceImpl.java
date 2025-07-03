package com.ecommerce.service.impl;

import com.ecommerce.model.AddressDto;
import com.ecommerce.model.DispatchDto;
import com.ecommerce.model.DispatchItemDto;
import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import com.ecommerce.service.DispatchService;
import com.ecommerce.util.EncryptionUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Implementación del servicio que genera el CSV de despacho para SAP.
 */
@Service
public class DispatchServiceImpl implements DispatchService {

    @Value("${sap.dispatch.csv.dir:C:/SFTP/SAP_IN}")
    private String sapDispatchCsvDir;

    @Value("${app.encryption.key:}")
    private String encryptionKey;

    private final ErrorLogRepository errorLogRepository;

    public DispatchServiceImpl(ErrorLogRepository errorLogRepository) {
        this.errorLogRepository = errorLogRepository;
    }

    @Override
    public void enviarDespachoASap(DispatchDto despacho) throws Exception {
        // 1) Armar líneas CSV
        List<String> lines = new ArrayList<>();
        lines.add("Order;ShipPt;ShipDate;Name1;Street;Postal;City;Country;Line;Material;Qty;Plant;SLoc");

        AddressDto addr = despacho.getAddressOverride();
        for (DispatchItemDto item : despacho.getItems()) {
            StringBuilder sb = new StringBuilder();
            sb.append(despacho.getOrderNumber()).append(';')
              .append(despacho.getShippingPoint()).append(';');
            String shipDate = despacho.getShippingDate();
            if (shipDate != null && shipDate.matches("\\d{4}-\\d{2}-\\d{2}")) {
                shipDate = shipDate.replace("-", "");
            }
            sb.append(shipDate).append(';')
              .append(addr.getName1()).append(';')
              .append(addr.getStreet()).append(';')
              .append(addr.getPostalCode()).append(';')
              .append(addr.getCity()).append(';')
              .append(addr.getCountry()).append(';')
              .append(item.getLine()).append(';')
              .append(item.getMaterial()).append(';')
              .append(item.getQuantity()).append(';')
              .append(item.getPlant()).append(';')
              .append(item.getStorageLoc());
            lines.add(sb.toString());
        }

        // 2) Preparar nombre de archivo de despacho (_dispatch.csv) y ruta destino
        String orderNo = despacho.getOrderNumber();
        if (orderNo.matches("\\d+") && orderNo.length() < 10) {
            orderNo = String.format("%010d", Long.parseLong(orderNo));
        }
        String fileName = "DESPACHO_" + orderNo + ".CSV";
        Path filePath = Paths.get(sapDispatchCsvDir, fileName);
        Files.createDirectories(filePath.getParent());

        IOException lastException = null;

        // 3) Intentar hasta 3 veces escribir el archivo CSV (manejo de concurrencia/transitorios)
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
                if (intento < 3) {
                    Thread.sleep(500);
                }
            }
        }

        // 4) Si falló tras 3 intentos, registrar error y lanzar excepción
        if (lastException != null) {
            ErrorLog errorLog = new ErrorLog();
            errorLog.setComponente("DispatchService");
            errorLog.setNivel(NivelError.ERROR);
            errorLog.setMensajeError("Fallo al escribir archivo de despacho tras 3 intentos: " + lastException.getMessage());
            errorLog.setOrigen("DISPATCH_IN");
            errorLogRepository.save(errorLog);

            throw new Exception("No se pudo generar el archivo de despacho tras 3 intentos. Detalle: " + lastException.getMessage());
        }
    }
}
