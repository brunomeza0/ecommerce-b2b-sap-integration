package com.ecommerce.scheduler;

import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.io.IOException;
import java.nio.file.*;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Component
public class DispatchCsvScheduler {

    private final ErrorLogRepository errorLogRepository;
    private final RestTemplate restTemplate;

    @Value("${sap.dispatch.csv.out.dir}")
    private String sapDispatchOutDir;

    @Value("${sap.dispatch.csv.out.processed.dir}")
    private String sapDispatchOutProcessedDir;

    @Value("${ecommerce.dispatch.webhook.url}")
    private String dispatchWebhookUrl;

    @Value("${ecommerce.dispatch.webhook.token}")
    private String dispatchWebhookToken;

    public DispatchCsvScheduler(ErrorLogRepository errorLogRepository,
                                RestTemplate restTemplate) {
        this.errorLogRepository = errorLogRepository;
        this.restTemplate       = restTemplate;
    }

    /**
     * Se ejecuta cada 2 segundos (configurable) para procesar resultados de despacho desde SAP_OUT.
     */
    @Scheduled(fixedDelayString = "${scheduler.dispatch.delay:2000}")
    public void pollDispatchResults() {
        try (DirectoryStream<Path> stream = Files.newDirectoryStream(
                Paths.get(sapDispatchOutDir), "*_dispatch_result.csv")) {

            for (Path file : stream) {
                processFile(file);
            }

        } catch (IOException e) {
            logError(
                "DispatchCsvScheduler",
                NivelError.ERROR,
                "Error listando archivos en SAP_OUT",
                e.getMessage(),
                null,
                "DISPATCH_POLL"
            );
        }
    }

    private void processFile(Path path) {
        try {
            // 1) Leer todas las líneas del CSV
            List<String> lines = Files.readAllLines(path);
            if (lines.size() < 2) {
                // Sólo cabecera o vacío: no hay nada que procesar
                return;
            }

            // 2) Parsear la primera línea de datos
            String[] parts       = lines.get(1).split(";");
            String orderNumber   = parts[0];
            String deliveryNo    = parts[1];
            String materialDoc   = parts[2];
            String rawStatus     = parts[3];
            String message       = parts.length > 4 ? parts[4] : "";

            // 3) Preparar llamada al webhook
            HttpHeaders headers  = new HttpHeaders();
            headers.set("X-SAP-Token", dispatchWebhookToken);
            headers.setContentType(MediaType.APPLICATION_JSON);

            Map<String,Object> payload = new HashMap<>();
            String mappedStatus;
            if ("S".equals(rawStatus)) {
                mappedStatus = "COMPLETADO";
            } else if ("E".equals(rawStatus)) {
                mappedStatus = "ERROR";
            } else {
                // por si más adelante hay otros códigos
                mappedStatus = rawStatus;
            }

            // payload con estado mapeado
            payload.put("orderNumber",      orderNumber);
            payload.put("deliveryNumber",   deliveryNo);
            payload.put("materialDocument", materialDoc);
            payload.put("status",           mappedStatus);
            payload.put("messages", List.of(Map.of("text", message)));
            
            HttpEntity<Map<String,Object>> request = new HttpEntity<>(payload, headers);
            ResponseEntity<String> response = restTemplate
                    .exchange(dispatchWebhookUrl, HttpMethod.POST, request, String.class);

            if (!response.getStatusCode().is2xxSuccessful()) {
                logError(
                    "DispatchCsvScheduler",
                    NivelError.ERROR,
                    "Error invocando webhook de despacho: HTTP " + response.getStatusCodeValue(),
                    response.getBody(),
                    null,
                    "DISPATCH_WEBHOOK"
                );
            }

            // 4) Mover el archivo procesado a SAP_OUT_PROCESSED
            Path processedDir = Paths.get(sapDispatchOutProcessedDir);
            Files.createDirectories(processedDir);
            Path target = processedDir.resolve(path.getFileName());
            Files.move(path, target, StandardCopyOption.REPLACE_EXISTING);

        } catch (IOException e) {
            logError(
                "DispatchCsvScheduler",
                NivelError.ERROR,
                "Error procesando archivo: " + path.getFileName(),
                e.getMessage(),
                null,
                "DISPATCH_PROCESS"
            );
        } catch (Exception e) {
            logError(
                "DispatchCsvScheduler",
                NivelError.ERROR,
                "Error inesperado en DispatchCsvScheduler",
                e.toString(),
                null,
                "DISPATCH_GENERAL"
            );
        }
    }

    /**
     * Registra un error en la tabla ErrorLog.
     */
    private void logError(String componente,
                          NivelError nivel,
                          String mensaje,
                          String detalle,
                          String traza,
                          String origen) {
        ErrorLog err = new ErrorLog();
        err.setComponente(componente);
        err.setNivel(nivel);
        err.setMensajeError(mensaje);
        err.setDetalleError(detalle);
        //err.setTraza(traza);
        err.setOrigen(origen);
        errorLogRepository.save(err);
    }
}