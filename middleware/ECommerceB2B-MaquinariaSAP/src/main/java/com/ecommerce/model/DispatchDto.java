// src/main/java/com/ecommerce/model/DispatchDto.java
package com.ecommerce.model;

import java.util.List;

/**
 * DTO que representa un despacho recibido del eCommerce.
 */
public class DispatchDto {
    private String orderNumber;
    private String shippingPoint;
    private String shippingDate;
    private AddressDto addressOverride;
    private List<DispatchItemDto> items;

    public String getOrderNumber() { return orderNumber; }
    public void setOrderNumber(String orderNumber) { this.orderNumber = orderNumber; }

    public String getShippingPoint() { return shippingPoint; }
    public void setShippingPoint(String shippingPoint) { this.shippingPoint = shippingPoint; }

    public String getShippingDate() { return shippingDate; }
    public void setShippingDate(String shippingDate) { this.shippingDate = shippingDate; }

    public AddressDto getAddressOverride() { return addressOverride; }
    public void setAddressOverride(AddressDto addressOverride) { this.addressOverride = addressOverride; }

    public List<DispatchItemDto> getItems() { return items; }
    public void setItems(List<DispatchItemDto> items) { this.items = items; }

    @Override
    public String toString() {
        return "DispatchDto{" +
               "orderNumber='" + orderNumber + '\'' +
               ", shippingPoint='" + shippingPoint + '\'' +
               ", shippingDate='" + shippingDate + '\'' +
               ", addressOverride=" + addressOverride +
               ", items=" + (items != null ? items.size() : 0) +
               '}';
    }
}
