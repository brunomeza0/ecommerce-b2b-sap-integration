/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.controller;


import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.http.MediaType;
import org.springframework.test.web.servlet.MockMvc;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.*;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.status;

@SpringBootTest
@AutoConfigureMockMvc
public class MainPageStressTest {

    @Autowired
    private MockMvc mockMvc;

    @Test
    @DisplayName("Prueba de estrés: 100 usuarios concurrentes accediendo a /")
    public void testStressCon100Usuarios() throws InterruptedException, ExecutionException {
        int numeroUsuarios = 100;
        ExecutorService executor = Executors.newFixedThreadPool(numeroUsuarios);
        List<Callable<Void>> tareas = new ArrayList<>();

        for (int i = 0; i < numeroUsuarios; i++) {
            tareas.add(() -> {
                try {
                    mockMvc.perform(get("/")
                            .contentType(MediaType.APPLICATION_JSON))
                            .andExpect(status().isOk());
                } catch (Exception e) {
                    throw new RuntimeException(e);
                }
                return null;
            });
        }

        // Ejecuta todas las tareas de forma concurrente y espera a que terminen
        List<Future<Void>> resultados = executor.invokeAll(tareas);

        // Comprueba que todas las tareas se han completado sin excepción
        for (Future<Void> future : resultados) {
            future.get();
        }
        
        executor.shutdown();
    }
}