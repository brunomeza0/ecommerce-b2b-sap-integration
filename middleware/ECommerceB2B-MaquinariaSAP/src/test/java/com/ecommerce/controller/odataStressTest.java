/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.controller;

/**
 *
 * @author pc
 */
import com.ecommerce.BasicApplication;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.client.TestRestTemplate;
import org.springframework.http.*;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.*;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

@SpringBootTest(
    classes = BasicApplication.class,
    webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT,
    properties = {"server.ssl.enabled=false"}
)
public class odataStressTest {

    @Autowired
    private TestRestTemplate restTemplate;

    @Test
    @DisplayName("Prueba de estrés: 100 usuarios concurrentes accediendo a /odata/ (Productos OData)")
    public void testStressCon100Usuarios() throws InterruptedException, ExecutionException {
        int numeroUsuarios = 100;
        ExecutorService executor = Executors.newFixedThreadPool(numeroUsuarios);
        List<Callable<Void>> tareas = new ArrayList<>();

        for (int i = 0; i < numeroUsuarios; i++) {
            tareas.add(() -> {
                HttpHeaders headers = new HttpHeaders();
                headers.setAccept(Collections.singletonList(MediaType.parseMediaType("application/atomsvc+xml")));
                HttpEntity<?> entity = new HttpEntity<>(headers);
                
                ResponseEntity<String> response = restTemplate.exchange("/odata/", HttpMethod.GET, entity, String.class);
                
                // Verifica que el estatus HTTP sea 200 OK
                assertEquals(200, response.getStatusCodeValue(), "El estatus HTTP debe ser 200 OK");
                
                // Verifica que el content type sea compatible con application/atomsvc+xml
                String contentType = response.getHeaders().getContentType().toString();
                assertTrue(contentType.contains("application/atomsvc+xml"),
                           "El content type debe ser compatible con application/atomsvc+xml");
                
                // Verifica que el XML contenga el título esperado
                assertTrue(response.getBody().contains("<atom:title>Default</atom:title>"),
                           "El XML no contiene el título esperado");
                return null;
            });
        }

        // Ejecuta todas las tareas de forma concurrente y espera a que terminen
        List<Future<Void>> resultados = executor.invokeAll(tareas);
        for (Future<Void> future : resultados) {
            future.get();
        }
        executor.shutdown();
    }
}