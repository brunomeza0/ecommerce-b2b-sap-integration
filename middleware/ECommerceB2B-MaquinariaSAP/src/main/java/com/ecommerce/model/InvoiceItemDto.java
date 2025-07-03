// src/main/java/com/ecommerce/model/InvoiceItemDto.java
package com.ecommerce.model;

/**
 * DTO que representa cada línea de ítem en la factura.
 */
public class InvoiceItemDto {
    private String material;
    private Integer quantity;
    private Double unitPrice;
    private Double discount;

    public InvoiceItemDto() {
    }

    public String getMaterial() {
        return material;
    }

    public void setMaterial(String material) {
        this.material = material;
    }

    public Integer getQuantity() {
        return quantity;
    }

    public void setQuantity(Integer quantity) {
        this.quantity = quantity;
    }

    public Double getUnitPrice() {
        return unitPrice;
    }

    public void setUnitPrice(Double unitPrice) {
        this.unitPrice = unitPrice;
    }

    public Double getDiscount() {
        return discount;
    }

    public void setDiscount(Double discount) {
        this.discount = discount;
    }

    @Override
    public String toString() {
        return "InvoiceItemDto{" +
               "material='" + material + '\'' +
               ", quantity=" + quantity +
               ", unitPrice=" + unitPrice +
               ", discount=" + discount +
               '}';
    }
}
