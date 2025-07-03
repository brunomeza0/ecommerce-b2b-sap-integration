package com.ecommerce.service.impl;

import com.ecommerce.model.CotizacionDto;
import com.ecommerce.model.CotizacionItemDto;
import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import com.ecommerce.service.CotizacionService;
import com.ecommerce.util.EncryptionUtil;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.File;
import java.io.IOException;
import java.math.BigDecimal;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;

/**
 * Implementación de CotizacionService para procesar cotizaciones hacia SAP.
 */
@Service
public class CotizacionServiceImpl implements CotizacionService {

    // Directorio de entrada para SAP (carpeta monitorizada por SAP para cotizaciones entrantes)
    private static final String SAP_IN_DIR = "C:/SFTP/SAP_IN";
    private static final String ORG_VENTAS = "UE00";
    private static final String CANAL      = "WH";
    private static final String CENTRO     = "MI00";

    @Autowired
    private ErrorLogRepository errorLogRepository;

    @Value("${app.encryption.key:}")
    private String encryptionKey;

    @Override
    public void enviarCotizacionASap(CotizacionDto cotizacion) throws Exception {
        // ** Validación y corrección de datos recibidos **
        if (cotizacion.getCorrelationId() == null || cotizacion.getCorrelationId().trim().isEmpty()) {
            throw new Exception("CorrelationId de cotización no proporcionado");
        }
        // Código de cliente (KUNNR) obligatorio
        String customerCode = (cotizacion.getCustomerCode() != null) ? cotizacion.getCustomerCode().trim() : "";
        if (customerCode.isEmpty()) {
            throw new Exception("Código de cliente (customerCode) no proporcionado");
        }
        // Si es numérico y de longitud menor a 10, completar con ceros a la izquierda (formato KUNNR 10 dígitos)
        if (customerCode.matches("\\d+") && customerCode.length() < 10) {
            customerCode = String.format("%010d", Long.parseLong(customerCode));
        }
        cotizacion.setCustomerCode(customerCode);

        // Formato de fechas YYYYMMDD (lanza excepción si formato inválido)
        cotizacion.setInitialDate(normalizeDate(cotizacion.getInitialDate(), "Fecha inicial"));
        cotizacion.setFinalDate(normalizeDate(cotizacion.getFinalDate(), "Fecha final"));

        // Validar lista de ítems
        if (cotizacion.getItems() == null || cotizacion.getItems().isEmpty()) {
            throw new Exception("La cotización no contiene items");
        }
        for (CotizacionItemDto item : cotizacion.getItems()) {
            // productCode obligatorio
            if (item.getProductCode() == null || item.getProductCode().trim().isEmpty()) {
                throw new Exception("Un item de cotización no tiene productCode");
            }
            // Normalizar código de material (trim y mayúsculas)
            String prodCode = item.getProductCode().trim();
            item.setProductCode(prodCode.toUpperCase());
            // Descripción: si está vacía, usar el código como descripción
            if (item.getDescription() == null || item.getDescription().trim().isEmpty()) {
                item.setDescription(item.getProductCode());
            } else {
                item.setDescription(item.getDescription().trim());
            }
            // Unidad: valor por defecto "EA" si no especificado
            if (item.getUnit() == null || item.getUnit().trim().isEmpty()) {
                item.setUnit("EA");
            } else {
                item.setUnit(item.getUnit().trim());
            }
            // Cantidad: mínimo 1
            if (item.getQuantity() <= 0) {
                item.setQuantity(1);
            }
            // Precio: si es nulo o negativo, asignar 0
            if (item.getPrice() == null) {
                item.setPrice(BigDecimal.ZERO);
            } else if (item.getPrice().compareTo(BigDecimal.ZERO) < 0) {
                item.setPrice(BigDecimal.ZERO);
            }
        }

        // ** Escritura del archivo CSV en SAP_IN (con reintento y cifrado) **
        // Asegurar existencia de directorio SAP_IN
        File dir = new File(SAP_IN_DIR);
        if (!dir.exists()) {
            dir.mkdirs();
        }
        // Nombre de archivo con correlationId
        String fileName = "COTIZACION_" + cotizacion.getCorrelationId() + ".CSV";
        File csvFile = new File(dir, fileName);

        // Preparar contenido CSV (cabecera + filas por item)
        List<String> lines = new ArrayList<>();
        lines.add("CorrelationId;QuoteId;KUNNR;Material;Descripción;UM;OrgVentas;Canal;Centro;Cantidad;ValInicio;ValFin");
        for (CotizacionItemDto item : cotizacion.getItems()) {
            String line = String.join(";",
                    cotizacion.getCorrelationId(),
                    (cotizacion.getQuoteId() != null ? cotizacion.getQuoteId() : ""),
                    cotizacion.getCustomerCode(),
                    item.getProductCode(),
                    item.getDescription(),
                    item.getUnit(),
                    ORG_VENTAS,
                    CANAL,
                    CENTRO,
                    String.valueOf(item.getQuantity()),
                    cotizacion.getInitialDate(),
                    cotizacion.getFinalDate()
            );
            lines.add(line);
        }

        // Intentar escribir archivo (hasta 3 intentos)
        IOException lastException = null;
        for (int intento = 1; intento <= 3; intento++) {
            try {
                // Convertir contenido a bytes (cifrado si hay clave)
                byte[] contentBytes;
                String csvContent = String.join(System.lineSeparator(), lines);
                if (encryptionKey != null && !encryptionKey.trim().isEmpty()) {
                    contentBytes = EncryptionUtil.encrypt(encryptionKey, csvContent.getBytes(StandardCharsets.ISO_8859_1));
                } else {
                    contentBytes = csvContent.getBytes(StandardCharsets.ISO_8859_1);
                }
                Files.write(csvFile.toPath(), contentBytes);
                // Log de confirmación (opcional)
                System.out.println("Archivo CSV generado: " + csvFile.getAbsolutePath() +
                        " (Cotización ID " + cotizacion.getQuoteId() + ", CorrelationId=" + cotizacion.getCorrelationId() + ")");
                lastException = null;
                break; // éxito, salir del bucle de reintentos
            } catch (IOException e) {
                lastException = e;
                System.err.println("Error al escribir archivo " + csvFile.getName() + " (intento " + intento + "): " + e.getMessage());
                if (intento < 3) {
                    // Esperar un corto intervalo antes de reintentar
                    Thread.sleep(500);
                }
            }
        }
        if (lastException != null) {
            // Tras 3 intentos fallidos, registrar error en BD y lanzar excepción
            ErrorLog errorLog = new ErrorLog();
            errorLog.setComponente("CotizacionService");
            errorLog.setNivel(NivelError.ERROR);
            errorLog.setMensajeError("Fallo al escribir archivo de cotización tras 3 intentos: " + lastException.getMessage());
            errorLog.setDetalleError("");
            errorLog.setUsuario(null);
            errorLog.setOrigen("COTIZACION_IN");
            errorLogRepository.save(errorLog);
            throw new Exception("No se pudo generar el archivo CSV para SAP luego de 3 intentos. Detalle: " + lastException.getMessage());
        }
    }

    /**
     * Normaliza una fecha al formato YYYYMMDD.
     * Acepta formatos comunes (yyyy-MM-dd, dd/MM/yyyy, etc.) y retorna siempre 8 dígitos.
     * @param dateStr Cadena de la fecha (posiblemente en varios formatos).
     * @param campo Nombre del campo (para mensajes de error).
     * @return Cadena de 8 dígitos (YYYYMMDD).
     * @throws Exception si la fecha es inválida o no se puede parsear.
     */
    private String normalizeDate(String dateStr, String campo) throws Exception {
        if (dateStr == null) {
            throw new Exception(campo + " no proporcionada");
        }
        String trimmed = dateStr.trim();
        if (trimmed.matches("\\d{8}")) {
            return trimmed; // ya tiene formato YYYYMMDD
        }
        // Intentar parsear distintos formatos
        String[] formatos = {"yyyy-MM-dd", "dd/MM/yyyy", "dd-MM-yyyy", "yyyy/MM/dd"};
        Date fecha = null;
        for (String fmt : formatos) {
            try {
                fecha = new SimpleDateFormat(fmt).parse(trimmed);
                break;
            } catch (ParseException ignore) {}
        }
        if (fecha == null) {
            throw new Exception(campo + " con formato inválido (" + dateStr + ")");
        }
        return new SimpleDateFormat("yyyyMMdd").format(fecha);
    }
}
