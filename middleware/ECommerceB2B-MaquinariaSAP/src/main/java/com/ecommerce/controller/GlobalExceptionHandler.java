package com.ecommerce.controller;

import com.ecommerce.model.ErrorLog;
import com.ecommerce.model.NivelError;
import com.ecommerce.repository.ErrorLogRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;
import org.springframework.web.context.request.WebRequest;

import java.util.Arrays;

@RestControllerAdvice
public class GlobalExceptionHandler {

    @Autowired
    private ErrorLogRepository errorLogRepository;

    @ExceptionHandler(Exception.class)
    public ResponseEntity<String> handleAllExceptions(Exception ex, WebRequest request) {
        ErrorLog log = new ErrorLog();
        log.setComponente("API REST");
        log.setNivel(NivelError.ERROR);
        log.setMensajeError(ex.getMessage());
        log.setDetalleError(Arrays.toString(ex.getStackTrace()));
        log.setOrigen("GlobalExceptionHandler");

        errorLogRepository.save(log);

        return new ResponseEntity<>("Ocurrió un error interno en el servidor", HttpStatus.INTERNAL_SERVER_ERROR);
    }
}
