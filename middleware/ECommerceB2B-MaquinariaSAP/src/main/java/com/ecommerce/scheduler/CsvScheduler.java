package com.ecommerce.scheduler;

import com.ecommerce.model.ClienteDto;
import com.ecommerce.model.ClienteMaterialDto;
import com.ecommerce.model.CotizacionRespuestaDto;
import com.ecommerce.model.NivelError;
import com.ecommerce.model.ProductoDto;
import com.ecommerce.repository.ErrorLogRepository;
import com.ecommerce.util.EncryptionUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.io.File;
import java.io.IOException;
import java.math.BigDecimal;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.StandardCopyOption;
import java.text.NumberFormat;
import java.text.ParsePosition;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

/**
 * Scheduler que monitorea archivos CSV de SAP_OUT, los procesa y envía al eCommerce.
 */
@Component
public class CsvScheduler {

    private final RestTemplate restTemplate;
    private final ErrorLogRepository errorLogRepository;

    // URLs de servicios del eCommerce (configurables en application.properties)
    @Value("${app.url.clientes:http://localhost/clientes_service.php}")
    private String clientesUrl;
    @Value("${app.url.productos:http://localhost/productos_service.php}")
    private String productosUrl;
    @Value("${app.url.clienteMaterial:http://localhost/cliente_material_service.php}")
    private String clienteMaterialUrl;
    @Value("${app.url.cotizacionRespuesta:http://localhost/sap_quote_webhook.php}")
    private String cotizacionRespuestaUrl;
    @Value("${app.sap.token}")
    private String sapToken;
    @Value("${app.encryption.key:}")
    private String encryptionKey;

    public CsvScheduler(RestTemplate restTemplate, ErrorLogRepository errorLogRepository) {
        this.restTemplate = restTemplate;
        this.errorLogRepository = errorLogRepository;
    }

    // Procesamiento periódico de archivos:
    @Scheduled(fixedRate = 5000, initialDelay = 10000)
    public void procesarCsv() {
        processClientes();
        processProductos();
        processClienteMaterial();
        // Las cotizaciones se procesan en un ciclo más rápido separado
    }

    @Scheduled(fixedRate = 2000, initialDelay = 2000)
    public void procesarCotizacionesRapido() {
        processCotizaciones();
       
    }

    private void processClientes() {
        File file = new File("C:/SFTP/SAP_OUT/CLIENTES.CSV");
        if (!file.exists()) return;
        try {
            List<String> lines = Files.readAllLines(file.toPath(), StandardCharsets.ISO_8859_1);
            if (lines.size() < 2) {
                moveFileToProcessed(file);
                return;
            }
            List<ClienteDto> lista = new ArrayList<>();
            int expectedCols = lines.get(0).split(";", -1).length;
            for (String line : lines.subList(1, lines.size())) {
                if (line.trim().isEmpty()) continue;
                String[] campos = line.split(";", -1);
                if (campos.length < expectedCols) {
                    String[] newCampos = new String[expectedCols];
                    System.arraycopy(campos, 0, newCampos, 0, campos.length);
                    for (int j = campos.length; j < expectedCols; j++) {
                        newCampos[j] = "";
                    }
                    campos = newCampos;
                }
                String codigoCliente = campos[0] != null ? campos[0].trim() : "";
                if (codigoCliente.isEmpty()) {
                    logError("CsvScheduler", NivelError.WARNING,
                            "Registro de CLIENTES.CSV omitido por campo Cliente vacío",
                            "", null, "CLIENTES.CSV");
                    continue;
                }
                if (codigoCliente.matches("\\d+") && codigoCliente.length() < 10) {
                    codigoCliente = String.format("%010d", Long.parseLong(codigoCliente));
                }
                String razonSocial = (campos.length > 1 ? campos[1].trim() : "");
                String direccion   = (campos.length > 2 ? campos[2].trim() : "");
                String ciudad      = (campos.length > 3 ? campos[3].trim() : "");
                String region      = (campos.length > 4 ? campos[4].trim() : "");
                String cp          = (campos.length > 5 ? campos[5].trim() : "");
                String pais        = (campos.length > 6 ? campos[6].trim() : "");
                String centro      = (campos.length > 7 ? campos[7].trim() : "");
                String orgVenta    = (campos.length > 8 ? campos[8].trim() : "");
                String canal       = (campos.length > 9 ? campos[9].trim() : "");
                String division    = (campos.length > 10 ? campos[10].trim() : "");
                String moneda      = (campos.length > 11 ? campos[11].trim() : "");
                String grpPrecio   = (campos.length > 12 ? campos[12].trim() : "");
                String condPago    = (campos.length > 13 ? campos[13].trim() : "");

                ClienteDto dto = new ClienteDto(codigoCliente, razonSocial, direccion, ciudad, region,
                                                cp, pais, centro, orgVenta, canal,
                                                division, moneda, grpPrecio, condPago);
                lista.add(dto);
            }
            if (lista.isEmpty()) {
                logError("CsvScheduler", NivelError.ERROR,
                        "CLIENTES.CSV no contenía registros procesables (todos omitidos por errores)",
                        "", null, "CLIENTES.CSV");
                moveFileToProcessed(file);
                return;
            }
            postToEcommerceWithRetry(clientesUrl, lista, "CLIENTES.CSV");
            moveFileToProcessed(file);
        } catch (Exception e) {
            logError("CsvScheduler", NivelError.ERROR,
                     "Error procesando CLIENTES.CSV: " + e.getMessage(),
                     "", null, "CLIENTES.CSV");
            try { moveFileToProcessed(file); } catch (IOException ex) { /* Ignorar */ }
        }
    }

    private void processProductos() {
        File file = new File("C:/SFTP/SAP_OUT/PRODUCTOS.CSV");
        if (!file.exists()) return;
        try {
            List<String> lines = Files.readAllLines(file.toPath(), StandardCharsets.ISO_8859_1);
            if (lines.size() < 2) {
                moveFileToProcessed(file);
                return;
            }
            List<ProductoDto> lista = new ArrayList<>();
            int expectedCols = lines.get(0).split(";", -1).length;
            for (String line : lines.subList(1, lines.size())) {
                if (line.trim().isEmpty()) continue;
                String[] campos = line.split(";", -1);
                if (campos.length < expectedCols) {
                    String[] newCampos = new String[expectedCols];
                    System.arraycopy(campos, 0, newCampos, 0, campos.length);
                    for (int j = campos.length; j < expectedCols; j++) {
                        newCampos[j] = "";
                    }
                    campos = newCampos;
                }
                String productoId  = campos[0] != null ? campos[0].trim() : "";
                if (productoId.isEmpty()) {
                    logError("CsvScheduler", NivelError.WARNING,
                            "Registro de PRODUCTOS.CSV omitido por campo ProductoID vacío",
                            "", null, "PRODUCTOS.CSV");
                    continue;
                }
                String descripcion = (campos.length > 1 ? campos[1].trim() : "");
                String um          = (campos.length > 2 ? campos[2].trim() : "");
                String orgVentas   = (campos.length > 3 ? campos[3].trim() : "");
                String canal       = (campos.length > 4 ? campos[4].trim() : "");
                String grupo       = (campos.length > 5 ? campos[5].trim() : "");
                String centro      = (campos.length > 6 ? campos[6].trim() : "");
                String borradoStr  = campos.length > 7 ? campos[7].trim() : "";
                String precioStr   = campos.length > 8 ? campos[8].trim() : "";
                String stockStr    = campos.length > 9 ? campos[9].trim() : "";

                String borrado_planta = borradoStr;
                double precio = precioStr.isEmpty() 
                    ? 0.0 
                    : parseDouble(precioStr);
                Integer stock  = stockStr.isEmpty() 
                    ? 0
                    : parseInteger(stockStr);
                ProductoDto dto = new ProductoDto(
                    productoId,
                    descripcion,
                    um,
                    orgVentas,
                    canal,
                    grupo,
                    centro,
                    borradoStr,
                    precio,
                    stock
                );
                // ¡Y ahora sí seteamos el stock!
                dto.setStock((int) stock);
                lista.add(dto);
            }
            if (lista.isEmpty()) {
                logError("CsvScheduler", NivelError.ERROR,
                        "PRODUCTOS.CSV no contenía registros procesables (todos omitidos por errores)",
                        "", null, "PRODUCTOS.CSV");
                moveFileToProcessed(file);
                return;
            }
            postToEcommerceWithRetry(productosUrl, lista, "PRODUCTOS.CSV");
            moveFileToProcessed(file);
        } catch (Exception e) {
            logError("CsvScheduler", NivelError.ERROR,
                     "Error procesando PRODUCTOS.CSV: " + e.getMessage(),
                     "", null, "PRODUCTOS.CSV");
            try { moveFileToProcessed(file); } catch (IOException ex) { }
        }
    }

    private void processClienteMaterial() {
        File file = new File("C:/SFTP/SAP_OUT/CLIENTE_MATERIAL.CSV");
        if (!file.exists()) return;
        try {
            List<String> lines = Files.readAllLines(file.toPath(), StandardCharsets.ISO_8859_1);
            if (lines.size() < 2) {
                moveFileToProcessed(file);
                return;
            }
            List<ClienteMaterialDto> lista = new ArrayList<>();
            int expectedCols = lines.get(0).split(";", -1).length;
            for (String line : lines.subList(1, lines.size())) {
                if (line.trim().isEmpty()) continue;
                String[] campos = line.split(";", -1);
                if (campos.length < expectedCols) {
                    String[] newCampos = new String[expectedCols];
                    System.arraycopy(campos, 0, newCampos, 0, campos.length);
                    for (int j = campos.length; j < expectedCols; j++) {
                        newCampos[j] = "";
                    }
                    campos = newCampos;
                }
                String codigoCliente = campos[0] != null ? campos[0].trim() : "";
                String nombreCliente = (campos.length > 1 ? campos[1].trim() : "");
                String material      = (campos.length > 2 ? campos[2].trim() : "");
                String descripcion   = (campos.length > 3 ? campos[3].trim() : "");
                String precioStr     = (campos.length > 4 ? campos[4].trim() : "");
                String descK004Str   = (campos.length > 5 ? campos[5].trim() : "");
                String udescK004Str  = (campos.length > 6 ? campos[6].trim() : "");
                String descK005Str   = (campos.length > 7 ? campos[7].trim() : "");
                String udescK005Str  = (campos.length > 8 ? campos[8].trim() : "");
                String descK007Str   = (campos.length > 9 ? campos[9].trim() : "");
                String udescK007Str  = (campos.length > 10 ? campos[10].trim() : "");

                if (codigoCliente.isEmpty() || material.isEmpty()) {
                    logError("CsvScheduler", NivelError.WARNING,
                            "Registro omitido en CLIENTE_MATERIAL.CSV por Cliente o Material vacío",
                            "", null, "CLIENTE_MATERIAL.CSV");
                    continue;
                }
                if (codigoCliente.matches("\\d+") && codigoCliente.length() < 10) {
                    codigoCliente = String.format("%010d", Long.parseLong(codigoCliente));
                }
                BigDecimal precio   = parseBigDecimal(precioStr);
                BigDecimal descK004 = parseBigDecimal(descK004Str);
                Integer udescK004   = udescK004Str.isEmpty() ? 0 : parseInteger(udescK004Str);
                BigDecimal descK005 = parseBigDecimal(descK005Str);
                Integer udescK005   = udescK005Str.isEmpty() ? 0 : parseInteger(udescK005Str);
                BigDecimal descK007 = parseBigDecimal(descK007Str);
                Integer udescK007   = udescK007Str.isEmpty() ? 0 : parseInteger(udescK007Str);

                ClienteMaterialDto dto = new ClienteMaterialDto(codigoCliente, nombreCliente, material, descripcion,
                                                                precio, descK004, udescK004,
                                                                descK005, udescK005, descK007, udescK007);
                lista.add(dto);
            }
            if (lista.isEmpty()) {
                logError("CsvScheduler", NivelError.ERROR,
                        "CLIENTE_MATERIAL.CSV no contenía registros procesables (todos omitidos por errores)",
                        "", null, "CLIENTE_MATERIAL.CSV");
                moveFileToProcessed(file);
                return;
            }
            postToEcommerceWithRetry(clienteMaterialUrl, lista, "CLIENTE_MATERIAL.CSV");
            moveFileToProcessed(file);
        } catch (Exception e) {
            logError("CsvScheduler", NivelError.ERROR,
                     "Error procesando CLIENTE_MATERIAL.CSV: " + e.getMessage(),
                     "", null, "CLIENTE_MATERIAL.CSV");
            try { moveFileToProcessed(file); } catch (IOException ex) { }
        }
    }

    private void processCotizaciones() {
        try {
            File dir = new File("C:/SFTP/SAP_OUT");
            if (!dir.exists() || !dir.isDirectory()) return;
            File[] files = dir.listFiles((d, name) ->
                    name.toUpperCase().startsWith("COTIZACION_RESP") && name.toUpperCase().endsWith(".CSV")
            );
            if (files == null || files.length == 0) return;
            for (File file : files) {
                List<String> lines = Files.readAllLines(file.toPath(), StandardCharsets.ISO_8859_1);
                if (lines.size() < 2) {
                    // Sin datos válidos (solo cabecera quizás)
                    moveFileToProcessed(file);
                    continue;
                }
                String dataLine = lines.get(1);
                String[] fields = dataLine.split(";", -1);
                String correlationId = fields.length > 0 ? fields[0].trim() : "";
                String sapQuoteId    = fields.length > 1 ? fields[1].trim() : "";
                String statusCode    = fields.length > 2 ? fields[2].trim() : "";

                if (correlationId.isEmpty() || statusCode.isEmpty()) {
                    logError("CsvScheduler", NivelError.ERROR,
                            "Archivo " + file.getName() + " con datos incompletos (sin CorrelationId o Status)",
                            "", null, "COTIZACION_RESP");
                    moveFileToProcessed(file);
                    continue;
                }

                String status = (!sapQuoteId.isEmpty() && statusCode.equalsIgnoreCase("OK"))
                                ? "CREADO_EN_SAP" : "ERROR_SAP";

                CotizacionRespuestaDto respuesta = new CotizacionRespuestaDto(correlationId, sapQuoteId, status);
                // Enviar al webhook eCommerce con token de autenticación
                org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
                headers.set("X-SAP-Token", sapToken);
                org.springframework.http.HttpEntity<CotizacionRespuestaDto> requestEntity =
                        new org.springframework.http.HttpEntity<>(respuesta, headers);

                boolean notificado = false;
                Exception ultimaEx = null;
                for (int intento = 1; intento <= 3; intento++) {
                    try {
                        restTemplate.postForObject(cotizacionRespuestaUrl, requestEntity, String.class);
                        notificado = true;
                        break;
                    } catch (Exception ex) {
                        ultimaEx = ex;
                        System.err.println("Error notificando cotización (CorrelationId=" + correlationId +
                                           "), intento " + intento + ": " + ex.getMessage());
                        if (intento < 3) {
                            Thread.sleep(500);
                        }
                    }
                }
                if (!notificado) {
                    logError("CsvScheduler", NivelError.ERROR,
                            "No se pudo notificar resultado de cotización " + correlationId + " tras 3 intentos: " +
                                    (ultimaEx != null ? ultimaEx.getMessage() : "(error desconocido)"),
                            "", null, "COTIZACION_RESP");
                }
                // Mover el archivo (éxito o no, igual se archiva)
                moveFileToProcessed(file);
            }
        } catch (Exception e) {
            logError("CsvScheduler", NivelError.ERROR,
                     "Error en processCotizaciones: " + e.getMessage(),
                     "", null, "processCotizaciones");
        }
    }

    /**
     * Envía una lista de datos al servicio PHP del eCommerce con reintentos.
     */
    private void postToEcommerceWithRetry(String url, Object data, String origen) {
        Exception lastEx = null;
        for (int intento = 1; intento <= 3; intento++) {
            try {
                restTemplate.postForObject(url, data, String.class);
                lastEx = null;
                break;
            } catch (Exception e) {
                lastEx = e;
                System.err.println("Fallo al enviar datos a " + url + " (intento " + intento + "): " + e.getMessage());
                if (intento < 3) {
                    try { Thread.sleep(1000); } catch (InterruptedException ie) { /* ignorar */ }
                }
            }
        }
        if (lastEx != null) {
            logError("CsvScheduler", NivelError.ERROR,
                     "No se pudo enviar datos a " + url + " tras 3 intentos: " + lastEx.getMessage(),
                     "", null, origen);
        }
    }

    /**
     * Mueve un archivo procesado al directorio SAP_OUT_PROCESSED, cifrándolo con AES (si hay clave) y renombrándolo con timestamp.
     */
    private void moveFileToProcessed(File file) throws IOException {
        String processedDirPath = "C:/SFTP/SAP_OUT_PROCESSED";
        File processedDir = new File(processedDirPath);
        if (!processedDir.exists()) {
            processedDir.mkdirs();
        }
        String name = file.getName();
        String baseName = name.contains(".") ? name.substring(0, name.lastIndexOf('.')) : name;
        String timestamp = new java.text.SimpleDateFormat("ddMMyyyyHHmmss").format(new java.util.Date());
        File dest = new File(processedDir, baseName + "_" + timestamp + ".csv");
        try {
            byte[] originalBytes = Files.readAllBytes(file.toPath());
            byte[] encryptedBytes;
            if (encryptionKey != null && !encryptionKey.trim().isEmpty()) {
                // Cifrar contenido antes de archivar
                encryptedBytes = EncryptionUtil.encrypt(encryptionKey, originalBytes);
            } else {
                encryptedBytes = originalBytes;
            }
            Files.write(dest.toPath(), encryptedBytes);
            Files.delete(file.toPath());
        } catch (Exception e) {
            // Envuelve cualquier error de cifrado/escritura en IOException para manejar uniformemente
            throw new IOException("Error al cifrar archivo: " + e.getMessage(), e);
        }
    }

    /**
     * Registra un error en la tabla error_logs.
     */
    private void logError(String componente, NivelError nivel, String mensajeError,
                          String detalleError, String usuario, String origen) {
        try {
            com.ecommerce.model.ErrorLog log = new com.ecommerce.model.ErrorLog();
            log.setComponente(componente);
            log.setNivel(nivel);
            log.setMensajeError(mensajeError);
            log.setDetalleError(detalleError);
            log.setUsuario(usuario);
            log.setOrigen(origen);
            errorLogRepository.save(log);
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    // Métodos auxiliares para parseo numérico seguro:

    private BigDecimal parseBigDecimal(String valor) {
        if (valor == null || valor.trim().isEmpty()) {
            return BigDecimal.ZERO;
        }
        String str = valor.trim();
        try {
            return new BigDecimal(str);
        } catch (NumberFormatException e) {
            String normalized = str.replace(".", "").replace(",", ".");
            try {
                return new BigDecimal(normalized);
            } catch (NumberFormatException e2) {
                NumberFormat nf = NumberFormat.getInstance(Locale.getDefault());
                ParsePosition pos = new ParsePosition(0);
                Number number = nf.parse(str, pos);
                if (number != null && pos.getIndex() == str.length()) {
                    return BigDecimal.valueOf(number.doubleValue());
                }
            }
        }
        return BigDecimal.ZERO;
    }

    private int parseInteger(String valor) {
        if (valor == null || valor.trim().isEmpty()) {
            return 0;
        }
        String str = valor.trim();
        try {
            return Integer.parseInt(str);
        } catch (NumberFormatException e) {
            String digits = str.replaceAll("\\D", "");
            if (digits.isEmpty()) return 0;
            try {
                return Integer.parseInt(digits);
            } catch (NumberFormatException e2) {
                return 0;
            }
        }
    }

    private double parseDouble(String valor) {
        if (valor == null || valor.trim().isEmpty()) {
            return 0.0;
        }
        String str = valor.trim();
        try {
            return Double.parseDouble(str);
        } catch (NumberFormatException e) {
            String normalized = str.replace(",", ".");
            try {
                return Double.parseDouble(normalized);
            } catch (NumberFormatException e2) {
                return 0.0;
            }
        }
    }
    @Value("${app.url.pedidoRespuesta:http://localhost/sap_order_webhook.php}")
    private String pedidoRespuestaUrl;

    // Procesamiento de archivos de respuesta de PEDIDOS
    @Scheduled(fixedRate = 2000, initialDelay = 2000)
    public void procesarPedidos() {
        try {
            File dir = new File("C:/SFTP/SAP_OUT");
            if (!dir.exists() || !dir.isDirectory()) return;
            File[] files = dir.listFiles((d, name) ->
                    name.toUpperCase().startsWith("PEDIDO_RESP") && name.toUpperCase().endsWith(".CSV")
            );
            if (files == null || files.length == 0) return;
            for (File file : files) {
                List<String> lines = Files.readAllLines(file.toPath(), StandardCharsets.ISO_8859_1);
                if (lines.size() < 2) {
                    // Sin datos útiles; archivar y continuar
                    moveFileToProcessed(file);
                    continue;
                }
                String dataLine = lines.get(1);
                String[] fields = dataLine.split(";", -1);
                String correlationId = fields.length > 0 ? fields[0].trim() : "";
                String sapOrderId = fields.length > 1 ? fields[1].trim() : "";
                String statusCode    = fields.length > 2 ? fields[2].trim() : "";
                String errorMessage  = fields.length > 3 ? fields[3].trim() : "";
                if (correlationId.isEmpty() || statusCode.isEmpty()) {
                    logError("CsvScheduler", NivelError.ERROR,
                            "Archivo " + file.getName() + " con datos incompletos (sin CorrelationId o status)",
                            "", null, "PEDIDO_RESP");
                    moveFileToProcessed(file);
                    continue;
                }
                // Enviar CSV completo al webhook del eCommerce (que espera CSV en body)
                String content = String.join("\n", lines);
                org.springframework.http.HttpHeaders headers = new org.springframework.http.HttpHeaders();
                headers.set("X-SAP-Token", sapToken);
                headers.setContentType(org.springframework.http.MediaType.TEXT_PLAIN);
                org.springframework.http.HttpEntity<String> requestEntity =
                        new org.springframework.http.HttpEntity<>(content, headers);
                boolean notificado = false;
                Exception ultimaEx = null;
                for (int intento = 1; intento <= 3; intento++) {
                    try {
                        restTemplate.postForObject(pedidoRespuestaUrl, requestEntity, String.class);
                        notificado = true;
                        break;
                    } catch (Exception ex) {
                        ultimaEx = ex;
                        System.err.println("Error notificando pedido (CorrelationId=" + correlationId +
                                           "), intento " + intento + ": " + ex.getMessage());
                        if (intento < 3) {
                            Thread.sleep(500);
                        }
                    }
                }
                if (!notificado) {
                    logError("CsvScheduler", NivelError.ERROR,
                            "No se pudo notificar resultado de pedido " + correlationId +
                            " tras 3 intentos: " + (ultimaEx != null ? ultimaEx.getMessage() : "(error desconocido)"),
                            "", null, "PEDIDO_RESP");
                }
                // Archivar el archivo (éxito o no)
                moveFileToProcessed(file);
            }
        } catch (Exception e) {
            logError("CsvScheduler", NivelError.ERROR,
                    "Error en processPedidos: " + e.getMessage(), "", null, "processPedidos");
        }
    }
}
