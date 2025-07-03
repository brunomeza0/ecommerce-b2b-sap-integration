package com.ecommerce.scheduler;

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
public class InvoiceCsvScheduler {

    private final ErrorLogRepository errorLogRepository;
    private final RestTemplate restTemplate;

    @Value("${sap.invoice.csv.out.dir}")
    private String sapInvoiceOutDir;
    @Value("${sap.invoice.csv.out.processed.dir}")
    private String sapInvoiceOutProcessedDir;
    @Value("${ecommerce.invoice.webhook.url}")
    private String invoiceWebhookUrl;
    @Value("${ecommerce.invoice.webhook.token}")
    private String invoiceWebhookToken;

    public InvoiceCsvScheduler(ErrorLogRepository errorLogRepository,
                               RestTemplate restTemplate) {
        this.errorLogRepository = errorLogRepository;
        this.restTemplate       = restTemplate;
    }

    @Scheduled(fixedDelayString = "${scheduler.invoice.delay:2000}")
    public void pollInvoiceResults() {
        try (DirectoryStream<Path> stream = Files.newDirectoryStream(
                Paths.get(sapInvoiceOutDir), "*_invoice_result.csv")) {
            for (Path file : stream) {
                processFile(file);
            }
        } catch (IOException e) {
            logError(
                "InvoiceCsvScheduler",
                NivelError.ERROR,
                "Error listando archivos en SAP_OUT",
                e.getMessage(),
                null,
                "INVOICE_POLL"
            );
        }
    }

    private void processFile(Path path) {
        try {
            List<String> lines = Files.readAllLines(path);
            if (lines.size() < 2) return; // nada que procesar
            String[] parts = lines.get(1).split(";");
            String orderNumber  = parts[0];
            String invoiceNo    = parts[1];
            String rawStatus    = parts[2];
            String message      = parts.length > 3 ? parts[3] : "";

            HttpHeaders headers  = new HttpHeaders();
            headers.set("X-SAP-Token", invoiceWebhookToken);
            headers.setContentType(MediaType.APPLICATION_JSON);

            Map<String,Object> payload = new HashMap<>();
            String mappedStatus;
            if ("S".equals(rawStatus)) {
                mappedStatus = "COMPLETADO";
            } else if ("E".equals(rawStatus)) {
                mappedStatus = "ERROR";
            } else {
                mappedStatus = rawStatus;
            }

            payload.put("orderNumber",   orderNumber);
            payload.put("invoiceNumber", invoiceNo);
            payload.put("status",        mappedStatus);
            payload.put("messages", List.of(Map.of("text", message)));

            HttpEntity<Map<String,Object>> request = new HttpEntity<>(payload, headers);
            ResponseEntity<String> response = restTemplate
                    .exchange(invoiceWebhookUrl, HttpMethod.POST, request, String.class);

            if (!response.getStatusCode().is2xxSuccessful()) {
                logError(
                    "InvoiceCsvScheduler",
                    NivelError.ERROR,
                    "Error invocando webhook de factura: HTTP " + response.getStatusCodeValue(),
                    response.getBody(),
                    null,
                    "INVOICE_WEBHOOK"
                );
            }

            Path processedDir = Paths.get(sapInvoiceOutProcessedDir);
            Files.createDirectories(processedDir);
            Path target = processedDir.resolve(path.getFileName());
            Files.move(path, target, StandardCopyOption.REPLACE_EXISTING);

        } catch (IOException e) {
            logError(
                "InvoiceCsvScheduler",
                NivelError.ERROR,
                "Error procesando archivo: " + path.getFileName(),
                e.getMessage(),
                null,
                "INVOICE_PROCESS"
            );
        } catch (Exception e) {
            logError(
                "InvoiceCsvScheduler",
                NivelError.ERROR,
                "Error inesperado en InvoiceCsvScheduler",
                e.toString(),
                null,
                "INVOICE_GENERAL"
            );
        }
    }

    private void logError(String componente,
                          NivelError nivel,
                          String mensaje,
                          String detalle,
                          String traza,
                          String origen) {
        com.ecommerce.model.ErrorLog err = new com.ecommerce.model.ErrorLog();
        err.setComponente(componente);
        err.setNivel(nivel);
        err.setMensajeError(mensaje);
        err.setDetalleError(detalle);
        err.setOrigen(origen);
        errorLogRepository.save(err);
    }
}
