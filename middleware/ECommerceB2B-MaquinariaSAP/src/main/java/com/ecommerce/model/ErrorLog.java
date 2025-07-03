package com.ecommerce.model;

import javax.persistence.*;
import java.sql.Timestamp;

@Entity
@Table(name = "error_logs")
public class ErrorLog {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer errorId;

    @Column(nullable = false, insertable = false, updatable = false)
    private Timestamp fecha;

    @Column(nullable = false, length = 100)
    private String componente;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    private NivelError nivel;

    @Column(nullable = false, columnDefinition = "TEXT")
    private String mensajeError;

    @Column(columnDefinition = "TEXT")
    private String detalleError;

    @Column(length = 50)
    private String usuario;

    @Column(length = 100)
    private String origen;

    @Column(length = 200)
    private String resolucion;

    // Constructor por defecto
    public ErrorLog() {}

    // Getters y Setters
    public Integer getErrorId() {
        return errorId;
    }
    public void setErrorId(Integer errorId) {
        this.errorId = errorId;
    }
    public Timestamp getFecha() {
        return fecha;
    }
    public void setFecha(Timestamp fecha) {
        this.fecha = fecha;
    }
    public String getComponente() {
        return componente;
    }
    public void setComponente(String componente) {
        this.componente = componente;
    }
    public NivelError getNivel() {
        return nivel;
    }
    public void setNivel(NivelError nivel) {
        this.nivel = nivel;
    }
    public String getMensajeError() {
        return mensajeError;
    }
    public void setMensajeError(String mensajeError) {
        this.mensajeError = mensajeError;
    }
    public String getDetalleError() {
        return detalleError;
    }
    public void setDetalleError(String detalleError) {
        this.detalleError = detalleError;
    }
    public String getUsuario() {
        return usuario;
    }
    public void setUsuario(String usuario) {
        this.usuario = usuario;
    }
    public String getOrigen() {
        return origen;
    }
    public void setOrigen(String origen) {
        this.origen = origen;
    }
    public String getResolucion() {
        return resolucion;
    }
    public void setResolucion(String resolucion) {
        this.resolucion = resolucion;
    }
}
