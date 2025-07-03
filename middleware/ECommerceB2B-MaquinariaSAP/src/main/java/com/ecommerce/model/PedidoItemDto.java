// PedidoItemDto.java
package com.ecommerce.model;

/**
 * DTO que representa un ítem dentro de un pedido.
 */
public class PedidoItemDto {
    private String material;
    private int quantity;
    private String dateFrom;
    private String dateTo;
    private String refQuote;
    private String plant;
    private String refQuoteItem;
    private String description;
    private String unit;
    private String salesOrg;
    private String distrChan;
    private String division;

    // Getters y setters...
    public String getMaterial() { return material; }
    public void setMaterial(String material) { this.material = material; }
    public int getQuantity() { return quantity; }
    public void setQuantity(int quantity) { this.quantity = quantity; }
    public String getDateFrom() { return dateFrom; }
    public void setDateFrom(String dateFrom) { this.dateFrom = dateFrom; }
    public String getDateTo() { return dateTo; }
    public void setDateTo(String dateTo) { this.dateTo = dateTo; }
    public String getRefQuote() { return refQuote; }
    public void setRefQuote(String refQuote) { this.refQuote = refQuote; }
    public String getPlant() { return plant; }
    public void setPlant(String plant) { this.plant = plant; }
    public String getRefQuoteItem() { return refQuoteItem; }
    public void setRefQuoteItem(String refQuoteItem) { this.refQuoteItem = refQuoteItem; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public String getUnit() { return unit; }
    public void setUnit(String unit) { this.unit = unit; }
    public String getSalesOrg() { return salesOrg; }
    public void setSalesOrg(String salesOrg) { this.salesOrg = salesOrg; }
    public String getDistrChan() { return distrChan; }
    public void setDistrChan(String distrChan) { this.distrChan = distrChan; }
    public String getDivision() { return division; }
    public void setDivision(String division) { this.division = division; }
}
