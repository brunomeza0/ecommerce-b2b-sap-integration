/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.controller;

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
import com.ecommerce.BasicApplication;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.client.TestRestTemplate;
import org.springframework.http.*;
import java.util.Collections;
import static org.junit.jupiter.api.Assertions.*;

@SpringBootTest(
    classes = BasicApplication.class,
    webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT,
    properties = {"server.ssl.enabled=false"}
)
public class odataControllerTest {

    @Autowired
    private TestRestTemplate restTemplate;

    @Test
    @DisplayName("Prueba GET /odata/ - OData")
    public void testListarOData() {
  
         HttpHeaders headers = new HttpHeaders();
         headers.setAccept(Collections.singletonList(MediaType.parseMediaType("application/atomsvc+xml")));
         HttpEntity<?> entity = new HttpEntity<>(headers);

         ResponseEntity<String> response = restTemplate.exchange("/odata/", HttpMethod.GET, entity, String.class);

         assertEquals(200, response.getStatusCodeValue(), "El estatus HTTP debe ser 200 OK");

         String contentType = response.getHeaders().getContentType().toString();
 
         assertTrue(contentType.contains("application/atomsvc+xml"),
                   "El content type debe ser compatible con application/atomsvc+xml");

         assertTrue(response.getBody().contains("<atom:title>Default</atom:title>"),
                   "El XML no contiene el título esperado");
    }
}