// src/main/java/com/ecommerce/model/DispatchItemDto.java
package com.ecommerce.model;

/**
 * DTO que representa cada línea de ítem en el despacho.
 */
public class DispatchItemDto {
    private String line;
    private String material;
    private Integer quantity;
    private String plant;
    private String storageLoc;

    public String getLine() { return line; }
    public void setLine(String line) { this.line = line; }

    public String getMaterial() { return material; }
    public void setMaterial(String material) { this.material = material; }

    public Integer getQuantity() { return quantity; }
    public void setQuantity(Integer quantity) { this.quantity = quantity; }

    public String getPlant() { return plant; }
    public void setPlant(String plant) { this.plant = plant; }

    public String getStorageLoc() { return storageLoc; }
    public void setStorageLoc(String storageLoc) { this.storageLoc = storageLoc; }

    @Override
    public String toString() {
        return "DispatchItemDto{" +
               "line='" + line + '\'' +
               ", material='" + material + '\'' +
               ", quantity=" + quantity +
               ", plant='" + plant + '\'' +
               ", storageLoc='" + storageLoc + '\'' +
               '}';
    }
}
