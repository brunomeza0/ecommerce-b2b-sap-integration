package com.ecommerce.model;

import com.fasterxml.jackson.annotation.JsonProperty;
import java.math.BigDecimal;

public class ClienteMaterialDto {

    @JsonProperty("Cliente")
    private String cliente;

    @JsonProperty("Nombre Cliente")
    private String nombreCliente;

    @JsonProperty("Material")
    private String material;

    @JsonProperty("Descripción")
    private String descripcion;

    @JsonProperty("Precio")
    private BigDecimal precio;

    @JsonProperty("Desc_K004")
    private BigDecimal descuentoK004;

    @JsonProperty("UDesc_K004")
    private Integer udescuentoK004;

    @JsonProperty("Desc_K005")
    private BigDecimal descuentoK005;

    @JsonProperty("UDesc_K005")
    private Integer udescuentoK005;

    @JsonProperty("Desc_K007")
    private BigDecimal descuentoK007;

    @JsonProperty("UDesc_K007")
    private Integer udescuentoK007;

    public ClienteMaterialDto() {
    }

    public ClienteMaterialDto(
            String cliente,
            String nombreCliente,
            String material,
            String descripcion,
            BigDecimal precio,
            BigDecimal descuentoK004,
            Integer udescuentoK004,
            BigDecimal descuentoK005,
            Integer udescuentoK005,
            BigDecimal descuentoK007,
            Integer udescuentoK007) {
        this.cliente = cliente;
        this.nombreCliente = nombreCliente;
        this.material = material;
        this.descripcion = descripcion;
        this.precio = precio;
        this.descuentoK004 = descuentoK004;
        this.udescuentoK004 = udescuentoK004;
        this.descuentoK005 = descuentoK005;
        this.udescuentoK005 = udescuentoK005;
        this.descuentoK007 = descuentoK007;
        this.udescuentoK007 = udescuentoK007;
    }

    public String getCliente() {
        return cliente;
    }

    public void setCliente(String cliente) {
        this.cliente = cliente;
    }

    public String getNombreCliente() {
        return nombreCliente;
    }

    public void setNombreCliente(String nombreCliente) {
        this.nombreCliente = nombreCliente;
    }

    public String getMaterial() {
        return material;
    }

    public void setMaterial(String material) {
        this.material = material;
    }

    public String getDescripcion() {
        return descripcion;
    }

    public void setDescripcion(String descripcion) {
        this.descripcion = descripcion;
    }

    public BigDecimal getPrecio() {
        return precio;
    }

    public void setPrecio(BigDecimal precio) {
        this.precio = precio;
    }

    public BigDecimal getDescuentoK004() {
        return descuentoK004;
    }

    public void setDescuentoK004(BigDecimal descuentoK004) {
        this.descuentoK004 = descuentoK004;
    }

    public Integer getUdescuentoK004() {
        return udescuentoK004;
    }

    public void setUdescuentoK004(Integer udescuentoK004) {
        this.udescuentoK004 = udescuentoK004;
    }

    public BigDecimal getDescuentoK005() {
        return descuentoK005;
    }

    public void setDescuentoK005(BigDecimal descuentoK005) {
        this.descuentoK005 = descuentoK005;
    }

    public Integer getUdescuentoK005() {
        return udescuentoK005;
    }

    public void setUdescuentoK005(Integer udescuentoK005) {
        this.udescuentoK005 = udescuentoK005;
    }

    public BigDecimal getDescuentoK007() {
        return descuentoK007;
    }

    public void setDescuentoK007(BigDecimal descuentoK007) {
        this.descuentoK007 = descuentoK007;
    }

    public Integer getUdescuentoK007() {
        return udescuentoK007;
    }

    public void setUdescuentoK007(Integer udescuentoK007) {
        this.udescuentoK007 = udescuentoK007;
    }
}