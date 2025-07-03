// src/main/java/com/ecommerce/model/InvoiceDto.java
package com.ecommerce.model;

public class InvoiceDto {
    private String orderNumber;
    private String deliveryNumber;
    private String invoiceDate;

    public InvoiceDto() {
    }

    public InvoiceDto(String orderNumber, String deliveryNumber, String invoiceDate) {
        this.orderNumber = orderNumber;
        this.deliveryNumber = deliveryNumber;
        this.invoiceDate = invoiceDate;
    }

    public String getOrderNumber() {
        return orderNumber;
    }
    public void setOrderNumber(String orderNumber) {
        this.orderNumber = orderNumber;
    }

    public String getDeliveryNumber() {
        return deliveryNumber;
    }
    public void setDeliveryNumber(String deliveryNumber) {
        this.deliveryNumber = deliveryNumber;
    }

    public String getInvoiceDate() {
        return invoiceDate;
    }
    public void setInvoiceDate(String invoiceDate) {
        this.invoiceDate = invoiceDate;
    }

    @Override
    public String toString() {
        return "InvoiceDto{" +
               "orderNumber='" + orderNumber + '\'' +
               ", deliveryNumber='" + deliveryNumber + '\'' +
               ", invoiceDate='" + invoiceDate + '\'' +
               '}';
    }
}
