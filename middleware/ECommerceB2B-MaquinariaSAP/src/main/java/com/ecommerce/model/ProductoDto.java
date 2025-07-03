// src/main/java/com/ecommerce/model/ProductoDto.java
package com.ecommerce.model;

import com.fasterxml.jackson.annotation.JsonProperty;

public class ProductoDto {

    @JsonProperty("ProductoID")
    private String productoID;
    @JsonProperty("Descripción")
    private String descripcion;
    @JsonProperty("UM")
    private String um;
    @JsonProperty("OrgVentas")
    private String orgVentas;
    @JsonProperty("Canal")
    private String canal;
    @JsonProperty("Grupo")
    private String grupo;
    @JsonProperty("Centro")
    private String centro;
    @JsonProperty("BorradoPlanta")
    private String borradoPlanta;
    @JsonProperty("Precio")
    private Double precio;
    @JsonProperty("Stock")
    private Integer stock;

    public ProductoDto() {
    }

    public ProductoDto(String productoID, String descripcion, String um, String orgVentas,
                  String canal, String grupo, String centro,
                  String borradoPlanta, Double precio, Integer stock) {
        this.productoID = productoID;
        this.descripcion = descripcion;
        this.um = um;
        this.orgVentas = orgVentas;
        this.canal = canal;
        this.grupo = grupo;
        this.centro = centro;
        this.borradoPlanta = borradoPlanta;
        this.precio = precio;
        this.stock = stock;
    }

    public String getProductoID() { return productoID; }
    public void setProductoID(String productoID) { this.productoID = productoID; }

    public String getDescripcion() { return descripcion; }
    public void setDescripcion(String descripcion) { this.descripcion = descripcion; }

    public String getUm() { return um; }
    public void setUm(String um) { this.um = um; }

    public String getOrgVentas() { return orgVentas; }
    public void setOrgVentas(String orgVentas) { this.orgVentas = orgVentas; }

    public String getCanal() { return canal; }
    public void setCanal(String canal) { this.canal = canal; }

    public String getGrupo() { return grupo; }
    public void setGrupo(String grupo) { this.grupo = grupo; }

    public String getCentro() { return centro; }
    public void setCentro(String centro) { this.centro = centro; }

    public String getBorradoPlanta() { return borradoPlanta; }
    public void setBorradoPlanta(String borradoPlanta) { this.borradoPlanta = borradoPlanta; }

    public Double getPrecio() { return precio; }
    public void setPrecio(Double precio) { this.precio = precio; }
    
    public Integer getStock() {
        return stock;
    }
    public void setStock(Integer stock) {
        this.stock = stock;
    }
}
