/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.model;

import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * DTO que representa la respuesta de SAP para cada máquina consultada.
 * Incluye el ID de transacción para correlacionar con la solicitud original.
 */
public class ConsultaRespuestaDto {

    @JsonProperty("IDTransaccion")
    private String idTransaccion;

    @JsonProperty("Maquina")
    private String maquina;

    @JsonProperty("Accesorios")
    private String accesorios;

    @JsonProperty("Precio")
    private Double precio;  // Ejemplo de dato de respuesta, p. ej., precio cotizado

    public ConsultaRespuestaDto() {
    }

    public ConsultaRespuestaDto(String idTransaccion, String maquina, String accesorios, Double precio) {
        this.idTransaccion = idTransaccion;
        this.maquina = maquina;
        this.accesorios = accesorios;
        this.precio = precio;
    }

    public String getIdTransaccion() {
        return idTransaccion;
    }

    public void setIdTransaccion(String idTransaccion) {
        this.idTransaccion = idTransaccion;
    }

    public String getMaquina() {
        return maquina;
    }

    public void setMaquina(String maquina) {
        this.maquina = maquina;
    }

    public String getAccesorios() {
        return accesorios;
    }

    public void setAccesorios(String accesorios) {
        this.accesorios = accesorios;
    }

    public Double getPrecio() {
        return precio;
    }

    public void setPrecio(Double precio) {
        this.precio = precio;
    }
}