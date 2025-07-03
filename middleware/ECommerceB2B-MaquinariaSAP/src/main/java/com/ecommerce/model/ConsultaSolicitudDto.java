/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.model;


import java.util.List;
import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * DTO que representa la solicitud de consulta enviada desde el e-commerce al middleware.
 */
public class ConsultaSolicitudDto {

    @JsonProperty("Cliente")  // Código de la empresa/cliente que solicita la consulta
    private String cliente;

    @JsonProperty("Maquinas") // Lista de máquinas con sus accesorios a consultar
    private List<MaquinaItemDto> maquinas;

    public ConsultaSolicitudDto() {
    }

    public ConsultaSolicitudDto(String cliente, List<MaquinaItemDto> maquinas) {
        this.cliente = cliente;
        this.maquinas = maquinas;
    }

    public String getCliente() {
        return cliente;
    }

    public void setCliente(String cliente) {
        this.cliente = cliente;
    }

    public List<MaquinaItemDto> getMaquinas() {
        return maquinas;
    }

    public void setMaquinas(List<MaquinaItemDto> maquinas) {
        this.maquinas = maquinas;
    }
}