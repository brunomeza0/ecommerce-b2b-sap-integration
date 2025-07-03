/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Classes/Class.java to edit this template
 */
package com.ecommerce.model;

import java.util.List;
import com.fasterxml.jackson.annotation.JsonProperty;

/**
 * DTO que representa una máquina específica y la lista de accesorios asociados para la consulta.
 */
public class MaquinaItemDto {

    @JsonProperty("Maquina")    // Código de la máquina a consultar
    private String maquina;

    @JsonProperty("Accesorios") // Lista de códigos de accesorios para esa máquina
    private List<String> accesorios;

    public MaquinaItemDto() {
    }

    public MaquinaItemDto(String maquina, List<String> accesorios) {
        this.maquina = maquina;
        this.accesorios = accesorios;
    }

    public String getMaquina() {
        return maquina;
    }

    public void setMaquina(String maquina) {
        this.maquina = maquina;
    }

    public List<String> getAccesorios() {
        return accesorios;
    }

    public void setAccesorios(List<String> accesorios) {
        this.accesorios = accesorios;
    }
}