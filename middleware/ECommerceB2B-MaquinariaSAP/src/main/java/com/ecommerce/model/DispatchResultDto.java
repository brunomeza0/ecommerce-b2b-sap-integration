// src/main/java/com/ecommerce/model/DispatchResultDto.java
package com.ecommerce.model;

import java.util.List;

/**
 * DTO para notificar el resultado del despacho al e-commerce.
 */
public class DispatchResultDto {
    private String orderNumber;
    private String deliveryNumber;
    private String materialDocument;
    private String status; // "COMPLETADO" o "ERROR"
    private List<Message> messages;

    public static class Message {
        private String type;
        private String text;
        public String getType() { return type; }
        public void setType(String type) { this.type = type; }
        public String getText() { return text; }
        public void setText(String text) { this.text = text; }
    }

    public String getOrderNumber() { return orderNumber; }
    public void setOrderNumber(String orderNumber) { this.orderNumber = orderNumber; }

    public String getDeliveryNumber() { return deliveryNumber; }
    public void setDeliveryNumber(String deliveryNumber) { this.deliveryNumber = deliveryNumber; }

    public String getMaterialDocument() { return materialDocument; }
    public void setMaterialDocument(String materialDocument) { this.materialDocument = materialDocument; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public List<Message> getMessages() { return messages; }
    public void setMessages(List<Message> messages) { this.messages = messages; }
}
