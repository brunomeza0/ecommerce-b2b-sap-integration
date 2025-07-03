// CotizacionItemDto.java
package com.ecommerce.model;

import java.math.BigDecimal;

/**
 * DTO que representa un item dentro de una cotización (producto, cantidad, precio).
 */
public class CotizacionItemDto {
    private String productCode;   // Código de material (producto) en SAP
    private String description;   // Descripción del material
    private String unit;          // UM (ej. EA)
    private int quantity;         // Cantidad solicitada
    private BigDecimal price;     // Precio unitario cotizado

    public CotizacionItemDto() {}

    public CotizacionItemDto(String productCode, String description, String unit,
                             int quantity, BigDecimal price) {
        this.productCode = productCode;
        this.description = description;
        this.unit        = unit;
        this.quantity    = quantity;
        this.price       = price;
    }

    public String getProductCode() {
        return productCode;
    }
    public void setProductCode(String productCode) {
        this.productCode = productCode;
    }

    public String getDescription() {
        return description;
    }
    public void setDescription(String description) {
        this.description = description;
    }

    public String getUnit() {
        return unit;
    }
    public void setUnit(String unit) {
        this.unit = unit;
    }

    public int getQuantity() {
        return quantity;
    }
    public void setQuantity(int quantity) {
        this.quantity = quantity;
    }

    public BigDecimal getPrice() {
        return price;
    }
    public void setPrice(BigDecimal price) {
        this.price = price;
    }

    @Override
    public String toString() {
        return "CotizacionItemDto{" +
               "productCode='" + productCode + '\'' +
               ", description='" + description + '\'' +
               ", unit='" + unit + '\'' +
               ", quantity=" + quantity +
               ", price=" + price +
               '}';
    }
}
