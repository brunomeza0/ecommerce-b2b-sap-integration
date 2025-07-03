package com.ecommerce.test;

import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.context.ApplicationContext;

import java.util.List;

@SpringBootApplication(scanBasePackages = "com.ecommerce")
public class DataManipulationTest {

    public static void main(String[] args) {
        // Inicia el contexto de Spring Boot
        ApplicationContext context = SpringApplication.run(DataManipulationTest.class, args);
        
        // Obtiene el bean del repositorio
        ErrorLogRepository repository = context.getBean(ErrorLogRepository.class);
        
        // Inserta un registro de prueba
        ErrorLog log = new ErrorLog();
        log.setComponente("DataManipulationTest");
        log.setNivel(NivelError.INFO);
        log.setMensajeError("Inserción de prueba");
        log.setDetalleError("Detalle de inserción de prueba");
        log.setUsuario("TestUser");
        log.setOrigen("DataManipulationTest");
        // No establecemos 'fecha': la BD asignará CURRENT_TIMESTAMP
        repository.save(log);
        System.out.println("Registro insertado correctamente.");

        // Selección: recupera todos los registros de la tabla error_logs
        List<ErrorLog> logs = repository.findAll();
        System.out.println("Listado de registros en la tabla error_logs:");
        for (ErrorLog registro : logs) {
            System.out.println("ID: " + registro.getErrorId() +
                               " | Componente: " + registro.getComponente() +
                               " | Mensaje: " + registro.getMensajeError());
        }
        
        // Finaliza la aplicación
        SpringApplication.exit(context);
    }
}
